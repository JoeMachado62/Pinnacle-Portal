<?php
/**
 * Plugin Name: Pinnacle Auto Finance Core
 * Description: Core functionality for the Pinnacle Auto Finance Dealer Portal.
 * Version: 1.0.0
 * Author: Joe Machado
 * Author URI: https://www.linkedin.com/in/joemachado/
 * Text Domain: paf-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'PAF_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAF_CORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include plugin files - REVISED ORDER
require_once PAF_CORE_PLUGIN_DIR . 'includes/helper-functions.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/encryption-functions.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/cpt-definitions.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/acf-fields.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/ultimate-member-integration.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/frontend-forms.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/frontend-views.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/dashboard-partials.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/dashboard-ajax.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/advertising-management.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/rest-api-endpoints.php';
require_once PAF_CORE_PLUGIN_DIR . 'includes/action-scheduler-jobs.php';

// Activation and Deactivation Hooks
function paf_core_activate() {
    // Register CPTs to flush rewrite rules
    paf_core_register_cpts();
    flush_rewrite_rules();

    // --- CORRECTED CAPABILITY ASSIGNMENT SECTION ---
    // First check if UM roles exist, if not create a custom role
    if (function_exists('um_get_role')) {
        $dealer_role_slug = 'um_dealer';
    } else {
        // If UM not active, try to use a standard role
        $dealer_role_slug = 'dealer';
        // Create the role if it doesn't exist
        if (!get_role($dealer_role_slug)) {
            add_role($dealer_role_slug, 'Dealer', array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            ));
        }
    }
    
    $dealer_role = get_role($dealer_role_slug);
    if ($dealer_role) {
        $dealer_role->add_cap('paf_submit_credit_application', true);
        $dealer_role->add_cap('paf_view_dealer_dashboard', true);
        // $dealer_role->add_cap('read_paf_deal', true); // Example if using map_meta_cap for paf_deal viewing
    } else {
        error_log("PAF Core Activation: Could not find the role '{$dealer_role_slug}' to assign capabilities.");
    }

    // Example for another role, e.g., 'dealer_services_manager'
    $manager_role_slug = 'um_dealer-services-manager'; // REPLACE with actual slug if you have this role
    $manager_role = get_role($manager_role_slug);
    if ($manager_role) {
        $manager_role->add_cap('paf_submit_credit_application', true);
        $manager_role->add_cap('paf_view_dealer_dashboard', true);
    } else {
        error_log("PAF Core Activation: Could not find the role '{$manager_role_slug}' to assign capabilities.");
    }

    // Add capabilities to administrator role
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('paf_submit_credit_application', true);
        $admin_role->add_cap('paf_view_dealer_dashboard', true);
        $admin_role->add_cap('manage_paf_dealers', true); // Special cap for admins to manage dealers
    }
    
    // --- END OF CORRECTED CAPABILITY ASSIGNMENT SECTION ---
}
register_activation_hook( __FILE__, 'paf_core_activate' );

function paf_core_deactivate() {
    flush_rewrite_rules();
    // Optionally remove capabilities here if desired, though often not necessary
    // $dealer_role = get_role('dealer');
    // if ($dealer_role) {
    //     $dealer_role->remove_cap('paf_submit_credit_application');
    //     $dealer_role->remove_cap('paf_view_dealer_dashboard');
    // }
}
register_deactivation_hook( __FILE__, 'paf_core_deactivate' );

// Enqueue scripts and styles
function paf_core_enqueue_scripts() {
    // Check if we are on a page that uses the credit application form shortcode
    // or a specific page template if you create one for the form.
    global $post;
    $is_credit_app_page = false;
    $is_dashboard_page = false;
    
    if ( $post && has_shortcode( $post->post_content, 'paf_credit_application_form' ) ) {
        $is_credit_app_page = true;
    }
    
    if ( $post && has_shortcode( $post->post_content, 'paf_dealer_dashboard' ) ) {
        $is_dashboard_page = true;
    }
    
    // Add other conditions if the form is loaded in other ways, e.g., via a page template:
    // if (is_page_template('your-credit-app-template.php')) $is_credit_app_page = true;

    if ( $is_credit_app_page ) {
        wp_enqueue_script(
            'paf-frontend-credit-app-js',
            PAF_CORE_PLUGIN_URL . 'assets/js/paf-frontend-credit-app.js',
            array( 'jquery' ),
            '1.0.1', // Cache buster
            true
        );
        // If your JS file needs localized data (like AJAX URL or nonces FOR THE JS FILE)
        // The nonce for the form submission itself is handled by wp_nonce_field() in the form.
        // wp_localize_script('paf-frontend-credit-app-js', 'paf_ajax_object', array(
        //     'ajax_url' => admin_url( 'admin-ajax.php' )
        // ));
    }

    // Register dashboard assets
    wp_register_style(
        'paf-dashboard-css',
        PAF_CORE_PLUGIN_URL . 'assets/css/paf-dashboard.css',
        array(),
        '1.0.1' // Cache buster
    );
    
    wp_register_script(
        'paf-dashboard-js',
        PAF_CORE_PLUGIN_URL . 'assets/js/paf-dashboard.js',
        array( 'jquery' ),
        '1.0.1', // Cache buster
        true
    );
    
    // Enqueue dashboard assets if needed
    if ( $is_dashboard_page ) {
        wp_enqueue_style( 'paf-dashboard-css' );
        wp_enqueue_script( 'paf-dashboard-js' );
        wp_enqueue_style( 'dashicons' ); // Ensure dashicons are available
        
        // Localize script for AJAX
        wp_localize_script( 'paf-dashboard-js', 'paf_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'paf_dashboard_nonce' )
        ));
    }

    wp_enqueue_style(
        'paf-frontend-css',
        PAF_CORE_PLUGIN_URL . 'assets/css/paf-frontend.css',
        array(),
        '1.0.1' // Cache buster
    );
    
    // Enqueue UM form styling (for registration forms)
    wp_enqueue_style(
        'paf-um-form-styling',
        PAF_CORE_PLUGIN_URL . 'assets/css/um-form-styling.css',
        array(),
        '1.0.1' // Cache buster
    );
}
add_action( 'wp_enqueue_scripts', 'paf_core_enqueue_scripts' );

// Initialize plugin components
add_action( 'init', 'paf_core_register_cpts' );
add_action( 'init', 'paf_core_register_um_hooks' );
add_action( 'init', 'paf_core_register_form_actions_and_shortcodes' );
add_action( 'init', 'paf_core_register_view_shortcodes' );
add_action( 'rest_api_init', 'paf_core_register_rest_routes' );
add_action( 'init', 'paf_core_register_action_scheduler_hooks');

// Add settings page for API key and Local Client Webhook URL
function paf_core_register_settings() {
    // --- THESE ARE THE CORRECT OPTION NAMES ---
    add_option('paf_local_client_webhook_url', '');
    add_option('paf_local_client_api_key', ''); 
    add_option('paf_wp_api_shared_secret', ''); 

    register_setting('paf_options_group', 'paf_local_client_webhook_url', 'sanitize_text_field');
    register_setting('paf_options_group', 'paf_local_client_api_key', 'sanitize_text_field');
    register_setting('paf_options_group', 'paf_wp_api_shared_secret', 'sanitize_text_field');
    // --- END OF CORRECT OPTION NAMES ---
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
            // do_settings_sections('paf_options_group'); // Not needed for this simple layout
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="id_paf_local_client_webhook_url">Local Client Webhook URL</label></th>
                    <td><input type="text" id="id_paf_local_client_webhook_url" name="paf_local_client_webhook_url" class="regular-text" value="<?php echo esc_attr(get_option('paf_local_client_webhook_url')); ?>" />
                    <p class="description">The URL on the local client that WordPress will notify for new jobs (e.g., http://local-client-ip:3000/new-job-notification).</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="id_paf_local_client_api_key">API Key for Local Client (WP Calls Local)</label></th>
                    <td><input type="text" id="id_paf_local_client_api_key" name="paf_local_client_api_key" class="regular-text" value="<?php echo esc_attr(get_option('paf_local_client_api_key')); ?>" />
                    <p class="description">API Key WordPress uses to authenticate with the local client's webhook.</p></td>
                </tr>
                 <tr valign="top">
                    <th scope="row"><label for="id_paf_wp_api_shared_secret">Shared Secret for WordPress API (Local Calls WP)</label></th>
                    <td><input type="text" id="id_paf_wp_api_shared_secret" name="paf_wp_api_shared_secret" class="regular-text" value="<?php echo esc_attr(get_option('paf_wp_api_shared_secret')); ?>" />
                    <p class="description">A shared secret key the local client uses to authenticate with the WordPress REST API. This will be checked by the permission_callback.</p></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Adds a metabox for dealer information in the admin edit screen
 */
