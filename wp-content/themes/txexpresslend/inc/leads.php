<?php
/**
 * Leads section in the admin: everything submitted through the site's forms.
 *
 * A lead is a post of its own type (custom post type) txl_lead. The title is
 * generated automatically, the form data is stored in the _txl_data meta field.
 * Leads can't be created by hand, only viewed and deleted.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		register_post_type(
			'txl_lead',
			array(
				'labels'          => array(
					'name'               => 'Leads',
					'singular_name'      => 'Lead',
					'menu_name'          => 'Leads',
					'all_items'          => 'All Leads',
					'edit_item'          => 'Lead',
					'search_items'       => 'Search Leads',
					'not_found'          => 'No leads yet',
					'not_found_in_trash' => 'No leads in Trash',
				),
				'public'          => false,
				'show_ui'         => true,
				'menu_icon'       => 'dashicons-email-alt',
				'menu_position'   => 4,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}
);

/**
 * Lead title: "Loan form: John Doe, john@example.com, $500".
 */
function txl_lead_title( string $form_id, array $data ): string {
	$form = txl_forms()[ $form_id ]['label'] ?? $form_id;
	$name = trim( ( $data['first-name'] ?? '' ) . ' ' . ( $data['last-name'] ?? '' ) );

	$parts = array_filter(
		array(
			$name,
			$data['email'] ?? '',
			empty( $data['amount'] ) ? '' : txl_money( (int) $data['amount'] ),
		)
	);

	return $form . ': ' . implode( ', ', $parts );
}

/**
 * Saves a lead. Returns its ID, or 0 on failure.
 */
function txl_save_lead( string $form_id, array $data, string $page_url ): int {
	$id = wp_insert_post(
		array(
			'post_type'   => 'txl_lead',
			'post_status' => 'publish',
			'post_title'  => txl_lead_title( $form_id, $data ),
		)
	);

	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}

	update_post_meta( $id, '_txl_form', $form_id );
	update_post_meta( $id, '_txl_data', $data );
	update_post_meta( $id, '_txl_page', $page_url );

	return $id;
}

// ---- Leads list: columns and a filter by form ----

add_filter(
	'manage_txl_lead_posts_columns',
	static fn( $columns ) => array(
		'cb'       => $columns['cb'],
		'title'    => 'Lead',
		'txl_form' => 'Form',
		'date'     => 'Date',
	)
);

add_action(
	'manage_txl_lead_posts_custom_column',
	static function ( $column, $post_id ) {
		if ( 'txl_form' === $column ) {
			$form_id = (string) get_post_meta( $post_id, '_txl_form', true );
			echo esc_html( txl_forms()[ $form_id ]['label'] ?? $form_id );
		}
	},
	10,
	2
);

add_action(
	'restrict_manage_posts',
	static function ( $post_type ) {
		if ( 'txl_lead' !== $post_type ) {
			return;
		}

		$current = sanitize_key( wp_unslash( $_GET['txl_form'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification

		echo '<select name="txl_form"><option value="">All forms</option>';
		foreach ( txl_forms() as $form_id => $form ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $form_id ), selected( $current, $form_id, false ), esc_html( $form['label'] ) );
		}
		echo '</select>';
	}
);

add_action(
	'pre_get_posts',
	static function ( WP_Query $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'txl_lead' !== $query->get( 'post_type' ) ) {
			return;
		}

		$form_id = sanitize_key( wp_unslash( $_GET['txl_form'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( $form_id ) {
			$query->set( 'meta_key', '_txl_form' ); // phpcs:ignore WordPress.DB.SlowDBQuery
			$query->set( 'meta_value', $form_id ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}
	}
);

// ---- Lead screen: a table with the form data ----

add_action(
	'add_meta_boxes_txl_lead',
	static function () {
		add_meta_box( 'txl_lead_data', 'Lead Details', 'txl_render_lead_box', 'txl_lead', 'normal', 'high' );
	}
);

function txl_render_lead_box( WP_Post $post ): void {
	$form_id = (string) get_post_meta( $post->ID, '_txl_form', true );
	$data    = (array) get_post_meta( $post->ID, '_txl_data', true );
	$page    = (string) get_post_meta( $post->ID, '_txl_page', true );
	$fields  = txl_forms()[ $form_id ]['fields'] ?? array();

	echo '<table class="widefat striped"><tbody>';

	foreach ( $data as $key => $value ) {
		$field = $fields[ $key ] ?? array( 'label' => $key );

		if ( 'amount' === ( $field['type'] ?? '' ) ) {
			$value = txl_money( (int) $value );
		}

		printf(
			'<tr><th style="width:12rem">%s</th><td>%s</td></tr>',
			esc_html( $field['label'] ),
			nl2br( esc_html( (string) $value ) )
		);
	}

	if ( $page ) {
		printf( '<tr><th>Page</th><td><a href="%1$s" target="_blank">%1$s</a></td></tr>', esc_url( $page ) );
	}

	echo '</tbody></table>';
}
