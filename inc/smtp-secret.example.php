<?php
/**
 * Example local SMTP secret (do not commit the real file).
 *
 * Copy to inc/smtp-secret.php and set your Hostinger mailbox app password.
 * inc/smtp-secret.php is gitignored.
 *
 * Prefer defining WPMS_SMTP_PASS in wp-config.php on the server instead
 * (see deploy/wp-config-smtp-snippet.php).
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('SSBD_SMTP_PASS')) {
    define('SSBD_SMTP_PASS', 'REPLACE_WITH_HOSTINGER_APP_PASSWORD');
}
