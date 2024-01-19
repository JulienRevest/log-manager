<?php
/**
 * Plugin Name:       e-labo Log Manager
 * Description:       Gestion des logs d'erreurs WordPress
 * Requires at least: 5.7
 * Requires PHP:      7.0
 * Version:           1.0
 * Author:            e-labo
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       log-manager
 *
 * @package           log-manager
 */

/* This is the URL our updater / license checker pings. This should be the URL of the site with EDD installed */
define( 'ELABO_EDD_URL', '' );

/* The download ID for the product in Easy Digital Downloads */
define( 'ELABO_EDD_ITEM_ID', -1 );

/* The name of the product in Easy Digital Downloads */
define( 'ELABO_EDD_ITEM_NAME', 'Log Manager' );

/* The name of the settings page for the license input to be displayed */
define( 'ELABO_EDD_PLUGIN_LICENSE_PAGE', 'log-manager' );

/* The name of the file of the plugin */
define( 'ELABO_PLUGIN_FILE', 'log-manager.php' );

/* Version of the plugin, don't forget to also change it at the top of this file */
define( 'ELABO_PLUGIN_VERSION', '1.0' );

define( 'LOG_MANAGER_ARCHIVE_FOLDER', '/wp-content/uploads/archived-logs/' );

require_once 'edd-updater-licensing.php';
require_once 'log-manager-admin-settings.php';

/**
 * Load backoffice styles
 */
function admin_css() {
	$admin_stylesheet = plugin_dir_url( __FILE__ ) . '/styles.css';
	wp_enqueue_style( 'admin_css', $admin_stylesheet );
}
add_action( 'admin_print_styles', 'admin_css', 11 );

/**
 * Load backoffice javascript
 */
function admin_js() {
	$admin_javascript = plugin_dir_url( __FILE__ ) . 'js/log-manager-ajax.min.js';
	wp_enqueue_script( 'log-manager-js', $admin_javascript );
}
add_action( 'admin_enqueue_scripts', 'admin_js' );

/**
 * Set default settings, without overwritting existing values
 */
function log_manager_default_options() {
	$args = array(
		'log_manager_last_run'          => 'Never',
		'log_manager_file_size'         => '10',
		'log_manager_alternative_email' => '',
		'log_manager_check_interval'    => 'weekly',
		'log_manager_notify_email'      => 'on',
		'log_manager_notify_slack'      => 'on',
	);
	$current = get_option( 'log_manager_options' );

	if ( empty( $current ) ) {
		add_option( 'log_manager_options', $args );
		wp_schedule_event( time(), $args['log_manager_check_interval'], 'log_manager_cron_hook' );
		return;
	}
	if ( empty( $current['log_manager_last_run'] ) ) {
		$args['log_manager_last_run'] = get_option( 'log_manager_last_run' );
	}
	if ( empty( $current['log_manager_file_size'] ) ) {
		$args['log_manager_file_size'] = get_option( 'log_manager_file_size' );
	}
	if ( empty( $current['log_manager_alternative_email'] ) ) {
		$args['log_manager_alternative_email'] = get_option( 'log_manager_alternative_email' );
	}
	if ( empty( $current['log_manager_check_interval'] ) ) {
		$args['log_manager_check_interval'] = get_option( 'log_manager_check_interval' );
	}
	if ( empty( $current['log_manager_notify_email'] ) ) {
		$args['log_manager_notify_email'] = get_option( 'log_manager_notify_email' );
	}
	if ( empty( $current['log_manager_notify_slack'] ) ) {
		$args['log_manager_notify_slack'] = get_option( 'log_manager_notify_slack' );
	}
	update_option( 'log_manager_options', $args );
	wp_schedule_event( time(), $args['log_manager_check_interval'], 'log_manager_cron_hook' );
}
register_activation_hook( __FILE__, 'log_manager_default_options' );

/**
 * Clear CRON on deactivation
 */
function log_manager_deactivate_actions() {
	wp_clear_scheduled_hook( 'log_manager_cron_hook' );
}
register_deactivation_hook( __FILE__, 'log_manager_deactivate_actions' );

/**
 * Clear settings and CRON on uninstall
 */
function log_manager_remove_options() {
	wp_clear_scheduled_hook( 'log_manager_cron_hook' );
	delete_option( 'log_manager_options' );
}
register_uninstall_hook( __FILE__, 'log_manager_remove_options' );
