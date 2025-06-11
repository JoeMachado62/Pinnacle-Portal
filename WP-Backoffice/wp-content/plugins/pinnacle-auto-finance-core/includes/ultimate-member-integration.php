<?php
// File: /root/Pinnacle-Portal/WP-Backoffice/wp-content/plugins/pinnacle-auto-finance-core/includes/ultimate-member-integration.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Debug function to log registration completion
function paf_debug_registration_complete($user_id, $args) {
    error_log("PAF DEBUG: UM registration complete for user ID: {$user_id}");
    $user = get_userdata($user_id);
    if ($user) {
        error_log("PAF DEBUG: User roles after registration: " . implode(', ', (array) $user->roles));
    }
}

// Enhanced function to add dealer fields to appropriate UM forms
function paf_add_dealer_registration_fields_filter( $fields_array ) {
    // Check if UM and its form property are available
    if ( ! function_exists('UM') || ! UM()->form() || ! isset(UM()->form()->form_data['form_id']) ) {
        return $fields_array;
    }

    $current_form_id = UM()->form()->form_data['form_id'];
    $current_form_mode = get_post_meta($current_form_id, '_um_mode', true);
    
    // Determine if we should add dealer fields
    $should_add_fields = false;
    
    // Add to any registration form
    if ($current_form_mode === 'register') {
        $should_add_fields = true;
        error_log("PAF UM FIELDS: Adding dealer fields to registration form ID: {$current_form_id}");
    }
    
    // Also add to specific profile forms (for editing dealer info)
    $dealer_profile_forms = [19, 62]; // Include both the default profile and dealer profile forms
    if (in_array($current_form_id, $dealer_profile_forms) && $current_form_mode === 'profile') {
        $should_add_fields = true;
        error_log("PAF UM FIELDS: Adding dealer fields to profile form ID: {$current_form_id}");
    }
    
    if ( $should_add_fields ) {
        
        // DEFINE $paf_dealer_fields HERE, INSIDE THE FUNCTION SCOPE
        $paf_dealer_fields = [
            'dealer_legal_name' => [
                'title' => 'Dealership Legal Name', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Dealership Legal Name', 'metakey' => 'dealer_legal_name',
                'help' => 'Must match your dealer license exactly'
            ],
            'dealer_federal_tax_id' => [
                'title' => 'Federal Tax ID (EIN)', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Federal Tax ID (EIN)', 'metakey' => 'dealer_federal_tax_id',
            ],
            'dealer_owner_full_name' => [ // This is for the Principal/Licensee
                'title' => 'Owner Full Name (Principal)', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Owner Full Name (Principal)', 'metakey' => 'dealer_owner_full_name',
            ],
            'dealer_phone' => [
                'title' => 'Dealership Phone', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Dealership Phone', 'metakey' => 'dealer_phone',
            ],
            'dealer_fax' => [
                'title' => 'Fax Number', 'type' => 'text', 'required' => 0, 'public' => 1, 'editable' => 1,
                'label' => 'Fax Number', 'metakey' => 'dealer_fax',
            ],
            'dealer_license_num' => [
                'title' => 'Dealer License #', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Dealer License #', 'metakey' => 'dealer_license_num',
            ],
            'dealer_address' => [
                'title' => 'Dealership Address', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Dealership Address', 'metakey' => 'dealer_address',
            ],
            'dealer_city' => [
                'title' => 'City', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'City', 'metakey' => 'dealer_city',
            ],
            'dealer_state' => [
                'title' => 'State', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'State', 'max_length' => 2, 'metakey' => 'dealer_state', // Consider a dropdown for states
            ],
            'dealer_zip' => [
                'title' => 'Zip Code', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Zip Code', 'metakey' => 'dealer_zip',
            ],
            'dealer_contact_for_approval' => [ // Name/Title of contact person
                'title' => 'Contact for Approval/Counter', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Contact for Approval/Counter', 'metakey' => 'dealer_contact_for_approval',
            ],
            'dealer_contact_cell_phone' => [
                'title' => 'Contact Cell Phone', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Contact Cell Phone', 'metakey' => 'dealer_contact_cell_phone',
            ],
            'dealer_email_for_approvals' => [
                'title' => 'Email for Approvals', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'validate' => 'email', 'label' => 'Email for Approvals', 'metakey' => 'dealer_email_for_approvals',
            ],
        ];
        // END OF $paf_dealer_fields DEFINITION

        // Add the custom fields to UM's field array for the current form
        // The array key for each field should be its metakey.
        foreach ($paf_dealer_fields as $metakey_as_key => $details) {
            if (!isset($fields_array[$details['metakey']])) { // Check if not already defined by UM UI
                $fields_array[$details['metakey']] = $details;
                error_log("PAF UM FIELDS: Added field '{$details['metakey']}' to form {$current_form_id}");
            }
        }
    } else {
        error_log("PAF UM FIELDS: Skipping field addition for form ID: {$current_form_id}, mode: {$current_form_mode}");
    }
    
    return $fields_array;
}

