<?php
/**
 * Template Name: Wishlist
 *
 * Products saved with "Add to wishlist". The list lives in the browser
 * (localStorage) and, for logged-in shoppers, in their account; js/wishlist.js
 * renders it into [data-sft-wishlist-page]. See inc/wishlist.php.
 *
 * @package safestore-minimal
 */

get_header();

$sft_wl_title = get_the_title( get_queried_object_id() );
if ( '' === $sft_wl_title ) {
	$sft_wl_title = __( 'Wishlist', 'safestore-minimal' );
}
?>
<main class="sft-wishlist-page" id="main-content">
	<div class="sft-wishlist-page__inner">
		<header class="sft-wishlist-page__header">
			<h1 class="sft-wishlist-page__title"><?php echo esc_html( $sft_wl_title ); ?></h1>
			<p class="sft-wishlist-page__lede" data-sft-wishlist-count>
				<?php esc_html_e( 'Products you saved for later.', 'safestore-minimal' ); ?>
			</p>
		</header>

		<div class="sft-wishlist-page__body" data-sft-wishlist-page aria-live="polite">
			<p class="sft-wishlist-page__loading"><?php esc_html_e( 'Loading your wishlist…', 'safestore-minimal' ); ?></p>
			<noscript><p><?php esc_html_e( 'Please enable JavaScript to see your wishlist.', 'safestore-minimal' ); ?></p></noscript>
		</div>
	</div>
</main>
<?php
get_footer();
