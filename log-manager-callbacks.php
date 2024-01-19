<?php
/**
 * Fill field callback function.
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * If `name` in your input does not match your WP Option, it will not be saved automatically.
 *
 * @param array $args Arguments of the callback.
 */
function log_manager_file_size_callback( $args ) {
	$log_manager_size_limit = get_option( 'log_manager_options' );
	$log_manager_size_limit = $log_manager_size_limit['log_manager_file_size'];
	?>
	<input type="number" name="log_manager_options[log_manager_file_size]" min="1" max="1000" value="<?php echo isset( $log_manager_size_limit ) ? esc_html( $log_manager_size_limit ) : ''; ?>"/> Mb
	<?php
}

function log_manager_email_field_callback( $args ) {
	$log_manager_email_field = get_option( 'log_manager_options' );
	$log_manager_email_field = $log_manager_email_field['log_manager_alternative_email'];
	?>
	<input type="email" name="log_manager_options[log_manager_alternative_email]" placeholder="<?php echo esc_html( get_option( 'admin_email' ) ); ?>" value="<?php echo empty( $log_manager_email_field ) ? '' : esc_html( $log_manager_email_field ); ?>"/>
	<?php
}

function log_manager_notify_email_callback( $args ) {
	$log_manager_notify = get_option( 'log_manager_options' );
	$log_manager_notify = $log_manager_notify['log_manager_notify_email'];
	$checked = '';
	if ( isset( $log_manager_notify ) && 'on' == $log_manager_notify ) {
		$checked = 'checked';
	}
	?>
	<label class="log-manager-switch">
		<input type="checkbox" name="log_manager_options[log_manager_notify_email]" <?php echo esc_html( $checked ); ?>>
	</label>
	<?php
}

function log_manager_notify_slack_callback( $args ) {
	$log_manager_notify = get_option( 'log_manager_options' );
	$log_manager_notify = $log_manager_notify['log_manager_notify_slack'];
	$checked = '';
	if ( isset( $log_manager_notify ) && 'on' == $log_manager_notify ) {
		$checked = 'checked';
	}
	?>
	<label class="log-manager-switch">
		<input type="checkbox" name="log_manager_options[log_manager_notify_slack]" <?php echo esc_html( $checked ); ?>>
	</label>
	<?php
}

function log_manager_check_interval_callback( $args ) {
	$log_manager_check_interval = get_option( 'log_manager_options' );
	$log_manager_check_interval = $log_manager_check_interval['log_manager_check_interval'];
	?>
	<select name="log_manager_options[log_manager_check_interval]">
		<option value=""><?php esc_html_e( 'Interval de vérification', 'elabo-log-manager' ); ?></option>
		<option value="hourly" <?php echo ( 'hourly' == $log_manager_check_interval ? 'selected' : '' ); ?>><?php esc_html_e( 'Toutes les heures', 'elabo-log-manager' ); ?></option>
		<option value="twicedaily" <?php echo ( 'twicedaily' == $log_manager_check_interval ? 'selected' : '' ); ?>><?php esc_html_e( 'Deux fois par jour', 'elabo-log-manager' ); ?></option>
		<option value="daily" <?php echo ( 'daily' == $log_manager_check_interval ? 'selected' : '' ); ?>><?php esc_html_e( 'Tous les jours', 'elabo-log-manager' ); ?></option>
		<option value="weekly" <?php echo ( 'weekly' == $log_manager_check_interval ? 'selected' : '' ); ?>><?php esc_html_e( 'Chaque semaine', 'elabo-log-manager' ); ?></option>
	</select>
	<?php
	if ( empty( $log_manager_check_interval ) ) {
		echo sprintf(
			'<strong>%s</strong>',
			esc_html( 'La vérification est désactivée !', 'elabo-log-manager' ),
		);
	}
}
