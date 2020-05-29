<?php
/**
 * Weather_Info Uninstall.
 *
 * @package    GW_Weather
 */
defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;
// Delete options.
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%weather_info%';");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%weather-info%';");