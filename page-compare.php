<?php
/**
 * Template Name: Compare products
 *
 * Side-by-side product comparison. Product IDs come from localStorage
 * (`sft_compare`); the table is rendered by js/product-compare.js.
 *
 * @package safestore-minimal
 */

get_header();

$page_title = get_the_title( get_queried_object_id() );
if ( $page_title === '' ) {
	$page_title = __( 'Compare products', 'safestore-minimal' );
}
?>
<main class="sft-compare-page" id="main-content">
	<div class="sft-compare-page__inner">
		<header class="sft-compare-page__header">
			<h1 class="sft-compare-page__title"><?php echo esc_html( $page_title ); ?></h1>
			<p class="sft-compare-page__lede">
				<?php esc_html_e( 'Review price, availability, and key specs side by side — up to 4 products.', 'safestore-minimal' ); ?>
			</p>
		</header>

		<div
			class="sft-compare-page__body"
			data-sft-compare-page
			aria-live="polite"
		>
			<p class="sft-compare-page__loading"><?php esc_html_e( 'Loading comparison…', 'safestore-minimal' ); ?></p>
		</div>
	</div>
</main>
<?php
get_footer();
