<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'testsitedb' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '{kdI1@I0rlZ|VdVSbw$7.UFZh*3|!D*uu9/LB+;P]7$)4U=qdN{QK/l*<~M^>=95' );
define( 'SECURE_AUTH_KEY',  'f>WPDX3@mL9YX^=!SY$q7`vOQs=e*d$[i&Y(Ob$ZYE4}+HtU9TjsvDK_57?:rM[@' );
define( 'LOGGED_IN_KEY',    'BrX)gK>{ZB,n5=m(HZ0:NvWrA#CE4n3#0b~]xskT>;=r6eqtP,mF8R;2@Y.`>e&]' );
define( 'NONCE_KEY',        'DNJ<PH~6-ymZd6[S=%><KYNgR7Jy;An{X@oJY$<lq~OSh[NsJ;9!y[FKm*2KhP]l' );
define( 'AUTH_SALT',        'G~H576o#c7Wztqm^b4F,&]tTo~*wHI?kZ/ff+?x%zxpG5FqF2ox,V?hr)ez8,3&$' );
define( 'SECURE_AUTH_SALT', '?&o(q^|qgnhtZJqaE_jmBR&{%^7q<JKt(%W<X`PZgwC97wX4E*/,+$y{dJ3;;VZ&' );
define( 'LOGGED_IN_SALT',   '7t>7f_.c#_H@+E}uVL/{p[*3rx7)2wua4Q.E2<=g^Jt,BX(H6e.T2h6?:R}8~5]=' );
define( 'NONCE_SALT',       '(9pjH^/57*@5K$l%FF2]VM)xTCRzk]r=&0rrb?(67GkyFv$/tAuBw>&](B13Y)rX' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
