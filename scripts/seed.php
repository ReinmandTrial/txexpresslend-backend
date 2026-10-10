<?php
/**
 * Fills the site with content from the design: pages, images, fields, menus.
 *
 * Run once by setup.sh. To run it by hand (overwrites texts edited in the admin):
 *   docker compose run --rm cli wp eval-file /scripts/seed.php --user=admin
 *
 * Site Settings are filled by keys like field_settings_<name>, pages get their page builder
 * blocks (field_builder_blocks) — both are defined in the theme's acf-json.
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

if ( ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'The Secure Custom Fields plugin is not active.' );
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Page by slug: creates or updates it and fills its page builder.
 *   blocks  — builder rows: [ [ 'acf_fc_layout' => 'faq', 'title' => '…', … ], … ]
 *   content — shortcut for a text page: a Hero block followed by a Text block with this HTML
 */
function txl_seed_page( string $slug, string $title, array $args = array() ): int {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );

	$data = array(
		'post_type'     => 'page',
		'post_status'   => 'publish',
		'post_name'     => $slug,
		'post_title'    => $title,
		'post_content'  => '',
		// Pages used to have their own templates; now every page is built from blocks.
		'page_template' => 'default',
	);

	if ( $existing ) {
		$data['ID'] = $existing->ID;
		$id         = wp_update_post( $data, true );
	} else {
		$id = wp_insert_post( $data, true );
	}

	if ( is_wp_error( $id ) ) {
		WP_CLI::error( "Page {$slug}: " . $id->get_error_message() );
	}

	if ( isset( $args['content'] ) ) {
		$args['blocks'] = array(
			array( 'acf_fc_layout' => 'hero' ),
			array(
				'acf_fc_layout' => 'text',
				'content'       => $args['content'],
			),
		);
	}

	if ( isset( $args['blocks'] ) ) {
		txl_seed_blocks( $id, $args['blocks'] );
	}

	return $id;
}

/**
 * Image from scripts/seed-images into the Media Library. Doesn't upload it twice.
 */
function txl_seed_image( string $relative, string $alt = '' ): int {
	$existing = get_posts(
		array(
			'post_type'   => 'attachment',
			'post_status' => 'any',
			'meta_key'    => '_txl_seed_source',
			'meta_value'  => $relative,
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);

	if ( $existing ) {
		return (int) $existing[0];
	}

	$source = __DIR__ . '/seed-images/' . $relative;
	$tmp    = wp_tempnam( basename( $source ) );
	copy( $source, $tmp );

	$id = media_handle_sideload(
		array(
			'name'     => sanitize_file_name( basename( $source ) ),
			'tmp_name' => $tmp,
		),
		0
	);

	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( "Image {$relative}: " . $id->get_error_message() );
		return 0;
	}

	update_post_meta( $id, '_txl_seed_source', $relative );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );

	return $id;
}

/**
 * Field values of a group: txl_seed_fields( 'settings', [ 'hero_title' => '…' ], 'option' ).
 */
function txl_seed_fields( string $group, array $values, $post_id ): void {
	foreach ( $values as $name => $value ) {
		update_field( "field_{$group}_{$name}", $value, $post_id );
	}
}

/**
 * Page builder blocks of a page. Fields a row doesn't mention stay empty.
 */
function txl_seed_blocks( int $post_id, array $rows ): void {
	update_field( 'field_builder_blocks', $rows, $post_id );
}

/**
 * Menu: recreates it and assigns it to a theme location.
 * An item is [ 'Title', page ID ] or [ 'Title', 'https://…' ].
 */
