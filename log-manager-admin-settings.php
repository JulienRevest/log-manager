<?php
/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */

/**
 * Custom option and settings.
 */
function log_manager_settings_init() {
	// Register a new setting for "elabo-log-manager" page.
	register_setting( 'elabo-log-manager', 'log_manager_options' );

	add_submenu_page(
		'tools.php',
		ELABO_EDD_ITEM_NAME,
		ELABO_EDD_ITEM_NAME,
		'manage_options',
		'elabo-log-manager',
		'log_manager_options_page_html'
	);

	// Register a new section in the "elabo-log-manager" page.
	add_settings_section(
		'log_manager_section_developers',
		__( 'Paramètres fichiers', 'elabo-log-manager' ),
		null,
		'elabo-log-manager'
	);

	add_settings_section(
		'log_manager_section_archives',
		null,
		'log_manager_management_html',
		'elabo-log-archives'
	);

	// Register a new field in the "log_manager_section_developers" section, inside the "elabo-log-manager" page.
	// https://developer.wordpress.org/reference/functions/add_settings_field/#comment-3531
	add_settings_field(
		'log_manager_file_size',
		__( 'Taille max. du fichier', 'elabo-log-manager' ),
		'log_manager_file_size_callback',
		'elabo-log-manager',
		'log_manager_section_developers',
		array(
			'label_for' => 'fileSize',
		)
	);
	add_settings_field(
		'log_manager_check_interval',                           // Setting ID.
		__( 'Interval de vérification', 'elabo-log-manager' ),  // Setting title.
		'log_manager_check_interval_callback',                  // Callback function.
		'elabo-log-manager',                                    // Page slug.
		'log_manager_section_developers',                       // Slug of the section to show the box.
		array(
			'label_for' => 'waitTime',
		)
	);

	add_settings_field(
		'log_manager_notify_email',                                        // Setting ID.
		__( 'Notifications par mail', 'elabo-log-manager' ),   // Setting title.
		'log_manager_notify_email_callback',                               // Callback function.
		'elabo-log-manager',                                               // Page slug.
		'log_manager_section_developers',                                  // Slug of the section to show the box.
		array(
			'label_for' => 'notifyEmail',
		)
	);

	add_settings_field(
		'log_manager_alternative_email',                            // Setting ID.
		__( "E-mail d'alertes alternatif", 'elabo-log-manager' ),   // Setting title.
		'log_manager_email_field_callback',                         // Callback function.
		'elabo-log-manager',                                        // Page slug.
		'log_manager_section_developers',                           // Slug of the section to show the box.
		array(
			'label_for' => 'alternativeEmail',
		)
	);

	add_settings_field(
		'log_manager_notify_slack',                                         // Setting ID.
		__( 'Notifications sur Slack', 'elabo-log-manager' ),   // Setting title.
		'log_manager_notify_slack_callback',                                // Callback function.
		'elabo-log-manager',                                                // Page slug.
		'log_manager_section_developers',                                   // Slug of the section to show the box.
		array(
			'label_for' => 'notifySlack',
		)
	);
}

/**
 * Register our log_manager_settings_init to the admin_init action hook.
 */
add_action( 'admin_menu', 'log_manager_settings_init' );

function get_archives_size() {
	$total_size = 0;
	$di = new RecursiveDirectoryIterator( ABSPATH . LOG_MANAGER_ARCHIVE_FOLDER );
	foreach ( new RecursiveIteratorIterator( $di ) as $filename => $file ) {
		if ( $file->isFile() ) {
			$total_size += $file->getSize();
		}
	}
	return $total_size;
}

/**
 * Create archives folder and associated .htaccess
 */
