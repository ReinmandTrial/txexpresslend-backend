<?php
/**
 * Block "Loan form (full screen)": the loan form on the left, benefits on the right
 * (form.html in the design). The form is handled in inc/forms.php.
 *
 * The Quick Loan form leads here (/form/?amount=300). Its amount and email are filled in
 * by src/main.js, not here: that keeps this page's HTML the same for everyone, so it can be
 * cached. PHP only restores what the visitor entered after a validation error.
 *
 * @var array $args Block fields plus h1 and uid (see txl_render_blocks()).
 */

defined( 'ABSPATH' ) || exit;

$txl_uid     = $args['uid'];
$txl_title   = $args['title'] ?: get_the_title();
$txl_tag     = $args['h1'] ? 'h1' : 'h2';
$txl_state   = txl_form_state( 'loan' );
$txl_amounts = txl_amounts();
$txl_old     = $txl_state['old'];
$txl_amount  = in_array( (int) ( $txl_old['amount'] ?? 0 ), $txl_amounts['values'], true ) ? (int) $txl_old['amount'] : 0;
?>
<div class="relative">
	<div class="absolute inset-0 max-lg:hidden grid grid-cols-2">
		<div class=""></div>
		<div class="bg-white-bg"></div>
	</div>
	<div class="ui-container relative z-10 min-h-screen lg:grid lg:grid-cols-2">
		<div
			class="max-lg:bg-white-bg max-lg:-mx-4 max-lg:px-4 max-lg:min-h-screen flex flex-col justify-end pt-10 pb-33 lg:pt-10 lg:pb-30 lg:justify-center lg:order-1">
			<<?php echo $txl_tag; // phpcs:ignore WordPress.Security.EscapeOutput -- only h1 or h2. ?> class="mb-6 t-h1-m lg:hidden text-dark">
				<?php echo esc_html( $txl_title ); ?>
			</<?php echo $txl_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>

			<?php if ( 'sent' === $txl_state['status'] ) : ?>
				<div id="loan-form<?php echo esc_attr( $txl_uid ); ?>" class="w-full max-w-100 mx-auto">
					<?php txl_form_notice( $txl_state, 'form_success_loan' ); ?>
				</div>
			<?php else : ?>
				<form id="loan-form<?php echo esc_attr( $txl_uid ); ?>" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"
					class="w-full max-w-100 mx-auto flex flex-col gap-6" data-loan-form>
					<?php txl_form_hidden_fields( 'loan' ); ?>
					<?php txl_form_notice( $txl_state, 'form_success_loan' ); ?>

					<div class="ui-input [&>input]:pl-4">
						<label for="first-name<?php echo esc_attr( $txl_uid ); ?>" class="sr-only"><?php echo esc_html( $args['first_name_placeholder'] ); ?></label>
						<input type="text" id="first-name<?php echo esc_attr( $txl_uid ); ?>" name="first-name" placeholder="<?php echo esc_attr( $args['first_name_placeholder'] ); ?>" required
							value="<?php echo esc_attr( $txl_old['first-name'] ?? '' ); ?>"
							autocomplete="given-name" class="peer"<?php txl_invalid( $txl_state, 'first-name' ); ?> />
					</div>

					<div class="ui-input [&>input]:pl-4">
						<label for="last-name<?php echo esc_attr( $txl_uid ); ?>" class="sr-only"><?php echo esc_html( $args['last_name_placeholder'] ); ?></label>
						<input type="text" id="last-name<?php echo esc_attr( $txl_uid ); ?>" name="last-name" placeholder="<?php echo esc_attr( $args['last_name_placeholder'] ); ?>" required
							value="<?php echo esc_attr( $txl_old['last-name'] ?? '' ); ?>"
							autocomplete="family-name" class="peer"<?php txl_invalid( $txl_state, 'last-name' ); ?> />
					</div>

					<div class="ui-input">
						<label for="email<?php echo esc_attr( $txl_uid ); ?>" class="sr-only"><?php echo esc_html( $args['email_placeholder'] ); ?></label>
						<input type="email" id="email<?php echo esc_attr( $txl_uid ); ?>" name="email" placeholder="<?php echo esc_attr( $args['email_placeholder'] ); ?>" required
							value="<?php echo esc_attr( $txl_old['email'] ?? '' ); ?>"
							autocomplete="email" class="peer"<?php txl_invalid( $txl_state, 'email' ); ?> />
						<?php txl_icon( 'mail' ); ?>
					</div>

					<?php // Custom dropdown instead of <select>: the logic is in src/main.js (initDropdown). ?>
					<div class="relative" data-dropdown>
						<span id="amount-label<?php echo esc_attr( $txl_uid ); ?>" class="sr-only"><?php echo esc_html( txl_option( 'hero_amount_label' ) ); ?></span>
						<input type="hidden" name="amount" value="<?php echo esc_attr( $txl_amount ?: '' ); ?>" data-dropdown-value />

						<button type="button" id="amount-toggle<?php echo esc_attr( $txl_uid ); ?>" aria-haspopup="listbox" aria-expanded="false"
							aria-controls="amount-list<?php echo esc_attr( $txl_uid ); ?>" aria-labelledby="amount-label<?php echo esc_attr( $txl_uid ); ?> amount-toggle<?php echo esc_attr( $txl_uid ); ?>"
							data-dropdown-toggle<?php echo $txl_amount ? '' : ' data-placeholder'; ?><?php txl_invalid( $txl_state, 'amount' ); ?>
							class="group peer relative w-full h-12 pl-10 pr-10 rounded-10 border border-blue-light bg-white t-label text-blue-lux text-left cursor-pointer outline-none transition-colors duration-300 hover:border-blue-lux focus-visible:border-blue-lux aria-expanded:border-blue-lux">
							<span data-dropdown-label class="group-data-placeholder:text-blue-light"><?php echo esc_html( $txl_amount ? txl_money( $txl_amount ) : $args['amount_placeholder'] ); ?></span>
							<?php txl_icon( 'banknote', 'size-3.5 text-blue-light absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none' ); ?>
							<?php txl_icon( 'chevron-down', 'size-4 text-blue-lux absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none transition-transform duration-300 group-aria-expanded:rotate-180' ); ?>
						</button>

						<ul id="amount-list<?php echo esc_attr( $txl_uid ); ?>" role="listbox" aria-labelledby="amount-label<?php echo esc_attr( $txl_uid ); ?>" data-dropdown-list
							class="absolute inset-x-0 top-full z-20 mt-2 max-h-60 overflow-y-auto py-1 rounded-10 border border-blue-light bg-white shadow-[0px_8px_24px_-4px_#12262614] invisible opacity-0 -translate-y-1 transition duration-200 peer-aria-expanded:visible peer-aria-expanded:opacity-100 peer-aria-expanded:translate-y-0">
							<?php foreach ( $txl_amounts['values'] as $txl_value ) : ?>
								<li role="option" aria-selected="<?php echo $txl_value === $txl_amount ? 'true' : 'false'; ?>" data-value="<?php echo esc_attr( $txl_value ); ?>"
									class="px-4 py-2.5 t-label text-blue-lux cursor-pointer transition-colors duration-150 data-active:bg-white-bg aria-selected:text-orange">
									<?php echo esc_html( txl_money( $txl_value ) ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>

					<button type="submit" class="btn-orange-base w-full"><?php echo esc_html( $args['button_text'] ); ?></button>

					<div
						class="t-caption text-blue-light text-center [&_a]:underline [&_a]:underline-offset-2 [&_a]:hover:no-underline">
						<?php txl_rich( $args['consent'] ); ?>
					</div>
				</form>
			<?php endif; ?>
		</div>

		<?php get_template_part( 'template-parts/benefits', null, array( 'title' => $txl_title ) ); ?>
	</div>
</div>
