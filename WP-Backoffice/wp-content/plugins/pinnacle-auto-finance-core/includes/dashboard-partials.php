<?php
// /wp-content/plugins/pinnacle-auto-finance-core/includes/dashboard-partials.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders the profile section of the dealer dashboard.
 */
function paf_render_dashboard_profile_section($dealer_cpt_id, $current_user) {
    if (!$dealer_cpt_id || !$current_user) {
        return '<p class="paf-error">Error: Dealer profile data not available.</p>';
    }

    $dealership_display_name = get_post_meta($dealer_cpt_id, '_dealership_legal_name', true);
    if (empty($dealership_display_name)) {
        $dealership_display_name = $current_user->display_name . " (Profile Incomplete)";
    }

    $dealership_address = get_post_meta($dealer_cpt_id, '_address', true);
    $dealership_city = get_post_meta($dealer_cpt_id, '_city', true);
    $dealership_state = get_post_meta($dealer_cpt_id, '_state', true);
    $dealership_zip = get_post_meta($dealer_cpt_id, '_zip', true);
    
    $address_parts = [];
    if ($dealership_address) $address_parts[] = $dealership_address;
    if ($dealership_city) $address_parts[] = $dealership_city;
    if ($dealership_state) $address_parts[] = $dealership_state;
    if ($dealership_zip) $address_parts[] = $dealership_zip;
    $full_address = implode(', ', $address_parts);
    // A slightly better formatting for common US addresses
    if ($dealership_address && $dealership_city && $dealership_state && $dealership_zip) {
        $full_address = sprintf('%s, %s, %s %s', $dealership_address, $dealership_city, $dealership_state, $dealership_zip);
    } else {
        $full_address = implode(', ', array_filter([$dealership_address, $dealership_city, $dealership_state, $dealership_zip]));
    }
    $full_address = trim(str_replace(' ,', ',', $full_address), ' ,');


    $profile_pic_url = '';
    // More compatible approach for Ultimate Member profile pictures
    if (function_exists('um_get_user_avatar_url')) {
        // Get the profile photo URL directly - this method will return false or empty if no custom photo
        $um_profile_photo = um_get_user_avatar_url($current_user->ID, 200);
        
        // Check if the returned URL is not empty and doesn't contain default gravatar
        if (!empty($um_profile_photo) && strpos($um_profile_photo, 'gravatar.com/avatar') === false) {
            $profile_pic_url = $um_profile_photo;
        }
    }

    // If UM didn't provide a specific URL or it's still a default Gravatar link, try WordPress's get_avatar_url
    if (empty($profile_pic_url) || strpos($profile_pic_url, 'gravatar.com/avatar') !== false) {
        $profile_pic_url = get_avatar_url($current_user->ID, ['size' => 200, 'default' => 'mystery']);
    }

    // Final fallback to a local placeholder if still a Gravatar mystery man or empty
    if (empty($profile_pic_url) || strpos($profile_pic_url, 'gravatar.com/avatar/?d=mm') !== false || strpos($profile_pic_url, 'gravatar.com/avatar/?d=mystery') !== false) {
        // Ensure you have this image in your plugin's assets/images/ folder
        if (file_exists(PAF_CORE_PLUGIN_DIR . 'assets/images/default-profile-placeholder.png')) {
            $profile_pic_url = PAF_CORE_PLUGIN_URL . 'assets/images/default-profile-placeholder.png'; 
        } else {
             $profile_pic_url = ''; // Or some other truly generic placeholder if your image isn't there
        }
    }


    $profile_fields_config = [
        ['meta_key' => '_dealership_legal_name', 'label' => 'Dealership Legal Name', 'instructions' => 'Must match dealer license', 'required' => true],
        [
            'meta_key' => 'full_address_group', 'label' => 'Address/City/State/Zip', 'instructions' => 'Enter dealership address details.',
            'fields' => [
                ['_meta_key' => '_address', 'label' => 'Street Address', 'placeholder' => 'Street Address', 'required' => true],
                ['_meta_key' => '_city', 'label' => 'City', 'placeholder' => 'City', 'required' => true],
                ['_meta_key' => '_state', 'label' => 'State', 'placeholder' => 'ST', 'maxlength' => 2, 'required' => true],
                ['_meta_key' => '_zip', 'label' => 'Zip Code', 'placeholder' => 'Zip Code', 'type' => 'tel', 'required' => true]
            ]
        ],
        ['_meta_key' => '_owner_full_name', 'label' => 'Owner Full Name (Principal)', 'instructions' => 'Full name of the dealership principal or owner.', 'required' => true],
        ['_meta_key' => '_dealership_phone', 'label' => 'Dealership Phone #', 'type' => 'tel', 'instructions' => 'Main phone number for the dealership.', 'required' => true],
        ['_meta_key' => '_fax', 'label' => 'Fax #', 'type' => 'tel', 'instructions' => 'Dealership fax number, if any.', 'required' => false],
        ['_meta_key' => '_dealer_license_num', 'label' => 'Dealer License#', 'instructions' => 'Official dealer license number.', 'required' => true],
        ['_meta_key' => '_contact_for_approval_or_counter', 'label' => 'Contact Person for approval or counter offer?', 'instructions' => 'Who should be contacted for approvals or counter offers?', 'required' => true],
        ['_meta_key' => '_contact_cell_phone', 'label' => 'What is their cell phone?', 'type' => 'tel', 'instructions' => 'Cell phone of the primary contact person.', 'required' => true],
        ['_meta_key' => '_email_for_approvals', 'label' => 'Where should we email approvals?', 'type' => 'email', 'instructions' => 'Email address for receiving approval notifications.', 'required' => true],
        ['_meta_key' => '_federal_tax_id', 'label' => 'Federal Tax ID (EIN)', 'instructions' => 'Dealership\'s Federal Employer Identification Number.', 'required' => true],
    ];

    ob_start();
    ?>
    <div class="paf-dashboard-profile-widget">
        <div class="paf-profile-header">
            <div class="paf-profile-image-container">
                <img src="<?php echo esc_url($profile_pic_url); ?>" alt="<?php echo esc_attr($dealership_display_name); ?>" class="paf-profile-image">
                <!-- Edit photo button can be a link to UM profile photo edit or a custom uploader -->
                <a href="<?php echo esc_url(um_get_core_page('account', 'general')); // Adjust UM tab if needed, e.g., 'profile/photo/' if UM has specific photo edit page. Check UM docs. ?>" class="paf-profile-edit-photo-btn"><span class="dashicons dashicons-edit"></span> Edit</a>
            </div>
            <h3 class="paf-profile-dealership-name"><?php echo esc_html($dealership_display_name); ?></h3>
            <p class="paf-profile-dealership-address"><?php echo esc_html($full_address); ?></p>
        </div>
        <button id="pafEditProfileBtn" class="paf-button paf-edit-profile-main-btn">Edit Profile Details</button>
        
        <div id="pafProfileAccordionContent" class="paf-profile-accordion-content" style="display: none;">
            <ul class="paf-profile-edit-links">
                <?php foreach ($profile_fields_config as $field_group) : 
                    $current_value_display = '';
                    if ($field_group['meta_key'] !== 'full_address_group') {
                        $current_value_display = get_post_meta($dealer_cpt_id, $field_group['meta_key'], true);
                    } else {
                        $addr_parts_display = [];
                        if (!empty($field_group['fields']) && is_array($field_group['fields'])) {
                            foreach ($field_group['fields'] as $sub_field) {
                                $val = get_post_meta($dealer_cpt_id, $sub_field['_meta_key'], true);
                                if ($val) $addr_parts_display[] = $val;
                            }
                        }
                        $current_value_display = implode(', ', $addr_parts_display);
                    }
                ?>
                    <li>
                        <a href="#" 
                           class="paf-profile-edit-link" 
                           data-fieldgroup="<?php echo esc_attr(json_encode($field_group)); ?>"
                           data-dealer-id="<?php echo esc_attr($dealer_cpt_id); ?>">
                            <span class="paf-edit-link-label"><?php echo esc_html($field_group['label']); ?>:</span>
                            <span class="paf-edit-link-value"><?php echo esc_html(wp_trim_words($current_value_display, 10, '...')); ?></span>
                            <span class="dashicons dashicons-edit-alt"></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Modal structure for editing profile fields -->
    <div id="pafProfileEditModal" class="paf-modal" style="display:none;">
        <div class="paf-modal-content">
            <span class="paf-modal-close">×</span>
            <h4 id="pafModalTitle">Edit Field</h4>
            <div id="pafModalInstructions" class="paf-modal-instructions"></div>
            <form id="pafProfileEditForm">
                <?php wp_nonce_field('paf_update_profile_field_nonce', 'paf_profile_field_nonce'); ?>
                <input type="hidden" id="pafModalDealerId" name="dealer_id" value="<?php echo esc_attr($dealer_cpt_id); ?>">
                <div id="pafModalFieldsContainer">
                    <!-- Input fields will be dynamically inserted here by JavaScript -->
                </div>
                <button type="submit" class="paf-button paf-modal-save-btn">Save Changes</button>
            </form>
            <div id="pafModalMessage" class="paf-modal-message" style="display:none;"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders the "Get Pre-qualified" image section.
 */
function paf_render_dashboard_prequal_image_section() {
    // Image URL from your description - fallback to the existing image if needed
    $image_url = 'https://portal.pinnacleautofinance.com/wp-content/uploads/2025/03/Submit-Deal.png';
    // Link URL from your description
    $link_url = home_url('/submit-new-deal/'); // Use home_url() for portability

    ob_start();
    ?>
    <div class="paf-dashboard-prequal-section">
        <div class="paf-prequal-card">
            <h3>Get pre-qualified in minutes</h3>
            <p>Financing is easier than ever</p>
            <a href="<?php echo esc_url($link_url); ?>" class="paf-submit-deal-btn">SUBMIT NEW DEAL</a>
            
            <div class="paf-prequal-image-wrapper">
                <img src="<?php echo esc_url($image_url); ?>" alt="Get pre-qualified in minutes. Financing is easier than ever. Submit New Deal." class="paf-prequal-image">
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders the pipeline view section of the dealer dashboard.
 */
function paf_render_dashboard_pipeline_section($user_id) {
    // Fetch active deals for the current user
    $args = [
        'post_type'      => 'paf_deal',
        'author'         => $user_id,
        'posts_per_page' => 20, // Or -1 for all, consider pagination for many deals
        'orderby'        => 'date',
        'order'          => 'DESC',
        // 'meta_query'     => array(
        //     array(
        //         'key'     => '_status',
        //         'value'   => array('deal_funded', 'completed_archived', 'deal_declined'),
        //         'compare' => 'NOT IN', // Example: show only active deals
        //     ),
        // ),
    ];
    $deals_query = new WP_Query($args);

    ob_start();
    ?>
    <div class="paf-dashboard-pipeline-section">
        <div class="paf-pipeline-header">
            <h3>Deals Pipeline</h3>
            <!-- Add filters or search here if needed in future -->
        </div>
        <div class="paf-pipeline-table-container">
            <table class="paf-table paf-deals-pipeline-table">
                <thead>
                    <tr>
                        <th data-sort="client_name">Client Name <span class="paf-sort-icon"></span></th>
                        <th data-sort="start_date">Start Date <span class="paf-sort-icon"></span></th>
                        <th data-sort="status">Status <span class="paf-sort-icon"></span></th>
                        <th data-sort="next_action">Next Action <span class="paf-sort-icon"></span></th>
                        <th data-sort="vin">Vin# <span class="paf-sort-icon"></span></th>
                        <th data-sort="loan_num">Loan# <span class="paf-sort-icon"></span></th>
                        <th>Message Logs</th>
                        <th>Last Message Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($deals_query->have_posts()) : ?>
                        <?php while ($deals_query->have_posts()) : $deals_query->the_post(); ?>
                            <?php
                            $deal_id              = get_the_ID();
                            $credit_app_id        = get_post_meta($deal_id, '_credit_application_post_id', true);
                            $client_name          = 'N/A';
                            $vehicle_vin          = 'N/A';

                            if ($credit_app_id) {
                                $credit_app_post = get_post($credit_app_id);
                                if ($credit_app_post) {
                                    // Get client name from primary borrower data if possible
                                    $primary_borrower_json = get_post_meta($credit_app_id, '_primary_borrower', true);
                                    if ($primary_borrower_json) {
                                        $primary_borrower_data = json_decode($primary_borrower_json, true); // No need to decrypt here for name
                                        $client_name = esc_html($primary_borrower_data['personalInformation']['applicantName'] ?? $credit_app_post->post_title);
                                    } else {
                                       $client_name = esc_html( str_replace(' - App Data - ' . get_the_date('Y-m-d H:i:s', $credit_app_post), '', $credit_app_post->post_title) );
                                    }
                                }
                                $vehicle_json = get_post_meta($credit_app_id, '_vehicle_data', true);
                                if ($vehicle_json) {
                                    $vehicle_data = json_decode($vehicle_json, true);
                                    $vehicle_vin = esc_html($vehicle_data['vin'] ?? 'N/A');
                                }
                            }

                            $dt_ref_num        = get_post_meta($deal_id, '_dt_reference_number', true);
                            $deal_status_key   = get_post_meta($deal_id, '_status', true);
                            $deal_status_label = paf_get_deal_status_label($deal_status_key);
                            $next_action       = get_post_meta($deal_id, '_next_action_text', true); 
                                                       // Placeholder for message data - this would need more complex querying
                            $message_count_display = "0 messages"; // Default
                            $last_message_detail = "No messages yet."; // Default

                            $comms_args = [
                                'post_type' => 'paf_communication',
                                'posts_per_page' => 1,
                                'meta_key' => '_deal_post_id',
                                'meta_value' => $deal_id,
                                'orderby' => 'date',
                                'order' => 'DESC'
                            ];
                            $comms_query = new WP_Query($comms_args);
                            $total_comms = $comms_query->found_posts;

                            if ($total_comms > 0) {
                                $message_count_display = $total_comms . ' message' . ($total_comms > 1 ? 's' : '');
                                if ($comms_query->have_posts()) {
                                    $comms_query->the_post();
                                    $last_message_detail = esc_html(wp_trim_words(get_the_content(), 15, '...'));
                                }
                            }
                            wp_reset_postdata(); // Reset post data from comms_query
                            ?>
                            <tr>
                                <td><a href="<?php echo esc_url(add_query_arg('deal_id', $deal_id, home_url('/deal-jacket/'))); ?>"><?php echo esc_html($client_name); ?></a></td>
                                <td><?php echo get_the_date('n/j/Y', $deal_id); ?></td>
                                <td class="paf-status-<?php echo esc_attr($deal_status_key); ?>"><?php echo esc_html($deal_status_label); ?></td>
                                <td><?php echo esc_html($next_action); ?></td>
                                <td><?php echo esc_html($vehicle_vin); ?></td>
                                <td><?php echo esc_html($dt_ref_num); ?></td>
                                <td><a href="<?php echo esc_url(add_query_arg('deal_id', $deal_id, home_url('/deal-jacket/')) . '#paf-communications-log'); ?>"><?php echo esc_html($message_count_display); ?></a></td>
                                <td class="paf-last-message-details"><?php echo esc_html($last_message_detail); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); // Reset post data from deals_query ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8" class="paf-no-deals-found">No active deals found. <a href="<?php echo esc_url(home_url('/submit-new-deal/')); ?>">Submit a New Deal</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders the Account Manager section (placeholder).
 * This will be expanded when you're ready to implement it.
 */
function paf_render_dashboard_account_manager_section($dealer_cpt_id) {
    // Logic to get assigned account manager for $dealer_cpt_id
    // For now, static placeholder based on your image
    $rep_name = "J.C. Lopez";
    $rep_title = "Broker Finance Sales";
    $rep_email = "financeriskbroker@gmail.com";
    $rep_phone = "(786) 731-8493"; // Example
    $rep_address = "Miami, Florida"; // Example
    $rep_office_address = "345 Washington Street, Stoughton, MA 02072"; // Example
    $rep_image_url = PAF_CORE_PLUGIN_URL . 'assets/images/jc-lopez-placeholder.png'; // Provide a placeholder image

    // In a real scenario, you'd fetch this from user meta of the assigned account manager user
    // $assigned_rep_user_id = get_post_meta($dealer_cpt_id, '_assigned_account_rep_id', true);
    // if ($assigned_rep_user_id) {
    //    $rep_user_data = get_userdata($assigned_rep_user_id);
    //    $rep_name = $rep_user_data->display_name;
    //    $rep_email = $rep_user_data->user_email;
    //    $rep_phone = get_user_meta($assigned_rep_user_id, 'phone_number', true);
    //    $rep_image_url = get_avatar_url($assigned_rep_user_id) or um_get_profile_photo_url...
    // }


    ob_start();
    ?>
    <div class="paf-dashboard-account-manager-section">
        <div class="paf-account-manager-image">
            <img src="<?php echo esc_url($rep_image_url); ?>" alt="<?php echo esc_attr($rep_name); ?>">
        </div>
        <div class="paf-account-manager-details">
            <h4><?php echo esc_html($rep_name); ?></h4>
            <p class="paf-rep-title"><?php echo esc_html($rep_title); ?></p>
            <ul class="paf-rep-contact-info">
                <?php if ($rep_email) : ?><li><span class="dashicons dashicons-email-alt"></span> <a href="mailto:<?php echo esc_attr($rep_email); ?>"><?php echo esc_html($rep_email); ?></a></li><?php endif; ?>
                <?php if ($rep_phone) : ?><li><span class="dashicons dashicons-phone"></span> <?php echo esc_html($rep_phone); ?></li><?php endif; ?>
                <?php if ($rep_address) : ?><li><span class="dashicons dashicons-location"></span> <?php echo esc_html($rep_address); ?></li><?php endif; ?>
                <?php if ($rep_office_address) : ?><li><span class="dashicons dashicons-admin-home"></span> <?php echo esc_html($rep_office_address); ?></li><?php endif; ?>
            </ul>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

?>
