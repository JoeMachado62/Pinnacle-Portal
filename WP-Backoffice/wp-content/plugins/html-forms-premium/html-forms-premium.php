<?php
/*
Plugin Name: HTML Forms Premium
Plugin URI: https://www.htmlformsplugin.com/premium/
Description: Contains all premium functionality for HTML Forms.
Version: 1.5.1
Author: HTML Forms
Author URI: https://htmlformsplugin.com/
License: GPL v3
Text Domain: html-forms-premium

HTML Forms
Copyright (C) 2017-2024, Link Software LLC, support@linksoftwarellc.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


// Prevent direct file access
defined( 'ABSPATH' ) or exit;


/**
 * Loads the various premium add-on plugins
 *
 * @ignore
 */
function _hf_premium_bootstrap() {
    // don't run if HTML Forms itself is not activated
    if ( ! defined( 'HTML_FORMS_VERSION' ) ) {
        add_action( 'admin_notices', function() {
           echo '<div class="notice notice-error"><p>'. sprintf(__( 'You need to install and activate <a href="%s">HTML Forms</a> in order to use HTML Forms Premium.', 'html-forms-premium'), admin_url('plugin-install.php?s=html+forms+ibericode&tab=search&type=term')) .'</p></div>';
        });
        return;
    }

	// Define some useful constants
	define( 'HF_PREMIUM_VERSION', '1.5.1' );
	define( 'HF_PREMIUM_PLUGIN_FILE', __FILE__ );
    define( 'HF_STORE_URL', 'https://htmlformsplugin.com' );
    define( 'HF_STORE_PRODUCT_ID', 300 );

    require __DIR__ . '/data-exporter/data-exporter.php';
	require __DIR__ . '/data-management/data-management.php';
	require __DIR__ . '/webhooks/webhooks.php';
	require __DIR__ . '/file-upload/file-upload.php';
	require __DIR__ . '/notifications/notifications.php';
	require __DIR__ . '/submission-limit/submission-limit.php';
	require __DIR__ . '/require-user-logged-in/require-user-logged-in.php';
	require __DIR__ . '/dashboard-widget/dashboard-widget.php';
	require __DIR__ . '/licensing/licensing.php';
    if (_hf_license_system() === 'edd') {
        require __DIR__ . '/licensing/class-edd-plugin-updater.php';
        add_action('admin_init', '_hf_edd_updater');
    }
}

function _hf_load_license() {
    $plugin_basename = plugin_basename( HF_PREMIUM_PLUGIN_FILE );
    $network_activated = false;

    if (is_multisite() ) {
        if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }

        $network_activated = is_plugin_active_for_network( $plugin_basename );
    }

    $defaults = array( 'activated' => false, 'key' => '', 'token' => '', 'system' => '' );
    $values = $network_activated ? get_site_option( 'html-forms-premium_license', array() ) : get_option( 'html-forms-premium_license', array() );
    $license = (object) array_merge( $defaults, $values );

    if ($license->system == '') {
        $license->system = _hf_license_system($license);
    }

    return $license;
}

function _hf_license_system($license = null) {
	if ($license === null) {
        $license = _hf_load_license();
    }

    if (!isset($license->key)) {
        return '';
    }

    if (preg_match('/^[^-]{1,7}-[^-]{1,7}-[^-]{1,7}-[^-]{1,7}$/', $license->key)) {
        return 'my';
    } else {
        return 'edd';
    }
}

function _hf_edd_updater() {
    $license = _hf_load_license();

    if (isset($license->activated) && $license->activated) {
        $edd_plugin_updater = new EDD_Plugin_Updater(
            HF_STORE_URL,
            HF_PREMIUM_PLUGIN_FILE,
            [
                'version' => HF_PREMIUM_VERSION,
                'license' => $license->key,
                'item_id' => HF_STORE_PRODUCT_ID,
                'author' => 'HTML Forms',
            ]
        );
    }
}

// Only bootstrap on PHP 5.3 and later
if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
	add_action( 'plugins_loaded', '_hf_premium_bootstrap', 30 );
}

