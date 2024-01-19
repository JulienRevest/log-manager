<?php

/**
 * Top level menu callback function
 */
function log_manager_options_page_html() {
	// check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Get the value of the setting we've registered with register_setting().
	$options = get_option( 'log_manager_options' );
	$log_manager_size_limit = $options['log_manager_file_size'];
	$log_manager_check_interval = $options['log_manager_check_interval'];
	$cron_last_run = isset( $options['log_manager_last_run'] ) ? $options['log_manager_last_run'] : '0';
	$cron_last_run = intval( $cron_last_run );
	$cron_next_run = wp_next_scheduled( 'log_manager_cron_hook' );
	$error_log_path = ABSPATH . '/error_log';

	// check if the user have submitted the settings.
	// WordPress will add the "settings-updated" $_GET parameter to the url.
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated".
		add_settings_error( 'log_manager_messages', 'log_manager_message', __( 'Paramètres sauvegardés', 'elabo-log-manager' ), 'updated' );

		// Run once on update, and schedule the next run.
		wp_clear_scheduled_hook( 'log_manager_cron_hook' );
		if ( ! empty( $log_manager_check_interval ) ) {
			schedule_cronjob( $log_manager_check_interval );
		}
	}

	// show error/update messages.
	settings_errors( 'log_manager_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<div class="log-manager-container">
			<form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "elabo-log-manager".
				settings_fields( 'elabo-log-manager' );
				// output setting sections and their fields.
				// (sections are registered for "elabo-log-manager", each field is registered to a specific section).
				do_settings_sections( 'elabo-log-manager' );
				?>
				<div>
					<?php
					if ( file_exists( $error_log_path ) ) {
						// Get file size, format it to be human readable with 2 decimals.
						echo esc_html( __( 'Taille actuelle du error_log : ', 'log-manager' ) . size_format( wp_filesize( $error_log_path ), 2 ) );
					} else {
						esc_html_e( 'Pas de fichier error_log !', 'log-manager' );
					}
					?>
				</div>
				<div>
					<?php
					esc_html_e( 'Dernière vérification : ', 'elabo-log-manager' );
					if ( ! empty( $cron_last_run ) && $cron_last_run && $cron_last_run > 1 ) {
						echo esc_html( wp_date( 'd/m/Y G:i:s', $cron_last_run ) );
					} else {
						echo esc_html__( 'Jamais', 'log-manager' );
					}
					?>
				</div>
				<div>
					<?php
					esc_html_e( 'Prochaine vérification : ', 'elabo-log-manager' );
					if ( ! empty( $cron_next_run ) && $cron_next_run && $cron_next_run > 1 ) {
						echo esc_html( wp_date( 'd/m/Y G:i:s', $cron_next_run ) );
					} else {
						echo esc_html__( 'Jamais', 'log-manager' );
					}
					?>
				</div>
				<?php
				// output save settings button.
				submit_button();
				?>
			</form>
			<?php do_settings_sections( 'elabo-log-archives' ); ?>
		</div>
	</div>
	<?php
}

function log_manager_management_html() {
	// check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// Get rid of the . and .. folders of UNIX env.
	$archive_folder = ABSPATH . LOG_MANAGER_ARCHIVE_FOLDER;
	$archive_scan = scandir( $archive_folder );
	$archive_list = $archive_scan ? array_diff( $archive_scan, array( '..', '.', '.htaccess' ) ) : array();

	// check if the user have submitted the settings.
	// WordPress will add the "settings-updated" $_GET parameter to the url.
	add_settings_error(
		'elabo-log-manager-archives',
		'log_manager_erased',
		__( 'Les archives ont été vidées.', 'elabo-log-manager' ),
		'success'
	);

	?>
	<form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="post">
		<h2><?php echo __( 'Gestion des archives', 'elabo-log-manager' ); ?></h2>
		<div>
			<?php
			if ( file_exists( $archive_folder ) ) {
				// Recursively get archive size, format it to be human readable with 2 decimals.
				$archive_size = get_archives_size();
				echo esc_html( __( 'Taille actuelle des archives : ', 'log-manager' ) . size_format( $archive_size, 2 ) );
			} else {
				esc_html_e( "Pas d'archives", 'log-manager' );
			}
			?>
		</div>
		<div class="log-manager-archive-list-container">
			<table name="log_manager_archive_list" class="log-manager-archive-list">
				<?php
				if ( empty( $archive_list ) ) {
					echo sprintf(
						'<tr><td class="log-manager-filename">%1$s</td></tr>',
						__( "Pas d'archives disponibles", 'elabo-log-manager' ),
					);
				} else {
					foreach ( $archive_list as $archived_file ) {
						echo sprintf(
							'<tr><td class="log-manager-filename"><a href="%1$s" target="_blank">%2$s</a></td><td class="log-manager-delete-button"><span class="dashicons dashicons-trash" data-path="%3$s"></span></td></tr>',
							LOG_MANAGER_ARCHIVE_FOLDER . $archived_file,
							esc_html( $archived_file ),
							esc_html( $archived_file ),
						);
					}
				}
				?>
			</table>
		</div>
		<input type="button" class="button button-primary log-manager-clear-archives" style="margin: 1rem 0;"value="<?php echo esc_html( __( 'Vider les archives', 'elabo-log-manager' ) ); ?>"/>
	</form>
	<?php
}
