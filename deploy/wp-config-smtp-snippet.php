<?php
/**
 * Paste into the server wp-config.php ABOVE the line:
 * That's all, stop editing! Happy publishing.
 *
 * This file is documentation only — WordPress does not load it from the theme.
 * Hostinger Git theme deploys do not overwrite wp-config.php.
 *
 * Use a Hostinger Email app password for contact@safestorebd.com
 * (not the hPanel login password).
 */

define('WPMS_ON', true);
define('WPMS_MAILER', 'smtp');
define('WPMS_SMTP_HOST', 'smtp.hostinger.com');
define('WPMS_SMTP_PORT', 587);
define('WPMS_SSL', 'tls');
define('WPMS_SMTP_AUTH', true);
define('WPMS_SMTP_AUTOTLS', true);
define('WPMS_SMTP_USER', 'contact@safestorebd.com');
define('WPMS_SMTP_PASS', 'REPLACE_WITH_HOSTINGER_APP_PASSWORD');
define('WPMS_MAIL_FROM', 'contact@safestorebd.com');
define('WPMS_MAIL_FROM_FORCE', true);
define('WPMS_MAIL_FROM_NAME', 'SafestoreBD');
define('WPMS_MAIL_FROM_NAME_FORCE', true);