function txl_seed_menu( string $name, string $location, array $items ): void {
	$existing = wp_get_nav_menu_object( $name );

	if ( $existing ) {
		wp_delete_nav_menu( $existing->term_id );
	}

	$menu_id = wp_create_nav_menu( $name );

	foreach ( $items as $position => [ $title, $target ] ) {
		$item = array(
			'menu-item-title'    => $title,
			'menu-item-status'   => 'publish',
			'menu-item-position' => $position + 1,
		);

		if ( is_int( $target ) ) {
			$item += array(
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $target,
			);
		} else {
			$item += array(
				'menu-item-type' => 'custom',
				'menu-item-url'  => $target,
			);
		}

		wp_update_nav_menu_item( $menu_id, 0, $item );
	}

	$locations              = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * HTML for a Text block: paragraphs, h2 headings and lists.
 */
function txl_seed_html( array $blocks ): string {
	$html = array();

	foreach ( $blocks as [ $type, $content ] ) {
		switch ( $type ) {
			case 'h2':
				$html[] = "<h2>{$content}</h2>";
				break;

			case 'ul':
				$html[] = "<ul>\n" . implode( "\n", array_map( static fn( $li ) => "\t<li>{$li}</li>", $content ) ) . "\n</ul>";
				break;

			default:
				$html[] = "<p>{$content}</p>";
		}
	}

	return implode( "\n\n", $html );
}

$placeholder = txl_seed_html( array( array( 'p', '[Placeholder] The text for this page will be added later.' ) ) );

// ---------------------------------------------------------------------------
// Remove what WP creates on install
// ---------------------------------------------------------------------------

foreach ( array( 'sample-page' ) as $slug ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		wp_delete_post( $page->ID, true );
	}
}

foreach ( get_posts( array( 'post_type' => 'post', 'name' => 'hello-world', 'post_status' => 'any' ) ) as $post ) {
	wp_delete_post( $post->ID, true );
}

// ---------------------------------------------------------------------------
// Pages
// ---------------------------------------------------------------------------

WP_CLI::log( 'Pages…' );

$home      = txl_seed_page( 'home', 'Home' );
$loan_form = txl_seed_page( 'form', 'Get Started' );
$apply     = txl_seed_page( 'apply', 'Get Started' );
$contacts  = txl_seed_page( 'contacts', 'Contact Us' );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );

// The article from text-page.html.
$article = txl_seed_page(
	'texas-payday-loans-no-credit-check',
	'Texas Payday Loans No Credit Checks: Main Things to Consider When Taking a Loan',
	array(
		'content' => txl_seed_html(
			array(
				array( 'p', 'Whether you are dealing with a large utility bill, undergoing a medical emergency, or other unforeseen issues, unexpected financial expenses can offset anyone’s budget. Payday loans are cash advances that help borrowers deal with emergencies without scraping money for groceries. In this brief guide to instant loans, we’ll discuss their main pros and cons, key aspects to consider, and how our Texas online lender can be of use.' ),
				array( 'h2', 'Payday Loan: Definition, Pros, and Cons' ),
				array( 'p', 'Cash advance loans are short-term, high-interest loans aimed at tackling smaller financial challenges like medical expenses, unexpected house and car repairs, etc. Although the overall sum depends on the lender’s policies and the borrower’s income, the loans typically range from $100 to $1,000 and become available in lump sum after an online application and quick approval.' ),
				array( 'p', 'In addition to quick approval rates, the other advantages of this loan type are as follows:' ),
				array(
					'ul',
					array(
						'The loans are unsecured, so borrowers don’t have to risk their assets as collateral.',
						'It has fewer requirements than other loans, which allows borrowers with poor credit histories to apply.',
						'Convenient and easy to access. After applying online, you typically get a confirmation within 24 hours.',
					),
				),
				array( 'p', 'Despite the innumerable advantages, instant loans also have several drawbacks, including:' ),
				array(
					'ul',
					array(
						'Instant loans are expensive.',
						'High interest rates can reinforce the debt cycle and leave the borrowers worse off.',
					),
				),
				array( 'p', 'With these pros and cons in mind, let’s take a closer look at other key aspects you should consider.' ),
				array( 'h2', 'Browse for Reliable Lender' ),
				array( 'p', 'Before settling on one lender, comb through the competitors to ensure you’re getting the best loan for your circumstances. Here’s what to do:' ),
				array(
					'ul',
					array(
						'Use comparison aggregator-like platforms that feature a pool of online direct lenders.',
						'Investigate the preferred lender and ensure it provides up-to-date information, detailed terms and conditions, exhaustive contact information, and responsive customer support.',
						'Make sure the online lender has positive customer reviews on other platforms.',
						'Pay special attention to the fine print, including such key provisions as fees, overall loan cost, interest rate, and due date.',
					),
				),
				array( 'p', 'If you’re browsing for a loan in Texas, we provide balanced payday loans with quick confirmation and no credit checks.' ),
				array( 'h2', 'Impact on Your Credit Score' ),
				array( 'p', 'As with any other loan, missed payments and defaults can impact your credit history. If you want to protect your creditworthiness, consider browsing for a lower-interest loan and making timely payments.' ),
				array( 'h2', 'Consider Alternatives' ),
				array( 'p', 'Before signing the loan contract, consider other options, like borrowing from family or friends or choosing a personal loan with a lower interest rate. Another alternative is to tap into your savings account or ask if your workplace has paycheck advances.' ),
				array( 'p', 'To wrap it up, if you’re browsing for a payday loan in Texas, we offer easy-to-access loans with fast confirmation and no credit checks. Whichever loan you end up choosing, there are several tips and tricks — like running background checks, ensuring the lender has positive ratings, and making timely payments — that will increase your chances of finding a reputable online lender, safeguarding your credit score, and avoiding debt traps.' ),
			)
		),
	)
);

