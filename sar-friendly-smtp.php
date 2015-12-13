<?php
/*
Plugin Name: SAR Friendly SMTP
Plugin URI: http://www.samuelaguilera.com
Description: A friendly SMTP plugin for WordPress. No third-party, simply using WordPress native possibilities.
Author: Samuel Aguilera
Version: 1.1.3
Author URI: http://www.samuelaguilera.com
License: GPL3
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

if ( !defined( 'ABSPATH' ) ) { exit; }

// Current plugin version, for now only used for XMailer setting
define('SAR_FSMTP_VER', '1.1.3');

// Add/Remove custom capability for settings access upon activation/deactivation
register_activation_hook( __FILE__, 'sar_friendly_smtp_add_cap' );

function sar_friendly_smtp_add_cap() {
    // Add the capability for the administrator
    $role = get_role('administrator');    
    $role->add_cap("sar_fsmtp_options");    
}

register_deactivation_hook( __FILE__, 'sar_friendly_smtp_remove_cap' );

function sar_friendly_smtp_remove_cap() {
    // Remove the capability for the administrator
    $role = get_role('administrator');    
    $role->remove_cap("sar_fsmtp_options");    
}

// Settings page
$sarfsmtp_options = get_option( 'sarfsmtp_settings' );

require('includes/email-test.php');

// Action links
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'sarfsmtp_action_links' );

function sarfsmtp_action_links( $links ) {
   $links[] = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=sar_friendly_smtp') ) .'">Settings</a>';
   $links[] = '<a href="'. esc_url( get_admin_url(null, 'tools.php?page=sar-friendly-smtp%2Fincludes%2Femail-test.php') ) .'">Send Email Test</a>';
   return $links;
}

// Load translation
add_action( 'plugins_loaded', 'sar_friendly_smtp_load_textdomain' );

function sar_friendly_smtp_load_textdomain() {
  load_plugin_textdomain( 'sar-friendly-smtp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

// The party starts :)
add_action('phpmailer_init','sar_friendly_smtp', 99999); // Very low priority to ensure we run after any other

function sar_friendly_smtp ($phpmailer) {

	global $sarfsmtp_options;

	// If server name or password are empty, don't touch anything!
	if ( empty( $sarfsmtp_options['smtp_server'] ) || empty( $sarfsmtp_options['password'] ) ) { return; }

	$phpmailer->IsSMTP(); // Set PHPMailer to use SMTP
	$phpmailer->SMTPAuth = true; // Always use authentication. I don't support open relays!

	// Override saved settings if constants are set in wp-config.php file
	( defined( 'SAR_FSMTP_PORT' ) && is_string( SAR_FSMTP_USER ) ) ? $phpmailer->Username = SAR_FSMTP_USER : $phpmailer->Username = $sarfsmtp_options['username'];
	( defined( 'SAR_FSMTP_PASSWORD' ) && is_string( SAR_FSMTP_PASSWORD ) ) ? $phpmailer->Password = SAR_FSMTP_PASSWORD : $phpmailer->Password = $sarfsmtp_options['password'];
	( defined( 'SAR_FSMTP_HOST' ) && is_string( SAR_FSMTP_HOST ) ) ? $phpmailer->Host = SAR_FSMTP_HOST : $phpmailer->Host = $sarfsmtp_options['smtp_server'];
	// IMPORTANT! Don't use quotes for the SAR_FSMTP_PORT value or the check will fail and the port will be not used
	( defined( 'SAR_FSMTP_PORT' ) && is_int( SAR_FSMTP_PORT ) ) ? $phpmailer->Port = SAR_FSMTP_PORT : $phpmailer->Port = $sarfsmtp_options['port'];
	( defined( 'SAR_FSMTP_ENCRYPTION' ) && in_array( SAR_FSMTP_ENCRYPTION , array( 'ssl', 'tls' ) ) ) ? $phpmailer->SMTPSecure = SAR_FSMTP_ENCRYPTION : $phpmailer->SMTPSecure = $sarfsmtp_options['encryption'];

	$phpmailer->XMailer = 'SAR Friendly SMTP '.SAR_FSMTP_VER.' - WordPress Plugin';

	// Be friendly with other plugins that may replace FROM field (i.e. Gravity Forms)
	$wp_email_start = substr( $phpmailer->From, 0, 9 );	

	// Replace From only when default value and FROM Address setting are set
	if ( $wp_email_start === 'wordpress' && ( defined( 'SAR_FSMTP_FROM' ) || !empty( $sarfsmtp_options['from_address'] ) ) ) { 
		( defined( 'SAR_FSMTP_FROM' ) && is_email( SAR_FSMTP_FROM ) ) ? $phpmailer->From = SAR_FSMTP_FROM : $phpmailer->From = $sarfsmtp_options['from_address'];		
	}

	// Replace FromName only when default value and FROM Name setting are set
	if ( $phpmailer->FromName === 'WordPress' && ( defined( 'SAR_FSMTP_FROM_NAME' ) || !empty( $sarfsmtp_options['from_name'] ) ) ) {
		( defined( 'SAR_FSMTP_FROM_NAME' ) && is_string( SAR_FSMTP_FROM_NAME ) ) ? $phpmailer->FromName = SAR_FSMTP_FROM_NAME : $phpmailer->FromName = $sarfsmtp_options['from_name'];		
	}

	// Debug mode
	if ( $sarfsmtp_options['debug_mode'] == 'error_log' ) {
		$phpmailer->SMTPDebug = 2; // Adds commands and data between WordPress and your SMTP server
		$phpmailer->Debugoutput = 'error_log'; // to PHP error_log file
	}

}


add_action( 'admin_menu', 'sarfsmtp_add_admin_menu' );
add_action( 'admin_init', 'sarfsmtp_settings_init' );

function sarfsmtp_add_admin_menu(  ) { 

	add_options_page( 'SAR Friendly SMTP', 'SAR Friendly SMTP', 'sar_fsmtp_options', 'sar_friendly_smtp', 'sar_friendly_smtp_options_page' );

}


function sarfsmtp_settings_exist(  ) { 

	if( false == get_option( 'sar_friendly_smtp_settings' ) ) { 

		add_option( 'sar_friendly_smtp_settings' );

	}

}


function sarfsmtp_settings_init(  ) { 

	register_setting( 'sarfsmtp_settings_page', 'sarfsmtp_settings' );


	add_settings_section(
		'sarfsmtp_sarfsmtp_settings_page_section', 
		__( 'SMTP Server details', 'sar-friendly-smtp' ), 
		'sarfsmtp_server_details_section_callback', 
		'sarfsmtp_settings_page'
	);

	add_settings_field( 
		'username', 
		__( 'Username', 'sar-friendly-smtp' ), 
		'sarfsmtp_username_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_sarfsmtp_settings_page_section',

		array( __( 'Most SMTP servers (i.e. Gmail/Google Apps) requires your full email address as username.', 'sar-friendly-smtp' ) ) 	 
	);

	add_settings_field( 
		'password', 
		__( 'Password', 'sar-friendly-smtp' ), 
		'sarfsmtp_password_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_sarfsmtp_settings_page_section',
		array('') 	 
	);

	add_settings_field( 
		'smtp_server', 
		__( 'SMTP Server', 'sar-friendly-smtp' ), 
		'sarfsmtp_smtp_server_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_sarfsmtp_settings_page_section',
		array( __( 'Hostname of your SMTP server (e.g. smtp.gmail.com).', 'sar-friendly-smtp' ) )		 
	);

	add_settings_field( 
		'port', 
		__( 'Port', 'sar-friendly-smtp' ), 
		'sarfsmtp_port_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_sarfsmtp_settings_page_section',
		array( __('If your server uses encryption, this should be 587 or 465 (e.g. Gmail and Mandrill uses 587). If not, standard non encrypted port is 25.', 'sar-friendly-smtp' ) )		 
	);

	add_settings_field( 
		'encryption', 
		__( 'Encryption', 'sar-friendly-smtp' ), 
		'sarfsmtp_encryption_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_sarfsmtp_settings_page_section',
		array( __('When using ecryption, most common setting is TLS. (e.g. Gmail and Mandrill uses TLS).', 'sar-friendly-smtp' ) )		 

	);

	// Optional settings

	add_settings_section(
		'sarfsmtp_optional_fields_section', 
		__( 'FROM Field Settings (Optional)', 'sar-friendly-smtp' ), 
		'sarfsmtp_optional_fields_section', 
		'sarfsmtp_settings_page'
	);

	add_settings_field( 
		'from_name', 
		__( 'FROM Name', 'sar-friendly-smtp' ), 
		'sarfsmtp_from_name_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_optional_fields_section',
		array( __('Name for the email FROM field. Only used if the original email uses your Site Title from Settings -> General.', 'sar-friendly-smtp' ) ) 

	);

	add_settings_field( 
		'from_address', 
		__( 'FROM Address', 'sar-friendly-smtp' ), 
		'sarfsmtp_from_address_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_optional_fields_section',
		array( __('Email address for the email FROM field. Only used if the outgoing original message uses default value: wordpress@yourdomain.com', 'sar-friendly-smtp' ) ) 
	);

	// Misc. Settings

	add_settings_section(
		'sarfsmtp_misc_settings_section', 
		__( 'Miscellaneous Settings', 'sar-friendly-smtp' ), 
		'sarfsmtp_misc_settings_section', 
		'sarfsmtp_settings_page'
	);

	add_settings_field( 
		'debug_mode', 
		__( 'Debug Mode', 'sar-friendly-smtp' ), 
		'sarfsmtp_debug_mode_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_misc_settings_section',
		array( __('Error Log option adds commands and data between WordPress and your SMTP server to PHP error_log file. <a href="https://wordpress.org/plugins/sar-friendly-smtp/faq/" title="SAR Friendly SMTP - FAQ" target="_blank">More information in the plugin\'s FAQ.</a>', 'sar-friendly-smtp' ) ) 
	);

}


function sarfsmtp_from_name_setting_render( $args ) { 
	global $sarfsmtp_options;
	if ( defined( 'SAR_FSMTP_FROM_NAME' ) && is_string( SAR_FSMTP_FROM_NAME ) ) {
		echo '<div class="error" >';
		_e( 'This setting is being overridden by SAR_FSMTP_FROM_NAME constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</div>';
	// add current value in db as hidden input to prevent resetting the stored value
	?>
	<input type="hidden" name="sarfsmtp_settings[from_name]" value="<?php echo $sarfsmtp_options['from_name']; ?>">
	<?php
	} else {
	?>
	<input type="text" class="regular-text" name="sarfsmtp_settings[from_name]" value="<?php echo $sarfsmtp_options['from_name']; ?>">
	<p class="description"><?php echo $args[0] ?></p>
	<?php
	}
}


function sarfsmtp_from_address_setting_render( $args ) { 
	global $sarfsmtp_options;
	if ( defined( 'SAR_FSMTP_FROM' ) && is_email( SAR_FSMTP_FROM ) ) {
		echo '<div class="error" >';
		_e( 'This setting is being overridden by SAR_FSMTP_FROM constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</div>';
	// add current value in db as hidden input to prevent resetting the stored value
	?>
	<input type="hidden" name="sarfsmtp_settings[from_address]" value="<?php echo $sarfsmtp_options['from_address']; ?>">
	<?php
	} else {
	?>
	<input type="email" class="regular-text ltr" name="sarfsmtp_settings[from_address]" value="<?php echo $sarfsmtp_options['from_address']; ?>">
    <p class="description"><?php echo $args[0] ?></p>
	<?php
	}

}

function sarfsmtp_username_setting_render( $args ) { 
	global $sarfsmtp_options;
	if ( defined( 'SAR_FSMTP_USER' ) && is_string( SAR_FSMTP_USER ) ) {
		echo '<div class="error" >';
		_e( 'This setting is being overridden by SAR_FSMTP_USER constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</div>';
	// add current value in db as hidden input to prevent resetting the stored value
	?>
	<input type="hidden" name="sarfsmtp_settings[username]" value="<?php echo $sarfsmtp_options['username']; ?>">
	<?php
	} else {
	?>
	<input type="text" class="regular-text" name="sarfsmtp_settings[username]" value="<?php echo $sarfsmtp_options['username']; ?>">
    <p class="description"><?php echo $args[0] ?></p>
	<?php
	}

}


function sarfsmtp_password_setting_render( $args ) { 
	global $sarfsmtp_options;
	if ( defined( 'SAR_FSMTP_PASSWORD' ) && is_string( SAR_FSMTP_PASSWORD ) ) {
		echo '<div class="error" >';
		_e( 'This setting is being overridden by SAR_FSMTP_PASSWORD constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</div>';
	// add current value in db as hidden input to prevent resetting the stored value
	?>
	<input type="hidden" name="sarfsmtp_settings[password]" value="<?php echo $sarfsmtp_options['password']; ?>">
	<?php
	} else {
	?>
	<input type="password" name="sarfsmtp_settings[password]" value="<?php echo $sarfsmtp_options['password']; ?>">
    <p class="description"><?php echo $args[0] ?></p>
	<?php
	}

}

function sarfsmtp_smtp_server_setting_render( $args ) { 
	global $sarfsmtp_options;
	if ( defined( 'SAR_FSMTP_HOST' ) && is_string( SAR_FSMTP_HOST ) ) {
		echo '<div class="error" >';
		_e( 'This setting is being overridden by SAR_FSMTP_HOST constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</div>';
	// add current value in db as hidden input to prevent resetting the stored value
	?>
	<input type="hidden" name="sarfsmtp_settings[smtp_server]" value="<?php echo $sarfsmtp_options['smtp_server']; ?>">
	<?php
	} else {
	?>
	<input type="text" class="regular-text" name="sarfsmtp_settings[smtp_server]" value="<?php echo $sarfsmtp_options['smtp_server']; ?>">
    <p class="description"><?php echo $args[0] ?></p>
	<?php
	}

}

function sarfsmtp_port_setting_render( $args ) { 
	global $sarfsmtp_options;
	if ( defined( 'SAR_FSMTP_PORT' ) && is_int( SAR_FSMTP_PORT ) ) {
		echo '<div class="error" >';
		_e( 'This setting is being overridden by SAR_FSMTP_PORT constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</div>';	
	// add current value in db as hidden input to prevent resetting the stored value
	?>
	<input type="hidden" name="sarfsmtp_settings[port]" value="<?php echo $sarfsmtp_options['port']; ?>">
	<?php
	} else {
	?>
	<input type="text" class="small-text" name="sarfsmtp_settings[port]" value="<?php echo $sarfsmtp_options['port']; ?>">
    <p class="description"><?php echo $args[0] ?></p>
	<?php
	}

}


function sarfsmtp_encryption_setting_render( $args ) { 
	global $sarfsmtp_options;
	if ( defined( 'SAR_FSMTP_ENCRYPTION' ) && in_array( SAR_FSMTP_ENCRYPTION , array( 'ssl', 'tls' ) ) ) {
		echo '<div class="error" >';
		_e( 'This setting is being overridden by SAR_FSMTP_ENCRYPTION constant set in your wp-config.php file.', 'sar-friendly-smtp' );
		echo '</div>';
	// add current value in db as hidden input to prevent resetting the stored value
	?>
	<input type="hidden" name="sarfsmtp_settings[encryption]" value="<?php echo $sarfsmtp_options['encryption']; ?>">
	<?php
	} else {
	?>
	<select name="sarfsmtp_settings[encryption]">
		<option value="" <?php selected( $sarfsmtp_options['encryption'],'' ); ?>><?php _e( 'None', 'sar-friendly-smtp' ) ?></option>
		<option value="tls" <?php selected( $sarfsmtp_options['encryption'], 'tls' ); ?>><?php _e( 'TLS', 'sar-friendly-smtp' ) ?></option>
		<option value="ssl" <?php selected( $sarfsmtp_options['encryption'], 'ssl' ); ?>><?php _e( 'SSL', 'sar-friendly-smtp' ) ?></option>
	</select>
    <p class="description"><?php echo $args[0] ?></p>	
	<?php
	}

}


function sarfsmtp_debug_mode_setting_render( $args ) { 
	global $sarfsmtp_options;
	?>
	<select name="sarfsmtp_settings[debug_mode]">
		<option value="off" <?php selected( $sarfsmtp_options['debug_mode'],'off' ); ?>><?php _e( 'Off', 'sar-friendly-smtp' ) ?></option>
		<option value="error_log" <?php selected( $sarfsmtp_options['debug_mode'], 'error_log' ); ?>><?php _e( 'Error Log', 'sar-friendly-smtp' ) ?></option>
	</select>
	<p class="description"><?php echo $args[0] ?></p>
	<?php

}


function sarfsmtp_server_details_section_callback(  ) { 

	echo __( 'These settings are <strong>required</strong>. Be sure to put the correct settings here or your mail send will fail. If you\'re not sure about what values you need to put in each field, contact your SMTP server support. After saving these settings you can test them from Tools -> Send Email Test.', 'sar-friendly-smtp' );

}

function sarfsmtp_optional_fields_section(  ) { 

	echo __( 'These settings are optional and only used if no other plugin using wp_mail() set his own data for these fields. (E.g. If you use Gravity Forms, these settings <strong>will not replace</strong> your FROM name/address for notifications created in Form Settings -> Notifications). If you leave this blank and no other plugin is setting their own info, WordPress will use the default core settings for these fields.', 'sar-friendly-smtp' );

}

function sarfsmtp_misc_settings_section(  ) { 

	echo __( 'These settings are optional too. Remember to turn off Debug Mode when you\'re done with the troubleshooting to avoid raising your server load by generating unnecessary logs.' , 'sar-friendly-smtp' );

}

function sar_friendly_smtp_options_page(  ) { 

	?>
	<form action='options.php' method='post'>
		
		<h2>SAR Friendly SMTP</h2>
		
		<?php
		settings_fields( 'sarfsmtp_settings_page' );
		do_settings_sections( 'sarfsmtp_settings_page' );
		submit_button();
		?>
		
	</form>
	<?php

}