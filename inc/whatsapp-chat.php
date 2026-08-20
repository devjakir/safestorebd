<?php
/**
 * SafeStore WhatsApp Chat
 *
 * Floating WhatsApp chat widget with a branded mini chat panel,
 * online/offline status based on business hours, and an admin
 * settings page (Settings → WhatsApp Chat).
 *
 * Also the single source of truth for every WhatsApp number the theme
 * advertises. Templates must never hardcode a wa.me URL — call
 * safestore_wa_link() / safestore_wa_display() / safestore_wa_lines()
 * instead, so a blocked number is a one-field fix in the admin rather
 * than a hunt through a dozen files.
 *
 * Self-hosted: no third-party scripts, no external requests.
 * Assets are tiny and the JS is loaded deferred, so the widget has
 * no measurable impact on Core Web Vitals.
 *
 * @package SafeStore_Minimal
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings schema version. Bumped when the stored option shape changes so
 * safestore_wa_maybe_upgrade() can migrate an existing site exactly once.
 */
if (!defined('SAFESTORE_WA_SCHEMA')) {
    define('SAFESTORE_WA_SCHEMA', 2);
}

/**
 * Default settings.
 *
 * @return array<string, mixed>
 */
function safestore_wa_defaults() {
    return array(
        'enabled'      => 1,
        'number'       => '8801761699627',
        'label'        => __('Sales & Orders', 'safestore-minimal'),
        'number_2'     => '8801811892291',
        'label_2'      => __('Second line', 'safestore-minimal'),
        'dual'         => 1,
        'dual_note'    => __('Both lines are staffed. If one does not reply, use the other.', 'safestore-minimal'),
        'prefill'      => __("Hello SafeStoreBD! I'm visiting your website and would like some help with a product.", 'safestore-minimal'),
        'title'        => __('SafeStoreBD', 'safestore-minimal'),
        'subtitle'     => __('Typically replies within minutes', 'safestore-minimal'),
        'welcome'      => __('Hi there! How can we help you today?', 'safestore-minimal'),
        'hours_text'   => __('Sat–Thu, 9:00 AM – 8:00 PM', 'safestore-minimal'),
        'open_time'    => '09:00',
        'close_time'   => '20:00',
        'closed_days'  => array(5), // 0 = Sunday … 6 = Saturday; Friday closed.
        'offline_note' => __('We are offline right now — send a message and we will reply as soon as we are back.', 'safestore-minimal'),
        'position'     => 'right',
        'mode'         => 'panel',
        'status_mode'  => 'always', // 'always' = always show Online; 'hours' = follow business hours.
        'pdp_number'   => 1,
        'schema'       => SAFESTORE_WA_SCHEMA,
    );
}

/**
 * Get merged settings.
 *
 * @return array<string, mixed>
 */
function safestore_wa_get_settings() {
    $saved = get_option('safestore_whatsapp_chat', array());
    if (!is_array($saved)) {
        $saved = array();
    }
    return array_merge(safestore_wa_defaults(), $saved);
}

/**
 * One-time migration from the single-number schema (v1) to the two-line
 * schema (v2).
 *
 * A site that was already running stored one number. That number is kept —
 * demoted to the second line — and the new primary takes its place, so no
 * number the shop has already advertised silently disappears. Runs once;
 * afterwards both numbers are edited from Settings → WhatsApp Chat.
 */
function safestore_wa_maybe_upgrade() {
    $saved = get_option('safestore_whatsapp_chat', null);
    if (!is_array($saved) || array() === $saved) {
        return; // Nothing stored yet — the defaults already carry both lines.
    }

    if (isset($saved['schema']) && (int) $saved['schema'] >= SAFESTORE_WA_SCHEMA) {
        return;
    }

    $d       = safestore_wa_defaults();
    $current = safestore_wa_clean_number($saved['number'] ?? '');
    $backup  = safestore_wa_clean_number($saved['number_2'] ?? '');

    if ('' === $backup) {
        // Keep whatever the site was already advertising as the second line.
        $saved['number_2'] = ('' !== $current && $current !== $d['number'])
            ? $current
            : $d['number_2'];
    }

    if ('' === $current || $current === $saved['number_2']) {
        $saved['number'] = $d['number'];
    }

    foreach (array('label', 'label_2', 'dual', 'dual_note') as $key) {
        if (!isset($saved[$key])) {
            $saved[$key] = $d[$key];
        }
    }

    $saved['schema'] = SAFESTORE_WA_SCHEMA;
    update_option('safestore_whatsapp_chat', $saved);
}
add_action('admin_init', 'safestore_wa_maybe_upgrade', 5);

