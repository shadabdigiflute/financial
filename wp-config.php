<?php
/**
 * The base configuration for WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', getenv('WORDPRESS_DB_NAME') ?: 'financial' );

/** Database username */
define( 'DB_USER', getenv('WORDPRESS_DB_USER') ?: 'root' );

/** Database password */
define( 'DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD') !== false ? getenv('WORDPRESS_DB_PASSWORD') : '' );

/** Database hostname */
define( 'DB_HOST', getenv('WORDPRESS_DB_HOST') ?: 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 */
define( 'AUTH_KEY',         getenv('WORDPRESS_AUTH_KEY') ?: 'a8F9z!pL0#xK2$mN5&vQ8*wE1@rT4%yU' );
define( 'SECURE_AUTH_KEY',  getenv('WORDPRESS_SECURE_AUTH_KEY') ?: 'b9G0a@qM1$yL3%nO6*wR9(xF2#sU5^zV' );
define( 'LOGGED_IN_KEY',    getenv('WORDPRESS_LOGGED_IN_KEY') ?: 'c0H1b#rN2%zM4^pO7(xS0)yG3$tV6&wW' );
define( 'NONCE_KEY',        getenv('WORDPRESS_NONCE_KEY') ?: 'd1I2c$sO3^aN5&qP8)yT1*zH4%uW7*xX' );
define( 'AUTH_SALT',        getenv('WORDPRESS_AUTH_SALT') ?: 'e2J3d%tP4&bO6*rQ9*zU2(aI5^vX8(yY' );
define( 'SECURE_AUTH_SALT', getenv('WORDPRESS_SECURE_AUTH_SALT') ?: 'f3K4e&uQ5*cP7(sR0(aV3)bJ6&wY9)zZ' );
define( 'LOGGED_IN_SALT',   getenv('WORDPRESS_LOGGED_IN_SALT') ?: 'g4L5f*vR6(dQ8)tS1)bW4*cK7*xZ0*aA' );
define( 'NONCE_SALT',       getenv('WORDPRESS_NONCE_SALT') ?: 'h5M6g(wS7)eR9*uT2*cX5(dL8(yA1(bB' );

/**#@-*/

/**
 * WordPress database table prefix.
 */
$table_prefix = getenv('WORDPRESS_TABLE_PREFIX') ?: 'wp_';

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

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

