<?php
/**
 * Template Name: Custom Home
 */
get_header();
?>

<main class="home">
	<?php
	get_template_part('templates/home/hero-slider');
	get_template_part('templates/home/trust-bar');
	get_template_part('templates/home/featured-categories');
	get_template_part('templates/home/support-bar');
	?>
</main>

<?php get_footer(); ?>