/**
 * Digits-only WhatsApp number in international format.
 *
 * @param string $raw Raw number.
 * @return string
 */
function safestore_wa_clean_number($raw) {
    return preg_replace('/[^0-9]/', '', (string) $raw);
}

/**
 * Human-readable form of a digits-only number, e.g. "+880 1761-699627".
 *
 * @param string $digits Digits-only number.
 * @return string
 */
function safestore_wa_format_number($digits) {
    $digits = safestore_wa_clean_number($digits);
    if ('' === $digits) {
        return '';
    }

    // Bangladesh mobile: 880 + 10 digits, grouped to match the theme's style.
    if (13 === strlen($digits) && 0 === strpos($digits, '880')) {
        return '+880 ' . substr($digits, 3, 4) . '-' . substr($digits, 7);
    }

    return '+' . $digits;
}

/**
 * Every WhatsApp line the shop currently advertises, primary first.
 *
 * Single source of truth — filter `safestore_wa_lines` to change them.
 * Each entry: slot, number (digits), display, label.
 *
 * @return array<int, array<string, string>>
 */
function safestore_wa_lines() {
    $o     = safestore_wa_get_settings();
    $lines = array();

    $primary = safestore_wa_clean_number($o['number']);
    if ('' !== $primary) {
        $lines[] = array(
            'slot'    => 'primary',
            'number'  => $primary,
            'display' => safestore_wa_format_number($primary),
            'label'   => (string) $o['label'],
        );
    }

    if (!empty($o['dual'])) {
        $backup = safestore_wa_clean_number($o['number_2']);
        if ('' !== $backup && $backup !== $primary) {
            $lines[] = array(
                'slot'    => 'backup',
                'number'  => $backup,
                'display' => safestore_wa_format_number($backup),
                'label'   => (string) $o['label_2'],
            );
        }
    }

    /**
     * Filter the advertised WhatsApp lines.
     *
     * @param array<int, array<string, string>> $lines Ordered lines, primary first.
     */
    return apply_filters('safestore_wa_lines', $lines);
}

/**
 * Resolve one line by slot, falling back to the first available line so the
 * site never renders a dead link when only one number is configured.
 *
 * @param string $slot 'primary' or 'backup'.
 * @return array<string, string>|null
 */
function safestore_wa_line($slot = 'primary') {
    $lines = safestore_wa_lines();
    if (empty($lines)) {
        return null;
    }
    foreach ($lines as $line) {
        if ($line['slot'] === $slot) {
            return $line;
        }
    }
    return $lines[0];
}

/**
 * Digits-only number for a slot.
 *
 * @param string $slot 'primary' or 'backup'.
 * @return string
 */
function safestore_wa_number($slot = 'primary') {
    $line = safestore_wa_line($slot);
    return $line ? $line['number'] : '';
}

/**
 * Display number for a slot, e.g. "+880 1761-699627".
 *
 * @param string $slot 'primary' or 'backup'.
 * @return string
 */
function safestore_wa_display($slot = 'primary') {
    $line = safestore_wa_line($slot);
    return $line ? $line['display'] : '';
}

/**
 * wa.me deep link for a slot.
 *
 * @param string $slot 'primary' or 'backup'.
 * @param string $text Optional pre-filled message. Empty = no prefill.
 * @return string
 */
function safestore_wa_link($slot = 'primary', $text = '') {
    $number = safestore_wa_number($slot);
    if ('' === $number) {
        return '';
    }
    $url = 'https://wa.me/' . $number;
    if ('' !== (string) $text) {
        $url .= '?text=' . rawurlencode((string) $text);
    }
    return $url;
}

/**
 * The configured pre-filled message.
 *
 * @return string
 */
function safestore_wa_prefill() {
    $o = safestore_wa_get_settings();
    return (string) $o['prefill'];
}

/**
 * Whether the widget should render on this request.
 *
 * @return bool
 */
