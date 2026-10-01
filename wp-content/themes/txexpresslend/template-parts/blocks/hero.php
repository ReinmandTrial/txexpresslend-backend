<?php
/**
 * Block "Hero with Quick Loan".
 *
 * Everything except the title and the text comes from Site Settings → Hero. The block can
 * override the title and the text; if any of the three title parts is filled in the block,
 * all three are taken from it, so shared and own parts don't get mixed.
 *
 * The Quick Loan form opens the loan form with a GET request: /form/?amount=300.
 * The email input deliberately has no name, so it never ends up in the URL:
 * src/main.js carries it over in sessionStorage and fills it in on the loan form.
 *
 * @var array $args Block fields plus h1 and uid (see txl_render_blocks()).
 */

defined( 'ABSPATH' ) || exit;

$txl_uid       = $args['uid'];
$txl_title_tag = $args['h1'] ? 'h1' : 'p';
$txl_amounts   = txl_amounts();
$txl_stats     = txl_rows( txl_option( 'hero_stats' ) );

$txl_title = $args['title'] || $args['title_accent'] || $args['title_after']
	? array( $args['title'], $args['title_accent'], $args['title_after'] )
	: array( txl_option( 'hero_title' ), txl_option( 'hero_title_accent' ), txl_option( 'hero_title_after' ) );

$txl_text = $args['text'] ?: txl_option( 'hero_text' );
?>
<section class="relative lg:py-20" aria-labelledby="hero-title<?php echo esc_attr( $txl_uid ); ?>">
	<div class="ui-container relative z-10 flex flex-col lg:flex-row lg:items-center gap-4 lg:gap-20">
		<div class="max-lg:contents lg:flex-1 lg:flex lg:flex-col lg:gap-5">
			<<?php echo $txl_title_tag; // phpcs:ignore WordPress.Security.EscapeOutput -- only h1 or p. ?> id="hero-title<?php echo esc_attr( $txl_uid ); ?>" class="t-h1-m lg:t-h1 text-dark lg:max-w-180">
				<?php echo esc_html( $txl_title[0] ); ?>
				<?php if ( $txl_title[1] ) : ?>
					<span class="text-orange"><?php echo esc_html( $txl_title[1] ); ?></span>
				<?php endif; ?>
				<?php echo esc_html( $txl_title[2] ); ?>
			</<?php echo $txl_title_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>

			<p class="t-body-small max-w-165 max-lg:mb-2">
				<?php txl_text( $txl_text ); ?>
			</p>

			<?php if ( $txl_stats ) : ?>
				<dl class="grid grid-cols-[1fr_auto] gap-x-6 gap-y-4 max-lg:order-1 lg:flex lg:gap-12 lg:mt-6">
					<?php foreach ( $txl_stats as $txl_i => $txl_stat ) : ?>
						<?php // On mobile the second item moves to the end of the 2×2 grid, as in the design. ?>
						<div class="flex flex-col gap-1<?php echo 1 === $txl_i ? ' max-lg:order-3' : ''; ?>">
							<dt class="t-overline text-[#3A5C60]"><?php echo esc_html( $txl_stat['label'] ); ?></dt>
							<dd class="t-h4-m lg:t-h4 text-blue-lux"><?php echo esc_html( $txl_stat['value'] ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</div>

		<div
			class="lg:w-120 relative lg:flex-none lg:mx-5 flex flex-col gap-1 lg:gap-6 bg-white rounded-xl shadow-[0px_8px_24px_-4px_#12262614] p-3 lg:p-10">
			<?php
			txl_image(
				txl_option( 'hero_image' ),
				'max-lg:hidden absolute -z-10 -top-13 -left-25 -bottom-13 -right-25 object-contain max-w-none pointer-events-none',
				array(
					'alt'         => '',
					'aria-hidden' => 'true',
					'loading'     => 'eager',
				)
			);
			?>
			<h2 class="t-h3 text-dark"><?php echo esc_html( txl_option( 'hero_form_title' ) ); ?></h2>

			<form action="<?php echo esc_url( txl_loan_form_url() ); ?>" method="get" class="flex flex-col gap-4.5 lg:gap-6" novalidate data-quick-loan>
				<div class="bg-white-bg rounded-xl p-4 lg:p-6">
					<label for="loan-amount<?php echo esc_attr( $txl_uid ); ?>" class="t-overline block mb-1.5 text-[#3A5C60]">
						<?php echo esc_html( txl_option( 'hero_amount_label' ) ); ?>
					</label>
					<output for="loan-amount<?php echo esc_attr( $txl_uid ); ?>" id="loan-amount-value<?php echo esc_attr( $txl_uid ); ?>"
						class="t-h1-m lg:t-h1 text-blue-lux lg:mb-2.5"><?php echo esc_html( txl_money( $txl_amounts['default'] ) ); ?></output>

					<input type="range" id="loan-amount<?php echo esc_attr( $txl_uid ); ?>" name="amount"
						min="<?php echo esc_attr( $txl_amounts['min'] ); ?>"
						max="<?php echo esc_attr( $txl_amounts['max'] ); ?>"
						step="<?php echo esc_attr( $txl_amounts['step'] ); ?>"
						value="<?php echo esc_attr( $txl_amounts['default'] ); ?>" class="loan-slider" />

					<div class="flex items-center justify-between t-caption text-blue-light">
						<span><?php echo esc_html( txl_money( $txl_amounts['min'] ) ); ?></span>
						<span><?php echo esc_html( txl_money( $txl_amounts['max'] ) ); ?></span>
					</div>
				</div>

				<div class="flex flex-col gap-1.5">
					<label for="hero-email<?php echo esc_attr( $txl_uid ); ?>" class="t-label text-blue-lux"><?php echo esc_html( txl_option( 'hero_email_label' ) ); ?></label>
					<div class="ui-input">
						<input type="email" id="hero-email<?php echo esc_attr( $txl_uid ); ?>" placeholder="<?php echo esc_attr( txl_option( 'hero_email_placeholder' ) ); ?>" required
							autocomplete="email" class="peer" data-quick-loan-email />
						<?php txl_icon( 'mail', '' ); ?>
					</div>
				</div>

				<button type="submit" class="btn-orange-base w-full max-lg:py-3"><?php echo esc_html( txl_option( 'hero_button_text' ) ); ?></button>
			</form>

			<ul class="grid grid-cols-2 gap-x-4 gap-y-2 lg:gap-4 max-lg:mt-2 t-caption text-blue-light">
				<?php foreach ( txl_rows( txl_option( 'hero_badges' ) ) as $txl_badge ) : ?>
					<li class="flex items-center gap-1.5">
						<?php txl_icon( $txl_badge['icon'], 'size-3.5 flex-none' ); ?>
						<?php echo esc_html( $txl_badge['text'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
