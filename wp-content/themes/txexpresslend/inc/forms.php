<?php
/**
 * Site form handling: the loan form (/form) and the contact form (/contacts).
 *
 * How it works:
 * 1. The form is POSTed to wp-admin/admin-post.php, WP's built-in form endpoint.
 *    Based on the hidden field action=txl_form, WP fires the hook
 *    admin_post_nopriv_txl_form (for guests) or admin_post_txl_form (for logged-in users).
 * 2. txl_handle_form() runs the anti-spam checks, validates the fields,
 *    saves the lead in the admin, sends an email and passes the lead on.
 * 3. It redirects back to the page with ?txl_status=sent|error, so reloading the
 *    page won't submit the form a second time. A sent loan form instead goes on to the
 *    partner form with the visitor's data (txl_continue_to_partner_form()).
 *
 * The whole application flow, as on txexpresslend.com:
 *   Quick Loan (hero) → loan form (/form) → partner form (/apply), each step prefilled.
 *
 * Why there's no WP nonce: a nonce protects logged-in users from CSRF, and an anonymous
 * lead form has no account to protect. For guests WP gives everyone the same nonce anyway,
 * and on cached pages it goes stale after a day, breaking the forms. The real threat
 * here is spam, so the forms are protected by checks that work with page caching:
 *   - honeypot — a hidden field that people don't see and bots fill in;
 *   - Origin — the form must be submitted from our own site;
 *   - time trap — bots submit instantly, people take at least a couple of seconds;
 *   - rate limit — at most TXL_RATE_LIMIT leads per IP in 10 minutes.
 *
 * The Quick Loan form doesn't come here: it's a plain GET request that opens the
 * loan form (see template-parts/blocks/hero.php).
 */

defined( 'ABSPATH' ) || exit;

// Honeypot field: people don't see it, bots fill it in.
const TXL_HONEYPOT = 'website';

// Submitting faster than this after the page opened means a bot.
const TXL_MIN_FILL_TIME_MS = 2000;

/*
 * Max leads from one IP address per 10 minutes; 0 turns the limit off.
 * Can be overridden in wp-config.php. Behind Cloudflare or a load balancer, make sure
 * REMOTE_ADDR is the visitor's IP (hosts usually set this up), otherwise every visitor
 * shares one counter — then set it to 0.
 */
defined( 'TXL_RATE_LIMIT' ) || define( 'TXL_RATE_LIMIT', 5 );

/**
 * Form definitions. Field labels are used in the email and in the admin.
 */
function txl_forms(): array {
	return array(
		'loan'    => array(
			'label'  => 'Loan form',
			'fields' => array(
				'first-name' => array( 'label' => 'First name', 'required' => true ),
				'last-name'  => array( 'label' => 'Last name', 'required' => true ),
				'email'      => array( 'label' => 'Email', 'required' => true, 'type' => 'email' ),
				'amount'     => array( 'label' => 'Loan amount', 'required' => true, 'type' => 'amount' ),
			),
		),
		'contact' => array(
			'label'  => 'Contact form',
			'fields' => array(
				'first-name' => array( 'label' => 'First name', 'required' => false ),
				'email'      => array( 'label' => 'Email', 'required' => true, 'type' => 'email' ),
				'message'    => array( 'label' => 'Message', 'required' => true, 'type' => 'textarea' ),
			),
		),
	);
}

add_action( 'admin_post_nopriv_txl_form', 'txl_handle_form' );
add_action( 'admin_post_txl_form', 'txl_handle_form' );

function txl_handle_form(): void {
	// phpcs:disable WordPress.Security.NonceVerification -- public form without a nonce, see the file header.
	$form_id = sanitize_key( wp_unslash( $_POST['txl_form'] ?? '' ) );
	$return  = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['txl_return'] ?? '' ) ), home_url( '/' ) );
	$form    = txl_forms()[ $form_id ] ?? null;

	if ( ! $form ) {
		wp_safe_redirect( $return );
		exit;
	}

	// A bot filled in the honeypot, or the form was posted from another site:
	// pretend it worked and save nothing. Real visitors never get here.
	if ( ! empty( $_POST[ TXL_HONEYPOT ] ) || ! txl_is_same_origin() ) {
		txl_form_redirect( $return, $form_id, 'sent' );
	}

	[ $data, $errors ] = txl_validate_form( $form['fields'], $_POST );

	// Too fast, or no JS at all (bots rarely run it). A person could get here by accident,
	// so they get an error and can resubmit, not a fake success.
	if ( absint( $_POST['txl_elapsed'] ?? 0 ) < TXL_MIN_FILL_TIME_MS ) {
		$errors['_timer'] = true;
	}
	// phpcs:enable

	if ( TXL_RATE_LIMIT > 0 && txl_rate_limit_hits() >= TXL_RATE_LIMIT ) {
		$errors['_rate'] = true;
	}

	if ( $errors ) {
		$token = str_replace( '-', '', wp_generate_uuid4() );
		set_transient(
			'txl_form_' . $token,
			array(
				'errors' => array_keys( $errors ),
				'old'    => $data,
			),
			10 * MINUTE_IN_SECONDS
		);

		txl_form_redirect( $return, $form_id, 'error', $token );
	}

	$lead_id = txl_save_lead( $form_id, $data, $return );
	txl_notify( $form_id, $data, $return );
	txl_deliver_lead( $form_id, $data, $lead_id );
	txl_rate_limit_count();

	if ( 'loan' === $form_id ) {
		txl_continue_to_partner_form( $data );
	}

	txl_form_redirect( $return, $form_id, 'sent' );
}

