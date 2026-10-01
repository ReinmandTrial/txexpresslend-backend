<?php
/**
 * Block "Contacts and contact form": contact details on the left, the form on the right.
 * The form is handled in inc/forms.php.
 *
 * @var array $args Block fields plus h1 and uid (see txl_render_blocks()).
 */

defined( 'ABSPATH' ) || exit;

$txl_uid   = $args['uid'];
$txl_state = txl_form_state( 'contact' );
$txl_old   = $txl_state['old'];
$txl_hours = txl_rows( $args['hours'] );
?>
<section class="relative">
	<div class="absolute inset-0 max-lg:hidden grid grid-cols-2">
		<div class=""></div>
		<div class="bg-white-bg"></div>
	</div>
	<div class="ui-container relative z-10 grid grid-cols-1 lg:grid-cols-2">
		<div class="py-10 lg:pt-14 lg:pb-30">
			<?php if ( $args['title'] ) : ?>
				<h2 class="t-h1-m lg:t-h1 text-dark mb-12">
					<?php echo esc_html( $args['title'] ); ?>
				</h2>
			<?php endif; ?>
			<ul class="flex flex-col gap-12">
				<?php if ( $txl_hours ) : ?>
					<li class="flex gap-10">
						<div class="flex-none">
							<?php txl_icon( 'time-filled', 'size-10 text-orange' ); ?>
						</div>
						<div class="space-y-4">
							<?php foreach ( $txl_hours as $txl_row ) : ?>
								<dl class="flex flex-col">
									<dt class="t-body-small">
										<?php echo esc_html( $txl_row['days'] ); ?>
									</dt>
									<dd class="t-body-semibold">
										<?php echo esc_html( $txl_row['time'] ); ?>
									</dd>
								</dl>
							<?php endforeach; ?>
						</div>
					</li>
				<?php endif; ?>
				<?php if ( $args['email'] ) : ?>
					<li class="flex gap-10">
						<div class="flex-none">
							<?php txl_icon( 'email-filled', 'size-10 text-orange' ); ?>
						</div>
						<div class="space-y-4">
							<dl class="flex flex-col">
								<dt class="t-body-small">
									<?php echo esc_html( $args['email_label'] ); ?>
								</dt>
								<dd class="t-body-semibold">
									<a href="mailto:<?php echo esc_attr( antispambot( $args['email'] ) ); ?>"
										class=""><?php echo esc_html( antispambot( $args['email'] ) ); ?></a>
								</dd>
							</dl>
						</div>
					</li>
				<?php endif; ?>
				<?php if ( $args['address'] ) : ?>
					<li class="flex gap-10">
						<div class="flex-none">
							<?php txl_icon( 'location-pin-filled', 'size-10 text-orange' ); ?>
						</div>
						<div class="space-y-4">
							<p class="t-body-semibold">
								<?php echo esc_html( $args['address'] ); ?>
							</p>
						</div>
					</li>
				<?php endif; ?>
			</ul>
		</div>
		<div
			class="max-lg:bg-white-bg max-lg:-mx-4 max-lg:px-4 flex flex-col justify-end py-20 lg:pt-14 lg:pb-30 lg:justify-center">
			<div class="w-full max-w-100 mx-auto">
				<?php if ( $args['form_title'] ) : ?>
					<h2 class="t-h2-m lg:t-h2 text-dark mb-8 lg:mb-12">
						<?php echo esc_html( $args['form_title'] ); ?>
					</h2>
				<?php endif; ?>

				<form id="contact-form<?php echo esc_attr( $txl_uid ); ?>" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="flex flex-col gap-4">
					<?php txl_form_hidden_fields( 'contact' ); ?>
					<?php txl_form_notice( $txl_state, 'form_success_contact' ); ?>

					<div class="ui-input [&>input]:pl-4">
						<label for="contact-first-name<?php echo esc_attr( $txl_uid ); ?>" class="sr-only"><?php echo esc_html( $args['name_placeholder'] ); ?></label>
						<input type="text" id="contact-first-name<?php echo esc_attr( $txl_uid ); ?>" name="first-name" placeholder="<?php echo esc_attr( $args['name_placeholder'] ); ?>"
							value="<?php echo esc_attr( $txl_old['first-name'] ?? '' ); ?>"
							autocomplete="given-name" class="peer"<?php txl_invalid( $txl_state, 'first-name' ); ?> />
					</div>

					<div class="ui-input">
						<label for="contact-email<?php echo esc_attr( $txl_uid ); ?>" class="sr-only"><?php echo esc_html( $args['email_placeholder'] ); ?></label>
						<input type="email" id="contact-email<?php echo esc_attr( $txl_uid ); ?>" name="email" placeholder="<?php echo esc_attr( $args['email_placeholder'] ); ?>" required
							value="<?php echo esc_attr( $txl_old['email'] ?? '' ); ?>"
							autocomplete="email" class="peer"<?php txl_invalid( $txl_state, 'email' ); ?> />
						<?php txl_icon( 'mail' ); ?>
					</div>

					<div>
						<label for="contact-message<?php echo esc_attr( $txl_uid ); ?>" class="sr-only"><?php echo esc_html( $args['message_placeholder'] ); ?></label>
						<textarea id="contact-message<?php echo esc_attr( $txl_uid ); ?>" name="message" rows="6" placeholder="<?php echo esc_attr( $args['message_placeholder'] ); ?>" required
							class="w-full min-h-40 px-4 py-3.5 rounded-10 border border-blue-light bg-white t-label text-blue-lux placeholder:text-blue-light outline-none focus:border-blue-lux transition-colors duration-300 resize-y"<?php txl_invalid( $txl_state, 'message' ); ?>><?php echo esc_textarea( $txl_old['message'] ?? '' ); ?></textarea>
					</div>

					<button type="submit" class="btn-orange-base w-full mt-2 lg:mt-4"><?php echo esc_html( $args['button_text'] ); ?></button>
				</form>
			</div>
		</div>
	</div>
</section>