// Force add dealer fields to form during rendering - this ensures shortcode rendering works
function paf_force_add_dealer_fields_to_form( $args ) {
    if ( ! function_exists('UM') || ! UM()->form() ) {
        return;
    }
    
    // Check if we have form data
    if ( ! isset( $args['form_id'] ) ) {
        return;
    }
    
    $form_id = $args['form_id'];
    $form_mode = get_post_meta($form_id, '_um_mode', true);
    
    // Only add to registration forms or specific profile forms
    $should_add_fields = false;
    
    if ($form_mode === 'register') {
        $should_add_fields = true;
    }
    
    $dealer_profile_forms = [19, 62];
    if (in_array($form_id, $dealer_profile_forms) && $form_mode === 'profile') {
        $should_add_fields = true;
    }
    
    if ( $should_add_fields ) {
        // Get current form fields
        $current_fields = get_post_meta( $form_id, '_um_custom_fields', true );
        if ( ! is_array( $current_fields ) ) {
            $current_fields = array();
        }
        
        // Define dealer fields
        $dealer_fields = [
            'dealer_legal_name' => [
                'title' => 'Dealership Legal Name', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Dealership Legal Name', 'metakey' => 'dealer_legal_name',
                'help' => 'Must match your dealer license exactly'
            ],
            'dealer_federal_tax_id' => [
                'title' => 'Federal Tax ID (EIN)', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Federal Tax ID (EIN)', 'metakey' => 'dealer_federal_tax_id',
            ],
            'dealer_owner_full_name' => [
                'title' => 'Owner Full Name (Principal)', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Owner Full Name (Principal)', 'metakey' => 'dealer_owner_full_name',
            ],
            'dealer_phone' => [
                'title' => 'Dealership Phone', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Dealership Phone', 'metakey' => 'dealer_phone',
            ],
            'dealer_fax' => [
                'title' => 'Fax Number', 'type' => 'text', 'required' => 0, 'public' => 1, 'editable' => 1,
                'label' => 'Fax Number', 'metakey' => 'dealer_fax',
            ],
            'dealer_license_num' => [
                'title' => 'Dealer License #', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Dealer License #', 'metakey' => 'dealer_license_num',
            ],
            'dealer_address' => [
                'title' => 'Dealership Address', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Dealership Address', 'metakey' => 'dealer_address',
            ],
            'dealer_city' => [
                'title' => 'City', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'City', 'metakey' => 'dealer_city',
            ],
            'dealer_state' => [
                'title' => 'State', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'State', 'max_length' => 2, 'metakey' => 'dealer_state',
            ],
            'dealer_zip' => [
                'title' => 'Zip Code', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Zip Code', 'metakey' => 'dealer_zip',
            ],
            'dealer_contact_for_approval' => [
                'title' => 'Contact for Approval/Counter', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Contact for Approval/Counter', 'metakey' => 'dealer_contact_for_approval',
            ],
            'dealer_contact_cell_phone' => [
                'title' => 'Contact Cell Phone', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Contact Cell Phone', 'metakey' => 'dealer_contact_cell_phone',
            ],
            'dealer_email_for_approvals' => [
                'title' => 'Email for Approvals', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'validate' => 'email', 'label' => 'Email for Approvals', 'metakey' => 'dealer_email_for_approvals',
            ],
        ];
        
        // Add dealer fields that don't already exist
        $added_count = 0;
        foreach ( $dealer_fields as $field_key => $field_data ) {
            if ( ! isset( $current_fields[$field_key] ) ) {
                $current_fields[$field_key] = $field_data;
                $added_count++;
            }
        }
        
        if ( $added_count > 0 ) {
            // Update the form's custom fields
            update_post_meta( $form_id, '_um_custom_fields', $current_fields );
            error_log("PAF FORCE ADD FIELDS: Added {$added_count} dealer fields to form {$form_id} during rendering");
        }
    }
}