// Footer pages. The design has no texts for them — placeholders, so the links lead somewhere.
$company_pages = array();
foreach (
	array(
		'about-us'                           => 'About Us',
		'fees-and-rates'                     => 'Fees and Rates',
		'marketing-practices'                => 'Marketing Practices',
		'responsible-lending-practices'      => 'Responsible Lending Practices',
		'terms-of-use'                       => 'Terms of Use',
		'do-not-sell-my-personal-information' => 'Do Not Sell My Personal Information',
		'privacy-policy'                     => 'Privacy Policy',
	) as $slug => $title
) {
	$company_pages[ $slug ] = txl_seed_page( $slug, $title, array( 'content' => $placeholder ) );
}
update_option( 'wp_page_for_privacy_policy', $company_pages['privacy-policy'] );

$quick_pages = array();
foreach (
	array(
		'online-texas-payday-loans'            => 'Online Texas Payday Loans',
		'fast-texas-payday-loans'              => 'Fast Texas Payday Loans',
		'immediate-texas-payday-loans'         => 'Immediate Texas Payday Loans',
		'instant-texas-payday-loans'           => 'Instant Texas Payday Loans',
		'other-loans'                          => 'Other Loans',
		'same-day-texas-payday-loans'          => 'Same-Day Texas Payday Loans',
		'texas-payday-loans-with-bad-credit'   => 'Texas Payday Loans with Bad Credit',
	) as $slug => $title
) {
	$quick_pages[ $slug ] = txl_seed_page( $slug, $title, array( 'content' => $placeholder ) );
}

// ---------------------------------------------------------------------------
// Images
// ---------------------------------------------------------------------------

WP_CLI::log( 'Images…' );