function create_archives() {
	wp_mkdir_p( ABSPATH . LOG_MANAGER_ARCHIVE_FOLDER );
	$file_manager = new WP_Filesystem_Direct( null );
	$file_manager->chmod( ABSPATH . LOG_MANAGER_ARCHIVE_FOLDER, 0755 );
	$htaccess = 'RewriteEngine On
		RewriteCond %{REQUEST_FILENAME} -s [OR]
		RewriteCond %{REQUEST_FILENAME} -l 
		RewriteRule ^.*$ - [NC,L]
		RewriteRule ^.*$ index.php [NC,L]
		<IfModule mod_autoindex.c>
		Options -Indexes
		</IfModule>';
	file_put_contents( ABSPATH . LOG_MANAGER_ARCHIVE_FOLDER . '.htaccess', $htaccess, FILE_TEXT );
}

// Import settings callbacks.
require_once 'log-manager-callbacks.php';
// Import back-office HTML.
require_once 'log-manager-bo-html.php';

/**
 * Register Cron
 *
 * @param string $interval WordPress cron intervals (https://developer.wordpress.org/plugins/cron/understanding-wp-cron-scheduling/).
 */
function schedule_cronjob( $interval ) {
	wp_schedule_event( time(), $interval, 'log_manager_cron_hook' );
}


/**
 * Send an email to the admin email or alternative email.
 */
function log_manager_send_alert_email() {
	$options = get_option( 'log_manager_options' );
	$should_notify = $options['log_manager_notify_email'];
	$log_manager_alternative_email = $options['log_manager_alternative_email'];
	if ( ! isset( $should_notify ) || empty( $should_notify ) ) {
		return;
	}

	$subject = __( 'e-labo Alerte ', 'elabo-log-manager' ) . ELABO_EDD_ITEM_NAME;
	$message = sprintf(
		/*
		 * Translators:
		 * %1 : URL of the WordPress instance.
		 * %2 : Name of the WordPress instance.
		 * %3 : URL of the WordPress instance.
		 * %4 : EDD plugin name.
		 */
		__(
			'<p>Votre site <a href="%1$s">%2$s (%3$s)</a> a atteint la limite de taille des logs d\'erreur.
			<p>Vérifiez régulièrement la taille du dossier d\'archives pour ne pas occuper trop d\'espace sur votre site !</p>
			<p>(Dans Outils → %4$s)</p>',
			'elabo-log-manager'
		),
		get_bloginfo( 'url' ),
		get_bloginfo( 'name' ),
		get_bloginfo( 'url' ),
		ELABO_EDD_ITEM_NAME,
	);

	$html = sprintf(
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
			<style>
				td {
					vertical-align:top;
					padding: 3rem;
					color: white;
					font-family: Arial, sans-serif;
					font-size: 20px;
					line-height: 30px;
				}
				table {
					border-collapse: collapse; background-color:#152636
				}
				a:visited {
					color: gray;
				}
			</style>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<title>%1$s</title>
				<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
			</head>
			<body>
				<table align="center" border="1" cellpadding="0" cellspacing="0" width="600" height="500">
					<tr>
						<td align="center">
							<img src="https://www.e-labo.biz/wp-content/themes/theme-elabo/img/logo-e-labo-hp-x1.png" width=200/>
							<br>
							%2$s
						</td>
					</tr>
				</table>
			</body>
		</html>',
		$subject,
		$message
	);

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( empty( $log_manager_alternative_email ) ) {
		wp_mail( get_option( 'admin_email' ), $subject, $html, $headers );
	} else {
		wp_mail( $log_manager_alternative_email, $subject, $html, $headers );
	}
}

/**
 * Slack notifications
 * https://developer.wordpress.org/plugins/http-api/#posting-data-to-an-api
 * https://api.slack.com/messaging/webhooksw
 */
