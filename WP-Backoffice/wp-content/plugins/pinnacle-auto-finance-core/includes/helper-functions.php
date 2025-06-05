<?php
// This should be in: /root/Pinnacle-Portal/WP-Backoffice/wp-content/plugins/pinnacle-auto-finance-core/includes/helper-functions.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Helper to get user-friendly labels for deal statuses
function paf_get_deal_status_label( $status_key ) {
    $statuses = [
        'draft' => 'Draft (Local)', // For paf_credit_app
        'pending_submission_to_dealertrack' => 'Pending Submission to DealerTrack', // For paf_credit_app
        'submitted_to_dealertrack' => 'Submitted to DealerTrack', // For paf_credit_app
        'submission_error' => 'Submission Error to DealerTrack', // For paf_credit_app

        'deal_submitted' => 'Deal Submitted (to DT)', // For paf_deal - Initial status post-submission
        'processing' => 'Processing (in DT)', // For paf_deal - Generic "in progress" at DT
        'conditional_approval' => 'Conditional Approval (DT)',
        'final_approval' => 'Final Approval (DT)',
        'documents_ready' => 'Documents Ready (DT)',
        'original_docs_qc' => 'Original Docs Q/C Review (DT)',
        'deal_funded' => 'Deal Funded (DT)',
        'deal_declined' => 'Deal Declined (DT)',
        'completed_archived' => 'Completed/Archived (DT)',
    ];
    return isset( $statuses[$status_key] ) ? $statuses[$status_key] : ucfirst( str_replace( '_', ' ', $status_key ) );
}

// Helper to generate the HTML for the deal status bar
function paf_get_deal_status_bar_html( $current_status_key ) {
    $status_timeline = [
        'deal_submitted' => ['label' => 'Deal Submitted', 'icon' => 'dashicons-upload'],
        'conditional_approval' => ['label' => 'Conditional Approval', 'icon' => 'dashicons-thumbs-up'],
        'final_approval' => ['label' => 'Final Approval', 'icon' => 'dashicons-yes-alt'],
        'documents_ready' => ['label' => 'Documents Ready', 'icon' => 'dashicons-media-document'],
        'original_docs_qc' => ['label' => 'Original Docs Q/C', 'icon' => 'dashicons-clipboard'],
        'deal_funded' => ['label' => 'Deal Funded', 'icon' => 'dashicons-money-alt'],
    ];

    $output = '<div class="paf-status-timeline">';
    // $current_stage_reached = false; // This variable was unused
    $is_declined = ($current_status_key === 'deal_declined');

    $current_status_order = array_search($current_status_key, array_keys($status_timeline));
    if ($current_status_order === false && !$is_declined) {
         $current_status_order = 0; 
    }

    foreach ( $status_timeline as $key => $details ) {
        $is_active = false;
        $is_completed = false;
        $status_order = array_search($key, array_keys($status_timeline));

        if ($is_declined) {
            $is_active = false;
            $is_completed = false; 
        } elseif ($status_order < $current_status_order) {
            $is_completed = true;
        } elseif ($status_order === $current_status_order) {
            $is_active = true;
            $is_completed = true;
        }

        $class = 'paf-timeline-stage';
        if ( $is_completed ) $class .= ' completed';
        if ( $is_active && !$is_declined) $class .= ' active';
        if ( $is_declined ) $class .= ' declined';

        $output .= '<div class="' . esc_attr($class) . '">';
        $output .= '<span class="paf-timeline-icon ' . esc_attr($details['icon']) . '"></span>';
        $output .= '<span class="paf-timeline-label">' . esc_html($details['label']) . '</span>';
        $output .= '</div>';
    }
    $output .= '</div>';

    if ($is_declined) {
        $output .= '<p class="paf-deal-declined-message">This deal has been declined.</p>';
    }

    return $output;
}

// Add history entry - CORRECTED PARAMETER ORDER
function paf_add_history_entry( $action, $title_note, $app_id = 0, $deal_id = 0, $details_array = [] ) {
    $history_title = $action . ' - ' . $title_note . ' - ' . date('Y-m-d H:i:s');
    
    $meta_input = [
        '_action' => sanitize_text_field($action),
        '_details' => wp_json_encode($details_array), 
    ];
    if ($app_id) {
        $meta_input['_credit_application_id'] = intval($app_id);
    }
    if ($deal_id) {
        $meta_input['_deal_id'] = intval($deal_id);
    }
    if (isset($details_array['user_id'])) {
        $meta_input['_user_id'] = intval($details_array['user_id']);
    }

    wp_insert_post([
        'post_type' => 'paf_app_history',
        'post_title' => sanitize_text_field($history_title),
        'post_status' => 'publish',
        'meta_input' => $meta_input,
    ]);
}

// Sanitize currency input
function paf_sanitize_currency( $value ) {
    $value = preg_replace( '/[^\d.]/', '', $value );
    return is_numeric( $value ) ? floatval( $value ) : 0;
}

// Mask SSN for display
function paf_mask_ssn($ssn) {
    if (empty($ssn) || strlen($ssn) < 4) {
        return $ssn; 
    }
    return '***-**-' . substr($ssn, -4);
}

/**
 * Create a new credit application post and return its ID (or WP_Error on failure).
 */
function paf_create_credit_application_post( $primary, $co, $vehicle, $financial ) {
    $app_title_name = isset($primary['personalInformation']['applicantName']) ? $primary['personalInformation']['applicantName'] : 'Unknown Applicant';
    $post_id = wp_insert_post([
        'post_type'   => 'paf_credit_app', // Ensure this CPT is registered
        'post_title'  => sanitize_text_field( $app_title_name ) . ' - App Data - ' . date('Y-m-d H:i:s'),
        'post_status' => 'publish', // Changed from 'draft' as per form handler logic
        'post_author' => get_current_user_id(), // Assign to current user
    ]);

    if ( is_wp_error( $post_id ) || ! $post_id ) {
        return new WP_Error( 'post_creation_failed', 'Could not create credit application CPT.' );
    }

    // Save all the pieces as post meta
    // Encryption should ideally happen before this data is passed or handled within.
    // The paf_handle_credit_application_submission now handles encryption before calling this structure.
    update_post_meta( $post_id, '_primary_borrower',   wp_json_encode( $primary ) );
    if ( $co ) {
        update_post_meta( $post_id, '_co_borrower',    wp_json_encode( $co ) );
    }
    update_post_meta( $post_id, '_vehicle_data',       wp_json_encode( $vehicle ) );
    update_post_meta( $post_id, '_financial_data',     wp_json_encode( $financial ) );
    // _submission_date and _submitted_by_user are typically set in the main handler paf_handle_credit_application_submission
    // as they are specific to the form submission event, not just CPT creation.
    // But if this function is the SOLE creator, it's fine to add them here.
    // Let's assume they are handled in the main submission handler for now.

    return $post_id;
}

/**
 * Generate a unique PAF ID for dealers or other entities if needed.
 */
function paf_generate_unique_id( $prefix = 'PAF_DLR_' ) {
    return uniqid( $prefix, true ); 
}

?>