$img = array(
	'fintech'      => txl_seed_image( 'home/fintech-illustration.png' ),
	'credit'       => txl_seed_image( 'home/Credit-score-dollar.png', 'Credit Score Dollar' ),
	'upi'          => txl_seed_image( 'home/UPI-Dollar.png', 'UPI Dollar' ),
	'deposit'      => txl_seed_image( 'home/Deposit-Dollar.png', 'Deposit Dollar' ),
	'tracker'      => txl_seed_image( 'home/Tracker-Dollar.png', 'Tracker Dollar' ),
	'phone'        => txl_seed_image( 'home/Phone-W-Photoroom1.png', 'Step 1' ),
	'doc'          => txl_seed_image( 'home/Doc2-Photoroom1.png', 'Step 2' ),
	'wallet'       => txl_seed_image( 'home/WalletW-Photoroom 1.png', 'Step 3' ),
	'woman'        => txl_seed_image( 'home/woman.png' ),
	'woman_d'      => txl_seed_image( 'home/woman-d.png' ),
	'id'           => txl_seed_image( 'home/ID_White-removebg-preview.png' ),
	'collage'      => txl_seed_image( 'home/CollageBox.png' ),
	'collage2'     => txl_seed_image( 'home/CollageBox2.png' ),
	'collage3'     => txl_seed_image( 'home/CollageBox3.png' ),
	'money_bag'    => txl_seed_image( 'form/Money bag-Dollar.svg' ),
	'secure'       => txl_seed_image( 'form/secure.svg' ),
	'bond'         => txl_seed_image( 'form/Bond-Dollar.svg' ),
	'expense'      => txl_seed_image( 'form/Expense Tracker-Dollar.svg' ),
);

// ---------------------------------------------------------------------------
// Site Settings (shared by all pages)
// ---------------------------------------------------------------------------

WP_CLI::log( 'Site Settings…' );

txl_seed_fields(
	'settings',
	array(
		'header_cta_text'         => 'Get started',
		'loan_form_page'          => $loan_form,

		'hero_title'              => 'Payday Loans in Texas:',
		'hero_title_accent'       => 'Fast, Transparent & Secure',
		'hero_title_after'        => 'Financial Solutions',
		'hero_text'               => 'As a trusted loan matching service, we help Texas residents find licensed Credit Access Businesses and their third-party lending partners across the state. Review available options through a secure online process with high approval rates and a soft inquiry that does not lower your FICO® score.',
		'hero_stats'              => array(
			array( 'label' => 'Loan range', 'value' => '$100 – $1,500' ),
			array( 'label' => 'Response time', 'value' => 'Under 5 mins' ),
			array( 'label' => 'Active network', 'value' => '24/7 CAB Network' ),
		),
		'hero_form_title'         => 'Quick Loan Form',
		'hero_amount_label'       => 'Desired loan amount',
		'hero_email_label'        => 'Your Email Address',
		'hero_email_placeholder'  => 'Enter your email',
		'hero_button_text'        => 'Get started',
		'hero_badges'             => array(
			array( 'icon' => 'lock', 'text' => 'Secure SSL Encryption' ),
			array( 'icon' => 'check-circle', 'text' => 'Soft Credit Inquiry' ),
			array( 'icon' => 'check-circle', 'text' => 'No Obligation Estimate' ),
			array( 'icon' => 'shield-outline', 'text' => 'Trusted CAB Network' ),
		),
		'hero_image'              => $img['fintech'],

		'amount_min'              => 100,
		'amount_max'              => 1500,
		'amount_step'             => 100,
		'amount_default'          => 300,

		'benefits'                => array(
			array(
				'image' => $img['money_bag'],
				'title' => 'Soft Credit Check',
				'text'  => 'Check your loan options without impacting your credit score with a hard inquiry.',
			),
			array(
				'image' => $img['secure'],
				'title' => 'Secure SSL Encryption',
				'text'  => 'Your personal information is protected with secure SSL encryption throughout the application process.',
			),
			array(
				'image' => $img['bond'],
				'title' => "Trusted Network\nof Lenders",
				'text'  => 'We connect you with a trusted network of lenders to help you find loan options that fit your needs.',
			),
			array(
				'image' => $img['expense'],
				'title' => 'No-Obligation Estimate',
				'text'  => "Explore your available loan options with no obligation\nto accept an offer.",
			),
		),

		'footer_title'            => 'TX Express Lend',
		'footer_subtitle'         => 'LOAN MATCHING PLATFORM',
		'footer_socials'          => array(
			array( 'label' => 'f', 'url' => '' ),
			array( 'label' => 'X', 'url' => '' ),
			array( 'label' => 'in', 'url' => '' ),
		),
		'footer_col1_title'       => 'Company',
		'footer_col2_title'       => 'Quick Links',
		'footer_disclaimer_title' => 'DISCLAIMER',
		'footer_disclaimer'       => implode(
			"\n",
			array(
				'TX Express Lend is not an offer or solicitation to lend. We do not make any loan or credit decisions and are not representatives, brokers or agents of any lender. Participating lenders offer loans from $200 up to $5,000. Not all lenders can offer you amounts up to $5,000. Our service is not available in all states. Submission of a request through this website does not guarantee that you will receive a loan offer or an offer you\'ll be satisfied with. Funds transfer time may vary depending on your lender and/or financial institution. If you have any questions about your loan, please contact your lender directly. Credit checks may be performed with the three reporting credit bureaus: Experian, Equifax, and TransUnion. Submission of a request means you are authorizing the lenders to check your creditworthiness and your personal details.',
				'This service is not a solicitation for loan products and does not constitute a loan offer for any loan products that are prohibited by state law. This service is void where prohibited.',
				'State Availability: Not all lenders from our system operate in all US states. Residents of some US states may not be eligible for loan products in accordance with their legislation. By selecting your State at the start of our loan offer process, you shall be informed of any limitations regarding obtaining a loan if you reside in individual US states.',
				'This website collects personal information and transfers it to its third-party partners.',
				'The website contains links to third-party websites. Accessing them may result in a commission.',
				'Please note that personal loans should not be treated as financial cure-it-all. If you have major budget difficulties, consult specialists first.',
			)
		),
		'footer_copyright'        => '© {year} TX Express Lend. All rights reserved.',

		'apply_page'              => $apply,
		'notify_email'            => '',
		'form_success_loan'       => 'Thank you! Your request has been received. We will contact you by email shortly.',
		'form_success_contact'    => 'Thank you for your message! We will get back to you soon.',
		'form_error'              => 'Something went wrong. Please check your details and try again.',
	),
	'option'
);