// Enhanced function to create dealer CPT that handles multiple registration scenarios
function paf_create_dealer_cpt_on_um_approval( $user_id ) {
    error_log("PAF DEBUG UM HOOK: paf_create_dealer_cpt_on_um_approval called for user ID: {$user_id}");
    $user = get_userdata( $user_id );

    if ( ! $user ) {
        error_log("PAF DEBUG UM HOOK: User data not found for ID: {$user_id}");
        return;
    }
    
    $user_roles_on_hook = implode(', ', (array) $user->roles);
    error_log("PAF DEBUG UM HOOK: User roles on hook: {$user_roles_on_hook}");

    // Check for dealer roles - be flexible about role names
    $is_dealer = false;
    foreach ((array) $user->roles as $role) {
        if (strpos($role, 'dealer') !== false) {
            $is_dealer = true;
            break;
        }
    }

    if ( ! $is_dealer ) {
        error_log("PAF DEBUG UM HOOK: User ID {$user_id} does not have dealer role. Roles: " . $user_roles_on_hook);
        return;
    }

    // Check if a dealer CPT already exists for this user_id to prevent duplicates
    $existing_dealer = get_posts([
        'post_type' => 'paf_dealer',
        'meta_key' => '_associated_user_id',
        'meta_value' => $user_id,
        'posts_per_page' => 1,
        'fields' => 'ids',
        'suppress_filters' => true,
    ]);

    if ( ! empty( $existing_dealer ) ) {
        error_log("PAF DEBUG UM HOOK: paf_dealer CPT already exists for user ID: {$user_id}. Post ID: " . $existing_dealer[0]);
        return; 
    }

    $dealer_legal_name = get_user_meta( $user_id, 'dealer_legal_name', true );
    error_log("PAF DEBUG UM HOOK: Fetched dealer_legal_name from user meta for {$user_id}: " . ($dealer_legal_name ? $dealer_legal_name : 'EMPTY'));

    // Use a more descriptive title for incomplete profiles
    $dealer_title = !empty($dealer_legal_name) ? $dealer_legal_name : ($user->display_name . ' (Dealership Profile Incomplete)');

    $dealer_cpt_data = [
        'post_title' => sanitize_text_field($dealer_title),
        'post_type' => 'paf_dealer',
        'post_status' => 'publish', 
        'post_author' => $user_id, // Or a specific admin ID if preferred
    ];

    $dealer_post_id = wp_insert_post( $dealer_cpt_data );

    if ( is_wp_error( $dealer_post_id ) ) {
        error_log("PAF DEBUG UM HOOK: WP_Error creating paf_dealer CPT for user ID {$user_id}: " . $dealer_post_id->get_error_message());
        return;
    }
    
    if ( !$dealer_post_id ) {
        error_log("PAF DEBUG UM HOOK: Failed to create paf_dealer CPT for user ID {$user_id} (wp_insert_post returned 0 or false).");
        return;
    }
    
    error_log("PAF DEBUG UM HOOK: Successfully created paf_dealer CPT. Post ID: {$dealer_post_id} for User ID: {$user_id}");

    update_post_meta( $dealer_post_id, '_associated_user_id', $user_id );
    
    if (function_exists('paf_generate_unique_id')) {
        $dealer_id = paf_generate_unique_id('PAD_');
        update_post_meta( $dealer_post_id, '_paf_dealer_id', $dealer_id );
        error_log("PAF DEBUG UM HOOK: Generated unique dealer ID: {$dealer_id}");
    } else {
        $dealer_id = 'PAD_' . uniqid();
        update_post_meta( $dealer_post_id, '_paf_dealer_id', $dealer_id );
        error_log("PAF DEBUG UM HOOK: Generated fallback dealer ID: {$dealer_id}");
    }
    
    update_post_meta( $dealer_post_id, '_status', 'pending_approval' ); // Initial status for the dealer CPT

    // Map UM user meta keys to paf_dealer CPT meta keys
    $meta_map = [
        '_dealership_legal_name' => 'dealer_legal_name',
        '_federal_tax_id' => 'dealer_federal_tax_id',
        '_owner_full_name' => 'dealer_owner_full_name',
        '_dealership_phone' => 'dealer_phone',
        '_fax' => 'dealer_fax',
        '_dealer_license_num' => 'dealer_license_num',
        '_address' => 'dealer_address',
        '_city' => 'dealer_city',
        '_state' => 'dealer_state',
        '_zip' => 'dealer_zip',
        '_contact_for_approval_or_counter' => 'dealer_contact_for_approval',
        '_contact_cell_phone' => 'dealer_contact_cell_phone',
        '_email_for_approvals' => 'dealer_email_for_approvals',
    ];

    foreach ( $meta_map as $cpt_meta_key => $um_meta_key ) {
        $value = get_user_meta( $user_id, $um_meta_key, true );
        if ( $value ) { 
            update_post_meta( $dealer_post_id, $cpt_meta_key, sanitize_text_field( $value ) );
            error_log("PAF DEBUG UM HOOK: Copied user meta '{$um_meta_key}' to post meta '{$cpt_meta_key}': " . substr($value, 0, 50) . (strlen($value) > 50 ? '...' : ''));
        } else {
            error_log("PAF DEBUG UM HOOK: User meta '{$um_meta_key}' is empty, not copying to post meta");
        }
    }
    
    // Trigger action for other integrations to hook into
    do_action('paf_dealer_cpt_created', $dealer_post_id, $user_id);
    
    error_log("PAF DEBUG UM HOOK: Completed dealer CPT creation process for user ID: {$user_id}");
}

