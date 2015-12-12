<?php
/*
Plugin Name: SAR Friendly SMTP
Plugin URI: http://www.samuelaguilera.com
Description: A friendly SMTP plugin for WordPress. No third-party, simply using WordPress native possibilities.
Author: Samuel Aguilera
Version: 1.0.6
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
define('SAR_FSMTP_VER', '1.0.6');

// Add/Remove custom capability for settings access upon activation/deactivation
register_activation_hook(__FILE__, 'sar_friendly_smtp_add_cap');

function sar_friendly_smtp_add_cap() {
    // Add the capability for the administrator
    $role = get_role('administrator');    
    $role->add_cap("sar_fsmtp_options");    
}

register_deactivation_hook(__FILE__, 'sar_friendly_smtp_remove_cap');

function sar_friendly_smtp_remove_cap() {
    // Remove the capability for the administrator
    $role = get_role('administrator');    
    $role->remove_cap("sar_fsmtp_options");    
}

// Settings page
$sarfsmtp_options = get_option( 'sarfsmtp_settings' );
require('includes/options.php');
require('includes/email-test.php');

// The party starts :)
add_action('phpmailer_init','sar_friendly_smtp', 99999); // Very low priority to ensure we run after any other

function sar_friendly_smtp ($phpmailer) {

	global $sarfsmtp_options;

	// If server name or password are empty, don't touch anything!
	if ( empty( $sarfsmtp_options['smtp_server'] ) || empty( $sarfsmtp_options['password'] ) ) { return; }

	$phpmailer->IsSMTP();
	$phpmailer->SMTPAuth = true; // Always use authentication. I don't support open relays!
	$phpmailer->Username = $sarfsmtp_options['username'];
	$phpmailer->Password = $sarfsmtp_options['password'];
	$phpmailer->Host = $sarfsmtp_options['smtp_server'];
	$phpmailer->Port = $sarfsmtp_options['port'];
	$phpmailer->SMTPSecure = $sarfsmtp_options['encryption'];
	$phpmailer->XMailer = 'SAR Friendly SMTP '.SAR_FSMTP_VER.' - WordPress Plugin';

	// Be friendly with other plugins that may replace FROM field (i.e. Gravity Forms)
	$wp_email_start = substr( $phpmailer->From, 0, 9 );	

	// Replace From only when default value and FROM Address setting are set
	if ( $wp_email_start === 'wordpress' && !empty( $sarfsmtp_options['from_address'] ) ) { 
		$phpmailer->From = $sarfsmtp_options['from_address'];
	}

	// Replace FromName only when default value and FROM Name setting are set
	if ( $phpmailer->FromName === 'WordPress' && !empty( $sarfsmtp_options['from_name'] ) ) {
		$phpmailer->FromName = $sarfsmtp_options['from_name'];
	}

	// Debug mode
	if ( $sarfsmtp_options['debug_mode'] == 'error_log' ) {
		$phpmailer->SMTPDebug = 2; // Adds commands and data between WordPress and your SMTP server
		$phpmailer->Debugoutput = 'error_log'; // to PHP error_log file
	}

}

?>