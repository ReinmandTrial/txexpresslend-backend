<?php
/**
 * Block "Rates table". On mobile every table row turns into a card.
 *
 * @var array $args Block fields plus h1 and uid (see txl_render_blocks()).
 */

defined( 'ABSPATH' ) || exit;

// Column heading: the first line as is, the rest on a new line.
$txl_rate_heading = static function ( ?string $label ): void {
	$lines = txl_lines( $label );
	echo esc_html( (string) array_shift( $lines ) );

	foreach ( $lines as $line ) {
		echo ' <span class="block">' . esc_html( $line ) . '</span>';
	}
};

// Columns after the first one (amount): row key → heading.
$txl_rate_columns = array(
	'fee'   => $args['col_fee'],
	'apr'   => $args['col_apr'],
	'term'  => $args['col_term'],
	'total' => $args['col_total'],
);
?>
<section class="">
	<div class="ui-container">
		<?php if ( $args['title'] ) : ?>
			<h3 class="t-h2-m lg:t-h2 mb-6 text-center">
				<?php echo esc_html( $args['title'] ); ?>
			</h3>
		<?php endif; ?>

		<div class="mt-6 lg:mt-8">
			<div
				class="max-lg:contents lg:rounded-2xl border border-border-neutral lg:overflow-hidden lg:bg-white lg:shadow-[0px_8px_24px_-4px_#12262614]">
				<table class="w-full text-left max-lg:block lg:table-fixed">
					<thead class="max-lg:hidden bg-[#93A9BC] text-white">
						<tr>
							<th scope="col" class="t-label align-middle font-normal py-2.5 px-6">
								<?php $txl_rate_heading( $args['col_amount'] ); ?>
							</th>
							<?php foreach ( $txl_rate_columns as $txl_label ) : ?>
								<th scope="col" class="t-label align-middle font-normal py-2.5 px-6">
									<?php $txl_rate_heading( $txl_label ); ?>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody
						class="max-lg:block text-blue max-lg:text-[#2F4858] lg:divide-y lg:divide-border-neutral">
						<?php foreach ( txl_rows( $args['rows'] ) as $txl_row ) : ?>
							<tr
								class="max-lg:block max-lg:rounded-xl max-lg:border max-lg:border-border-neutral max-lg:bg-white max-lg:p-4 max-lg:not-last:mb-4">
								<td
									class="t-h4-m lg:t-body-semibold align-middle text-orange py-4 px-6 max-lg:block max-lg:px-0 max-lg:pt-0 max-lg:pb-2.5 max-lg:mb-2.5 max-lg:border-b max-lg:border-border-neutral">
									<?php echo esc_html( $txl_row['amount'] ); ?>
								</td>
								<?php foreach ( $txl_rate_columns as $txl_key => $txl_label ) : ?>
									<?php // On mobile the column label comes from data-label. ?>
									<td data-label="<?php echo esc_attr( implode( ' ', txl_lines( $txl_label ) ) ); ?>"
										class="t-label py-4 px-6 align-middle max-lg:flex max-lg:items-center max-lg:justify-between max-lg:gap-4 max-lg:px-0 max-lg:py-0 max-lg:before:content-[attr(data-label)] max-lg:before:t-caption max-lg:before:text-[#788C9B] max-lg:not-last:pb-2">
										<?php echo esc_html( $txl_row[ $txl_key ] ); ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php if ( $args['note'] ) : ?>
				<div class="mt-3 lg:mt-6 flex items-start gap-3 rounded-xl bg-white-bg p-4">
					<?php txl_icon( 'info', 'size-4.5 flex-none text-blue-lux' ); ?>
					<p class="t-caption text-blue-lux">
						<?php txl_text( $args['note'] ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