// Also hook into registration complete to catch immediate registrations
function paf_create_dealer_cpt_on_registration_complete( $user_id, $args ) {
    error_log("PAF DEBUG: Registration complete hook fired for user ID: {$user_id}");
    
    // Small delay to ensure user meta is saved
    wp_schedule_single_event( time() + 5, 'paf_delayed_dealer_cpt_creation', array( $user_id ) );
}

// Scheduled event handler for delayed CPT creation
function paf_delayed_dealer_cpt_creation( $user_id ) {
    error_log("PAF DEBUG: Delayed dealer CPT creation for user ID: {$user_id}");
    paf_create_dealer_cpt_on_um_approval( $user_id );
}

// Helper function (can also be in helper-functions.php)
if (!function_exists('paf_get_current_user_dealer_id')) {
    function paf_get_current_user_dealer_id() {
        if ( ! is_user_logged_in() ) return 0;
        $user_id = get_current_user_id();
        $dealers = get_posts([
            'post_type' => 'paf_dealer',
            'meta_key' => '_associated_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
            'fields' => 'ids',
            'suppress_filters' => true, 
        ]);
        return !empty($dealers) ? $dealers[0] : 0;
    }
}

// Add a function to force create dealer CPT for existing users
if (!function_exists('paf_force_create_dealer_cpt')) {
    function paf_force_create_dealer_cpt($user_id) {
        error_log("PAF DEBUG: Force creating dealer CPT for user ID: {$user_id}");
        
        // Call the same function that's used by the hook, but directly
        paf_create_dealer_cpt_on_um_approval($user_id);
        
        // Check if it worked by getting the dealer ID
        $user_id_current = get_current_user_id();
        $original_user_id = $user_id_current;
        
        // Temporarily set current user for the function
        wp_set_current_user($user_id);
        $dealer_id = paf_get_current_user_dealer_id();
        
        // Restore original user
        wp_set_current_user($original_user_id);
        
        if ($dealer_id) {
            error_log("PAF DEBUG: Successfully force-created dealer CPT with ID: {$dealer_id}");
            return $dealer_id;
        } else {
            error_log("PAF DEBUG: Failed to force-create dealer CPT for user ID: {$user_id}");
            return 0;
        }
    }
}

/**
 * Fixes dealer status when admin approves a dealer
 */
