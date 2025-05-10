<?php
/**
 * Plugin Name: Pinnacle Auto Finance Core
 * Description: Core functionality for the Pinnacle Auto Finance Dealer Portal.
 * Version: 1.0.0
 * Author: AI Developer
 * Text Domain: paf-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'PAF_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAF_CORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include plugin files - REVISED ORDER
require_once PAF_CORE_PLUGIN_DIR . 'includes/helper-functions.php'; // MOVED UP
require_once PAF_CORE_PLUGIN_DIR . 'includes/encryption-functions.php'; // MOVED UP
require_once PAF_CORE_PLUGIN_DIR . 'includes/cpt-definitions.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/ultimate-member-integration.php'; // Now helper-functions is included before
require_once PAF_CORE_PLUGIN_DIR . 'includes/frontend-forms.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/frontend-views.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/rest-api-endpoints.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/action-scheduler-jobs.php';

// Activation and Deactivation Hooks
function paf_core_activate() {
    // Register CPTs to flush rewrite rules
    paf_core_register_cpts();
    flush_rewrite_rules();

    // Add default roles and capabilities if needed (Ultimate Member might handle this)
    // Example: add_role('dealer', 'Dealer', array('read' => true, 'edit_posts' => false));
}
register_activation_hook( __FILE__, 'paf_core_activate' );

function paf_core_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'paf_core_deactivate' );

// Enqueue scripts and styles
function paf_core_enqueue_scripts() {
    // Frontend credit app form JS (only on specific pages if possible)
    if ( is_page_template('page-templates/credit-application-form.php') || has_shortcode(get_the_content(), 'paf_credit_application_form') ) { // Adjust condition
        wp_enqueue_script(
            'paf-frontend-credit-app-js',
            PAF_CORE_PLUGIN_URL . 'assets/js/paf-frontend-credit-app.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );
        wp_localize_script('paf-frontend-credit-app-js', 'paf_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ), // If using AJAX for parts of the form
            'nonce'    => wp_create_nonce( 'paf_app_submit' ) // Re-use for main form submission if not using admin-post
        ));
    }

    wp_enqueue_style(
        'paf-frontend-css',
        PAF_CORE_PLUGIN_URL . 'assets/css/paf-frontend.css',
        array(),
        '1.0.0'
    );
}
add_action( 'wp_enqueue_scripts', 'paf_core_enqueue_scripts' );

// Initialize plugin components
add_action( 'init', 'paf_core_register_cpts' );
add_action( 'init', 'paf_core_register_um_hooks' );
add_action( 'init', 'paf_core_register_form_actions_and_shortcodes' );
add_action( 'init', 'paf_core_register_view_shortcodes' );
add_action( 'rest_api_init', 'paf_core_register_rest_routes' );
add_action( 'init', 'paf_core_register_action_scheduler_hooks'); // For job processing

// Add settings page for API key and Local Client Webhook URL
function paf_core_register_settings() {
    add_option('paf_local_client_webhook_url', '');
    add_option('paf_local_client_api_key', ''); // This is for WP to call local client
    add_option('paf_wp_api_shared_secret', ''); // This is for local client to call WP

    register_setting('paf_options_group', 'paf_local_client_webhook_url', 'sanitize_text_field');
    register_setting('paf_options_group', 'paf_local_client_api_key', 'sanitize_text_field');
    register_setting('paf_options_group', 'paf_wp_api_shared_secret', 'sanitize_text_field');
}
add_action('admin_init', 'paf_core_register_settings');

function paf_core_settings_page() {
    add_options_page(
        'Pinnacle Auto Finance Settings',
        'Pinnacle Auto',
        'manage_options',
        'paf-core-settings',
        'paf_core_render_settings_page'
    );
}
add_action('admin_menu', 'paf_core_settings_page');

function paf_core_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Pinnacle Auto Finance Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('paf_options_group');
            do_settings_sections('paf_options_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Local Client Webhook URL</th>
                    <td><input type="text" name="paf_local_client_webhook_url" class="regular-text" value="<?php echo esc_attr(get_option('paf_local_client_webhook_url')); ?>" />
                    <p class="description">The URL on the local client that WordPress will notify for new jobs (e.g., http://local-client-ip:3000/new-job-notification).</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row">API Key for Local Client (WP Calls Local)</th>
                    <td><input type="text" name="paf_local_client_api_key" class="regular-text" value="<?php echo esc_attr(get_option('paf_local_client_api_key')); ?>" />
                    <p class="description">API Key WordPress uses to authenticate with the local client's webhook.</p></td>
                </tr>
                 <tr valign="top">
                    <th scope="row">Shared Secret for WordPress API (Local Calls WP)</th>
                    <td><input type="text" name="paf_wp_api_shared_secret" class="regular-text" value="<?php echo esc_attr(get_option('paf_wp_api_shared_secret')); ?>" />
                    <p class="description">A shared secret key the local client uses to authenticate with the WordPress REST API. This will be checked by the permission_callback.</p></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Generate a unique PAF ID for dealers or other entities if needed.
 */
function paf_generate_unique_id( $prefix = 'PAF_DLR_' ) {
    return uniqid( $prefix );
}

?>