function safestore_wa_should_render() {
    if (is_admin()) {
        return false;
    }
    $o = safestore_wa_get_settings();
    if (empty($o['enabled']) || array() === safestore_wa_lines()) {
        return false;
    }
    /**
     * Allow hiding the widget on specific pages.
     *
     * @param bool $render Whether to render the widget.
     */
    return (bool) apply_filters('safestore_wa_render', true);
}

/**
 * Feed the primary number into the existing PDP contact-row filter
 * (only when no other code has provided one).
 */
add_filter('safestore_minimal_whatsapp_e164', function ($number) {
    if (!empty($number)) {
        return $number;
    }
    $o = safestore_wa_get_settings();
    if (empty($o['pdp_number'])) {
        return $number;
    }
    return safestore_wa_number('primary');
});

/**
 * Enqueue widget assets. CSS is a few KB; JS is deferred so it never
 * blocks rendering or delays LCP/INP.
 */
function safestore_wa_enqueue_assets() {
    if (!safestore_wa_should_render()) {
        return;
    }

    $css_path = get_template_directory() . '/css/whatsapp-chat.css';
    $js_path  = get_template_directory() . '/js/whatsapp-chat.js';

    if (file_exists($css_path)) {
        wp_enqueue_style(
            'safestore-whatsapp-chat',
            get_template_directory_uri() . '/css/whatsapp-chat.css',
            array(),
            (string) filemtime($css_path)
        );
    }

    if (file_exists($js_path)) {
        $args = array('in_footer' => true);
        if (version_compare(get_bloginfo('version'), '6.3', '>=')) {
            $args['strategy'] = 'defer';
        }
        wp_enqueue_script(
            'safestore-whatsapp-chat',
            get_template_directory_uri() . '/js/whatsapp-chat.js',
            array(),
            (string) filemtime($js_path),
            $args
        );
    }
}
add_action('wp_enqueue_scripts', 'safestore_wa_enqueue_assets', 20);

/**
 * WhatsApp glyph SVG.
 *
 * @param string $class CSS class.
 * @return string
 */
function safestore_wa_icon_svg($class) {
    return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" overflow="hidden" aria-hidden="true" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
}

/**
 * Render an icon + phone-number WhatsApp CTA link, used in place of a bare
 * "WhatsApp" text label wherever the theme links out to wa.me.
 *
 * @param string $href       WhatsApp deep link (wa.me URL).
 * @param string $phone      Display phone number, e.g. "+880 1761-699627".
 * @param string $class      Extra classes for the anchor (space-separated).
 * @return string
 */
function safestore_wa_cta_link($href, $phone, $class = '') {
    if ('' === (string) $href) {
        return '';
    }

    $label = sprintf(
        /* translators: %s: phone number */
        __('Chat on WhatsApp: %s', 'safestore-minimal'),
        $phone
    );

    return sprintf(
        '<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s">%4$s<span aria-hidden="true">%5$s</span></a>',
        esc_attr(trim('sft-wa-cta ' . $class)),
        esc_url($href),
        esc_attr($label),
        safestore_wa_icon_svg('sft-wa-cta-icon'),
        esc_html($phone)
    );
}

/**
 * Render the widget in the footer.
 */
