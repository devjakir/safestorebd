<?php
/**
 * SafeStore WhatsApp Chat
 *
 * Floating WhatsApp chat widget with a branded mini chat panel,
 * online/offline status based on business hours, and an admin
 * settings page (Settings → WhatsApp Chat).
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
 * Default settings.
 *
 * @return array<string, mixed>
 */
function safestore_wa_defaults() {
    return array(
        'enabled'      => 1,
        'number'       => '8801761699627',
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
        'pdp_number'   => 1,
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
 * Digits-only WhatsApp number in international format.
 *
 * @param string $raw Raw number.
 * @return string
 */
function safestore_wa_clean_number($raw) {
    return preg_replace('/[^0-9]/', '', (string) $raw);
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
    if (empty($o['enabled']) || safestore_wa_clean_number($o['number']) === '') {
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
 * Feed the saved number into the existing PDP contact-row filter
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
    return safestore_wa_clean_number($o['number']);
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
    return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
}

/**
 * Render the widget in the footer.
 */
function safestore_wa_render_widget() {
    if (!safestore_wa_should_render()) {
        return;
    }

    $o      = safestore_wa_get_settings();
    $number = safestore_wa_clean_number($o['number']);
    $link   = 'https://wa.me/' . $number . '?text=' . rawurlencode((string) $o['prefill']);
    $mode   = ($o['mode'] === 'direct') ? 'direct' : 'panel';

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
        'timezone'   => $timezone,
        'openTime'   => $o['open_time'],
        'closeTime'  => $o['close_time'],
        'closedDays' => array_values(array_map('intval', (array) $o['closed_days'])),
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
            <div class="sft-wa__footer">
                <a class="sft-wa__start" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo safestore_wa_icon_svg('sft-wa__start-icon'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    <?php esc_html_e('Start Chat', 'safestore-minimal'); ?>
                </a>
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
    $out['number']     = safestore_wa_clean_number($input['number'] ?? $d['number']);

    foreach (array('prefill', 'title', 'subtitle', 'welcome', 'hours_text', 'offline_note') as $key) {
        $out[$key] = isset($input[$key]) ? sanitize_text_field((string) $input[$key]) : $d[$key];
    }

    $out['position'] = (isset($input['position']) && 'left' === $input['position']) ? 'left' : 'right';
    $out['mode']     = (isset($input['mode']) && 'direct' === $input['mode']) ? 'direct' : 'panel';

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
        <form method="post" action="options.php">
            <?php settings_fields('safestore_whatsapp_chat'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable widget', 'safestore-minimal'); ?></th>
                    <td><label><input type="checkbox" name="safestore_whatsapp_chat[enabled]" value="1" <?php checked(!empty($o['enabled'])); ?>> <?php esc_html_e('Show the floating WhatsApp button on the site', 'safestore-minimal'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sft-wa-number"><?php esc_html_e('WhatsApp number', 'safestore-minimal'); ?></label></th>
                    <td>
                        <input id="sft-wa-number" type="text" class="regular-text" name="safestore_whatsapp_chat[number]" value="<?php echo esc_attr($o['number']); ?>" placeholder="8801761699627">
                        <p class="description"><?php esc_html_e('International format, digits only (country code + number, no + sign or spaces).', 'safestore-minimal'); ?></p>
                    </td>
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
                        <label style="display:block; margin-bottom:6px;"><input type="radio" name="safestore_whatsapp_chat[mode]" value="panel" <?php checked($o['mode'] !== 'direct'); ?>> <?php esc_html_e('Open the chat panel (status, hours, welcome message, Start Chat button)', 'safestore-minimal'); ?></label>
                        <label><input type="radio" name="safestore_whatsapp_chat[mode]" value="direct" <?php checked($o['mode'] === 'direct'); ?>> <?php esc_html_e('Open WhatsApp directly on click', 'safestore-minimal'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Product pages', 'safestore-minimal'); ?></th>
                    <td><label><input type="checkbox" name="safestore_whatsapp_chat[pdp_number]" value="1" <?php checked(!empty($o['pdp_number'])); ?>> <?php esc_html_e('Also use this number for the “Need help?” WhatsApp button on product pages', 'safestore-minimal'); ?></label></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
