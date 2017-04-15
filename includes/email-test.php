<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

function sar_friendly_smtp_test_email() {

	global $phpmailer;

	$sar_test_email_nonce = wp_create_nonce( 'sarfsmtp_nonce' );

    if ( isset( $_POST['sarfsmtp_test'] ) ) {

		if ( !wp_verify_nonce( $_POST['sarfsmtp_nonce'], 'sarfsmtp_nonce' ) ) {
			wp_die('Security check not passed!');
		}

        $to = get_bloginfo( 'admin_email' );
        $content = __( 'SAR Friendly SMTP - Send Email Test', 'sar-friendly-smtp' );


    try{
        $mail_sent = wp_mail( $to, $content, $content );

        if ( $mail_sent == true ) {
            echo '<div id="message" class="updated fade"><p>'.__( '<p>According to WordPress <strong>the email has been passed correctly to the SMTP server</strong>.<p>This means that <strong>now the SMTP server will process the email and send it or reject</strong> based on the server policies. If you don\'t receive the email, contact with your SMTP server support.</p>', 'sar-friendly-smtp' ).'</p></div>';

        } else {
            echo '<div id="message" class="error fade"><p>'.__( 'WordPress was not able to pass the email to the SMTP server.', 'sar-friendly-smtp' ).'</p>';
               if ( !empty($phpmailer->ErrorInfo) ) {
                    echo '<p>'.__( 'Error returned by PHPMailer class:', 'sar-friendly-smtp' ).' <strong>'.$phpmailer->ErrorInfo.'</strong></p></div>'; 
               } else {
                    echo '<p>'.__( 'No additional information has been provided by PHPMailer class. Try enabling Error Log in Debug Mode setting and checking your server logs.', 'sar-friendly-smtp' ).'</p></div>';
               }
       } 

     } catch(Exception $e){ // In case of fatal error
        echo '<div id="message" class="error fade"><p>'.__( 'WordPress was not able to pass the email to the SMTP server.', 'sar-friendly-smtp' ).'</p>';
        echo '<p>'.__( 'Fatal Error returned by PHPMailer class:', 'sar-friendly-smtp' ).' <strong>'.$e->getMessage().'</strong></p></div>';
     }

    }	

// Form
?>

<div class="wrap">
<h2><?php _e( 'SAR Friendly SMTP', 'sar-friendly-smtp' ); ?></h2>
<h3><?php _e( 'Send Email Test', 'sar-friendly-smtp' ); ?></h3>

<p><?php _e( '<p>From this screen you can try to send an email to the WordPress admin email in <strong>Settings -> General -> E-mail Address</strong> to see if your SMTP settings are correct.</p><p>Simply click the button below.</p>', 'sar-friendly-smtp' ); ?></p>

<form action="" method="post" enctype="multipart/form-data" name="sarfsmtp_test_email_form">
<input type="hidden" name="sarfsmtp_test" value="test_email" />
<input type="hidden" name="sarfsmtp_nonce" value="<?php echo $sar_test_email_nonce; ?>" />
<input type="submit" class="button-primary" value="<?php _e( 'Send Email Test', 'sar-friendly-smtp' ); ?>" />
</form>
</div>

<?php
}