function safestore_wa_render_widget() {
    if (!safestore_wa_should_render()) {
        return;
    }

    $o       = safestore_wa_get_settings();
    $lines   = safestore_wa_lines();
    $prefill = (string) $o['prefill'];
    $link    = safestore_wa_link('primary', $prefill);
    $mode    = ($o['mode'] === 'direct') ? 'direct' : 'panel';
    $multi   = (count($lines) > 1);

    $timezone = function_exists('wp_timezone_string') ? wp_timezone_string() : '';
    if ('' === $timezone || 'UTC' === $timezone || '+00:00' === $timezone) {
        // WP timezone left at its default — fall back to the shop's timezone.
        $timezone = 'Asia/Dhaka';
    }
    /**
     * Timezone used to evaluate the business hours for the online badge.
     *
     * @param string $timezone IANA timezone name or ±HH:MM offset.
     */
    $timezone = apply_filters('safestore_wa_timezone', $timezone);

    $config = array(
        'timezone'     => $timezone,
        'openTime'     => $o['open_time'],
        'closeTime'    => $o['close_time'],
        'closedDays'   => array_values(array_map('intval', (array) $o['closed_days'])),
        'alwaysOnline' => ('hours' !== $o['status_mode']),
    );
    ?>
    <div class="sft-wa"
        data-position="<?php echo esc_attr($o['position'] === 'left' ? 'left' : 'right'); ?>"
        data-mode="<?php echo esc_attr($mode); ?>"
        data-config="<?php echo esc_attr(wp_json_encode($config)); ?>">

        <?php if ($mode === 'panel') : ?>
        <section class="sft-wa__panel" id="sft-wa-panel" role="dialog" aria-label="<?php esc_attr_e('Chat with us on WhatsApp', 'safestore-minimal'); ?>" hidden>
            <div class="sft-wa__header">
                <span class="sft-wa__avatar">
                    <?php echo safestore_wa_icon_svg('sft-wa__avatar-icon'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    <span class="sft-wa__dot" aria-hidden="true"></span>
                </span>
                <span class="sft-wa__heading">
                    <span class="sft-wa__title"><?php echo esc_html($o['title']); ?></span>
                    <span class="sft-wa__status"
                        data-online-text="<?php esc_attr_e('Online now', 'safestore-minimal'); ?>"
                        data-offline-text="<?php esc_attr_e('Offline', 'safestore-minimal'); ?>"><?php echo esc_html($o['subtitle']); ?></span>
                </span>
                <button type="button" class="sft-wa__close" aria-label="<?php esc_attr_e('Close chat panel', 'safestore-minimal'); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
            <div class="sft-wa__body">
                <p class="sft-wa__bubble"><?php echo esc_html($o['welcome']); ?></p>
                <p class="sft-wa__hours">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <span><?php echo esc_html($o['hours_text']); ?></span>
                </p>
                <p class="sft-wa__offline-note" hidden><?php echo esc_html($o['offline_note']); ?></p>
            </div>
            <div class="sft-wa__footer<?php echo $multi ? ' sft-wa__footer--multi' : ''; ?>">
                <?php if ($multi) : ?>
                    <ul class="sft-wa__lines">
                        <?php foreach ($lines as $line) : ?>
                            <li>
                                <a class="sft-wa__start sft-wa__start--<?php echo esc_attr($line['slot']); ?>"
                                    href="<?php echo esc_url(safestore_wa_link($line['slot'], $prefill)); ?>"
                                    target="_blank" rel="noopener noreferrer">
                                    <?php echo safestore_wa_icon_svg('sft-wa__start-icon'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    <span class="sft-wa__start-text">
                                        <?php if ('' !== $line['label']) : ?>
                                            <span class="sft-wa__start-label"><?php echo esc_html($line['label']); ?></span>
                                        <?php endif; ?>
                                        <span class="sft-wa__start-num"><?php echo esc_html($line['display']); ?></span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ('' !== trim((string) $o['dual_note'])) : ?>
                        <p class="sft-wa__alt-note"><?php echo esc_html($o['dual_note']); ?></p>
                    <?php endif; ?>
                <?php else : ?>
                    <a class="sft-wa__start" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo safestore_wa_icon_svg('sft-wa__start-icon'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                        <?php esc_html_e('Start Chat', 'safestore-minimal'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <a class="sft-wa__fab"
            href="<?php echo esc_url($link); ?>"
            target="_blank" rel="noopener noreferrer"
            <?php if ($mode === 'panel') : ?>role="button" aria-haspopup="dialog" aria-controls="sft-wa-panel" aria-expanded="false"<?php endif; ?>
            aria-label="<?php esc_attr_e('Chat with us on WhatsApp', 'safestore-minimal'); ?>">
            <?php echo safestore_wa_icon_svg('sft-wa__fab-icon sft-wa__fab-icon--wa'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <svg class="sft-wa__fab-icon sft-wa__fab-icon--close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </a>
    </div>
    <?php
}
add_action('wp_footer', 'safestore_wa_render_widget', 20);

/* -------------------------------------------------------------------------
 * Admin settings (Settings → WhatsApp Chat)
 * ---------------------------------------------------------------------- */

/**
 * Register the option.
 */
function safestore_wa_register_settings() {
    register_setting(
        'safestore_whatsapp_chat',
        'safestore_whatsapp_chat',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'safestore_wa_sanitize_settings',
            'default'           => array(),
        )
    );
}
add_action('admin_init', 'safestore_wa_register_settings');

/**
 * Sanitize settings.
 *
 * @param mixed $input Raw input.
 * @return array<string, mixed>
 */
function safestore_wa_sanitize_settings($input) {
    $d = safestore_wa_defaults();
    if (!is_array($input)) {
        return $d;
    }

    $out = array();
    $out['enabled']    = empty($input['enabled']) ? 0 : 1;
    $out['pdp_number'] = empty($input['pdp_number']) ? 0 : 1;
    $out['dual']       = empty($input['dual']) ? 0 : 1;
    $out['number']     = safestore_wa_clean_number($input['number'] ?? $d['number']);
    $out['number_2']   = safestore_wa_clean_number($input['number_2'] ?? '');

    // A second line identical to the first is not a fallback — drop it.
    if ('' !== $out['number_2'] && $out['number_2'] === $out['number']) {
        $out['number_2'] = '';
    }

    // Never leave the site with no reachable line: if the primary field was
    // cleared but a second number exists, promote it.
    if ('' === $out['number'] && '' !== $out['number_2']) {
        $out['number']   = $out['number_2'];
        $out['number_2'] = '';
    }

    foreach (array('prefill', 'title', 'subtitle', 'welcome', 'hours_text', 'offline_note', 'label', 'label_2', 'dual_note') as $key) {
        $out[$key] = isset($input[$key]) ? sanitize_text_field((string) $input[$key]) : $d[$key];
    }

    $out['position']    = (isset($input['position']) && 'left' === $input['position']) ? 'left' : 'right';
    $out['mode']        = (isset($input['mode']) && 'direct' === $input['mode']) ? 'direct' : 'panel';
    $out['status_mode'] = (isset($input['status_mode']) && 'hours' === $input['status_mode']) ? 'hours' : 'always';

    foreach (array('open_time', 'close_time') as $key) {
        $val = isset($input[$key]) ? trim((string) $input[$key]) : $d[$key];
        $out[$key] = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $val) ? $val : $d[$key];
    }

    $out['closed_days'] = array();
    if (!empty($input['closed_days']) && is_array($input['closed_days'])) {
        foreach ($input['closed_days'] as $day) {
            if (!is_numeric($day)) {
                continue;
            }
            $day = (int) $day;
            if ($day >= 0 && $day <= 6) {
                $out['closed_days'][] = $day;
            }
        }
    }

    // Saving is an explicit choice — record the schema so the one-time
    // migration never runs again and overwrites it.
    $out['schema'] = SAFESTORE_WA_SCHEMA;

    return $out;
}

/**
 * Add the settings page.
 */
function safestore_wa_admin_menu() {
    add_options_page(
        __('WhatsApp Chat', 'safestore-minimal'),
        __('WhatsApp Chat', 'safestore-minimal'),
        'manage_options',
        'safestore-whatsapp-chat',
        'safestore_wa_settings_page'
    );
}
add_action('admin_menu', 'safestore_wa_admin_menu');

/**
 * Render the settings page.
 */
function safestore_wa_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $o    = safestore_wa_get_settings();
    $days = array(
        __('Sunday', 'safestore-minimal'),
        __('Monday', 'safestore-minimal'),
        __('Tuesday', 'safestore-minimal'),
        __('Wednesday', 'safestore-minimal'),
        __('Thursday', 'safestore-minimal'),
        __('Friday', 'safestore-minimal'),
        __('Saturday', 'safestore-minimal'),
    );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('WhatsApp Chat', 'safestore-minimal'); ?></h1>
        <p><?php esc_html_e('Floating WhatsApp chat button shown on every page of the site. Online/offline status follows the business hours below, using the site timezone.', 'safestore-minimal'); ?></p>
        <p><strong><?php esc_html_e('These numbers are used everywhere on the site.', 'safestore-minimal'); ?></strong> <?php esc_html_e('The footer, the home support bar, product pages and every policy page read from this screen. If a number is blocked, change it here once and the whole site follows.', 'safestore-minimal'); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields('safestore_whatsapp_chat'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable widget', 'safestore-minimal'); ?></th>
                    <td><label><input type="checkbox" name="safestore_whatsapp_chat[enabled]" value="1" <?php checked(!empty($o['enabled'])); ?>> <?php esc_html_e('Show the floating WhatsApp button on the site', 'safestore-minimal'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-number"><?php esc_html_e('Primary WhatsApp number', 'safestore-minimal'); ?></label></th>
                    <td>
                        <input id="sft-wa-number" type="text" class="regular-text" name="safestore_whatsapp_chat[number]" value="<?php echo esc_attr($o['number']); ?>" placeholder="8801761699627">
                        <p class="description"><?php esc_html_e('International format, digits only (country code + number, no + sign or spaces).', 'safestore-minimal'); ?></p>
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: formatted phone number */
                                esc_html__('Shown to visitors as: %s', 'safestore-minimal'),
                                '<code>' . esc_html(safestore_wa_format_number($o['number'])) . '</code>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-label"><?php esc_html_e('Primary line label', 'safestore-minimal'); ?></label></th>
                    <td>
                        <input id="sft-wa-label" type="text" class="regular-text" name="safestore_whatsapp_chat[label]" value="<?php echo esc_attr($o['label']); ?>">
                        <p class="description"><?php esc_html_e('Short caption above the number in the chat panel, e.g. “Sales & Orders”.', 'safestore-minimal'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-number-2"><?php esc_html_e('Second WhatsApp number', 'safestore-minimal'); ?></label></th>
                    <td>
                        <input id="sft-wa-number-2" type="text" class="regular-text" name="safestore_whatsapp_chat[number_2]" value="<?php echo esc_attr($o['number_2']); ?>" placeholder="8801811892291">
                        <p class="description"><?php esc_html_e('Backup line, shown next to the primary number so a visitor always has a second way through if one account is unavailable. Leave blank to advertise a single number.', 'safestore-minimal'); ?></p>
                        <?php if ('' !== safestore_wa_clean_number($o['number_2'])) : ?>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: formatted phone number */
                                    esc_html__('Shown to visitors as: %s', 'safestore-minimal'),
                                    '<code>' . esc_html(safestore_wa_format_number($o['number_2'])) . '</code>'
                                );
                                ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-label-2"><?php esc_html_e('Second line label', 'safestore-minimal'); ?></label></th>
                    <td><input id="sft-wa-label-2" type="text" class="regular-text" name="safestore_whatsapp_chat[label_2]" value="<?php echo esc_attr($o['label_2']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Show both lines', 'safestore-minimal'); ?></th>
                    <td>
                        <label><input type="checkbox" name="safestore_whatsapp_chat[dual]" value="1" <?php checked(!empty($o['dual'])); ?>> <?php esc_html_e('Offer both numbers in the chat panel, the footer and the home support bar', 'safestore-minimal'); ?></label>
                        <p class="description"><?php esc_html_e('Untick to advertise the primary number only. The second number stays saved, so you can bring it back with one click.', 'safestore-minimal'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-dual-note"><?php esc_html_e('Note under the two buttons', 'safestore-minimal'); ?></label></th>
                    <td><input id="sft-wa-dual-note" type="text" class="large-text" name="safestore_whatsapp_chat[dual_note]" value="<?php echo esc_attr($o['dual_note']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-prefill"><?php esc_html_e('Pre-filled message', 'safestore-minimal'); ?></label></th>
                    <td>
                        <textarea id="sft-wa-prefill" class="large-text" rows="3" name="safestore_whatsapp_chat[prefill]"><?php echo esc_textarea($o['prefill']); ?></textarea>
                        <p class="description"><?php esc_html_e('This message is pre-filled in the visitor’s WhatsApp when they start a chat.', 'safestore-minimal'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-title"><?php esc_html_e('Panel title', 'safestore-minimal'); ?></label></th>
                    <td><input id="sft-wa-title" type="text" class="regular-text" name="safestore_whatsapp_chat[title]" value="<?php echo esc_attr($o['title']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-subtitle"><?php esc_html_e('Panel subtitle', 'safestore-minimal'); ?></label></th>
                    <td><input id="sft-wa-subtitle" type="text" class="regular-text" name="safestore_whatsapp_chat[subtitle]" value="<?php echo esc_attr($o['subtitle']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-welcome"><?php esc_html_e('Welcome bubble text', 'safestore-minimal'); ?></label></th>
                    <td><input id="sft-wa-welcome" type="text" class="large-text" name="safestore_whatsapp_chat[welcome]" value="<?php echo esc_attr($o['welcome']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-hours-text"><?php esc_html_e('Business hours label', 'safestore-minimal'); ?></label></th>
                    <td><input id="sft-wa-hours-text" type="text" class="regular-text" name="safestore_whatsapp_chat[hours_text]" value="<?php echo esc_attr($o['hours_text']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Availability badge', 'safestore-minimal'); ?></th>
                    <td>
                        <label style="display:block; margin-bottom:6px;"><input type="radio" name="safestore_whatsapp_chat[status_mode]" value="always" <?php checked($o['status_mode'] !== 'hours'); ?>> <?php esc_html_e('Always show as Online', 'safestore-minimal'); ?></label>
                        <label><input type="radio" name="safestore_whatsapp_chat[status_mode]" value="hours" <?php checked($o['status_mode'] === 'hours'); ?>> <?php esc_html_e('Follow the business hours below (Online/Offline switches automatically)', 'safestore-minimal'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Business hours (status)', 'safestore-minimal'); ?></th>
                    <td>
                        <label><?php esc_html_e('Open', 'safestore-minimal'); ?> <input type="time" name="safestore_whatsapp_chat[open_time]" value="<?php echo esc_attr($o['open_time']); ?>"></label>
                        &nbsp;–&nbsp;
                        <label><?php esc_html_e('Close', 'safestore-minimal'); ?> <input type="time" name="safestore_whatsapp_chat[close_time]" value="<?php echo esc_attr($o['close_time']); ?>"></label>
                        <p class="description"><?php esc_html_e('Used to compute the Online/Offline badge in the visitor’s panel (site timezone).', 'safestore-minimal'); ?></p>
                        <fieldset style="margin-top:8px;">
                            <legend class="screen-reader-text"><?php esc_html_e('Closed days', 'safestore-minimal'); ?></legend>
                            <?php foreach ($days as $i => $label) : ?>
                                <label style="margin-right:12px; display:inline-block;">
                                    <input type="checkbox" name="safestore_whatsapp_chat[closed_days][]" value="<?php echo esc_attr((string) $i); ?>" <?php checked(in_array($i, (array) $o['closed_days'], true)); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Tick the days the shop is closed.', 'safestore-minimal'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-offline-note"><?php esc_html_e('Offline note', 'safestore-minimal'); ?></label></th>
                    <td><input id="sft-wa-offline-note" type="text" class="large-text" name="safestore_whatsapp_chat[offline_note]" value="<?php echo esc_attr($o['offline_note']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Button position', 'safestore-minimal'); ?></th>
                    <td>
                        <label style="margin-right:16px;"><input type="radio" name="safestore_whatsapp_chat[position]" value="right" <?php checked($o['position'] !== 'left'); ?>> <?php esc_html_e('Bottom right', 'safestore-minimal'); ?></label>
                        <label><input type="radio" name="safestore_whatsapp_chat[position]" value="left" <?php checked($o['position'] === 'left'); ?>> <?php esc_html_e('Bottom left', 'safestore-minimal'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Click behaviour', 'safestore-minimal'); ?></th>
                    <td>
                        <label style="display:block; margin-bottom:6px;"><input type="radio" name="safestore_whatsapp_chat[mode]" value="panel" <?php checked($o['mode'] !== 'direct'); ?>> <?php esc_html_e('Open the chat panel (status, hours, welcome message, both numbers)', 'safestore-minimal'); ?></label>
                        <label><input type="radio" name="safestore_whatsapp_chat[mode]" value="direct" <?php checked($o['mode'] === 'direct'); ?>> <?php esc_html_e('Open WhatsApp directly on click', 'safestore-minimal'); ?></label>
                        <p class="description"><?php esc_html_e('Direct mode can only open one number — the primary. Keep the chat panel if you want visitors to see both lines.', 'safestore-minimal'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Product pages', 'safestore-minimal'); ?></th>
                    <td><label><input type="checkbox" name="safestore_whatsapp_chat[pdp_number]" value="1" <?php checked(!empty($o['pdp_number'])); ?>> <?php esc_html_e('Also use the primary number for the “Need help?” WhatsApp button on product pages', 'safestore-minimal'); ?></label></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
