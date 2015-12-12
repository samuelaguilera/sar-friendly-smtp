<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

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
		array('Most SMTP servers (i.e. Gmail/Google Apps) requires your full email address as username.') 	 
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
		array('Hostname of your SMTP server (i.e. smtp.gmail.com).')		 
	);

	add_settings_field( 
		'port', 
		__( 'Port', 'sar-friendly-smtp' ), 
		'sarfsmtp_port_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_sarfsmtp_settings_page_section',
		array('If your server uses encryption, this should be 587 or 465. If not, standard non encrypted port is 25. (i.e. Gmail and Mandrill uses 587).')		 
	);

	add_settings_field( 
		'encryption', 
		__( 'Encryption', 'sar-friendly-smtp' ), 
		'sarfsmtp_encryption_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_sarfsmtp_settings_page_section',
		array('When using ecryption, most common setting is TLS. (i.e. Gmail and Mandrill uses TLS).')		 

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
		array('Name for the email FROM field. Only used if the original email uses your Site Title from Settings -> General.') 

	);

	add_settings_field( 
		'from_address', 
		__( 'FROM Address', 'sar-friendly-smtp' ), 
		'sarfsmtp_from_address_setting_render', 
		'sarfsmtp_settings_page', 
		'sarfsmtp_optional_fields_section',
		array('Email address for the email FROM field. Only used if the outgoing original message uses default value: wordpress@yourdomain.com') 
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
		array('Error Log option adds commands and data between WordPress and your SMTP server to PHP error_log file. <a href="https://wordpress.org/plugins/sar-friendly-smtp/faq/" title="SAR Friendly SMTP - FAQ" target="_blank">More information in the plugin\'s FAQ.</a>') 
	);

}


function sarfsmtp_from_name_setting_render( $args ) { 

	global $sarfsmtp_options;
	?>
	<input type="text" class="regular-text" name="sarfsmtp_settings[from_name]" value="<?php echo $sarfsmtp_options['from_name']; ?>">
	<p class="description"><?php echo $args[0] ?></p>
  
	<?php

}


function sarfsmtp_from_address_setting_render( $args ) { 

	global $sarfsmtp_options;
	?>
	<input type="email" class="regular-text ltr" name="sarfsmtp_settings[from_address]" value="<?php echo $sarfsmtp_options['from_address']; ?>">
    <p class="description"><?php echo $args[0] ?></p>
   
	<?php

}

function sarfsmtp_username_setting_render( $args ) { 

	global $sarfsmtp_options;
	?>
	<input type="text" class="regular-text" name="sarfsmtp_settings[username]" value="<?php echo $sarfsmtp_options['username']; ?>">
    <p class="description"><?php echo $args[0] ?></p>
	<?php

}


function sarfsmtp_password_setting_render( $args ) { 

	global $sarfsmtp_options;
	?>
	<input type="password" name="sarfsmtp_settings[password]" value="<?php echo $sarfsmtp_options['password']; ?>">
    <p class="description"><?php echo $args[0] ?></p>
	<?php

}

function sarfsmtp_smtp_server_setting_render( $args ) { 

	global $sarfsmtp_options;
	?>
	<input type="text" class="regular-text" name="sarfsmtp_settings[smtp_server]" value="<?php echo $sarfsmtp_options['smtp_server']; ?>">
    <p class="description"><?php echo $args[0] ?></p>
	<?php

}

function sarfsmtp_port_setting_render( $args ) { 

	global $sarfsmtp_options;
	?>
	<input type="text" class="small-text" name="sarfsmtp_settings[port]" value="<?php echo $sarfsmtp_options['port']; ?>">
    <p class="description"><?php echo $args[0] ?></p>
	<?php

}


function sarfsmtp_encryption_setting_render( $args ) { 

	global $sarfsmtp_options;
	?>
	<select name="sarfsmtp_settings[encryption]">
		<option value="" <?php selected( $sarfsmtp_options['encryption'],'' ); ?>><?php _e( 'None', 'sar-friendly-smtp' ) ?></option>
		<option value="tls" <?php selected( $sarfsmtp_options['encryption'], 'tls' ); ?>><?php _e( 'TLS', 'sar-friendly-smtp' ) ?></option>
		<option value="ssl" <?php selected( $sarfsmtp_options['encryption'], 'ssl' ); ?>><?php _e( 'SSL', 'sar-friendly-smtp' ) ?></option>
	</select>
    <p class="description"><?php echo $args[0] ?></p>	

<?php

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

	echo __( 'These settings are <strong>required</strong>. Be sure to put the correct settings here or your mail send will fail. If you\'re not sure about what values you need to put in eache field, contact your SMTP server support. After saving these settings you can test them from Tools -> Send Email Test.', 'sar-friendly-smtp' );

}

function sarfsmtp_optional_fields_section(  ) { 

	echo __( 'These settings are optional and only used if no other plugin using wp_mail() set his own data for these fields. (I.e. If you use Gravity Forms, these settings <strong>will not replace</strong> your FROM name/address for notifications created in Form Settings -> Notifications). If you leave this blank and no other plugin is setting their own info, WordPress default settings will be used for these fields.', 'sar-friendly-smtp' );

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

?>