<?php
/**
 * Email Test Page.
 *
 * @package SAR Friendly SMTP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate the Send Email Test page.
 */
function sar_friendly_smtp_test_email() {

	global $phpmailer;

	if ( isset( $_POST['sarfsmtp_test'] ) ) {

		if ( ! wp_verify_nonce( sanitize_key( $_POST['sarfsmtp_nonce'] ), 'sarfsmtp_email_test' ) ) { // phpcs:ignore
			wp_die( 'Security check not passed!' );
		}

		$to      = get_bloginfo( 'admin_email' );
		$content = __( 'SAR Friendly SMTP - Send Email Test', 'sar-friendly-smtp' );

		try {
			$mail_sent = wp_mail( $to, $content, $content );

			if ( true === $mail_sent ) {
				echo '<div id="message" class="updated fade"><p>';
				// translators: Placeholders are HTML tags, keep them in the same place.
				$result_text = sprintf( wp_filter_nohtml_kses( __( '%3$sAccording to WordPress %1$sthe email has been passed correctly to the SMTP server%2$s.%4$s%3$sThis means that %1$snow the SMTP server will process the email and send or reject it%2$s based on the server policies. If you do not receive the email, contact with your SMTP server support.%4$s', 'sar-friendly-smtp' ) ), '<strong>', '</strong>', '<p>', '</p>' );
				echo wp_kses_post( $result_text );
				echo '</p></div>';

			} else {
					echo '<div id="message" class="error fade"><p>';
					esc_html_e( 'WordPress was not able to pass the email to the SMTP server.', 'sar-friendly-smtp' );
					echo '</p>';
				if ( ! empty( $phpmailer->ErrorInfo ) ) { // phpcs:ignore
					echo '<p>';
					esc_html_e( 'Error returned by PHPMailer class: ', 'sar-friendly-smtp' );
					echo '<strong>' . esc_textarea( wp_filter_nohtml_kses( $phpmailer->ErrorInfo ) ) . '</strong></p></div>'; // phpcs:ignore
				} else {
					echo '<p>';
					esc_html_e( 'No additional information has been provided by PHPMailer class. Try enabling Error Log in Debug Mode setting and checking your server logs.', 'sar-friendly-smtp' );
					echo '</p></div>';
				}
			}
		} catch ( Exception $e ) { // In case of fatal error.
			echo '<div id="message" class="error fade"><p>';
			esc_html_e( 'WordPress was not able to pass the email to the SMTP server.', 'sar-friendly-smtp' );
			echo '</p><p>';
			esc_html_e( 'Fatal Error returned by PHPMailer class: ', 'sar-friendly-smtp' );
			echo '<strong>' . esc_html( $e->getMessage() ) . '</strong></p></div>';
		}
	}

	// Form.
	?>
<div class="wrap">
<h2><?php esc_html_e( 'SAR Friendly SMTP', 'sar-friendly-smtp' ); ?></h2>
<h3><?php esc_html_e( 'Send Email Test', 'sar-friendly-smtp' ); ?></h3>
	<?php
	// translators: Placeholders are HTML tags, keep them in the same place.
	$note = sprintf( wp_filter_nohtml_kses( __( '%3$sThe purpose of this test is only to check if, using the SMTP details you have configured in the plugin settings page, %1$sWordPress is able to connect with your SMTP server and ask the server to send a simple plain text email%2$s. After this, sending the email or not is determined by the rules set by your SMTP provider. %1$sTherefore a successful connection does not guarantee the email sending or its deliverability.%2$s%3$sThe email will be sent to the WordPress admin email configured in %1$sSettings -> General -> E-mail Address%2$s. If the result of the test is an error or the email is not received, you will want to contact with your SMTP server support for assistance.%4$s%3$sTo start the test, simply click the button below.%4$s%4$s', 'sar-friendly-smtp' ) ), '<strong>', '</strong>', '<p>', '</p>' );
	echo '<div class="sar-test-info">' . wp_kses_post( $note ) . '</div>';
	?>
<form action="" method="post" enctype="multipart/form-data" name="sarfsmtp_test_email_form">
<input type="hidden" name="sarfsmtp_test" value="test_email" />
	<?php wp_nonce_field( 'sarfsmtp_email_test', 'sarfsmtp_nonce' ); ?>
<input type="submit" class="button-primary" value="<?php esc_html_e( 'Send Email Test', 'sar-friendly-smtp' ); ?>" />
</form>
</div>
	<?php
}