function paf_handle_dealer_status_change($post_id) {
    // Only run on paf_dealer posts
    if (get_post_type($post_id) !== 'paf_dealer') {
        return;
    }
    
    $new_status = get_post_meta($post_id, '_status', true);
    $old_status = get_post_meta($post_id, '_previous_status', true);
    
    // Only proceed if status is changing to approved
    if ($new_status === 'approved' && $new_status !== $old_status) {
        // Get associated user ID
        $user_id = get_post_meta($post_id, '_associated_user_id', true);
        
        if ($user_id) {
            // Update user capabilities if needed
            $user = new WP_User($user_id);
            $user->add_cap('paf_view_dealer_dashboard', true);
            $user->add_cap('paf_submit_credit_application', true);
            
            error_log("PAF STATUS CHANGE: Added capabilities to user {$user_id} for dealer {$post_id}");
            
            // Optional: Send notification to user
            $user_email = $user->user_email;
            $subject = 'Your Dealer Account has been Approved';
            $message = "Congratulations! Your dealer account on " . get_bloginfo('name') . " has been approved. You can now access the full dealer dashboard features.";
            
            wp_mail($user_email, $subject, $message);
            
            // Log the approval
            update_post_meta($post_id, '_approval_date', current_time('mysql'));
            update_post_meta($post_id, '_approved_by', get_current_user_id());
        }
    }
    
    // Store previous status for future reference
    update_post_meta($post_id, '_previous_status', $new_status);
}

/**
 * Adds filter to display dealers needing approval in admin
 */
function paf_add_dealer_status_filter() {
    global $typenow;
    if ($typenow === 'paf_dealer') {
        $current = isset($_GET['dealer_status']) ? $_GET['dealer_status'] : '';
        $statuses = array(
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'suspended' => 'Suspended',
            'inactive' => 'Inactive'
        );
        
        echo '<select name="dealer_status">';
        echo '<option value="">All Statuses</option>';
        
        foreach ($statuses as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($value, $current, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }
}

/**
 * Filters the dealer list based on status
 */
function paf_filter_dealers_by_status($query) {
    global $pagenow, $typenow;
    
    if (is_admin() && $pagenow === 'edit.php' && $typenow === 'paf_dealer' && isset($_GET['dealer_status']) && $_GET['dealer_status'] !== '') {
        $query->query_vars['meta_key'] = '_status';
        $query->query_vars['meta_value'] = sanitize_text_field($_GET['dealer_status']);
    }
}

/**
 * Register UM hooks function - this registers all the hooks we've defined above
 */
function paf_core_register_um_hooks() {
    // Use multiple filters to ensure fields are added in all contexts
    add_filter('um_prepare_fields_for_register', 'paf_add_dealer_registration_fields_filter', 10, 1);
    add_filter('um_prepare_fields_for_profile', 'paf_add_dealer_registration_fields_filter', 10, 1);
    
    // Add filters for shortcode rendering
    add_filter('um_form_fields', 'paf_add_dealer_registration_fields_filter', 10, 1);
    add_filter('um_get_form_fields', 'paf_add_dealer_registration_fields_filter', 10, 1);
    
    // Hook before form rendering to ensure fields are included
    add_action('um_before_form_render', 'paf_force_add_dealer_fields_to_form', 10, 1);

    // Hook after UM user is approved by admin
    add_action('um_after_user_is_approved', 'paf_create_dealer_cpt_on_um_approval', 10, 1);
    
    // Also hook into registration complete for immediate processing
    add_action('um_registration_complete', 'paf_create_dealer_cpt_on_registration_complete', 10, 2);
    
    // Add debug hook - optional but useful for troubleshooting
    add_action('um_registration_complete', 'paf_debug_registration_complete', 10, 2);
    
    // Register the scheduled event
    add_action('paf_delayed_dealer_cpt_creation', 'paf_delayed_dealer_cpt_creation');
}

// Actually register the hooks via init
add_action('init', 'paf_core_register_um_hooks');

// Register the dealer status change handler
add_action('acf/save_post', 'paf_handle_dealer_status_change', 20);

// Register admin UI filters for dealers
add_action('restrict_manage_posts', 'paf_add_dealer_status_filter');
add_action('pre_get_posts', 'paf_filter_dealers_by_status');
