<?php
/**
 * The base configuration for WordPress
 */

// Helper function to resolve configuration variables
function get_config_var($key, $default = '') {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    return $default;
}

$is_local = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
    || (php_sapi_name() === 'cli' && (!getenv('WORDPRESS_DB_HOST') || getenv('WORDPRESS_DB_HOST') === 'localhost'));

// ** Database settings ** //
define( 'DB_NAME', get_config_var('WORDPRESS_DB_NAME', 'financial') );
define( 'DB_USER', get_config_var('WORDPRESS_DB_USER', $is_local ? 'root' : 'financial_user') );
define( 'DB_PASSWORD', get_config_var('WORDPRESS_DB_PASSWORD', $is_local ? '' : 'financial_secure_password_2026') );
define( 'DB_HOST', get_config_var('WORDPRESS_DB_HOST', $is_local ? 'localhost' : 'db:3306') );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 */
define( 'AUTH_KEY',         get_config_var('WORDPRESS_AUTH_KEY', 'a8F9z!pL0#xK2$mN5&vQ8*wE1@rT4%yU') );
define( 'SECURE_AUTH_KEY',  get_config_var('WORDPRESS_SECURE_AUTH_KEY', 'b9G0a@qM1$yL3%nO6*wR9(xF2#sU5^zV') );
define( 'LOGGED_IN_KEY',    get_config_var('WORDPRESS_LOGGED_IN_KEY', 'c0H1b#rN2%zM4^pO7(xS0)yG3$tV6&wW') );
define( 'NONCE_KEY',        get_config_var('WORDPRESS_NONCE_KEY', 'd1I2c$sO3^aN5&qP8)yT1*zH4%uW7*xX') );
define( 'AUTH_SALT',        get_config_var('WORDPRESS_AUTH_SALT', 'e2J3d%tP4&bO6*rQ9*zU2(aI5^vX8(yY') );
define( 'SECURE_AUTH_SALT', get_config_var('WORDPRESS_SECURE_AUTH_SALT', 'f3K4e&uQ5*cP7(sR0(aV3)bJ6&wY9)zZ') );
define( 'LOGGED_IN_SALT',   get_config_var('WORDPRESS_LOGGED_IN_SALT', 'g4L5f*vR6(dQ8)tS1)bW4*cK7*xZ0*aA') );
define( 'NONCE_SALT',       get_config_var('WORDPRESS_NONCE_SALT', 'h5M6g(wS7)eR9*uT2*cX5(dL8(yA1(bB') );

/**#@-*/

/**
 * WordPress database table prefix.
 */
$table_prefix = get_config_var('WORDPRESS_TABLE_PREFIX', 'wp_');

/**
 * For developers: WordPress debugging mode.
 */
define( 'WP_DEBUG', false );

/* Reverse proxy header support for SSL behind Coolify/Traefik */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
    $_SERVER['HTTPS'] = 'on';
}

/* Dynamic site URL for seamless local & live server switching */
if ( isset( $_SERVER['HTTP_HOST'] ) ) {
    $is_ssl = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' )
        || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' )
        || ( isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] == 443 );
    $proto = $is_ssl ? 'https://' : 'http://';
    if ( ! defined( 'WP_HOME' ) ) {
        define( 'WP_HOME', $proto . $_SERVER['HTTP_HOST'] );
    }
    if ( ! defined( 'WP_SITEURL' ) ) {
        define( 'WP_SITEURL', $proto . $_SERVER['HTTP_HOST'] );
    }
}

/** Auto-seed database from sql/init.sql if tables are not yet initialized **/
if ( defined('DB_HOST') && defined('DB_USER') && defined('DB_PASSWORD') && defined('DB_NAME') ) {
    try {
        $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ( ! $mysqli->connect_error ) {
            $check = $mysqli->query("SHOW TABLES LIKE '{$table_prefix}posts'");
            if ( $check && $check->num_rows === 0 && file_exists(__DIR__ . '/sql/init.sql') ) {
                $sqlContent = file_get_contents(__DIR__ . '/sql/init.sql');
                if ( ! empty($sqlContent) ) {
                    $mysqli->multi_query($sqlContent);
                    while ( $mysqli->more_results() && $mysqli->next_result() ) { ; }
                }
            }
            $mysqli->close();
        }
    } catch ( \Throwable $e ) {
        // Silently continue
    }
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';