function log_manager_slack_call() {
	$options = get_option( 'log_manager_options' );
	$should_notify = $options['log_manager_notify_slack'];
	if ( ! isset( $should_notify ) || empty( $should_notify ) ) {
		return;
	}

	$text = sprintf(
		'Limite de taille (%1$s) atteinte sur <%2$s|%3$s>, taille des archives: %4$s',
		size_format( intval( $options['log_manager_file_size'] ) * 1000000 ),
		get_bloginfo( 'url' ),
		get_bloginfo( 'name' ),
		size_format( get_archives_size(), 2 ),
	);

	$body = array(
		'blocks' => array_values(        // JSON Square brackets.
			array(
				array(
					'type' => 'section',
					'text' => array(
						'type' => 'mrkdwn',
						'text' => $text,
					),
				),
			)
		),
	);

	$args = array(
		'headers' => array(
			'Content-type' => 'application/json',
		),
		'body'        => json_encode( $body ),
		'timeout'     => '5',
		'redirection' => '5',
		'httpversion' => '1.1',
		'blocking'    => true,
		'headers'     => array(),
		'cookies'     => array(),
	);

	wp_remote_post( '<slack hook URL removed for security purposes>', $args );
}

/**
 * CRON execution.
 *
 * @param bool $manual_run Has the cron been run manually? Default is false.
 */
function log_manager_cron_exec( $manual_run = false ) {
	$error_log_path = ABSPATH . '/error_log';
	$archive_folder = ABSPATH . LOG_MANAGER_ARCHIVE_FOLDER;

	$options = get_option( 'log_manager_file_size' );
	$options = $options['log_manager_file_size'];
	$multiplier = ( 'integer' == gettype( $options ) ) ? intval( $options ) : 1;

	$file_manager = new WP_Filesystem_Direct( null );
	$filesize = null;

	if ( file_exists( $error_log_path ) ) {
		$filesize = esc_html( wp_filesize( $error_log_path ) );
		if ( ! file_exists( $archive_folder ) || ! is_dir( $archive_folder ) ) {
			create_archives();
		}
		// Compare filesize to a set size in Mb.
		if ( intval( $filesize ) > ( $multiplier * 1024 * 1000 ) ) {
			$file_manager->move( $error_log_path, $archive_folder . wp_date( 'Y-d-m_G-i', time() ) . '.txt' );

			// Do not send a mail or slack if we manually run a check (Submitting settings).
			if ( ! $manual_run ) {
				log_manager_send_alert_email();
				log_manager_slack_call();
			}
		}
	} else {
		$filesize = esc_html_e( 'Pas de fichier error_log !', 'log-manager' );
	}
	update_option( 'log_manager_last_run', time() );
}
add_action( 'log_manager_cron_hook', 'log_manager_cron_exec' );

/**
 * Clear archived logs
 */
function log_manager_clear_archives() {
	$archive_folder = ABSPATH . LOG_MANAGER_ARCHIVE_FOLDER;
	$file_manager = new WP_Filesystem_Direct( null );
	$response = $file_manager->delete( $archive_folder, true, 'd' );

	if ( $response ) {
		create_archives();
		esc_html_e( 'Les archives ont été effacées.', 'elabo-log-manager' );
	} else {
		esc_html_e( 'Erreur lors de la suppression des archives.', 'elabo-log-manager' );
	}
	wp_die();
}
add_action( 'wp_ajax_log_manager_clear_archives', 'log_manager_clear_archives' );

/**
 * Clear a single archived log
 */
function log_manager_clear_single_archives() {
	if ( ! isset( $_POST['toClear'] ) ) {
		wp_send_json_error( 'Pas de fichier spécifié', 403 );
	}

	$archive_file = ABSPATH . LOG_MANAGER_ARCHIVE_FOLDER . $_POST['toClear'];
	$file_manager = new WP_Filesystem_Direct( null );
	$response = $file_manager->delete( $archive_file, false, 'f' );

	if ( $response ) {
		echo esc_html( 'Archive ' . $_POST['toClear'] . ' éffacée.', 'elabo-log-manager' );
	} else {
		esc_html_e( "Erreur lors de la suppression de l'archive.", 'elabo-log-manager' );
	}
	wp_die();
}
add_action( 'wp_ajax_log_manager_clear_single_archives', 'log_manager_clear_single_archives' );