// ---------------------------------------------------------------------------
// Home page (Hero + the sections of index.html; anchors are used by the header menu)
// ---------------------------------------------------------------------------

WP_CLI::log( 'Home page…' );

// Value of a link field pointing to a page.
$page_link = static fn( int $page ) => array(
	'url'    => get_permalink( $page ),
	'title'  => '',
	'target' => '',
);

txl_seed_blocks(
	$home,
	array(
		array( 'acf_fc_layout' => 'hero' ),
		array(
			'acf_fc_layout' => 'loan_options',
			'anchor'        => 'loan-options',
			'overline'      => 'Find the Right Loan for You',
			'title'         => 'Explore flexible short-term loan options.',
			'cards'         => array(
				array(
					'image' => $img['credit'],
					'title' => 'Instant',
					'text'  => 'Get a fast response and move forward without unnecessary delays.',
					'link'  => $page_link( $quick_pages['instant-texas-payday-loans'] ),
				),
				array(
					'image' => $img['upi'],
					'title' => 'Online',
					'text'  => 'Apply from anywhere, anytime, with a simple online application.',
					'link'  => $page_link( $quick_pages['online-texas-payday-loans'] ),
				),
				array(
					'image' => $img['deposit'],
					'title' => 'Bad Credit',
					'text'  => 'Explore loan options even if your credit history isn’t perfect.',
					'link'  => $page_link( $quick_pages['texas-payday-loans-with-bad-credit'] ),
				),
				array(
					'image' => $img['tracker'],
					'title' => 'No Credit Check',
					'text'  => 'Find options that may not rely on a traditional credit check.',
					'link'  => $page_link( $article ),
				),
			),
		),
		array(
			'acf_fc_layout' => 'steps',
			'anchor'        => 'how-it-works',
			'overline'      => 'Easy Steps',
			'title'         => '3 Simple Steps to Secure Your Short-Term Loan',
			'items'         => array(
				array(
					'image' => $img['phone'],
					'title' => 'Apply Online in Minutes',
					'text'  => 'Complete our 5-minute digital application securely from any Texas location. A 256-bit SSL-encrypted connection protects the personal and financial information submitted during the matching process.',
				),
				array(
					'image' => $img['doc'],
					'title' => 'Get a Quick Lending Decision',
					'text'  => 'The automated network reviews the request using alternative credit information. This process uses a soft inquiry rather than a hard credit pull, allowing applicants to check available options without lowering their FICO® scores.',
				),
				array(
					'image' => $img['wallet'],
					'title' => 'Receive Funds Securely',
					'text'  => 'Review the offer, required disclosures, and repayment terms before signing electronically. After verification and final lender approval, funds may reach the applicant’s checking account as soon as the next business day. Standard ACH transfers are not settled on weekends or federal banking holidays.',
				),
			),
		),
		array(
			'acf_fc_layout'    => 'how_it_works',
			'anchor'           => 'cab-transparency',
			'overline'         => 'How It Works',
			'title'            => 'How Texas Payday Loans Work: Rates, Fees & Terms',
			'subtitle'         => 'Short-term financing in Texas commonly follows a two-part cost structure.',
			'image_mobile'     => $img['woman'],
			'image_desktop'    => $img['woman_d'],
			'notes'            => array(
				array( 'text' => 'Our platform is a <strong>loan matching service</strong>, not a direct lender. We connect applicants with licensed Credit Access Businesses and their third-party lending partners.' ),
				array( 'text' => '<strong>A CAB is a licensed broker</strong> regulated by the Texas Office of Consumer Credit Commissioner. It helps the consumer obtain financing from an independent third-party lender.' ),
			),
			'components_title' => 'The total repayment generally includes two separate components',
			'components'       => array(
				array(
					'icon' => 'percent',
					'text' => '<strong>Lender Interest:</strong> The third-party lender charges base interest, generally up to 10% annually under the applicable Texas lending structure.',
				),
				array(
					'icon' => 'dollar-sign',
					'text' => '<strong>CAB Service Fees:</strong> The CAB charges a separate service fee, often calculated for each $100 borrowed. This fee represents the main cost of many short-term transactions.',
				),
			),
		),
		array(
			'acf_fc_layout' => 'rates',
			'anchor'        => 'rates-terms',
			'title'         => 'The examples below illustrate how these costs may combine:',
			'col_amount'    => "Loan Amount",
			'col_fee'       => "CAB Fee\n(per $100)",
			'col_apr'       => "Lender Interest\n(APR)",
			'col_term'      => "Total Loan\nTerm",
			'col_total'     => "Total\nRepayment",
			'rows'          => array(
				array( 'amount' => '$300', 'fee' => '$20 - $30', 'apr' => '~10%', 'term' => '14 days', 'total' => '$365 - $395' ),
				array( 'amount' => '$300', 'fee' => '$20 - $30', 'apr' => '~10%', 'term' => '14 days', 'total' => '$365 - $395' ),
				array( 'amount' => '$1,000', 'fee' => '$20 - $30', 'apr' => '~10%', 'term' => '30 days', 'total' => '$1,210 - $1,310' ),
			),
			'note'          => 'Review the lender interest, CAB fees, APR, due date, and total repayment shown in the required disclosures before accepting an offer.',
		),
		array(
			'acf_fc_layout' => 'eligibility',
			'anchor'        => 'eligibility',
			'overline'      => 'Eligibility',
			'title'         => 'Eligibility Requirements for Texas Borrowers',
			'subtitle'      => 'Participating lenders consider different credit histories and focus on the applicant\'s current ability to repay. Final approval and terms remain subject to each lender\'s criteria.',
			'card_title'    => 'What You Need to Qualify',
			'items'         => array(
				array( 'text' => 'Texas residency and a verifiable physical address;' ),
				array( 'text' => 'A minimum age of 18;' ),
				array( 'text' => 'Stable, predictable income from employment or regular benefit payments;' ),
				array( 'text' => 'An active checking account in the applicant\'s name for ACH funding;' ),
				array( 'text' => 'A working phone number and active email address.' ),
			),
			'note'          => 'Accurate information supports identity verification and prevents avoidable processing delays.',
			'image'         => $img['id'],
		),
		array(
			'acf_fc_layout' => 'regulations',
			'anchor'        => 'texas-compliance',
			'overline'      => 'Texas Regulations',
			'title'         => 'Municipal Payday Loan Regulations Across Texas Cities',
			'subtitle'      => 'The Texas OCCC licenses and regulates CABs statewide, while 49 Texas cities have adopted local payday and auto title lending ordinances. These requirements apply to businesses operating within their boundaries.',
			'main_image'    => $img['collage'],
			'main_text'     => 'A residential ZIP code is a useful starting point for checking whether a city has additional payday lending protections. Applicable requirements may also depend on where the CAB operates.',
			'cards'         => array(
				array(
					'image' => $img['collage2'],
					'title' => 'Dallas & Houston Lending Limits',
					'text'  => 'Dallas and Houston limit a single-payment payday loan to 20% of the consumer’s documented gross monthly income. This restriction ties the available amount to current income.',
				),
				array(
					'image' => $img['collage3'],
					'title' => 'Austin & San Antonio Rollover Rules',
					'text'  => 'Austin and San Antonio restrict single-payment loans to three renewals, with at least a 25% reduction required each time. These rules limit repeated extensions of the full balance.',
				),
			),
		),
		array(
			'acf_fc_layout' => 'responsible',
			'anchor'        => 'responsible-borrowing',
			'overline'      => 'Borrow Responsibly',
			'title'         => 'Responsible Borrowing and Financial Alternatives in Texas',
			'subtitle'      => 'Payday loans are high-cost financial instruments. They should be used for short-term emergencies only, not as long-term financial solutions.',
			'note'          => 'Review the full repayment amount and due date before accepting an offer. Texans can contact <strong>2-1-1 Texas</strong> for food, housing, utility, and emergency financial assistance. <strong>TDHCA Help for Texans</strong> can locate a local <strong>Comprehensive Energy Assistance Program</strong> provider. <strong>Federal credit unions</strong> may also offer <strong>Payday Alternative Loans</strong> with lower rates and longer repayment terms.',
		),
		array(
			'acf_fc_layout' => 'faq',
			'anchor'        => 'faq',
			'overline'      => 'Common Questions',
			'title'         => 'Frequently Asked Questions',
			'items'         => array(
				array(
					'question' => 'Are payday loans legal in Texas?',
					'answer'   => 'Yes. Payday loans are legal in Texas when provided through the applicable lending and Credit Access Business framework. Licensed CABs must follow state disclosure, licensing, and consumer protection requirements.',
				),
				array(
					'question' => 'How much money can I borrow in TX?',
					'answer'   => 'The amount depends on verified income, lender criteria, and applicable municipal rules. Our matching network generally offers options from $100 to $1,500, subject to final approval and required disclosures.',
				),
				array(
					'question' => 'Do you offer payday loans with no credit check in Texas?',
					'answer'   => 'The service does not promise financing without verification. The matching process uses a soft inquiry and alternative credit information rather than a hard credit pull, so checking available options does not lower the applicant’s FICO® score.',
				),
				array(
					'question' => 'Do you offer payday loans with no credit check in Texas?',
					'answer'   => 'The service does not promise financing without verification. The matching process uses a soft inquiry and alternative credit information rather than a hard credit pull, so checking available options does not lower the applicant’s FICO® score.',
				),
			),
		),
	)
);

