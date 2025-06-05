<?php
/**
 * Dashboard AJAX Handlers
 * Handles AJAX requests from the dealer dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register dashboard AJAX hooks
 */
function paf_core_register_dashboard_ajax_hooks() {
    // Profile update AJAX handler
    add_action( 'wp_ajax_paf_update_profile_field', 'paf_ajax_update_profile_field' );
    
    // Deal status update AJAX handler
    add_action( 'wp_ajax_paf_update_deal_status', 'paf_ajax_update_deal_status' );
    
    // Pipeline refresh AJAX handler
    add_action( 'wp_ajax_paf_refresh_pipeline', 'paf_ajax_refresh_pipeline' );
    
    // Communication log AJAX handler
    add_action( 'wp_ajax_paf_add_communication', 'paf_ajax_add_communication' );
}

/**
 * Handle profile field updates via AJAX
 */
function paf_ajax_update_profile_field() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['paf_profile_field_nonce'], 'paf_update_profile_field_nonce' ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Security check failed.' ) ) );
    }
    
    // Check user permissions
    if ( ! current_user_can( 'paf_view_dealer_dashboard' ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Insufficient permissions.' ) ) );
    }
    
    $dealer_id = intval( $_POST['dealer_id'] );
    $current_user_id = get_current_user_id();
    
    // Verify the dealer belongs to the current user
    $dealer_post = get_post( $dealer_id );
    if ( ! $dealer_post || $dealer_post->post_type !== 'paf_dealer' ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Invalid dealer ID.' ) ) );
    }
    
    // Check if user owns this dealer record or is admin
    $associated_user_id = get_post_meta( $dealer_id, '_associated_user_id', true );
    if ( $associated_user_id != $current_user_id && ! current_user_can( 'manage_paf_dealers' ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Access denied.' ) ) );
    }
    
    // Define allowed fields for security
    $allowed_fields = array(
        '_dealership_legal_name',
        '_address',
        '_city', 
        '_state',
        '_zip',
        '_owner_full_name',
        '_dealership_phone',
        '_fax',
        '_dealer_license_num',
        '_contact_for_approval_or_counter',
        '_contact_cell_phone',
        '_email_for_approvals',
        '_federal_tax_id'
    );
    
    $updated_fields = array();
    $validation_errors = array();
    
    // Process each submitted field
    foreach ( $_POST as $key => $value ) {
        if ( in_array( $key, $allowed_fields ) ) {
            $sanitized_value = sanitize_text_field( $value );
            
            // Field-specific validation
            $validation_result = paf_validate_profile_field( $key, $sanitized_value );
            if ( $validation_result !== true ) {
                $validation_errors[] = $validation_result;
                continue;
            }
            
            // Update the field
            update_post_meta( $dealer_id, $key, $sanitized_value );
            $updated_fields[ $key ] = $sanitized_value;
        }
    }
    
    if ( ! empty( $validation_errors ) ) {
        wp_die( json_encode( array( 
            'success' => false, 
            'data' => 'Validation errors: ' . implode( ', ', $validation_errors )
        ) ) );
    }
    
    if ( empty( $updated_fields ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'No valid fields to update.' ) ) );
    }
    
    // Log the update
    if ( function_exists( 'paf_add_history_entry' ) ) {
        paf_add_history_entry(
            'dealer_profile_updated',
            'Dealer profile updated via dashboard: ' . implode( ', ', array_keys( $updated_fields ) ),
            0, // No app ID
            0, // No deal ID  
            array( 'updated_fields' => array_keys( $updated_fields ), 'dealer_id' => $dealer_id )
        );
    }
    
    wp_die( json_encode( array( 'success' => true, 'data' => $updated_fields ) ) );
}

/**
 * Validate individual profile fields
 */
