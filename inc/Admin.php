<?php

namespace DBManager;
/*Core class*/
if (!defined('ABSPATH')) {
    exit();
}

use DBManager\Config as conf;

class Admin
{
    /*version string*/
    protected $version = null;
    /*filepath string*/
    protected $filepath = null;
    private $_backup_dir = DBMANAGER_DIR_PATH . 'backup/';

    /**
     * Constructor.
     * @param $version
     * @param $filepath
     */
    public function __construct($version, $filepath)
    {
        $this->version = $version;
        $this->filepath = $filepath;
        $this->init_hooks();
    }

    private function init_hooks()
    {
        register_activation_hook($this->filepath, array($this, 'plugin_activate')); //activate hook
        register_deactivation_hook($this->filepath, array($this, 'plugin_deactivate')); //deactivate hook
        register_uninstall_hook($this->filepath, 'DBManager\Admin::plugin_uninstall'); //deactivate hook

        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        new Settings();
        add_action('admin_init', array($this, 'execution'));
    }

    public function admin_scripts()
    {
        $file = DBMANAGER_NAME . '-admin';
        wp_enqueue_style($file, DBMANAGER_STYLES . "admin.css");
        wp_enqueue_style('dbmanager-bootstrap', DBMANAGER_STYLES . "bootstrap.min.css");
        wp_enqueue_script($file, DBMANAGER_SCRIPTS . "admin.js", array('jquery'));
    }


    public static function plugin_uninstall()
    {
        $options = array('dbmanager_config');
        foreach ($options as $item) {
            if (get_option($item) != false) {
                delete_option($item);
            }
        }
    }

    /*active/ de-active callback*/
    private function do_actions($status)
    {
        $options = conf::option_name();
    }

    /*execution of db manager*/
    public function execution()
    {
        if (is_admin() && current_user_can('manage_options')) {
            if (isset($_GET['action'])) {
                $res = array('', '');
                $input = conf::sanitize_data($_GET);
                if (isset($input['_wpnonce']) && wp_verify_nonce($input['_wpnonce'])) {
                    switch ($input['action']) {
                        case 'create-backup':
                            $res = $this->create_backup();
                            break;
                        case 'delete-backup':
                            $res = $this->delete_backup($input['file']);
                            break;
                        case 'restore-backup':
                            $res = $this->restore_backup($input['file']);
                        default:
                            break;
                    }
                }
                //echo '<pre>', print_r(conf::setting_url() . "&type=" . $res[0] . "&message=" . $res[1]), '</pre>', exit();
                wp_redirect(conf::setting_url() . "&type=" . $res[0] . "&message=" . $res[1]);
            }
        }
    }

    private function restore_backup($file_name)
    {
        $res = array('', 'Failed to restore DB backup');
        $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
        if ($conn) {
            $this->set_ini();
            $db_name = DB_NAME;
            /*select db */
            if (!mysqli_select_db($conn, $db_name)) {
                $sql = "CREATE DATABASE IF NOT EXISTS `{$db_name}`";
                mysqli_query($sql, $conn);
                mysqli_select_db($conn, $db_name);
            }

            /* removing tables */
            $tables = array();

            if ($result = mysqli_query($conn, "SHOW TABLES FROM `{$db_name}`")) {
                while ($row = mysqli_fetch_row($result)) {
                    $tables[] = $row[0];
                }
                if (count($tables) > 0) {
                    foreach ($tables as $table) {
                        mysqli_query($conn, "DROP TABLE `{$db_name}`.{$table}");
                    }

                    /*restoring db */
                    if ($file_name) {
                        if ($file_name && file_exists($file = $this->_backup_dir . $file_name)) {
                            $content = @file_get_contents($file, true);
                            $sql = explode(";\n", $content);

                            for ($i = 0; $i < count($sql); $i++) {
                                mysqli_query($conn, $sql[$i]);
                            }
                            /*removing backup file*/
                            @unlink($file);
                            $res = array('updated', 'DB backup restored');
                        }
                    }
                }
            }
        }
        return $res;
    }

    private function set_ini()
    {
        ini_set("max_execution_time", "4000");
        ini_set("max_input_time", "4000");
        ini_set('memory_limit', '900M');
        set_time_limit(0);
        return;
    }

    private function create_backup()
    {
        $res = array('', 'Failed to create DB backup');
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES");
        $sqlScript = '';
        if ($tables) {
            $this->set_ini();
            foreach ($tables as $table) {
                $result = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_N);
                $row1 = $wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_N);
                $sqlScript .= "\n\n" . $row1[1] . ";\n\n";
                for ($i = 0; $i < count($result); $i++) {
                    $row = $result[$i];
                    $sqlScript .= "INSERT INTO {$table} VALUES(";
                    for ($j = 0; $j < count($result[0]); $j++) {
                        $row[$j] = $wpdb->_real_escape($row[$j]);
                        $sqlScript .= (isset($row[$j])) ? '"' . $row[$j] . '"' : '""';
                        if ($j < (count($result[0]) - 1)) {
                            $sqlScript .= ',';
                        }
                    }
                    $sqlScript .= ");\n";
                }
                $sqlScript .= "\n";
            }
            if (!empty($sqlScript)) {
                // Save the SQL script to a backup file
                $file = DBMANAGER_DIR_PATH . 'backup/' . DBMANAGER_NAME . "__" . date('Y-m-d h-i-s') . ".sql";
                file_put_contents($file, $sqlScript);
                $this->delete_backup('', 10);
                $res = array('updated', 'DB backup created');
            }
        }
        return $res;
    }

    private function delete_backup($file_name = null, $limit = null)
    {
        $res = array('', 'Failed to delete DB backup');
        $backup_dir = DBMANAGER_DIR_PATH . 'backup/';
        if ($file_name && file_exists($file = $backup_dir . $file_name)) {
            @unlink($file);
            $res = array('updated', 'DB backup deleted');
        }

        if ($limit) {
            $backups = scandir($backup_dir);
            if (count($backups) > $limit) {
                $backups = array_diff($backups, array_slice($backups, -$limit));
                if ($backups) {
                    foreach ($backups as $item) if (file_exists($file = $backup_dir . $item)) @unlink($file);
                }
            }
        }
        return $res;
    }


}