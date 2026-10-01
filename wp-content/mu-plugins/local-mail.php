<?php
/**
 * Plugin Name: Local mail → Mailpit
 * Description: In the local environment all mail goes to Mailpit (http://localhost:8025) instead of real recipients.
 */

if ( wp_get_environment_type() !== 'local' ) {
	return;
}

add_action(
	'phpmailer_init',
	static function ( $mailer ) {
		$mailer->isSMTP();
		$mailer->Host        = 'mailpit';
		$mailer->Port        = 1025;
		$mailer->SMTPAuth    = false;
		$mailer->SMTPAutoTLS = false;
	}
);

// By default WP sends from wordpress@localhost, which PHPMailer considers
// invalid, so the email wouldn't be sent at all.
add_filter( 'wp_mail_from', static fn() => 'noreply@txexpresslend.test' );