// ---------------------------------------------------------------------------
// Contacts, loan form, Apply
// ---------------------------------------------------------------------------

WP_CLI::log( 'Contacts and loan form…' );

txl_seed_blocks(
	$contacts,
	array(
		array( 'acf_fc_layout' => 'hero' ),
		array(
			'acf_fc_layout'       => 'contacts',
			'title'               => 'Contact Us',
			'hours'               => array(
				array( 'days' => 'Monday - Friday:', 'time' => '8:00 AM - 8:00 PM' ),
				array( 'days' => 'Saturday:', 'time' => '8:00 AM - 4:00 PM' ),
				array( 'days' => 'Sunday:', 'time' => 'Closed' ),
			),
			'email_label'         => 'Support:',
			'email'               => 'contact@txexpresslend.com',
			'address'             => '1234 Elm Street, Austin, TX',
			'form_title'          => 'Feedback form',
			'name_placeholder'    => 'First name',
			'email_placeholder'   => 'Enter your email',
			'message_placeholder' => 'Your message',
			'button_text'         => 'Send',
		),
	)
);

$link = static fn( int $page, string $text ) => sprintf( '<a href="%s">%s</a>', get_permalink( $page ), $text );

txl_seed_blocks(
	$loan_form,
	array(
		array(
			'acf_fc_layout'          => 'loan_form',
			'first_name_placeholder' => 'First name',
			'last_name_placeholder'  => 'Last name',
			'email_placeholder'      => 'Enter your email',
			'amount_placeholder'     => 'Select loan amount',
			'button_text'            => 'Get started',
			'consent'                => sprintf(
				'By clicking “Get started”, you agree to %s, %s, %s and %s',
				$link( $company_pages['privacy-policy'], 'Privacy Policy' ),
				$link( $company_pages['terms-of-use'], 'Terms of Use' ),
				$link( $company_pages['responsible-lending-practices'], 'Responsible Lending Practices' ),
				$link( $company_pages['marketing-practices'], 'Marketing Practices' )
			),
		),
	)
);

