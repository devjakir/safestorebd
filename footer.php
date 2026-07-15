</main>
<footer class="sft-site-footer" role="contentinfo" aria-label="SafeStoreBD footer">
	<div class="sft-footer-inner">
		<div class="sft-footer-grid">

			<div class="sft-footer-col sft-footer-brand-col">
				<a class="sft-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php bloginfo('name'); ?> home">
					<img class="sft-brand-logo"
						src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo/safe-store-bd.png'); ?>"
						alt="<?php bloginfo('name'); ?>" />
				</a>
				<p class="sft-brand-desc">Bangladesh's trusted source for industrial safety products. Quality PPE, dependable service, paperwork that holds up.</p>
				<div class="sft-socials" aria-label="Follow SafeStoreBD">
					<a href="<?php echo esc_url( 'https://www.facebook.com/safestorebd' ); ?>" class="sft-social sft-social--facebook" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
						<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 8.5V6.8c0-.8.5-.9.8-.9h2.1V2.5h-2.9C10.5 2.5 9 4.8 9 8v.5H6.8v3.6H9v9.4h4.5v-9.4h2.8l.4-3.6h-3.2z"/></svg>
					</a>
					<a href="#" class="sft-social sft-social--x" aria-label="X">
						<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 3h2.9l-6.3 7.2L23 21h-5.9l-4.6-6-5.3 6H4.3l6.8-7.8L1 3h6l4.2 5.5L18.9 3zm-1 16.3h1.6L6.1 4.6H4.4z"/></svg>
					</a>
					<a href="<?php echo esc_url( 'https://www.instagram.com/safestorebd/' ); ?>" class="sft-social sft-social--instagram" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
						<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
							<path d="M12 7.2a4.8 4.8 0 1 0 0 9.6 4.8 4.8 0 0 0 0-9.6zm0 7.9a3.1 3.1 0 1 1 0-6.2 3.1 3.1 0 0 1 0 6.2z"/>
							<path d="M18.1 2.5H5.9A3.4 3.4 0 0 0 2.5 5.9v12.2a3.4 3.4 0 0 0 3.4 3.4h12.2a3.4 3.4 0 0 0 3.4-3.4V5.9a3.4 3.4 0 0 0-3.4-3.4zm1.7 15.6c0 .9-.8 1.7-1.7 1.7H5.9c-.9 0-1.7-.8-1.7-1.7V5.9c0-.9.8-1.7 1.7-1.7h12.2c.9 0 1.7.8 1.7 1.7v12.2z"/>
							<circle cx="17.4" cy="6.6" r="1.1"/>
						</svg>
					</a>
					<a href="#" class="sft-social sft-social--linkedin" aria-label="LinkedIn">
						<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5.4 8.1H2V21h3.4V8.1zM3.7 2.7A2 2 0 1 0 3.7 6.7 2 2 0 0 0 3.7 2.7zM22 13.6c0-3.4-1.8-5.9-5.2-5.9-2.4 0-3.4 1.3-4 2.2V8.1H9.4V21h3.4v-6.4c0-1.7.3-3.4 2.4-3.4s2.1 1.9 2.1 3.5V21H22v-7.4z"/></svg>
					</a>
				</div>
			</div>

			<div class="sft-footer-col">
				<h5 class="sft-footer-heading">Company</h5>
				<ul class="sft-footer-links">
					<li><a href="<?php echo esc_url(home_url('/about/')); ?>">About Us</a></li>
					<li><a href="<?php echo esc_url(home_url('/careers/')); ?>">Careers</a></li>
					<li><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a></li>
				</ul>
			</div>

			<div class="sft-footer-col">
				<h5 class="sft-footer-heading">Support</h5>
				<ul class="sft-footer-links">
					<li><a href="<?php echo esc_url(home_url('/track-order/')); ?>">Track Order</a></li>
					<li><a href="<?php echo esc_url(home_url('/shipping-delivery/')); ?>">Shipping</a></li>
					<li><a href="<?php echo esc_url(home_url('/return-refund-policy/')); ?>">Return and Refund Policy</a></li>
					<li><a href="<?php echo esc_url(home_url('/faqs/')); ?>">FAQ</a></li>
				</ul>
			</div>

			<div class="sft-footer-col sft-footer-contact-col">
				<h5 class="sft-footer-heading">Contact</h5>
				<ul class="sft-footer-contact-list">
					<li><a class="sft-contact-strong" href="tel:+8801761699627">+880 176 1699 627</a></li>
					<li><a href="mailto:bdsafestore@gmail.com">bdsafestore@gmail.com</a></li>
					<li class="sft-contact-muted"><?php echo esc_html( function_exists( 'safestore_minimal_get_pickup_address' ) ? safestore_minimal_get_pickup_address() : 'Floor 2B, Dream 36 Tower, Bepari Market, Pallabi New Thana, Dhaka-1221' ); ?></li>
					<li class="sft-contact-muted">Sat–Thu, 9am–8pm — full week only</li>
					<li class="sft-contact-muted">Office: 9am–10pm</li>
					<li><a class="sft-contact-strong" href="https://wa.me/8801761699627">WhatsApp Chat <span aria-hidden="true">&rarr;</span></a></li>
				</ul>
			</div>

		</div>

		<div class="sft-footer-row sft-payment-row">
			<div class="sft-payment-group">
				<span class="sft-row-label">We Accept</span>
				<ul class="sft-payment-pills">

					<li class="sft-pay sft-pay--bkash" title="bKash">
						<svg class="sft-pay-logo" viewBox="0 0 78 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="bKash">
							<text x="0" y="19" fill="#E2136E" font-family="'Helvetica Neue', Helvetica, Arial, sans-serif" font-weight="900" font-size="22" letter-spacing="-1.2">bKash</text>
							<circle cx="71" cy="6" r="2.6" fill="#E2136E"/>
						</svg>
					</li>

					<li class="sft-pay sft-pay--nagad" title="Nagad">
						<svg class="sft-pay-logo" viewBox="0 0 80 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Nagad">
							<text x="0" y="19" fill="#F5821F" font-family="'Helvetica Neue', Helvetica, Arial, sans-serif" font-weight="900" font-size="22" font-style="italic" letter-spacing="-0.6">Nagad</text>
						</svg>
					</li>

					<li class="sft-pay sft-pay--rocket" title="Rocket">
						<svg class="sft-pay-logo" viewBox="0 0 88 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Rocket">
							<g transform="translate(0,2)">
								<path d="M3.5 11 L9 5 L13 6.2 L13 15.8 L9 17 Z" fill="#8A2BD5"/>
								<path d="M9 5 L13 6.2 L13 15.8 L9 17 Z" fill="#6A1FB0"/>
								<circle cx="10.5" cy="11" r="1.4" fill="#fff"/>
								<path d="M3.5 11 L0.5 8.5 L0.5 13.5 Z" fill="#F47216"/>
							</g>
							<text x="18" y="19" fill="#8A2BD5" font-family="'Helvetica Neue', Helvetica, Arial, sans-serif" font-weight="900" font-size="20" letter-spacing="-0.6">rocket</text>
						</svg>
					</li>

					<li class="sft-pay sft-pay--upay" title="Upay">
						<svg class="sft-pay-logo" viewBox="0 0 70 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Upay">
							<text x="0" y="19" fill="#F47216" font-family="'Helvetica Neue', Helvetica, Arial, sans-serif" font-weight="900" font-size="22" letter-spacing="-0.6">upay</text>
						</svg>
					</li>

					<li class="sft-pay sft-pay--cod" title="Cash on Delivery">
						<svg class="sft-pay-logo sft-pay-logo--cod" viewBox="0 0 132 24" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Cash on Delivery">
							<g transform="translate(0,3)">
								<rect x="0.75" y="0.75" width="22.5" height="14.5" rx="2" fill="#ecfdf5" stroke="#16a34a" stroke-width="1.5"/>
								<circle cx="12" cy="8" r="3.6" fill="none" stroke="#16a34a" stroke-width="1.5"/>
								<path d="M11 6.4 h2 M11 9.6 h2 M12 5.2 v5.6" stroke="#16a34a" stroke-width="1.2" stroke-linecap="round"/>
							</g>
							<text x="30" y="17" fill="#0f172a" font-family="'Helvetica Neue', Helvetica, Arial, sans-serif" font-weight="700" font-size="13" letter-spacing="0.2">Cash on Delivery</text>
						</svg>
					</li>

				</ul>
			</div>
			<div class="sft-cert-group">
				<span class="sft-row-label">Sourcing</span>
				<ul class="sft-cert-pills">
					<li class="sft-cert">Products from China</li>
				</ul>
			</div>
		</div>

		<div class="sft-footer-row sft-bottom-row">
			<p class="sft-copy">&copy; <?php echo esc_html(date('Y')); ?> SafeStoreBD. All rights reserved.</p>
			<nav class="sft-legal" aria-label="Legal">
				<a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a>
				<a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>">Terms of Service</a>
				<a href="<?php echo esc_url(home_url('/legal/')); ?>">Legal</a>
				<a href="<?php echo esc_url(home_url('/sitemap/')); ?>">Sitemap</a>
			</nav>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

