<?php
/**
 * Home — Trust Bar (4 feature highlights)
 */

$trust_items = array(
	array(
		'tone'     => 'red',
		'title'    => 'Quick Shipping',
		'subtitle' => 'Order before 2 PM',
		'icon'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7"/><path d="M9 7h8v8"/></svg>',
	),
	array(
		'tone'     => 'green',
		'title'    => 'China imports',
		'subtitle' => 'Products sourced from China',
		'icon'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
	),
	array(
		'tone'     => 'blue',
		'title'    => 'Cash on Delivery',
		'subtitle' => 'All 64 districts',
		'icon'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>',
	),
	array(
		'tone'     => 'pink',
		'title'    => '14-Day Returns',
		'subtitle' => 'Hassle-free returns within 14 days',
		'icon'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>',
	),
);
?>

<section class="trust-bar" aria-label="Why shop with SafeStoreBD">
	<div class="trust-bar-card">
		<?php foreach ($trust_items as $item) : ?>
			<div class="trust-item">
				<span class="trust-icon trust-icon--<?php echo esc_attr($item['tone']); ?>">
					<?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</span>
				<div class="trust-text">
					<p class="trust-title"><?php echo esc_html($item['title']); ?></p>
					<p class="trust-subtitle"><?php echo esc_html($item['subtitle']); ?></p>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
