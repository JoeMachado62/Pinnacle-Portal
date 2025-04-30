<?php
define( 'WP_CACHE', true );
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'wordpress' );

/** Database password */
define( 'DB_PASSWORD', 'P1nn@cle2025!' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          'b?!O!Uz(RG|[n+%0E :>[y(2Xw tP[r~sb v.tt 7x89]fVWKS[)Dwu,m#:aapX_' );
define( 'SECURE_AUTH_KEY',   'Wh(FO4v{ibw%Ow{0-c^M(Zl,(pFHgW=QZ89g6M`F@IrExH]0i$=0I /`Lb0DN:7?' );
define( 'LOGGED_IN_KEY',     'qhzxbU<-z5Qo/A%LY6W<ZEGwo(IaQ!>YmNAz5syfZy5b.&qM9tF(Xho<1NvXq^0E' );
define( 'NONCE_KEY',         '!eL1}J4Litm@Vf#k&et/?V`FNZX7bzoB.WV88|8nuB8;m*xuc_az{vSorfdA^4^7' );
define( 'AUTH_SALT',         'xUS~(:>7Q{}bxnU)0.or?jkJ6=EQ}erH`6jDPR<?Mg!]>Hzu[zl<B;T~e!4lIaK5' );
define( 'SECURE_AUTH_SALT',  '!:}Tif?).?hrz!5^!k#ov=yq!V{>0v`:~+UL]DdcvD9xHXNHtH<TITk 1-aL`QhG' );
define( 'LOGGED_IN_SALT',    ':: ~R)Yeu=<Lj-=/cfSe}#u)wY7(CKYj3g7PW5#%:OH5OtN$cCnGk9mb]0lY#-h}' );
define( 'NONCE_SALT',        '!XGw#C5MoFJ=T$JKp,!i-,xn1+hHyH:EZ?ftjg 3Tx!V~Om=/QXv0Il:3$W)w8.E' );
define( 'WP_CACHE_KEY_SALT', 'NuFchozh+!xt@g9Ruv@G{8EI#_SDDZ%KFbaXif(s`c/z*=i}6?pT1Fiz#lNl8^f6' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */

// Allow direct file operations without FTP
define('FS_METHOD', 'direct');


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
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

define( 'COOKIEHASH', '276ddaefeda5db90739fc4b398203160' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
