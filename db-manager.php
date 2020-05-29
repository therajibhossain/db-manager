<?php
/**
 * @wordpress-plugin
 * Plugin Name:       DB Manager
 * Plugin URI:        https://wordpress.org/plugins/db-manager
 * Description:       Simple Database Manager for backup and restore
 * Version:           1.0.0
 * Author:            Rajib Hossain
 * Author URI:        https://www.upwork.com/freelancers/~0165130025eb932bed
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       db-manager
 * Domain Path:       /lang
 * WP requires at least: 5.0.0
 */


if (!defined('ABSPATH')) die('Direct access not allowed');
/*plugin environment variables*/
define('DBMANAGER_DIR_PATH', plugin_dir_path(__FILE__));
require plugin_dir_path(__FILE__) . 'vendor/autoload.php';
define('DBMANAGER_VERSION', '1.0.0');
define('DBMANAGER_NAME', 'db-manager');
define('DBMANAGER_FILE', plugin_basename(__FILE__));
define('DBMANAGER_URL', plugins_url(DBMANAGER_NAME . '/'));
define('DBMANAGER_STYLES', DBMANAGER_URL . 'assets/css/');
define('DBMANAGER_SCRIPTS', DBMANAGER_URL . 'assets/js/');
define('DBMANAGER_LOGS', DBMANAGER_DIR_PATH . 'logs/');

function is_requirements_met_dbm()
{
    $min_wp = '4.6'; // minimum WP version
    $min_php = '5.6'; // minimum PHP version
    // Check for WordPress version
    if (version_compare(get_bloginfo('version'), $min_wp, '<')) {
        return false;
    }
    // Check the PHP version
    if (version_compare(PHP_VERSION, $min_php, '<')) {
        add_action('admin_notices', function () {
            conf::notice_div('error', 'DB MANAGER requires at least PHP 5.6. Please upgrade PHP. The Plugin has been deactivated.');
        });
        return false;
    }
    return true;
}

/**
 * During plugin activation.
 */
function activate_dbmanager()
{
    GWWeatherActDeAct::activate();
}

//
/**
 * During plugin deactivation.
 */
function deactivate_dbmanager()
{
    GWWeatherActDeAct::deactivate();
}

register_activation_hook(__FILE__, 'activate_dbmanager');
register_deactivation_hook(__FILE__, 'deactivate_dbmanager');

/**
 * Execution of the plugin.
 */
function dbmanager_init()
{
    static $Plugin = null;
    if (null === $Plugin) {
        new DBManager\Admin(DBMANAGER_VERSION, DBMANAGER_FILE);
    }
    return $Plugin;
}

/*initialization*/
if (is_admin()) {
    dbmanager_init();
}