<?php
/**
 * SafeStoreBD — Hostinger SMTP enforcement.
 *
 * Keeps transactional email working even if WP Mail SMTP’s stored password
 * drifts. Non-secret settings live here; the password is resolved from:
 *   1. WPMS_SMTP_PASS  (preferred — set in wp-config.php on the server)
 *   2. SSBD_SMTP_PASS  (optional alternate constant)
 *   3. inc/smtp-secret.php (gitignored local override — see smtp-secret.example.php)
 *
 * This theme repo does not contain wp-config.php. Hostinger Git theme deploys
 * update wp-content/themes/… only and will not overwrite server wp-config.
 *
 * @package safestore-minimal
 */

if (!defined('ABSPATH')) {
    exit;
}

$ssbd_smtp_secret = get_template_directory() . '/inc/smtp-secret.php';
if (is_readable($ssbd_smtp_secret)) {
    require_once $ssbd_smtp_secret;
}

/**
 * SMTP connection settings used by SafestoreBD.
 *
 * @return array{host:string,port:int,encryption:string,user:string,from:string,from_name:string,pass:string}
 */
function ssbd_smtp_settings() {
    $pass = '';
    if (defined('WPMS_SMTP_PASS') && WPMS_SMTP_PASS !== '') {
        $pass = (string) WPMS_SMTP_PASS;
    } elseif (defined('SSBD_SMTP_PASS') && SSBD_SMTP_PASS !== '') {
        $pass = (string) SSBD_SMTP_PASS;
    }

    $settings = array(
        'host'       => 'smtp.hostinger.com',
        'port'       => 587,
        'encryption' => 'tls',
        'user'       => 'contact@safestorebd.com',
        'from'       => 'contact@safestorebd.com',
        'from_name'  => 'SafestoreBD',
        'pass'       => $pass,
    );

    /**
     * Filter SafeStoreBD SMTP settings.
     *
     * @param array $settings SMTP settings.
     */
    return (array) apply_filters('ssbd_smtp_settings', $settings);
}

/**
 * Force a consistent From address for store email.
 *
 * @param string $from From email.
 * @return string
 */
function ssbd_mail_from($from) {
    $settings = ssbd_smtp_settings();
    return !empty($settings['from']) ? $settings['from'] : $from;
}
add_filter('wp_mail_from', 'ssbd_mail_from', 1000);

/**
 * Force a consistent From name for store email.
 *
 * @param string $from_name From name.
 * @return string
 */
function ssbd_mail_from_name($from_name) {
    $settings = ssbd_smtp_settings();
    return !empty($settings['from_name']) ? $settings['from_name'] : $from_name;
}
add_filter('wp_mail_from_name', 'ssbd_mail_from_name', 1000);

/**
 * Apply Hostinger SMTP after WP Mail SMTP configures PHPMailer.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer instance.
 */
function ssbd_configure_phpmailer($phpmailer) {
    $settings = ssbd_smtp_settings();

    if ($settings['pass'] === '') {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $settings['host'];
    $phpmailer->Port       = (int) $settings['port'];
    $phpmailer->SMTPSecure = $settings['encryption'];
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = $settings['user'];
    $phpmailer->Password   = $settings['pass'];
    $phpmailer->From       = $settings['from'];
    $phpmailer->FromName   = $settings['from_name'];
    $phpmailer->Sender     = $settings['from'];
}
add_action('phpmailer_init', 'ssbd_configure_phpmailer', 1000);
