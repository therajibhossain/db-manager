<?php
/**
 * Uninstall.
 *
 */
defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;
// Delete options.
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%dbmanager%';");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%dbmanager%';");