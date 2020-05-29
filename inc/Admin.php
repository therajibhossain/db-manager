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

    public function execution()
    {
        if (is_admin() && current_user_can('manage_options')) {
            if (isset($_POST['action'])) {
                $res = array('', '', '');
                $input = conf::sanitize_data($_POST);
            }
        }
    }


}