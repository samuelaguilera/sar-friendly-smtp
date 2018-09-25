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
            echo '<div id="message" class="updated fade"><p>';
	        $result_text = sprintf( wp_filter_nohtml_kses( __( '%3$sAccording to WordPress %1$sthe email has been passed correctly to the SMTP server%2$s.%4$s%3$sThis means that %1$snow the SMTP server will process the email and send or reject it%2$s based on the server policies. If you do not receive the email, contact with your SMTP server support.%4$s', 'sar-friendly-smtp' ) ), '<strong>', '</strong>', '<p>', '</p>' );
            echo $result_text;
            echo '</p></div>';

        } else {
            echo '<div id="message" class="error fade"><p>';
   
            _e( 'WordPress was not able to pass the email to the SMTP server.', 'sar-friendly-smtp' );
  
            echo '</p>';
               if ( !empty($phpmailer->ErrorInfo) ) {
                    echo '<p>';
 
                    _e( 'Error returned by PHPMailer class:', 'sar-friendly-smtp' );                 
 
                    echo ' <strong>'. $phpmailer->ErrorInfo . '</strong></p></div>';
               } else {
                    echo '<p>';
 
                    _e( 'No additional information has been provided by PHPMailer class. Try enabling Error Log in Debug Mode setting and checking your server logs.', 'sar-friendly-smtp' );
 
                    echo '</p></div>';
               }
       } 

     } catch(Exception $e){ // In case of fatal error
        echo '<div id="message" class="error fade"><p>';
 
        _e( 'WordPress was not able to pass the email to the SMTP server.', 'sar-friendly-smtp' );
 
        echo '</p><p>';
 
        _e( 'Fatal Error returned by PHPMailer class:', 'sar-friendly-smtp' );
 
        echo ' <strong>'. $e->getMessage() . '</strong></p></div>';
     }

    }	

// Form
?>
<div class="wrap">
<h2><?php _e( 'SAR Friendly SMTP', 'sar-friendly-smtp' ); ?></h2>
<h3><?php _e( 'Send Email Test', 'sar-friendly-smtp' ); ?></h3>

<?php $note = sprintf( wp_filter_nohtml_kses( __( '%3$sThe purporse of this test is only to check if, using the SMTP details you have configured in the plugin settins page, %1$sWordPress is able to connect with your SMTP server and ask the server to send a simple plain text email%2$s. After this, sending the email or not is determined by the rules set by your SMTP provider. %1$sTherefore a successful connection does not guarantee the email sending or its deliverability.%2$s%3$sThe email will be sent to the WordPress admin email configured in %1$sSettings -> General -> E-mail Address%2$s. If the result of the test is an error or the email is not received, you will want to contact with your SMTP server support for assistance.%4$s%3$sTo start the test, simply click the button below.%4$s%4$s', 'sar-friendly-smtp' ) ), '<strong>', '</strong>', '<p>', '</p>' );
	echo '<div class="sar-test-info">' . $note . '</div>'; ?>

<form action="" method="post" enctype="multipart/form-data" name="sarfsmtp_test_email_form">
<input type="hidden" name="sarfsmtp_test" value="test_email" />
<input type="hidden" name="sarfsmtp_nonce" value="<?php echo $sar_test_email_nonce; ?>" />
<input type="submit" class="button-primary" value="<?php _e( 'Send Email Test', 'sar-friendly-smtp' ); ?>" />
</form>
</div>

<?php
}