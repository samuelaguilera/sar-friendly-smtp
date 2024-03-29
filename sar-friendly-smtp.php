<?php
/**
 * Plugin Name: SAR Friendly SMTP
 * Plugin URI: http://www.samuelaguilera.com
 * Description: A friendly SMTP plugin for WordPress. No third-party, simply using WordPress native possibilities.
 * Author: Samuel Aguilera
 * Version: 1.2.6
 * Author URI: http://www.samuelaguilera.com
 * Text Domain: sar-friendly-smtp
 * Domain Path: /languages
 * License: GPL3
 *
 * @package SAR Friendly SMTP
 */

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License version 3 as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Current plugin version, for now only used for XMailer setting.
define( 'SAR_FSMTP_VER', '1.2.6' );

// Add/Remove custom capability for settings access upon activation/deactivation.
register_activation_hook( __FILE__, 'sar_friendly_smtp_add_cap' );

/**
 * Add the capability for the administrator.
 */
function sar_friendly_smtp_add_cap() {
	$role = get_role( 'administrator' );
	$role->add_cap( 'sar_fsmtp_options' );
}


register_deactivation_hook( __FILE__, 'sar_friendly_smtp_remove_cap' );

/**
 * Remove the capability for the administrator.
 */
function sar_friendly_smtp_remove_cap() {
	$role = get_role( 'administrator' );
	$role->remove_cap( 'sar_fsmtp_options' );
}

// Get settings values.
$sarfsmtp_username          = get_option( 'sarfsmtp_username' );
$sarfsmtp_password          = get_option( 'sarfsmtp_password' );
$sarfsmtp_smtp_server       = get_option( 'sarfsmtp_smtp_server' );
$sarfsmtp_port              = get_option( 'sarfsmtp_port' );
$sarfsmtp_encryption        = get_option( 'sarfsmtp_encryption' );
$sarfsmtp_from_address      = get_option( 'sarfsmtp_from_address' );
$sarfsmtp_from_name         = get_option( 'sarfsmtp_from_name' );
$sarfsmtp_debug_mode        = get_option( 'sarfsmtp_debug_mode' );
$sarfsmtp_allow_invalid_ssl = get_option( 'sarfsmtp_allow_invalid_ssl' );

require 'includes/email-test.php';

// Action links.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sarfsmtp_action_links' );

/**
 * Return links to the Plugin row in Plugins page
 *
 * @param array $links Array of links for the plugin row.
 */
function sarfsmtp_action_links( $links ) {
	$links[] = '<a href="' . esc_url( get_admin_url( null, 'admin.php?page=sar_friendly_smtp' ) ) . '">' . __( 'Settings', 'sar-friendly-smtp' ) . '</a>';
	$links[] = '<a href="' . esc_url( get_admin_url( null, 'admin.php?page=sar_fsmtp_email_test' ) ) . '">' . __( 'Send Email Test', 'sar-friendly-smtp' ) . '</a>';
	return $links;
}


add_action( 'plugins_loaded', 'sar_friendly_smtp_load_textdomain' );

/**
 * Load Translation
 */
