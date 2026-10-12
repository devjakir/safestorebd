<?php

if (!defined('ABSPATH')) {
    exit;
}

$ssbd_smtp_secret = get_template_directory() . '/inc/smtp-secret.php';
if (is_readable($ssbd_smtp_secret)) {
    require_once $ssbd_smtp_secret;
}

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

    return (array) apply_filters('ssbd_smtp_settings', $settings);
}

function ssbd_mail_from($from) {
    $settings = ssbd_smtp_settings();
    return !empty($settings['from']) ? $settings['from'] : $from;
}
add_filter('wp_mail_from', 'ssbd_mail_from', 1000);

function ssbd_mail_from_name($from_name) {
    $settings = ssbd_smtp_settings();
    return !empty($settings['from_name']) ? $settings['from_name'] : $from_name;
}
add_filter('wp_mail_from_name', 'ssbd_mail_from_name', 1000);

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
