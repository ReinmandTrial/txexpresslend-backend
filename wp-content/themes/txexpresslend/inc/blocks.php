<?php
/**
 * Page builder: every page is a list of blocks — the SCF Flexible Content field "blocks".
 *
 * A block type is a layout of that field. Its fields are in acf-json/group_txl_builder.json
 * (SCF → Field Groups → Page Builder), its markup in template-parts/blocks/<template>.php,
 * which receives the block's fields in $args.
 *
 * Adding a block type:
 * 1. add a layout to the Page Builder field group;
 * 2. add its template to template-parts/blocks/;
 * 3. register it in txl_blocks() below.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registered block types: layout name => settings.
 *
 *   template — file in template-parts/blocks/, without .php
 *   flush    — no outer spacing: the block sits right against its neighbors and the footer
 *              (contacts, full-screen forms), as in the design
 *   tight    — less space above the block: the rates table follows "How it works" closely
 */
function txl_blocks(): array {
	return array(
		'hero'         => array( 'template' => 'hero' ),
		'loan_options' => array( 'template' => 'loan-options' ),
		'steps'        => array( 'template' => 'steps' ),
		'how_it_works' => array( 'template' => 'how-it-works' ),
		'rates'        => array(
			'template' => 'rates',
			'tight'    => true,
		),
		'eligibility'  => array( 'template' => 'eligibility' ),
		'regulations'  => array( 'template' => 'regulations' ),
		'responsible'  => array( 'template' => 'responsible' ),
		'faq'          => array( 'template' => 'faq' ),
		'contacts'     => array(
			'template' => 'contacts',
			'flush'    => true,
		),
		'text'         => array( 'template' => 'text' ),
		'loan_form'    => array(
			'template' => 'loan-form',
			'flush'    => true,
		),
		'partner_form' => array(
			'template' => 'partner-form',
			'flush'    => true,
		),
	);
}

/**
 * Outputs the page's blocks.
 *
 * Besides the block's own fields, each template gets in $args:
 *   h1  — whether this block holds the page's main heading. A page gets exactly one h1:
 *         the first Text block's title, else the Loan form heading, else the first Hero title.
 *   uid — suffix for element ids: '' for the first block of a type on the page, '-2', '-3'…
 *         for the next ones, so ids stay unique. The first one keeps the plain ids that
 *         src/main.js and the form handler's redirects rely on.
 */
function txl_render_blocks( $post_id = false ): void {
	$types = txl_blocks();

	$rows = array_values(
		array_filter(
			txl_rows( get_field( 'blocks', $post_id ) ),
			static fn( $row ) => isset( $types[ $row['acf_fc_layout'] ?? '' ] ) && empty( $row['hidden'] )
		)
	);

	if ( ! $rows ) {
		return;
	}

	$layouts = array_column( $rows, 'acf_fc_layout' );
	$h1      = false;

	foreach ( array( 'text', 'loan_form', 'hero' ) as $candidate ) {
		$h1 = array_search( $candidate, $layouts, true );

		if ( false !== $h1 ) {
			break;
		}
	}

	$instances = array();
	$last      = count( $rows ) - 1;

	echo '<div class="flex flex-col">';

	foreach ( $rows as $i => $row ) {
		$layout = $row['acf_fc_layout'];
		$type   = $types[ $layout ];

		$instances[ $layout ] = ( $instances[ $layout ] ?? 0 ) + 1;

		printf(
			'<div%s class="%s">',
			$row['anchor'] ? ' id="' . esc_attr( sanitize_title( $row['anchor'] ) ) . '"' : '',
			esc_attr( txl_block_spacing( $type, 0 === $i, $last === $i ) )
		);

		get_template_part(
			'template-parts/blocks/' . $type['template'],
			null,
			$row + array(
				'h1'  => $h1 === $i,
				'uid' => 1 === $instances[ $layout ] ? '' : '-' . $instances[ $layout ],
			)
		);

		echo '</div>';
	}

	echo '</div>';
}

/**
 * Vertical spacing around a block. Reproduces the pages from the design:
 * the page starts with pt-7 lg:pt-20, blocks are 60/80px apart, the page ends with
 * pb-30 lg:pb-20. Flush blocks bring their own spacing.
 */
function txl_block_spacing( array $type, bool $first, bool $last ): string {
	if ( ! empty( $type['flush'] ) ) {
		return '';
	}

	if ( $first ) {
		$classes = array( 'pt-7 lg:pt-20' );
	} elseif ( ! empty( $type['tight'] ) ) {
		$classes = array( 'mt-10 lg:mt-20' );
	} else {
		$classes = array( 'mt-15 lg:mt-20' );
	}

	if ( $last ) {
		$classes[] = 'pb-30 lg:pb-20';
	}

	return implode( ' ', $classes );
}

/**
 * ID of the first published page that has a block of the given type.
 * SCF keeps the list of a page's block types in the "blocks" meta field as a serialized
 * array, hence LIKE on the quoted layout name.
 */
function txl_page_with_block( string $layout ): int {
	$ids = get_posts(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => 'blocks',
					'value'   => '"' . $layout . '"',
					'compare' => 'LIKE',
				),
			),
		)
	);

	return (int) ( $ids[0] ?? 0 );
}