function sar_friendly_smtp_load_textdomain() {
	load_plugin_textdomain( 'sar-friendly-smtp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// Styles for settings page.
add_action( 'admin_enqueue_scripts', 'sar_friendly_smtp_settings_style' );

/**
 * Enqueue CSS Styles for Settings page
 *
 * @param string $hook Hook suffix for current admin page.
 */
function sar_friendly_smtp_settings_style( $hook ) {

	global $sarfsmtp_settings_page, $sarfsmtp_test_page;

	// Load only in plugin's pages.
	if ( $hook !== $sarfsmtp_settings_page && $hook !== $sarfsmtp_test_page ) {
		return;
	}

	wp_enqueue_style( 'custom_wp_admin_css', plugins_url( 'css/sar-fsmtp-styles.css', __FILE__ ), array(), '1.0' );

}

add_action( 'phpmailer_init', 'sar_friendly_smtp', 99999 ); // Very low priority to ensure we run after any other.

/**
 * The party starts :)
 *
 * @param array $phpmailer PHPMailer parameters provided by WP.
 */
function sar_friendly_smtp( $phpmailer ) {
	global $sarfsmtp_username, $sarfsmtp_password, $sarfsmtp_smtp_server, $sarfsmtp_port, $sarfsmtp_encryption, $sarfsmtp_from_address, $sarfsmtp_from_name, $sarfsmtp_debug_mode, $sarfsmtp_allow_invalid_ssl;

	// If server name or password are empty, don't touch anything!
	if ( ( ! defined( 'SAR_FSMTP_HOST' ) && empty( $sarfsmtp_smtp_server ) ) || ( ! defined( 'SAR_FSMTP_PASSWORD' ) && empty( $sarfsmtp_password ) ) ) {
		return;
	}

	// Note: WordPress coding standards will warn about incorrect snake_case for the PHPMailer variable names, but these can't be changed or PHPMailer will not recognize them!
	// Set PHPMailer to use SMTP.
	$phpmailer->IsSMTP(); // phpcs:ignore
	// Always use authentication. I don't support open relays!
	$phpmailer->SMTPAuth = true; // phpcs:ignore

	// Override saved settings if constants are set in wp-config.php file.
	( defined( 'SAR_FSMTP_USER' ) && is_string( SAR_FSMTP_USER ) ) ? $phpmailer->Username = SAR_FSMTP_USER : $phpmailer->Username = $sarfsmtp_username; // phpcs:ignore

	( defined( 'SAR_FSMTP_PASSWORD' ) && is_string( SAR_FSMTP_PASSWORD ) ) ? $phpmailer->Password = SAR_FSMTP_PASSWORD : $phpmailer->Password = $sarfsmtp_password; // phpcs:ignore

	( defined( 'SAR_FSMTP_HOST' ) && is_string( SAR_FSMTP_HOST ) ) ? $phpmailer->Host = SAR_FSMTP_HOST : $phpmailer->Host = $sarfsmtp_smtp_server; // phpcs:ignore

	// IMPORTANT! Don't use quotes for the SAR_FSMTP_PORT value or the check will fail and the port will be not used.
	( defined( 'SAR_FSMTP_PORT' ) && is_int( SAR_FSMTP_PORT ) ) ? $phpmailer->Port = SAR_FSMTP_PORT : $phpmailer->Port = $sarfsmtp_port; // phpcs:ignore

	( defined( 'SAR_FSMTP_ENCRYPTION' ) && in_array( SAR_FSMTP_ENCRYPTION, array( 'ssl', 'tls' ), true ) ) ? $phpmailer->SMTPSecure = SAR_FSMTP_ENCRYPTION : $phpmailer->SMTPSecure = $sarfsmtp_encryption; // phpcs:ignore

	$phpmailer->XMailer = 'SAR Friendly SMTP ' . SAR_FSMTP_VER . ' - WordPress Plugin'; // phpcs:ignore

	// Be friendly with other plugins that may replace FROM field (e.g. Gravity Forms).
	$wp_email_start = substr( $phpmailer->From, 0, 9 ); // phpcs:ignore

	// Replace From only when default value and FROM Address setting are set.
	if ( 'wordpress' === $wp_email_start && ( defined( 'SAR_FSMTP_FROM' ) || ! empty( $sarfsmtp_from_address ) ) ) { // phpcs:ignore
		( defined( 'SAR_FSMTP_FROM' ) && is_email( SAR_FSMTP_FROM ) ) ? $phpmailer->From = SAR_FSMTP_FROM : $phpmailer->From = $sarfsmtp_from_address; // phpcs:ignore
	}

	// Replace FromName only when default value and FROM Name setting are set.
	if ( 'WordPress' === $phpmailer->FromName && ( defined( 'SAR_FSMTP_FROM_NAME' ) || ! empty( $sarfsmtp_from_name ) ) ) { // phpcs:ignore
		( defined( 'SAR_FSMTP_FROM_NAME' ) && is_string( SAR_FSMTP_FROM_NAME ) ) ? $phpmailer->FromName = SAR_FSMTP_FROM_NAME : $phpmailer->FromName = $sarfsmtp_from_name; // phpcs:ignore
	}

	// Debug mode.
	if ( ( defined( 'SAR_FSMTP_DEBUG_MODE' ) && ( 'error_log' === SAR_FSMTP_DEBUG_MODE ) ) || 'error_log' === $sarfsmtp_debug_mode ) {
		// Adds commands and data between WordPress and your SMTP server.
		$phpmailer->SMTPDebug   = 2; // phpcs:ignore
		// to PHP error_log file.
		$phpmailer->Debugoutput = 'error_log'; // phpcs:ignore
	}

	// Allow invalid SSL https://github.com/PHPMailer/PHPMailer/issues/270 .
	if ( ( defined( 'SAR_FSMTP_ALLOW_INVALID_SSL' ) && ( 'on' === SAR_FSMTP_ALLOW_INVALID_SSL ) ) || 'on' === $sarfsmtp_allow_invalid_ssl ) {
		$phpmailer->smtpConnect(
			array(
				'ssl' => array(
					'verify_peer'       => false,
					'verify_peer_name'  => false,
					'allow_self_signed' => true,
				),
			)
		);
	}

}

add_action( 'admin_menu', 'sarfsmtp_add_admin_menu' );
add_action( 'admin_init', 'sarfsmtp_settings_init' );

/**
 * Menu page for settings.
 */
function sarfsmtp_add_admin_menu() {

	global $sarfsmtp_settings_page, $sarfsmtp_test_page;

	add_menu_page( 'SAR Friendly SMTP', 'SMTP', 'sar_fsmtp_options', 'sar_friendly_smtp', 'sar_friendly_smtp_options_page', 'dashicons-email-alt', '80' );

	// Adding pagges to variables for reference when enqueuing styles to the pages.
	$sarfsmtp_settings_page = add_submenu_page( 'sar_friendly_smtp', __( 'Settings', 'sar-friendly-smtp' ), __( 'Settings', 'sar-friendly-smtp' ), 'sar_fsmtp_options', 'sar_friendly_smtp' );
	$sarfsmtp_test_page     = add_submenu_page( 'sar_friendly_smtp', __( 'Send Email Test', 'sar-friendly-smtp' ), __( 'Send Email Test', 'sar-friendly-smtp' ), 'sar_fsmtp_options', 'sar_fsmtp_email_test', 'sar_friendly_smtp_test_email' );
}

/**
 * Settings registration.
 */
function sarfsmtp_settings_init() {

	// Register all setting keys. This includes sanitization of data being saved.
	register_setting( 'sarfsmtp_settings_smtp_page', 'sarfsmtp_username', 'wp_filter_nohtml_kses' );
	register_setting( 'sarfsmtp_settings_smtp_page', 'sarfsmtp_password', 'wp_filter_nohtml_kses' );
	register_setting( 'sarfsmtp_settings_smtp_page', 'sarfsmtp_smtp_server', 'wp_filter_nohtml_kses' );
	register_setting( 'sarfsmtp_settings_smtp_page', 'sarfsmtp_port', 'wp_filter_nohtml_kses' );
	register_setting( 'sarfsmtp_settings_smtp_page', 'sarfsmtp_encryption', 'wp_filter_nohtml_kses' );
	register_setting( 'sarfsmtp_settings_from_page', 'sarfsmtp_from_name', 'sar_wp_filter_nohtml_kses' );
	register_setting( 'sarfsmtp_settings_from_page', 'sarfsmtp_from_address', 'wp_filter_nohtml_kses' );
	register_setting( 'sarfsmtp_settings_misc_page', 'sarfsmtp_debug_mode', 'wp_filter_nohtml_kses' );
	register_setting( 'sarfsmtp_settings_misc_page', 'sarfsmtp_allow_invalid_ssl', 'wp_filter_nohtml_kses' );

	add_settings_section(
		'sarfsmtp_sarfsmtp_settings_page_section',
		__( 'SMTP Server details', 'sar-friendly-smtp' ),
		'sarfsmtp_server_details_section_callback',
		'sarfsmtp_settings_smtp_page'
	);

	add_settings_field(
		'sarfsmtp_username',
		__( 'Username', 'sar-friendly-smtp' ),
		'sarfsmtp_username_setting_render',
		'sarfsmtp_settings_smtp_page',
		'sarfsmtp_sarfsmtp_settings_page_section',
		array( __( 'Most SMTP servers requires your full email address as username (e.g. user@gmail.com ).', 'sar-friendly-smtp' ) )
	);

	add_settings_field(
		'sarfsmtp_password',
		__( 'Password', 'sar-friendly-smtp' ),
		'sarfsmtp_password_setting_render',
		'sarfsmtp_settings_smtp_page',
		'sarfsmtp_sarfsmtp_settings_page_section',
		array( '' )
	);

	add_settings_field(
		'sarfsmtp_smtp_server',
		__( 'SMTP Server', 'sar-friendly-smtp' ),
		'sarfsmtp_smtp_server_setting_render',
		'sarfsmtp_settings_smtp_page',
		'sarfsmtp_sarfsmtp_settings_page_section',
		array( __( 'Hostname of your SMTP server (e.g. smtp.gmail.com).', 'sar-friendly-smtp' ) )
	);

	add_settings_field(
		'sarfsmtp_port',
		__( 'Port', 'sar-friendly-smtp' ),
		'sarfsmtp_port_setting_render',
		'sarfsmtp_settings_smtp_page',
		'sarfsmtp_sarfsmtp_settings_page_section',
		array( __( 'If your server uses encryption, this should be 587 or 465 (e.g. Gmail and Mailgun use 587). If not, standard non encrypted port is 25.', 'sar-friendly-smtp' ) )
	);

	add_settings_field(
		'sarfsmtp_encryption',
		__( 'Encryption', 'sar-friendly-smtp' ),
		'sarfsmtp_encryption_setting_render',
		'sarfsmtp_settings_smtp_page',
		'sarfsmtp_sarfsmtp_settings_page_section',
		array( __( 'When using ecryption, most common setting is TLS. (e.g. Gmail and Mailgun use TLS).', 'sar-friendly-smtp' ) )
	);

	// Optional settings.
	add_settings_section(
		'sarfsmtp_optional_fields_section',
		__( 'FROM Field Settings', 'sar-friendly-smtp' ),
		'sarfsmtp_optional_fields_section',
		'sarfsmtp_settings_from_page'
	);

	add_settings_field(
		'sarfsmtp_from_name',
		__( 'FROM Name', 'sar-friendly-smtp' ),
		'sarfsmtp_from_name_setting_render',
		'sarfsmtp_settings_from_page',
		'sarfsmtp_optional_fields_section',
		array( __( 'Name for the email FROM field. Only used if the original email uses your Site Title from Settings -> General.', 'sar-friendly-smtp' ) )
	);

	add_settings_field(
		'sarfsmtp_from_address',
		__( 'FROM Address', 'sar-friendly-smtp' ),
		'sarfsmtp_from_address_setting_render',
		'sarfsmtp_settings_from_page',
		'sarfsmtp_optional_fields_section',
		array( __( 'Email address for the email FROM field. Only used if the outgoing original message uses default value: wordpress@yourdomain.com', 'sar-friendly-smtp' ) )
	);

	// Misc. Settings.
	add_settings_section(
		'sarfsmtp_misc_settings_section',
		__( 'Miscellaneous Settings', 'sar-friendly-smtp' ),
		'sarfsmtp_misc_settings_section',
		'sarfsmtp_settings_misc_page'
	);

	add_settings_field(
		'sarfsmtp_debug_mode',
		__( 'Debug Mode', 'sar-friendly-smtp' ),
		'sarfsmtp_debug_mode_setting_render',
		'sarfsmtp_settings_misc_page',
		'sarfsmtp_misc_settings_section',
		// translators: Placeholders are HTML tags for a link. Just leave them on the same position.
		array( sprintf( wp_filter_nohtml_kses( __( 'Error Log option adds commands and data between WordPress and your SMTP server to PHP error_log file. %1$sMore information in the FAQ%2$s.', 'sar-friendly-smtp' ) ), '<a href="https://wordpress.org/plugins/sar-friendly-smtp/faq/" title="SAR Friendly SMTP - FAQ" target="_blank" rel="noopener noreferrer">', '</a>' ) )
	);

	add_settings_field(
		'sarfsmtp_allow_invalid_ssl',
		__( 'Allow Invalid SSL', 'sar-friendly-smtp' ),
		'sarfsmtp_allow_invalid_ssl_setting_render',
		'sarfsmtp_settings_misc_page',
		'sarfsmtp_misc_settings_section',
		// translators: Placeholders are HTML tags for a link. Just leave them on the same position.
		array( sprintf( wp_filter_nohtml_kses( __( 'Allow connecting to a server with invalid SSL setup. Bear in mind this is only a workaround, the right thing would be to fix the server SSL setup. %1$sMore details at PHPMailer Github repository%2$s.', 'sar-friendly-smtp' ) ), '<a href="https://github.com/PHPMailer/PHPMailer/issues/270" title="SMTP connect() failed due to invalid SSL setup" target="_blank" rel="noopener noreferrer">', '</a>' ) )
	);

}

/**
 * Render From Name settings.
 *
 * @param array $args Array Description text for the setting.
 */
function sarfsmtp_from_name_setting_render( $args ) {

	global $sarfsmtp_from_name;

	if ( defined( 'SAR_FSMTP_FROM_NAME' ) && is_string( SAR_FSMTP_FROM_NAME ) ) {
		echo '<p class="sar-warning" >';
		esc_attr_e( 'This setting is being overridden by SAR_FSMTP_FROM_NAME constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</p>';
		// Add current value in db as hidden input to prevent resetting the stored value.
		?>
	<input type="hidden" name="sarfsmtp_from_name" value="<?php echo esc_attr( $sarfsmtp_from_name ); ?>">
		<?php
	} else {
		?>
	<input type="text" class="regular-text" name="sarfsmtp_from_name" value="<?php echo esc_attr( $sarfsmtp_from_name ); ?>" title="From Name">
	<p class="description"><?php echo esc_html( $args[0] ); ?></p>
		<?php
	}
}

/**
 * Render From Address settings.
 *
 * @param array $args Array Description text for the setting.
 */
function sarfsmtp_from_address_setting_render( $args ) {

	global $sarfsmtp_from_address;

	if ( defined( 'SAR_FSMTP_FROM' ) && is_email( SAR_FSMTP_FROM ) ) {
		echo '<p class="sar-warning" >';
		esc_attr_e( 'This setting is being overridden by SAR_FSMTP_FROM constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</p>';
		// Add current value in db as hidden input to prevent resetting the stored value.
		?>
	<input type="hidden" name="sarfsmtp_from_address" value="<?php echo esc_attr( $sarfsmtp_from_address ); ?>">
		<?php
	} else {
		?>
	<input type="email" class="regular-text ltr" name="sarfsmtp_from_address" value="<?php echo esc_attr( $sarfsmtp_from_address ); ?>" title="From Address">
	<p class="description"><?php echo esc_html( $args[0] ); ?></p>
		<?php
	}

}

/**
 * Render Username settings.
 *
 * @param array $args Array Description text for the setting.
 */
function sarfsmtp_username_setting_render( $args ) {

	global $sarfsmtp_username;

	if ( defined( 'SAR_FSMTP_USER' ) && is_string( SAR_FSMTP_USER ) ) {
		echo '<p class="sar-warning" >';
		esc_attr_e( 'This setting is being overridden by SAR_FSMTP_USER constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</p>';
		// add current value in db as hidden input to prevent resetting the stored value.
		?>
	<input type="hidden" name="sarfsmtp_username" value="<?php echo esc_html( $sarfsmtp_username ); ?>">
		<?php
	} else {
		?>
	<input type="text" class="regular-text" name="sarfsmtp_username" value="<?php echo esc_html( $sarfsmtp_username ); ?>" title="Username">
	<p class="description"><?php echo esc_html( $args[0] ); ?></p>
		<?php
	}

}

/**
 * Render Password setting.
 *
 * @param array $args Array Description text for the setting.
 */
function sarfsmtp_password_setting_render( $args ) {

	global $sarfsmtp_password;

	if ( defined( 'SAR_FSMTP_PASSWORD' ) && is_string( SAR_FSMTP_PASSWORD ) ) {
		echo '<p class="sar-warning" >';
		esc_attr_e( 'This setting is being overridden by SAR_FSMTP_PASSWORD constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</p>';
		// Add current value in db as hidden input to prevent resetting the stored value.
		?>
	<input type="hidden" name="sarfsmtp_password" value="<?php echo esc_attr( $sarfsmtp_password ); ?>">
		<?php
	} else {
		?>
	<input type="password" name="sarfsmtp_password" value="<?php echo esc_attr( $sarfsmtp_password ); ?>" title="Password">
	<p class="description"><?php echo esc_html( $args[0] ); ?></p>
		<?php
	}

}

/**
 * Render Hostname setting.
 *
 * @param array $args Array Description text for the setting.
 */
function sarfsmtp_smtp_server_setting_render( $args ) {

	global $sarfsmtp_smtp_server;

	if ( defined( 'SAR_FSMTP_HOST' ) && is_string( SAR_FSMTP_HOST ) ) {
		echo '<p class="sar-warning" >';
		esc_attr_e( 'This setting is being overridden by SAR_FSMTP_HOST constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</p>';
		// Add current value in db as hidden input to prevent resetting the stored value.
		?>
	<input type="hidden" name="sarfsmtp_smtp_server" value="<?php echo esc_attr( $sarfsmtp_smtp_server ); ?>">
		<?php
	} else {
		?>
	<input type="text" class="regular-text" name="sarfsmtp_smtp_server" value="<?php echo esc_attr( $sarfsmtp_smtp_server ); ?>" title="Server">
	<p class="description"><?php echo esc_html( $args[0] ); ?></p>
		<?php
	}

}

/**
 * Render Port setting.
 *
 * @param array $args Array Description text for the setting.
 */
function sarfsmtp_port_setting_render( $args ) {

	global $sarfsmtp_port;

	if ( defined( 'SAR_FSMTP_PORT' ) && is_int( SAR_FSMTP_PORT ) ) {
		echo '<p class="sar-warning" >';
		esc_attr_e( 'This setting is being overridden by SAR_FSMTP_PORT constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</p>';
		// Add current value in db as hidden input to prevent resetting the stored value.
		?>
	<input type="hidden" name="sarfsmtp_port" value="<?php echo esc_attr( $sarfsmtp_port ); ?>">
		<?php
	} else {
		?>
	<input type="text" class="small-text" name="sarfsmtp_port" value="<?php echo esc_attr( $sarfsmtp_port ); ?>" title="Port">
	<p class="description"><?php echo esc_html( $args[0] ); ?></p>
		<?php
	}

}

/**
 * Render Encryption setting.
 *
 * @param array $args Array Description text for the setting.
 */
function sarfsmtp_encryption_setting_render( $args ) {

	global $sarfsmtp_encryption;

	if ( defined( 'SAR_FSMTP_ENCRYPTION' ) && in_array( SAR_FSMTP_ENCRYPTION, array( 'ssl', 'tls' ), true ) ) {
		echo '<p class="sar-warning" >';
		esc_attr_e( 'This setting is being overridden by SAR_FSMTP_ENCRYPTION constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</p>';
		// Add current value in db as hidden input to prevent resetting the stored value.
		?>
	<input type="hidden" name="sarfsmtp_encryption" value="<?php echo esc_attr( $sarfsmtp_encryption ); ?>">
		<?php
	} else {
		?>
	<select name="sarfsmtp_encryption" title="Encryption">
		<option value="" <?php selected( $sarfsmtp_encryption, '' ); ?>><?php esc_attr_e( 'None', 'sar-friendly-smtp' ); ?></option>
		<option value="tls" <?php selected( $sarfsmtp_encryption, 'tls' ); ?>><?php esc_attr_e( 'TLS', 'sar-friendly-smtp' ); ?></option>
		<option value="ssl" <?php selected( $sarfsmtp_encryption, 'ssl' ); ?>><?php esc_attr_e( 'SSL', 'sar-friendly-smtp' ); ?></option>
	</select>
	<p class="description"><?php echo esc_html( $args[0] ); ?></p>	
		<?php
	}

}

/**
 * Render Debug mode setting.
 *
 * @param array $args Array Description text for the setting.
 */
function sarfsmtp_debug_mode_setting_render( $args ) {

	global $sarfsmtp_debug_mode;

	if ( defined( 'SAR_FSMTP_DEBUG_MODE' ) && is_string( SAR_FSMTP_DEBUG_MODE ) ) {
		echo '<p class="sar-warning" >';
		esc_attr_e( 'This setting is being overridden by SAR_FSMTP_DEBUG_MODE constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</p>';
		// Add current value in db as hidden input to prevent resetting the stored value.
		?>
	<input type="hidden" name="sarfsmtp_debug_mode" value="<?php echo esc_attr( $sarfsmtp_debug_mode ); ?>">
		<?php
	} else {
		?>
	<select name="sarfsmtp_debug_mode" title="Debug Mode">
		<option value="off" <?php selected( $sarfsmtp_debug_mode, 'off' ); ?>><?php esc_attr_e( 'Off', 'sar-friendly-smtp' ); ?></option>
		<option value="error_log" <?php selected( $sarfsmtp_debug_mode, 'error_log' ); ?>><?php esc_attr_e( 'Error Log', 'sar-friendly-smtp' ); ?></option>
	</select>
	<p class="description"><?php echo wp_kses_post( $args[0] ); ?></p>
		<?php
	}

}

/**
 * Render Invalid SSL setting.
 *
 * @param array $args Array Description text for the setting.
 */
function sarfsmtp_allow_invalid_ssl_setting_render( $args ) {

	global $sarfsmtp_allow_invalid_ssl;

	if ( defined( 'SAR_FSMTP_ALLOW_INVALID_SSL' ) && is_string( SAR_FSMTP_ALLOW_INVALID_SSL ) ) {
		echo '<p class="sar-warning" >';
		esc_attr_e( 'This setting is being overridden by SAR_FSMTP_ALLOW_INVALID_SSL constant in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</p>';
		// Add current value in db as hidden input to prevent resetting the stored value.
		?>
	<input type="hidden" name="sarfsmtp_allow_invalid_ssl" value="<?php echo esc_attr( $sarfsmtp_allow_invalid_ssl ); ?>">
		<?php
	} else {
		?>
	<select name="sarfsmtp_allow_invalid_ssl" title="Allow Invalid SSL">
		<option value="off" <?php selected( $sarfsmtp_allow_invalid_ssl, 'off' ); ?>><?php esc_attr_e( 'Off', 'sar-friendly-smtp' ); ?></option>
		<option value="on" <?php selected( $sarfsmtp_allow_invalid_ssl, 'on' ); ?>><?php esc_attr_e( 'On', 'sar-friendly-smtp' ); ?></option>
	</select>
	<p class="description"><?php echo wp_kses_post( $args[0] ); ?></p>
		<?php
	}

}

/**
 * Main settings section description.
 */
function sarfsmtp_server_details_section_callback() {
	$url = esc_url( get_admin_url( null, 'admin.php?page=sar_fsmtp_email_test' ) );
	// translators: Placeholders are HTML tags. Just leave them on the same position.
	$text = sprintf( wp_filter_nohtml_kses( __( 'These settings are %1$srequired%2$s. Be sure to put the correct settings here or your mail send will fail. If you are not sure about what values you need to put in each field, contact your SMTP server support. After saving these settings you can test them in %3$sSend Email Test%4$s page.', 'sar-friendly-smtp' ) ), '<strong>', '</strong>', "<a href='$url'>", '</a>' );
	echo wp_kses_post( $text );
}

/**
 * Optional settings section description.
 */
function sarfsmtp_optional_fields_section() {
	// translators: Placeholders are HTML tags. Just leave them on the same position.
	$text = sprintf( wp_filter_nohtml_kses( __( 'These settings are %1$soptional%2$s and only used if no other plugin using wp_mail() set its own data for these fields. (E.g. If you use Gravity Forms, these settings %1$swill not replace%2$s your FROM name/address for notifications created in Form Settings -> Notifications). If you leave this blank and no other plugin is setting their own info, WordPress will use the default core settings for these fields.', 'sar-friendly-smtp' ) ), '<strong>', '</strong>' );
	echo wp_kses_post( $text );
}

/**
 * Optional settings section description.
 */
function sarfsmtp_misc_settings_section() {
	// translators: Placeholders are HTML tags. Just leave them on the same position.
	$text = sprintf( wp_filter_nohtml_kses( __( 'These settings are %1$soptional%2$s too. Remember to turn off Debug Mode when you are done with the troubleshooting to avoid raising your server load by generating unnecessary logs.', 'sar-friendly-smtp' ) ), '<strong>', '</strong>' );
	echo wp_kses_post( $text );
}

/**
 * Options page output.
 */
function sar_friendly_smtp_options_page() {
	if ( ! current_user_can( 'sar_fsmtp_options' ) ) {
		wp_die( esc_html_e( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<!-- Create a header in the default WordPress 'wrap' container -->
	<div class="wrap"> 
	<h2><?php esc_html_e( 'SAR Friendly SMTP Settings', 'sar-friendly-smtp' ); ?></h2>

		<?php

		settings_errors(); // TODO: Register my own error messages and validations.

		// To check a nonce for $_GET add_submenu_page() should allow to add it to the URL, but it doesn't. I'm sanitizing the key anyway.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'smtp_server'; // phpcs:ignore
		?>
		<h2 class="nav-tab-wrapper">
			<a href="?page=sar_friendly_smtp&tab=smtp_server" class="nav-tab <?php echo 'smtp_server' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'SMTP Server details', 'sar-friendly-smtp' ); ?></a>
			<a href="?page=sar_friendly_smtp&tab=from_field" class="nav-tab <?php echo 'from_field' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'FROM Field Settings', 'sar-friendly-smtp' ); ?></a>
			<a href="?page=sar_friendly_smtp&tab=miscellaneous" class="nav-tab <?php echo 'miscellaneous' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Miscellaneous Settings', 'sar-friendly-smtp' ); ?></a>
		</h2>
	<form action='options.php' method='post'>
		<?php

		if ( 'smtp_server' === $active_tab ) {
			settings_fields( 'sarfsmtp_settings_smtp_page' );
			do_settings_sections( 'sarfsmtp_settings_smtp_page' );
		} elseif ( 'from_field' === $active_tab ) {
			settings_fields( 'sarfsmtp_settings_from_page' );
			do_settings_sections( 'sarfsmtp_settings_from_page' );
		} elseif ( 'miscellaneous' === $active_tab ) {
			settings_fields( 'sarfsmtp_settings_misc_page' );
			do_settings_sections( 'sarfsmtp_settings_misc_page' );
		} // end if/else

		submit_button();
		?>
	</form>
	</div>
	<?php
}

/**
 * Maybe update settings if a new version requires it.
 */
function sarfsmtp_maybe_upgrade_settings() {
	// Upgrade settings to new format if needed.

	$current_version = get_option( 'sarfsmtp_version' );

	// Return without changes if running a recent version of the plugin.
	if ( version_compare( $current_version, '1.2', '>=' ) ) {
		return;
	}

	// Settings array for older versions.
	$sarfsmtp_options = get_option( 'sarfsmtp_settings' );

	if ( is_array( $sarfsmtp_options ) ) {

		foreach ( $sarfsmtp_options as $key => $value ) {
			update_option( 'sarfsmtp_' . $key, $value );
		}

		// Deleting old settings array.
		delete_option( 'sarfsmtp_settings' );

		// Update version info in DB.
		update_option( 'sarfsmtp_version', SAR_FSMTP_VER );
	}

}
add_action( 'admin_init', 'sarfsmtp_maybe_upgrade_settings' );

/**
 * Modified wp_filter_nohtml_kses to allow the use of single quotes https://core.trac.wordpress.org/ticket/40606 .
 *
 * @param string $data Data to filter.
 */
function sar_wp_filter_nohtml_kses( $data ) {
	return wp_kses( stripslashes( $data ), 'strip' ); // In theory 'strip' should not be a valid parameter, but I'm just doing the same that wp_filter_nohtml_kses() core function does.
}