/**
 * Whether the form was submitted from our own site.
 *
 * Browsers send the Origin header with every form POST; Referer is the fallback.
 * If neither is there (or Origin is "null" because of privacy settings), there's
 * nothing to compare — the other checks still apply.
 */
function txl_is_same_origin(): bool {
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput -- only the host is compared.
	$origin = wp_unslash( $_SERVER['HTTP_ORIGIN'] ?? '' );
	$source = ( $origin && 'null' !== $origin ) ? $origin : wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' );
	// phpcs:enable

	if ( ! $source ) {
		return true;
	}

	return wp_parse_url( $source, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST );
}

/**
 * Transient key of the lead counter for the visitor's IP.
 */
function txl_rate_limit_key(): string {
	$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );

	return 'txl_rate_' . md5( $ip );
}

/**
 * How many leads the visitor's IP sent in the last 10 minutes.
 */
function txl_rate_limit_hits(): int {
	return (int) get_transient( txl_rate_limit_key() );
}

/**
 * Counts a saved lead. The 10-minute window restarts with every lead.
 */
function txl_rate_limit_count(): void {
	set_transient( txl_rate_limit_key(), txl_rate_limit_hits() + 1, 10 * MINUTE_IN_SECONDS );
}

/**
 * Sanitizes and validates the fields.
 *
 * @return array{0: array, 1: array} [ data, errors ] — errors keyed by field.
 */
function txl_validate_form( array $fields, array $input ): array {
	$data   = array();
	$errors = array();

	foreach ( $fields as $key => $field ) {
		$raw   = wp_unslash( $input[ $key ] ?? '' );
		$raw   = is_string( $raw ) ? $raw : '';
		$valid = true;

		switch ( $field['type'] ?? 'text' ) {
			case 'email':
				$value = mb_substr( sanitize_text_field( $raw ), 0, 254 );
				$valid = '' === $value || is_email( $value );
				break;

			case 'amount':
				$amounts = txl_amounts();
				$value   = absint( $raw );
				$valid   = 0 === $value || ( $value >= $amounts['min'] && $value <= $amounts['max'] );
				break;

			case 'textarea':
				$value = mb_substr( sanitize_textarea_field( $raw ), 0, 5000 );
				break;

			default:
				$value = mb_substr( sanitize_text_field( $raw ), 0, 100 );
		}

		$data[ $key ] = $value;

		if ( ! $valid || ( $field['required'] && empty( $value ) ) ) {
			$errors[ $key ] = true;
		}
	}

	return array( $data, $errors );
}

/**
 * New lead notification email. Locally it's caught by Mailpit: http://localhost:8025
 */
function txl_notify( string $form_id, array $data, string $page_url ): void {
	$fields = txl_forms()[ $form_id ]['fields'];
	$lines  = array();

	foreach ( $fields as $key => $field ) {
		$value   = 'amount' === ( $field['type'] ?? '' ) ? txl_money( (int) $data[ $key ] ) : $data[ $key ];
		$lines[] = $field['label'] . ': ' . $value;
	}

	$lines[] = '';
	$lines[] = 'Page: ' . $page_url;

	$headers = array();

	// So the client can be answered straight from the mailbox with "Reply".
	if ( ! empty( $data['email'] ) ) {
		$headers[] = 'Reply-To: ' . $data['email'];
	}

	wp_mail(
		txl_option( 'notify_email' ) ?: get_option( 'admin_email' ),
		sprintf( '[%s] %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), txl_lead_title( $form_id, $data ) ),
		implode( "\n", $lines ),
		$headers
	);
}

/**
 * Passes the lead on: to a CRM, a lead network or a partner API.
 *
 * Until the client decides where leads should go, this does nothing: the lead is
 * already saved in the admin and emailed. Once there is an API, the sending code
 * goes here (or into a plugin via the txl_deliver_lead hook).
 */
function txl_deliver_lead( string $form_id, array $data, int $lead_id ): void {
	do_action( 'txl_deliver_lead', $form_id, $data, $lead_id );
}

/**
 * The loan form is the first step of the application: after it the visitor goes on to the
 * partner form (Site Settings → Forms → Partner form page), the same way as on txexpresslend.com.
 * The partner form reads these URL parameters and fills in its own fields with them.
 * Without a partner form page this returns, and the loan form shows its success message.
 */
function txl_continue_to_partner_form( array $data ): void {
	$url = txl_partner_form_url();

	if ( ! $url ) {
		return;
	}

	$params = array(
		'first_name'       => $data['first-name'],
		'last_name'        => $data['last-name'],
		'email'            => $data['email'],
		'requested_amount' => $data['amount'],
	);

	wp_safe_redirect( add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $params ) ), $url ) );
	exit;
}

