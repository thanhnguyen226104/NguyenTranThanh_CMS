<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress_nguyentranthanh' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'YB 21#Hul(#4^(!#q651pCEr*&(H:k#Em7DJ9mn]=5PQ0.X&EE7D)1IO3d;u?8:J' );
define( 'SECURE_AUTH_KEY',  '(Mp#3>,:{*7jE;XG]*}*I#IGZMR/UG=)K~jQ-iVO^0N2jPcWb8&K95cYsfV(jJ$;' );
define( 'LOGGED_IN_KEY',    'C%LX*@|mM8~v, -e|ON/PB.%nS)>ae-[.;feywU<][U+ i0x,QO*R&@3T_Xk0c_y' );
define( 'NONCE_KEY',        'S/U@c,[YjU4lA4XdXl<dt%SK(h[77p4w^c62C/DVJJSU BVJN!g${~Jra&zr&Q=@' );
define( 'AUTH_SALT',        'q_e=Fi75[9>shHzut[0Wk,qkQCOPh:_UQ#t=O$f ^plg~WV +|2H=pYa_aX(loH*' );
define( 'SECURE_AUTH_SALT', '+4z-_{8_0(yNPx@gm|W(I&K&{nA17sQ=u&To>3N-NCjHBH;<>y6#`Xit#]nc;K}&' );
define( 'LOGGED_IN_SALT',   '59wy,:p!xcRX# U9m~0u_[3~9+QK-xg5paL6DM5qju@DF:}O?GXlEX/5Y(H)$n1s' );
define( 'NONCE_SALT',       'Q)i]Js?pU:yD|y-^P.BPH*:E P)xM-oiK@Hr}w2lXA~wA9P% 0-XpB$D.*+rR.)]' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
