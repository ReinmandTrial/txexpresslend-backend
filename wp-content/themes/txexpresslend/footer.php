<?php
/**
 * End of every page: the footer and scripts.
 */

defined( 'ABSPATH' ) || exit;

$txl_columns = array(
	array( txl_option( 'footer_col1_title' ), txl_menu_items( 'footer_company' ) ),
	array( txl_option( 'footer_col2_title' ), txl_menu_items( 'footer_links' ) ),
);
?>
	<footer class="flex-none bg-[#2F4858] text-white">
		<div class="ui-container">
			<div class="pt-10 lg:pt-8 pb-10 grid grid-cols-1 lg:grid-cols-3 gap-14">
				<div class="">
					<h4 class="t-h3-m lg:t-h3 mb-1">
						<?php echo esc_html( txl_option( 'footer_title' ) ); ?>
					</h4>
					<p class="t-overline mb-4 lg:mb-5 text-orange-600"><?php echo esc_html( txl_option( 'footer_subtitle' ) ); ?></p>
					<div class="flex gap-3">
						<?php foreach ( txl_rows( txl_option( 'footer_socials' ) ) as $txl_social ) : ?>
							<a <?php echo $txl_social['url'] ? 'href="' . esc_url( $txl_social['url'] ) . '" target="_blank" rel="noopener"' : 'href="#"'; ?>
								class="size-9 rounded-full bg-[#FFFFFF1F] t-label text-center flex justify-center items-center"><?php echo esc_html( $txl_social['label'] ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>

				<?php foreach ( $txl_columns as [ $txl_title, $txl_items ] ) : ?>
					<div class="">
						<h4 class="t-body-semibold mb-2">
							<?php echo esc_html( $txl_title ); ?>
						</h4>
						<span class="block h-0.75 bg-orange-600 rounded-full w-7 mb-3 lg:mb-4"></span>
						<div class="flex flex-col text-[#FFFFFFA6]">
							<?php foreach ( $txl_items as $txl_item ) : ?>
								<a <?php txl_menu_link_attrs( $txl_item ); ?> class="py-1.5 lg:px-1 t-label">
									<?php echo esc_html( $txl_item->title ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="py-8 lg:py-10 border-t border-[#FFFFFF26]">
				<div class="">
					<h4 class="t-overline mb-3 lg:mb-4"><?php echo esc_html( txl_option( 'footer_disclaimer_title' ) ); ?></h4>
					<p class="text-[#FFFFFF80]">
						<?php txl_text( txl_option( 'footer_disclaimer' ) ); ?>
					</p>
				</div>
				<div class="mt-6 lg:mt-8 pt-4 lg:pt-8 border-t border-[#FFFFFF26]">
					<div class="text-center opacity-50 t-caption">
						<?php echo esc_html( str_replace( '{year}', wp_date( 'Y' ), (string) txl_option( 'footer_copyright' ) ) ); ?>
					</div>
				</div>
			</div>
		</div>
	</footer>

	<?php wp_footer(); ?>
</body>

</html>