function txl_form_redirect( string $url, string $form_id, string $status, string $token = '' ): void {
	$url = remove_query_arg( array( 'txl_form', 'txl_status', 'txl_token' ), $url );
	$url = add_query_arg(
		array_filter(
			array(
				'txl_form'   => $form_id,
				'txl_status' => $status,
				'txl_token'  => $token,
			)
		),
		$url
	);

	wp_safe_redirect( $url . '#' . $form_id . '-form' );
	exit;
}

/*
 * A page opened after a submission (?txl_status=...) may show what the visitor entered,
 * so it must never end up in a page cache. DONOTCACHEPAGE is the convention most caching
 * plugins respect (WP Rocket, W3 Total Cache, WP Super Cache, LiteSpeed Cache).
 */
add_action(
	'send_headers',
	static function () {
		if ( isset( $_GET['txl_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			nocache_headers();
			defined( 'DONOTCACHEPAGE' ) || define( 'DONOTCACHEPAGE', true );
		}
	}
);

// ---- For templates ----

/**
 * Hidden fields the handler requires to accept the form.
 * They're the same for every visitor, so the page can be cached; txl_elapsed is
 * filled in by src/main.js on submit.
 */
function txl_form_hidden_fields( string $form_id ): void {
	echo '<input type="hidden" name="action" value="txl_form" />';
	printf( '<input type="hidden" name="txl_form" value="%s" />', esc_attr( $form_id ) );
	printf( '<input type="hidden" name="txl_return" value="%s" />', esc_url( get_permalink() ) );
	echo '<input type="hidden" name="txl_elapsed" value="" />';

	printf(
		'<div class="hidden" aria-hidden="true"><label>Leave this field empty <input type="text" name="%s" value="" tabindex="-1" autocomplete="off" /></label></div>',
		esc_attr( TXL_HONEYPOT )
	);
}

/**
 * Form state after the redirect: sent, error (plus what was entered) or nothing.
 *
 * @return array{status: ?string, errors: string[], old: array}
 */
function txl_form_state( string $form_id ): array {
	// phpcs:disable WordPress.Security.NonceVerification -- only reads the status for display.
	$state = array(
		'status' => null,
		'errors' => array(),
		'old'    => array(),
	);

	if ( sanitize_key( wp_unslash( $_GET['txl_form'] ?? '' ) ) !== $form_id ) {
		return $state;
	}

	$status = sanitize_key( wp_unslash( $_GET['txl_status'] ?? '' ) );

	if ( 'sent' === $status ) {
		$state['status'] = 'sent';
	} elseif ( 'error' === $status ) {
		$state['status'] = 'error';
		$saved           = get_transient( 'txl_form_' . sanitize_key( wp_unslash( $_GET['txl_token'] ?? '' ) ) );

		if ( is_array( $saved ) ) {
			$state['errors'] = $saved['errors'];
			$state['old']    = $saved['old'];
		}
	}
	// phpcs:enable

	return $state;
}

/**
 * "Sent" / "Error" notice above the form.
 */
function txl_form_notice( array $state, string $success_option ): void {
	if ( ! $state['status'] ) {
		return;
	}

	$sent = 'sent' === $state['status'];
	$text = $sent ? txl_option( $success_option ) : txl_option( 'form_error' );
	?>
	<div class="flex items-start gap-3 rounded-xl bg-white border border-border-neutral p-4" role="<?php echo $sent ? 'status' : 'alert'; ?>">
		<?php txl_icon( $sent ? 'check-circle' : 'info', 'size-4.5 flex-none mt-0.5 ' . ( $sent ? 'text-[#16A34A]' : 'text-orange' ) ); ?>
		<p class="t-body-small text-blue-lux"><?php txl_text( $text ); ?></p>
	</div>
	<?php
}

/**
 * aria-invalid for a field with an error: highlights the border and informs screen readers.
 */
function txl_invalid( array $state, string $key ): void {
	if ( in_array( $key, $state['errors'], true ) ) {
		echo ' aria-invalid="true"';
	}
}
