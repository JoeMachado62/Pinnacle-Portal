<?php
// /root/Pinnacle-Portal/WP-Backoffice/wp-content/plugins/pinnacle-auto-finance-core/includes/acf-fields.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register ACF fields using the acf/init hook
 */
function paf_core_register_acf_field_groups() {
    // Only log in debug mode to reduce log spam
    // if (defined('WP_DEBUG') && WP_DEBUG) {
    //     error_log("PAF ACF: Registering 'Dealer Account Management' field group");
    // }

    acf_add_local_field_group(array(
        'key' => 'group_paf_dealer_management',
        'title' => 'Dealer Account Management',
        'fields' => array(
            // Field for Dealer Status
            array(
                'key' => 'field_paf_dealer_status_select',
                'label' => 'Dealer Status',
                'name' => '_status',
                'type' => 'select',
                'instructions' => 'Manage the approval status of this dealer account.',
                'required' => 1,
                'choices' => array(
                    'pending_approval' => 'Pending Approval',
                    'approved' => 'Approved',
                    'suspended' => 'Suspended',
                    'inactive' => 'Inactive',
                ),
                'default_value' => 'pending_approval',
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'ajax' => 0,
                'return_format' => 'value',
                'placeholder' => '',
            ),
            // Read-only field to display the PAF Dealer ID
            array(
                'key' => 'field_paf_dealer_id_display_acf',
                'label' => 'PAF Dealer ID (System)',
                'name' => '_paf_dealer_id',
                'type' => 'text',
                'instructions' => 'The unique system ID generated for this dealer.',
                'required' => 0,
                'readonly' => 1,
                'disabled' => 1,
                'default_value' => '',
                'placeholder' => 'Automatically generated',
            ),
            // Associated WordPress User - MADE EDITABLE
            array(
                'key' => 'field_paf_associated_user_id_acf',
                'label' => 'Associated WordPress User',
                'name' => '_associated_user_id',
                'type' => 'user',
                'instructions' => 'The WordPress user associated with this dealer profile.',
                'required' => 0,
                'allow_null' => 1,
                'multiple' => 0,
                'return_format' => 'id', // Important: Return user ID not object
                // Removed readonly and disabled attributes to make it editable
            ),
            // Read-only field to display the Legal Name from CPT meta
            array(
                'key' => 'field_paf_dl_name_acf',
                'label' => 'Dealership Legal Name (from Profile)',
                'name' => '_dealership_legal_name',
                'type' => 'text',
                'readonly' => 1,
                'disabled' => 1,
            ),
            // Read-only field to display the License # from CPT meta
            array(
                'key' => 'field_paf_dl_license_acf',
                'label' => 'Dealer License # (from Profile)',
                'name' => '_dealer_license_num',
                'type' => 'text',
                'readonly' => 1,
                'disabled' => 1,
            ),
            // Read-only field to display the Tax ID from CPT meta
            array(
                'key' => 'field_paf_dl_tax_id_acf',
                'label' => 'Federal Tax ID (EIN) (from Profile)',
                'name' => '_federal_tax_id',
                'type' => 'text',
                'readonly' => 1,
                'disabled' => 1,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'paf_dealer',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ));
}

// Only register once when ACF is initialized
if (function_exists('acf_add_local_field_group')) {
    add_action('acf/init', 'paf_core_register_acf_field_groups');
}

/**
 * Preserve dealer-user association when updating fields
 */
function paf_preserve_dealer_user_association($post_id) {
    // Only process dealer CPTs
    if (get_post_type($post_id) !== 'paf_dealer') {
        return;
    }
    
    // Get the current associated user ID before save
    $current_user_id = get_post_meta($post_id, '_associated_user_id', true);
    
    // Store it temporarily
    if ($current_user_id) {
        update_post_meta($post_id, '_temp_associated_user_id', $current_user_id);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("PAF PRESERVE: Stored user ID {$current_user_id} for dealer {$post_id} before ACF save");
        }
    }
}
add_action('acf/save_post', 'paf_preserve_dealer_user_association', 1); // Run before ACF save

/**
 * Restore dealer-user association after save if it was lost
 */
function paf_restore_dealer_user_association($post_id) {
    // Only process dealer CPTs
    if (get_post_type($post_id) !== 'paf_dealer') {
        return;
    }
    
    // Check if we have a temp stored value
    $temp_user_id = get_post_meta($post_id, '_temp_associated_user_id', true);
    
    if ($temp_user_id) {
        // Clean up temporary meta
        delete_post_meta($post_id, '_temp_associated_user_id');
        
        // Check if the current value is empty
        $current_user_id = get_post_meta($post_id, '_associated_user_id', true);
        
        if (!$current_user_id) {
            // If it's empty, restore the previous value
            update_post_meta($post_id, '_associated_user_id', $temp_user_id);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("PAF RESTORE: Restored user ID {$temp_user_id} for dealer {$post_id}");
            }
            
            // Make sure the user has the correct capabilities if status is approved
            $status = get_post_meta($post_id, '_status', true);
            if ($status === 'approved') {
                $user = new WP_User($temp_user_id);
                if (!$user->has_cap('paf_view_dealer_dashboard')) {
                    $user->add_cap('paf_view_dealer_dashboard', true);
                    $user->add_cap('paf_submit_credit_application', true);
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("PAF RESTORE: Added capabilities to user {$temp_user_id} for dealer {$post_id}");
                    }
                }
            }
        }
    }
}
add_action('acf/save_post', 'paf_restore_dealer_user_association', 20); // Run after ACF save

/**
 * When dealer status changes to 'approved', ensure user has capabilities
 */
function paf_acf_update_dealer_capabilities($post_id) {
    // Only run on paf_dealer posts
    if (get_post_type($post_id) !== 'paf_dealer') {
        return;
    }
    
    $status = get_post_meta($post_id, '_status', true);
    
    // Only interested in 'approved' dealers
    if ($status === 'approved') {
        $user_id = get_post_meta($post_id, '_associated_user_id', true);
        
        // If no user ID is directly associated, check temp storage
        if (!$user_id) {
            $user_id = get_post_meta($post_id, '_temp_associated_user_id', true);
        }
        
        if ($user_id) {
            // Update user capabilities
            $user = new WP_User($user_id);
            $user->add_cap('paf_view_dealer_dashboard', true);
            $user->add_cap('paf_submit_credit_application', true);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("PAF STATUS: Added capabilities to user {$user_id} for dealer {$post_id}");
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("PAF STATUS WARNING: Dealer {$post_id} is approved but has no associated user!");
            }
        }
    }
}
add_action('acf/save_post', 'paf_acf_update_dealer_capabilities', 15); // Run after field updates but before restoration

/**
 * Debug hook to track postmeta changes for dealer status
 */
function paf_debug_postmeta_updates($meta_id, $post_id, $meta_key, $meta_value) {
    if (get_post_type($post_id) === 'paf_dealer' && defined('WP_DEBUG') && WP_DEBUG) {
        if ($meta_key === '_status') {
            error_log("PAF META DEBUG: Post ID {$post_id} - Status changed to: {$meta_value}");
        } elseif ($meta_key === '_associated_user_id') {
            error_log("PAF META DEBUG: Post ID {$post_id} - Associated user changed to: " . ($meta_value ?: 'empty'));
        }
    }
}
add_action('added_post_meta', 'paf_debug_postmeta_updates', 10, 4);
add_action('updated_post_meta', 'paf_debug_postmeta_updates', 10, 4);
?>