// The partner form code is pasted in the admin (Pages → Get Started /apply → Partner form block).
txl_seed_blocks(
	$apply,
	array(
		array(
			'acf_fc_layout' => 'partner_form',
			'widget'        => '',
			'footer_code'   => '',
		),
	)
);

// ---------------------------------------------------------------------------
// Menus
// ---------------------------------------------------------------------------

WP_CLI::log( 'Menus…' );

txl_seed_menu(
	'Header',
	'primary',
	array(
		array( 'How It Works', home_url( '/#how-it-works' ) ),
		array( 'CAB Transparency', home_url( '/#cab-transparency' ) ),
		array( 'Rates & Terms', home_url( '/#rates-terms' ) ),
		array( 'Texas Compliance', home_url( '/#texas-compliance' ) ),
		array( 'FAQ', home_url( '/#faq' ) ),
	)
);

txl_seed_menu(
	'Footer: Company',
	'footer_company',
	array(
		array( 'About Us', $company_pages['about-us'] ),
		array( 'Contact Us', $contacts ),
		array( 'Fees and Rates', $company_pages['fees-and-rates'] ),
		array( 'Marketing Practices', $company_pages['marketing-practices'] ),
		array( 'Responsible Lending Practices', $company_pages['responsible-lending-practices'] ),
		array( 'Terms of Use', $company_pages['terms-of-use'] ),
		array( 'Do Not Sell My Personal Information', $company_pages['do-not-sell-my-personal-information'] ),
	)
);

txl_seed_menu(
	'Footer: Quick Links',
	'footer_links',
	array(
		array( 'Online Texas Payday Loans', $quick_pages['online-texas-payday-loans'] ),
		array( 'Fast Texas Payday Loans', $quick_pages['fast-texas-payday-loans'] ),
		array( 'Immediate Texas Payday Loans', $quick_pages['immediate-texas-payday-loans'] ),
		array( 'Instant Texas Payday Loans', $quick_pages['instant-texas-payday-loans'] ),
		array( 'Other Loans', $quick_pages['other-loans'] ),
		array( 'Same-Day Texas Payday Loans', $quick_pages['same-day-texas-payday-loans'] ),
		array( 'Texas Payday Loans No Credit Check', $article ),
		array( 'Texas Payday Loans with Bad Credit', $quick_pages['texas-payday-loans-with-bad-credit'] ),
	)
);

update_option( 'txl_seeded', '1' );

WP_CLI::success( 'Content imported.' );
