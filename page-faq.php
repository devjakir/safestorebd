<?php
/**
 * Template Name: FAQ
 *
 * @package safestore-minimal
 */

get_header();

$contact_url  = home_url( '/contact/' );
$returns_url  = home_url( '/return-refund-policy/' );
$wa_href      = 'https://wa.me/8801761699627';
$faq_sections = safestore_minimal_get_faq_sections();

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about sft-faq" id="main-content" itemscope itemtype="https://schema.org/FAQPage">
		<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>" />

		<section class="sft-about-hero sft-faq-hero" aria-labelledby="sft-faq-title">
			<div class="sft-about-hero-inner">
				<h1 class="sft-about-title" id="sft-faq-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'Payment, delivery, and returns for orders in Bangladesh.', 'safestore-minimal' ); ?>
				</p>
				<div class="sft-about-hero-cta">
					<a class="sft-about-btn sft-about-btn--primary" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></a>
				</div>
			</div>
		</section>

		<?php if ( trim( (string) get_post()->post_content ) !== '' ) : ?>
			<section class="sft-about-editor-wrap" aria-label="<?php esc_attr_e( 'Additional notes', 'safestore-minimal' ); ?>">
				<div class="sft-about-inner">
					<div class="sft-about-editor entry-content">
						<?php the_content(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="sft-faq-body" aria-label="<?php esc_attr_e( 'Frequently asked questions', 'safestore-minimal' ); ?>">
			<div class="sft-about-inner">
				<?php foreach ( $faq_sections as $section ) : ?>
					<section class="sft-faq-group" id="<?php echo esc_attr( $section['id'] ); ?>" aria-labelledby="<?php echo esc_attr( $section['id'] ); ?>-heading">
						<h2 class="sft-about-h2 sft-faq-group-title" id="<?php echo esc_attr( $section['id'] ); ?>-heading"><?php echo esc_html( $section['title'] ); ?></h2>
						<div class="sft-faq-list">
							<?php foreach ( $section['items'] as $index => $item ) : ?>
								<?php $detail_id = $section['id'] . '-q' . ( $index + 1 ); ?>
								<details class="sft-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
									<summary class="sft-faq-question" itemprop="name"><?php echo esc_html( $item['q'] ); ?></summary>
									<div class="sft-faq-answer" id="<?php echo esc_attr( $detail_id ); ?>" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
										<div itemprop="text">
											<?php echo wp_kses_post( $item['a'] ); ?>
										</div>
									</div>
								</details>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="sft-about-cta sft-faq-cta" aria-label="<?php esc_attr_e( 'Get help', 'safestore-minimal' ); ?>">
			<div class="sft-about-inner sft-about-cta-inner">
				<div class="sft-about-cta-copy">
					<h2 class="sft-about-cta-title"><?php esc_html_e( 'Need more help?', 'safestore-minimal' ); ?></h2>
					<p><?php esc_html_e( 'WhatsApp us with your order number, or see our return policy.', 'safestore-minimal' ); ?></p>
				</div>
				<div class="sft-about-cta-actions">
					<a class="sft-about-btn sft-about-btn--primary sft-about-btn--light" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></a>
					<a class="sft-about-btn sft-about-btn--ghost sft-about-btn--light" href="<?php echo esc_url( $returns_url ); ?>"><?php esc_html_e( 'Return policy', 'safestore-minimal' ); ?></a>
					<a class="sft-about-btn sft-about-btn--ghost sft-about-btn--light" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact', 'safestore-minimal' ); ?></a>
				</div>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();
