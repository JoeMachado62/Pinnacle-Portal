<?php
// File: /root/Pinnacle-Portal/WP-Backoffice/wp-content/plugins/pinnacle-auto-finance-core/includes/ultimate-member-integration.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function paf_core_register_um_hooks() {
    // Use a filter to add/modify fields for the registration form
    add_filter('um_prepare_fields_for_register', 'paf_add_dealer_registration_fields_filter', 10, 1);

    // Hook after UM user is approved by admin
    add_action('um_after_user_is_approved', 'paf_create_dealer_cpt_on_um_approval', 10, 1);
}

// This function will be hooked to a filter that allows modification of fields.
function paf_add_dealer_registration_fields_filter( $fields_array ) {
    // Check if UM and its form property are available and if it's the correct form ID
    // IMPORTANT: Replace '17' with the actual ID of your dealer registration form if different.
    if ( function_exists('UM') && UM()->form() && isset(UM()->form()->form_data['form_id']) && UM()->form()->form_data['form_id'] == 17 ) {
        
        // DEFINE $paf_dealer_fields HERE, INSIDE THE FUNCTION SCOPE
        $paf_dealer_fields = [
            'dealer_legal_name' => [
                'title' => 'Dealership Legal Name', 'type' => 'text', 'required' => 1, 'public' => 1, 'editable' => 1,
                'label' => 'Dealership Legal Name', 'metakey' => 'dealer_legal_name',
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
            }
        }
    }
    return $fields_array;
}


function paf_create_dealer_cpt_on_um_approval( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user || ! in_array( 'dealer', (array) $user->roles ) ) {
        return; // Not a dealer or user not found
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
        return; 
    }

    $dealer_legal_name = get_user_meta( $user_id, 'dealer_legal_name', true );
    $dealer_title = !empty($dealer_legal_name) ? $dealer_legal_name : ($user->display_name . ' (Dealership)');

    $dealer_cpt_data = [
        'post_title' => sanitize_text_field($dealer_title),
        'post_type' => 'paf_dealer',
        'post_status' => 'publish', 
        'post_author' => $user_id, // Or a specific admin ID if preferred
    ];

    $dealer_post_id = wp_insert_post( $dealer_cpt_data );

    if ( $dealer_post_id && ! is_wp_error( $dealer_post_id ) ) {
        update_post_meta( $dealer_post_id, '_associated_user_id', $user_id );
        
        if (function_exists('paf_generate_unique_id')) {
            update_post_meta( $dealer_post_id, '_paf_dealer_id', paf_generate_unique_id('PAD_') );
        } else {
             update_post_meta( $dealer_post_id, '_paf_dealer_id', 'PAD_' . uniqid() ); // Fallback
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
            }
        }
        // Note: 'approval_date' for the dealer CPT would be set when an admin changes its '_status' to 'approved'.
    }
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
?>