function paf_add_dealer_details_metabox() {
    add_meta_box(
        'paf_dealer_details_metabox',
        'Dealer Information',
        'paf_render_dealer_details_metabox',
        'paf_dealer',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'paf_add_dealer_details_metabox');

/**
 * Render the dealer details metabox
 */
function paf_render_dealer_details_metabox($post) {
    // Get the dealer data
    $meta_fields = array(
        '_dealership_legal_name' => 'Legal Name',
        '_federal_tax_id' => 'Federal Tax ID (EIN)',
        '_owner_full_name' => 'Owner Name',
        '_dealer_license_num' => 'Dealer License #',
        '_dealership_phone' => 'Phone',
        '_fax' => 'Fax',
        '_address' => 'Address',
        '_city' => 'City',
        '_state' => 'State',
        '_zip' => 'ZIP',
        '_contact_for_approval_or_counter' => 'Approval Contact',
        '_contact_cell_phone' => 'Contact Cell',
        '_email_for_approvals' => 'Email for Approvals'
    );
    
    $associated_user_id = get_post_meta($post->ID, '_associated_user_id', true);
    $user = $associated_user_id ? get_userdata($associated_user_id) : null;
    $status = get_post_meta($post->ID, '_status', true);
    $approval_date = get_post_meta($post->ID, '_approval_date', true);
    $approved_by_id = get_post_meta($post->ID, '_approved_by', true);
    $approved_by = $approved_by_id ? get_userdata($approved_by_id) : null;
    
    echo '<style>
        .paf-dealer-details-table { width: 100%; border-collapse: collapse; }
        .paf-dealer-details-table th, .paf-dealer-details-table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .paf-dealer-details-table th { width: 200px; }
        .paf-status-pending_approval { color: #f39c12; font-weight: bold; }
        .paf-status-approved { color: #27ae60; font-weight: bold; }
        .paf-status-suspended { color: #e74c3c; font-weight: bold; }
        .paf-status-inactive { color: #7f8c8d; font-weight: bold; }
        .paf-user-link { text-decoration: none; }
    </style>';
    
    echo '<table class="paf-dealer-details-table">';
    
    // Status information
    echo '<tr><th>Status:</th><td><span class="paf-status-' . esc_attr($status) . '">' . esc_html(ucfirst(str_replace('_', ' ', $status))) . '</span></td></tr>';
    
    // Associated user
    if ($user) {
        echo '<tr><th>WP User:</th><td><a href="' . esc_url(admin_url('user-edit.php?user_id=' . $associated_user_id)) . '" class="paf-user-link">' . esc_html($user->user_login) . ' (' . esc_html($user->user_email) . ')</a></td></tr>';
    } else {
        echo '<tr><th>WP User:</th><td>No user associated</td></tr>';
    }
    
    // Approval info if approved
    if ($status === 'approved' && $approval_date) {
        echo '<tr><th>Approved On:</th><td>' . esc_html($approval_date) . '</td></tr>';
        if ($approved_by) {
            echo '<tr><th>Approved By:</th><td>' . esc_html($approved_by->display_name) . '</td></tr>';
        }
    }
    
    // Display all dealer meta data
    foreach ($meta_fields as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        if (!empty($value)) {
            echo '<tr><th>' . esc_html($label) . ':</th><td>' . esc_html($value) . '</td></tr>';
        }
    }
    
    echo '</table>';
    
    // Add direct approval button if pending
    if ($status === 'pending_approval' && $associated_user_id) {
        echo '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">';
        echo '<h3>Direct Approval</h3>';
        echo '<p>Use this button to directly approve this dealer account. This will bypass the ACF field and directly update user capabilities.</p>';
        echo '<form method="post">';
        echo wp_nonce_field('paf_direct_dealer_approval', 'paf_direct_approval_nonce', true, false);
        echo '<input type="hidden" name="dealer_id" value="' . esc_attr($post->ID) . '">';
        echo '<input type="submit" name="paf_approve_dealer" class="button button-primary button-large" value="Approve This Dealer">';
        echo ' <span class="description">This will immediately grant the dealer access to the dashboard and send an email notification.</span>';
        echo '</form>';
        echo '</div>';
    }
}

/**
 * Handle direct approval from dealer detail page and dashboard widget
 */
function paf_handle_direct_dealer_approval() {
    // Check if this is a direct approval action
    if (isset($_POST['paf_approve_dealer']) && isset($_POST['dealer_id']) && 
        (isset($_POST['paf_direct_approval_nonce']) && wp_verify_nonce($_POST['paf_direct_approval_nonce'], 'paf_direct_dealer_approval')) && 
        current_user_can('manage_paf_dealers')) {
        
        $dealer_id = intval($_POST['dealer_id']);
        
        // Verify this is a valid dealer CPT
        if (get_post_type($dealer_id) === 'paf_dealer') {
            // Get the current status
            $current_status = get_post_meta($dealer_id, '_status', true);
            error_log("PAF DIRECT APPROVAL: Processing dealer {$dealer_id}. Current status: {$current_status}");
            
            // Update status
            update_post_meta($dealer_id, '_previous_status', $current_status);
            update_post_meta($dealer_id, '_status', 'approved');
            
            // Get the user ID
            $user_id = get_post_meta($dealer_id, '_associated_user_id', true);
            if ($user_id) {
                // Update user capabilities directly
                $user = new WP_User($user_id);
                $user->add_cap('paf_view_dealer_dashboard', true);
                $user->add_cap('paf_submit_credit_application', true);
                
                error_log("PAF DIRECT APPROVAL: Added capabilities to user {$user_id} for dealer {$dealer_id}");
                
                // Notify the user
                $user_email = $user->user_email;
                $subject = 'Your Dealer Account has been Approved';
                $message = "Congratulations! Your dealer account on " . get_bloginfo('name') . " has been approved. You can now access the full dealer dashboard features.";
                
                wp_mail($user_email, $subject, $message);
                
                // Log approval details
                update_post_meta($dealer_id, '_approval_date', current_time('mysql'));
                update_post_meta($dealer_id, '_approved_by', get_current_user_id());
                
                // Add an admin notice
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>Dealer approved successfully! User notified via email.</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>Error: Could not find associated user for this dealer.</p></div>';
                });
            }
        }
    }
}
add_action('admin_init', 'paf_handle_direct_dealer_approval');

/**
 * Adds a dashboard widget for pending dealer approvals with direct approval functionality
 */
function paf_add_admin_dashboard_widget() {
    if (current_user_can('manage_paf_dealers')) {
        wp_add_dashboard_widget(
            'paf_pending_dealers_widget',
            'Pending Dealer Approvals',
            'paf_pending_dealers_widget_callback'
        );
    }
}
add_action('wp_dashboard_setup', 'paf_add_admin_dashboard_widget');

/**
 * Callback for the dashboard widget
 */
function paf_pending_dealers_widget_callback() {
    // Check if we're approving a dealer
    if (isset($_POST['widget_approve_dealer']) && isset($_POST['dealer_id']) && 
        isset($_POST['widget_approval_nonce']) && wp_verify_nonce($_POST['widget_approval_nonce'], 'paf_widget_dealer_approval') &&
        current_user_can('manage_paf_dealers')) {
        
        $dealer_id = intval($_POST['dealer_id']);
        
        // Verify this is a valid dealer CPT
        if (get_post_type($dealer_id) === 'paf_dealer') {
            // Get the current status
            $current_status = get_post_meta($dealer_id, '_status', true);
            error_log("PAF ADMIN WIDGET: Processing direct approval for dealer {$dealer_id}. Current status: {$current_status}");
            
            // Update to approved
            update_post_meta($dealer_id, '_previous_status', $current_status);
            update_post_meta($dealer_id, '_status', 'approved');
            
            // Get the user ID
            $user_id = get_post_meta($dealer_id, '_associated_user_id', true);
            if ($user_id) {
                // Update user capabilities directly
                $user = new WP_User($user_id);
                $user->add_cap('paf_view_dealer_dashboard', true);
                $user->add_cap('paf_submit_credit_application', true);
                
                error_log("PAF ADMIN WIDGET: Added capabilities to user {$user_id} for dealer {$dealer_id}");
                
                // Notify the user
                $user_email = $user->user_email;
                $subject = 'Your Dealer Account has been Approved';
                $message = "Congratulations! Your dealer account on " . get_bloginfo('name') . " has been approved. You can now access the full dealer dashboard features.";
                
                wp_mail($user_email, $subject, $message);
                
                // Log approval details
                update_post_meta($dealer_id, '_approval_date', current_time('mysql'));
                update_post_meta($dealer_id, '_approved_by', get_current_user_id());
                
                echo '<div class="notice notice-success"><p>Dealer approved successfully! User notified via email.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error: Could not find associated user for this dealer.</p></div>';
            }
        }
    }

    // Display pending dealers
    $pending_dealers = get_posts(array(
        'post_type' => 'paf_dealer',
        'posts_per_page' => 10,
        'meta_key' => '_status',
        'meta_value' => 'pending_approval'
    ));
    
    if (empty($pending_dealers)) {
        echo '<p>No dealers pending approval.</p>';
        return;
    }
    
    echo '<table class="widefat" style="margin-bottom: 15px;">';
    echo '<thead><tr><th>Dealer Name</th><th>Legal Name</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($pending_dealers as $dealer) {
        $legal_name = get_post_meta($dealer->ID, '_dealership_legal_name', true);
        echo '<tr>';
        echo '<td>' . esc_html($dealer->post_title) . '</td>';
        echo '<td>' . esc_html($legal_name) . '</td>';
        echo '<td>';
        echo '<form method="post" style="display:inline;">';
        echo wp_nonce_field('paf_widget_dealer_approval', 'widget_approval_nonce', true, false);
        echo '<input type="hidden" name="dealer_id" value="' . esc_attr($dealer->ID) . '">';
        echo '<input type="submit" name="widget_approve_dealer" class="button button-primary" value="Approve Now">';
        echo '</form>';
        echo ' <a href="' . esc_url(get_edit_post_link($dealer->ID)) . '" class="button">Edit</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    
    echo '<p><a href="' . admin_url('edit.php?post_type=paf_dealer&dealer_status=pending_approval') . '">View all pending dealers</a></p>';
}

// Moved paf_generate_unique_id to helper-functions.php
?>