function paf_validate_profile_field( $field_key, $value ) {
    switch ( $field_key ) {
        case '_state':
            if ( strlen( $value ) !== 2 ) {
                return 'State must be 2 characters';
            }
            break;
            
        case '_zip':
            if ( ! preg_match( '/^\d{5}(-\d{4})?$/', $value ) ) {
                return 'Invalid ZIP code format';
            }
            break;
            
        case '_dealership_phone':
        case '_contact_cell_phone':
        case '_fax':
            if ( ! empty( $value ) && ! preg_match( '/^[\d\s\-\(\)\+\.]+$/', $value ) ) {
                return 'Invalid phone number format';
            }
            break;
            
        case '_email_for_approvals':
            if ( ! empty( $value ) && ! is_email( $value ) ) {
                return 'Invalid email format';
            }
            break;
            
        case '_federal_tax_id':
            if ( ! empty( $value ) && ! preg_match( '/^\d{2}-\d{7}$/', $value ) ) {
                return 'Federal Tax ID must be in format XX-XXXXXXX';
            }
            break;
    }
    
    return true;
}

/**
 * Handle deal status updates via AJAX
 */
function paf_ajax_update_deal_status() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'paf_update_deal_status' ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Security check failed.' ) ) );
    }
    
    // Check permissions - only admins can manually update deal status
    if ( ! current_user_can( 'manage_paf_dealers' ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Insufficient permissions.' ) ) );
    }
    
    $deal_id = intval( $_POST['deal_id'] );
    $new_status = sanitize_key( $_POST['status'] );
    $notes = sanitize_textarea_field( $_POST['notes'] );
    
    // Verify deal exists
    $deal_post = get_post( $deal_id );
    if ( ! $deal_post || $deal_post->post_type !== 'paf_deal' ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Invalid deal ID.' ) ) );
    }
    
    // Define allowed statuses
    $allowed_statuses = array(
        'deal_submitted',
        'processing_by_lender',
        'conditional_approval',
        'final_approval',
        'deal_funded',
        'deal_declined',
        'completed_archived'
    );
    
    if ( ! in_array( $new_status, $allowed_statuses ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Invalid status.' ) ) );
    }
    
    // Update deal status
    $old_status = get_post_meta( $deal_id, '_status', true );
    update_post_meta( $deal_id, '_status', $new_status );
    
    // Add notes if provided
    if ( ! empty( $notes ) ) {
        $existing_notes = get_post_meta( $deal_id, '_processing_notes', true );
        $updated_notes = $existing_notes . "\n[" . current_time( 'Y-m-d H:i:s' ) . "] Manual update by " . wp_get_current_user()->display_name . ": " . $notes;
        update_post_meta( $deal_id, '_processing_notes', $updated_notes );
    }
    
    // Log the status change
    if ( function_exists( 'paf_add_history_entry' ) ) {
        paf_add_history_entry(
            'deal_status_manual_update',
            "Deal status manually updated from '{$old_status}' to '{$new_status}'",
            0, // No app ID
            $deal_id,
            array( 
                'old_status' => $old_status,
                'new_status' => $new_status,
                'notes' => $notes,
                'updated_by' => get_current_user_id()
            )
        );
    }
    
    wp_die( json_encode( array( 
        'success' => true, 
        'data' => array(
            'new_status' => $new_status,
            'status_label' => function_exists( 'paf_get_deal_status_label' ) ? paf_get_deal_status_label( $new_status ) : ucfirst( str_replace( '_', ' ', $new_status ) )
        )
    ) ) );
}

/**
 * Handle pipeline refresh via AJAX
 */
function paf_ajax_refresh_pipeline() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'paf_refresh_pipeline' ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Security check failed.' ) ) );
    }
    
    // Check user permissions
    if ( ! current_user_can( 'paf_view_dealer_dashboard' ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Insufficient permissions.' ) ) );
    }
    
    $user_id = get_current_user_id();
    
    // Get fresh pipeline data
    $pipeline_html = function_exists( 'paf_render_dashboard_pipeline_section' ) ? paf_render_dashboard_pipeline_section( $user_id ) : '<p>Pipeline data unavailable.</p>';
    
    wp_die( json_encode( array( 'success' => true, 'data' => $pipeline_html ) ) );
}

/**
 * Handle adding communication entries via AJAX
 */
function paf_ajax_add_communication() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'paf_add_communication' ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Security check failed.' ) ) );
    }
    
    // Check user permissions
    if ( ! current_user_can( 'paf_view_dealer_dashboard' ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Insufficient permissions.' ) ) );
    }
    
    $deal_id = intval( $_POST['deal_id'] );
    $message = sanitize_textarea_field( $_POST['message'] );
    $communication_type = sanitize_text_field( $_POST['type'] );
    
    // Verify deal exists and user has access
    $deal_post = get_post( $deal_id );
    if ( ! $deal_post || $deal_post->post_type !== 'paf_deal' ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Invalid deal ID.' ) ) );
    }
    
    // Check if user owns this deal or is admin
    if ( $deal_post->post_author != get_current_user_id() && ! current_user_can( 'manage_paf_dealers' ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Access denied.' ) ) );
    }
    
    if ( empty( $message ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Message cannot be empty.' ) ) );
    }
    
    // Create communication entry
    $comm_post_data = array(
        'post_title' => 'Communication for Deal #' . $deal_id . ' - ' . current_time( 'Y-m-d H:i:s' ),
        'post_content' => $message,
        'post_type' => 'paf_communication',
        'post_status' => 'publish',
        'post_author' => get_current_user_id()
    );
    
    $comm_id = wp_insert_post( $comm_post_data );
    
    if ( is_wp_error( $comm_id ) ) {
        wp_die( json_encode( array( 'success' => false, 'data' => 'Failed to create communication entry.' ) ) );
    }
    
    // Add meta data
    update_post_meta( $comm_id, '_deal_post_id', $deal_id );
    update_post_meta( $comm_id, '_communication_type', $communication_type );
    update_post_meta( $comm_id, '_direction', 'outbound' ); // Since it's from dashboard
    
    wp_die( json_encode( array( 
        'success' => true, 
        'data' => array(
            'communication_id' => $comm_id,
            'message' => 'Communication added successfully.'
        )
    ) ) );
}

/**
 * Enqueue dashboard scripts and styles with localized AJAX data
 */
function paf_enqueue_dashboard_scripts() {
    // Only enqueue on dashboard pages
    if ( ! is_page() || ! current_user_can( 'paf_view_dealer_dashboard' ) ) {
        return;
    }
    
    global $post;
    if ( ! $post || ! has_shortcode( $post->post_content, 'paf_dealer_dashboard' ) ) {
        return;
    }
    
    // Enqueue dashboard CSS
    wp_enqueue_style(
        'paf-dashboard-css',
        PAF_CORE_PLUGIN_URL . 'assets/css/paf-dashboard.css',
        array(),
        '1.0.0'
    );
    
    // Enqueue dashboard JavaScript
    wp_enqueue_script(
        'paf-dashboard-js',
        PAF_CORE_PLUGIN_URL . 'assets/js/paf-dashboard.js',
        array( 'jquery' ),
        '1.0.0',
        true
    );
    
    // Localize script with AJAX data
    wp_localize_script( 'paf-dashboard-js', 'paf_dashboard_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'paf_dashboard_nonce' ),
        'profile_nonce' => wp_create_nonce( 'paf_update_profile_field_nonce' ),
        'deal_status_nonce' => wp_create_nonce( 'paf_update_deal_status' ),
        'pipeline_nonce' => wp_create_nonce( 'paf_refresh_pipeline' ),
        'communication_nonce' => wp_create_nonce( 'paf_add_communication' )
    ) );
}
add_action( 'wp_enqueue_scripts', 'paf_enqueue_dashboard_scripts' );
