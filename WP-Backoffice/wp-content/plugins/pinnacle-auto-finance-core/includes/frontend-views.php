<?php
// /wp-content/plugins/pinnacle-auto-finance-core/includes/frontend-views.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function paf_core_register_view_shortcodes() {
    add_shortcode( 'paf_dealer_dashboard', 'paf_render_dealer_dashboard' );
    add_shortcode( 'paf_deal_jacket_view', 'paf_render_deal_jacket_view' );
    add_shortcode( 'paf_application_confirmation', 'paf_render_application_confirmation_page' );
    // The 'paf_deal_documents' shortcode is now better placed within the deal jacket view or dashboard partials if needed directly.
    // For now, it's called from paf_render_deal_jacket_view.
}

function paf_render_dealer_dashboard() {
    // Check login
    if (!is_user_logged_in()) {
        return '<p class="paf-alert paf-alert-warning">You must be logged in to view the dashboard.</p>';
    }

    $current_user = wp_get_current_user();
    $dealer_cpt_id = paf_get_current_user_dealer_id();

    // --- Logic to show initial profile form if needed ---
    if (!$dealer_cpt_id) {
        return paf_render_initial_dealer_profile_form();
    }
    
    $dealer_cpt_status = get_post_meta($dealer_cpt_id, '_status', true);
    $dealership_legal_name = get_post_meta($dealer_cpt_id, '_dealership_legal_name', true);

    if (empty($dealership_legal_name) && $dealer_cpt_status !== 'approved') {
        return paf_render_initial_dealer_profile_form();
    }

    // --- Logic for pending or other non-approved statuses ---
    if ($dealer_cpt_status === 'pending_approval') {
        return '<p class="paf-alert paf-alert-info">Your dealer profile has been submitted and is currently pending final approval. You will receive an email once your account has been fully activated.</p>';
    } elseif ($dealer_cpt_status !== 'approved') {
        return '<p class="paf-alert paf-alert-warning">Your dealer account status is currently: ' . esc_html(ucfirst(str_replace('_', ' ', $dealer_cpt_status))) . '. Dashboard access requires an approved dealer account. Please contact support.</p>';
    }

    // --- If status is 'approved', check capability for full dashboard ---
    if (!current_user_can('paf_view_dealer_dashboard')) {
         return '<p class="paf-alert paf-alert-warning">Your account is approved but dashboard access is not yet fully enabled. Please contact support or try again shortly.</p>';
    }
    
    // --- All checks passed — Render the full dashboard using partials ---

    // Enqueue dashboard-specific assets (actual enqueueing will be in main plugin file or paf_core_enqueue_scripts)
    // We ensure they are marked for loading if this shortcode is processed.
    wp_enqueue_script('paf-dashboard-js');
    wp_enqueue_style('paf-dashboard-css');
    wp_enqueue_style('dashicons'); // Ensure Dashicons are available for icons

    ob_start();
    ?>
    <div class="paf-dashboard-container">
        <div class="paf-dashboard-grid">
            <div class="paf-dashboard-left-column">
                <?php 
                if (function_exists('paf_render_dashboard_profile_section')) {
                    echo paf_render_dashboard_profile_section($dealer_cpt_id, $current_user);
                } else {
                    echo '<div class="paf-dashboard-profile-widget"><p>Profile section unavailable</p></div>';
                }
                ?>
                
                <?php
                if (function_exists('paf_render_dashboard_prequal_image_section')) {
                    echo paf_render_dashboard_prequal_image_section();
                } else {
                    echo '<div class="paf-dashboard-prequal-section"><p>Submit New Deal section unavailable</p></div>';
                }
                ?>
                
                <?php
                if (function_exists('paf_render_dashboard_account_manager_section')) {
                    echo paf_render_dashboard_account_manager_section($dealer_cpt_id);
                } else {
                    echo '<div class="paf-dashboard-account-manager-section"><p>Account manager section unavailable</p></div>';
                }
                ?>
            </div>

            <div class="paf-dashboard-right-column">
                <?php
                if (function_exists('paf_render_dashboard_pipeline_section')) {
                    echo paf_render_dashboard_pipeline_section($current_user->ID);
                } else {
                    echo '<div class="paf-dashboard-pipeline-section"><h3>Deals Pipeline</h3><p>Pipeline section unavailable</p></div>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}


// --- paf_render_initial_dealer_profile_form() function remains unchanged ---
// --- (Copied from your provided file for completeness of this file's context) ---
function paf_render_initial_dealer_profile_form() {
    ob_start();
    ?>
    <div class="paf-initial-profile-form-container">
        <h4>Your dealer profile is not yet fully set up. Please complete the form below to gain full access to the portal.</h4>
        <form id="pafInitialDealerProfileForm" method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <input type="hidden" name="action" value="paf_submit_initial_dealer_profile">
            <?php wp_nonce_field( 'paf_initial_profile_nonce', 'paf_initial_profile_nonce_field' ); ?>

            <table class="paf-profile-table">
                <tr>
                    <td><label for="paf_dl_name">Dealership Legal Name <span class="paf-required">(Must match dealer license)</span></label></td>
                    <td><input type="text" id="paf_dl_name" name="dealer_legal_name" required></td>
                    <td><label for="paf_dl_license">Dealer License #</label></td>
                    <td><input type="text" id="paf_dl_license" name="dealer_license_num" required></td>
                </tr>
                <tr>
                    <td><label for="paf_dl_address">Address/City/State/Zip</label></td>
                    <td>
                        <input type="text" name="dealer_address" placeholder="Street Address" required style="margin-bottom:5px; width:98%;">
                        <input type="text" name="dealer_city" placeholder="City" required style="width:48%; margin-right:2%;">
                        <input type="text" name="dealer_state" placeholder="State" required style="width:15%; margin-right:2%;" maxlength="2">
                        <input type="text" name="dealer_zip" placeholder="Zip" required style="width:29%;">
                    </td>
                    <td><label for="paf_dl_contact_person">Contact Person for approval or counter offer?</label></td>
                    <td><input type="text" id="paf_dl_contact_person" name="dealer_contact_for_approval" required></td>
                </tr>
                <tr>
                    <td><label for="paf_dl_owner_name">Owner Full Name (Principal)</label></td>
                    <td><input type="text" id="paf_dl_owner_name" name="dealer_owner_full_name" required></td>
                    <td><label for="paf_dl_contact_cell">What is their cell phone?</label></td>
                    <td><input type="tel" id="paf_dl_contact_cell" name="dealer_contact_cell_phone" required></td>
                </tr>
                <tr>
                    <td><label for="paf_dl_phone">Dealership Phone #</label></td>
                    <td><input type="tel" id="paf_dl_phone" name="dealer_phone" required></td>
                    <td><label for="paf_dl_approval_email">Where should we email approvals?</label></td>
                    <td><input type="email" id="paf_dl_approval_email" name="dealer_email_for_approvals" required></td>
                </tr>
                <tr>
                    <td><label for="paf_dl_fax">Fax #</label></td>
                    <td><input type="tel" id="paf_dl_fax" name="dealer_fax"></td>
                    <td><label for="paf_dl_tax_id">Federal Tax ID (EIN)</label></td>
                    <td><input type="text" id="paf_dl_tax_id" name="dealer_federal_tax_id" required></td>
                </tr>
            </table>
            <p><button type="submit" class="paf-button save-button">Save Profile</button></p>
        </form>
    </div>
    <style> /* Consider moving this to your main CSS file */
        .paf-profile-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .paf-profile-table td { vertical-align: top; padding: 5px; }
        .paf-profile-table label { font-weight: bold; display: block; margin-bottom: 3px; }
        .paf-profile-table input[type="text"], .paf-profile-table input[type="tel"], .paf-profile-table input[type="email"] { width: 95%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .paf-required { color: #777; font-size: 0.9em; font-weight:normal; }
        .save-button { background-color: #6e50c4; border-color: #6e50c4; padding: 10px 25px; font-size: 16px; }
    </style>
    <?php
    return ob_get_clean();
}

// --- paf_handle_initial_dealer_profile_submission() function remains unchanged ---
// --- (Copied from your provided file for completeness) ---
function paf_handle_initial_dealer_profile_submission() {
    if ( ! is_user_logged_in() || ! isset( $_POST['paf_initial_profile_nonce_field'] ) || ! wp_verify_nonce( $_POST['paf_initial_profile_nonce_field'], 'paf_initial_profile_nonce' ) ) {
        wp_die('Security check failed or not logged in.');
    }

    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $dealer_cpt_id = paf_get_current_user_dealer_id();

    if ( !$dealer_cpt_id ) {
        $dealer_legal_name = isset($_POST['dealer_legal_name']) ? sanitize_text_field($_POST['dealer_legal_name']) : '';
        $dealer_title = !empty($dealer_legal_name) ? $dealer_legal_name : ($current_user->display_name . ' (Dealership)');
        $dealer_cpt_id = wp_insert_post([
            'post_type' => 'paf_dealer',
            'post_title' => $dealer_title,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        if (is_wp_error($dealer_cpt_id) || !$dealer_cpt_id) {
            wp_die('Error creating dealer profile. Please contact support. ' . ($dealer_cpt_id ? $dealer_cpt_id->get_error_message() : ''));
        }
        update_post_meta( $dealer_cpt_id, '_associated_user_id', $user_id );
        if (function_exists('paf_generate_unique_id')) {
            update_post_meta( $dealer_cpt_id, '_paf_dealer_id', paf_generate_unique_id('PAD_') );
        }
    }
    
    $meta_map_cpt = [
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

    foreach ($meta_map_cpt as $cpt_key => $form_key) {
        if (isset($_POST[$form_key])) {
            $value = sanitize_text_field($_POST[$form_key]);
            update_post_meta($dealer_cpt_id, $cpt_key, $value);
            update_user_meta($user_id, $form_key, $value); 
        }
    }
    
    update_post_meta( $dealer_cpt_id, '_status', 'pending_approval' );
    update_post_meta( $dealer_cpt_id, '_profile_completed_date', current_time('mysql') );

    $dashboard_url = um_get_core_page('user');
    if (!$dashboard_url) $dashboard_url = home_url('/');
    
    wp_redirect( add_query_arg('profile_updated', 'true', $dashboard_url) );
    exit;
}


// --- paf_render_deal_jacket_view() function remains unchanged ---
// --- (Copied from your provided file for completeness) ---
function paf_render_deal_jacket_view() {
    if ( ! is_user_logged_in() || ! isset( $_GET['deal_id'] ) ) {
        return '<p class="paf-alert paf-alert-warning">Invalid request or insufficient permissions.</p>';
    }

    $deal_id = intval( $_GET['deal_id'] );
    $deal    = get_post( $deal_id );

    if ( ! $deal || $deal->post_type !== 'paf_deal' || ( $deal->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
        return '<p class="paf-alert paf-alert-warning">Deal not found or you do not have permission to view it.</p>';
    }

    $credit_app_id       = get_post_meta( $deal_id, '_credit_application_post_id', true );
    $primary_borrower    = []; $co_borrower = []; $vehicle_data = []; $financial_data = [];
    $applicant_name_display = "N/A";

    if ( $credit_app_id ) {
        $primary_borrower_json = get_post_meta( $credit_app_id, '_primary_borrower', true );
        if ( $primary_borrower_json ) $primary_borrower = json_decode( paf_decrypt_borrower_data( $primary_borrower_json ), true );
        
        $co_borrower_json = get_post_meta( $credit_app_id, '_co_borrower', true );
        if ( $co_borrower_json ) $co_borrower = json_decode( paf_decrypt_borrower_data( $co_borrower_json ), true );

        $vehicle_data_json = get_post_meta( $credit_app_id, '_vehicle_data', true );
        if ( $vehicle_data_json ) $vehicle_data = json_decode( $vehicle_data_json, true );

        $financial_data_json = get_post_meta( $credit_app_id, '_financial_data', true );
        if ( $financial_data_json ) $financial_data = json_decode( $financial_data_json, true );

        $applicant_name_display = esc_html( $primary_borrower['personalInformation']['applicantName'] ?? 'N/A' );
    }

    $deal_jacket_data_json = get_post_meta( $deal_id, '_deal_jacket_data', true );
    $deal_jacket_data      = $deal_jacket_data_json ? json_decode( $deal_jacket_data_json, true ) : [];
    $current_deal_status_key = get_post_meta( $deal_id, '_status', true );

    ob_start();
    ?>
    <div class="paf-deal-jacket-view">
        <h2>Deal Jacket: <?php echo $applicant_name_display; ?> - <?php echo esc_html( get_post_meta( $deal_id, '_dt_reference_number', true ) ); ?></h2>
        <div class="deal-status-bar"><?php echo paf_get_deal_status_bar_html( $current_deal_status_key ); ?></div>
        <div class="paf-grid">
            <div class="paf-grid-col-8">
                <div class="paf-section">
                    <h3>Customer & Deal Information <button id="viewCreditAppDetailsBtn" class="paf-button-small">View Full Application</button></h3>
                    <div id="fullCreditAppDetails" style="display:none; border:1px solid #ccc; padding:15px; margin-top:10px;">
                        <h4>Primary Borrower</h4>
                        <p>Name: <?php echo esc_html( $primary_borrower['personalInformation']['applicantName'] ?? 'N/A' ); ?></p>
                        <p>SSN: <?php echo esc_html( paf_mask_ssn( $primary_borrower['personalInformation']['ssn_decrypted'] ?? '' ) ); ?></p>
                        <p>DOB: <?php echo esc_html( $primary_borrower['personalInformation']['dateOfBirth'] ?? 'N/A' ); ?></p>
                        <p>Address: <?php echo esc_html( $primary_borrower['addressInformation']['current']['address'] ?? 'N/A' ); ?></p>
                        <?php if ( $co_borrower && isset($co_borrower['personalInformation']['applicantName']) ) : ?>
                            <h4>Co-Borrower</h4>
                            <p>Name: <?php echo esc_html( $co_borrower['personalInformation']['applicantName'] ); ?></p>
                        <?php endif; ?>
                        <h4>Vehicle Information (from Application)</h4>
                        <p>Year: <?php echo esc_html( $vehicle_data['year'] ?? 'N/A' ); ?> Make/Model: <?php echo esc_html( $vehicle_data['makeAndModel'] ?? 'N/A' ); ?></p>
                        <h4>Financial Information (from Application)</h4>
                        <p>Selling Price: $<?php echo esc_html( number_format( floatval( $financial_data['sellingPrice'] ?? 0 ), 2 ) ); ?></p>
                    </div>
                    <p><strong>Applicant:</strong> <?php echo esc_html( $primary_borrower['personalInformation']['applicantName'] ?? 'N/A' ); ?></p>
                    <p><strong>Address:</strong> <?php echo esc_html( $primary_borrower['addressInformation']['current']['address'] ?? 'N/A' ); ?></p>
                    <p><strong>SSN:</strong> <?php echo esc_html( paf_mask_ssn( $primary_borrower['personalInformation']['ssn_decrypted'] ?? '' ) ); ?></p>
                    <p><strong>Annual Income:</strong> $<?php echo esc_html( number_format( floatval( $primary_borrower['employmentInformation']['current']['income'] ?? 0 ) * 12, 2 ) ); /* Assuming monthly income stored */ ?></p>
                </div>
                <div class="paf-section">
                    <h3>Conversations History</h3>
                    <div class="paf-communications-log" id="paf-communications-log">
                        <?php
                        $comms_args = ['post_type' => 'paf_communication', 'meta_key' => '_deal_post_id', 'meta_value' => $deal_id, 'orderby' => 'date', 'order' => 'DESC'];
                        $comms_query = new WP_Query( $comms_args );
                        if ( $comms_query->have_posts() ) : while ( $comms_query->have_posts() ) : $comms_query->the_post(); ?>
                            <div class="paf-comm-entry">
                                <p><strong><?php echo esc_html(get_the_author_meta('display_name')); ?> (<?php echo esc_html(get_post_meta(get_the_ID(), '_sender_type', true)); ?>)</strong> - <?php echo get_the_date(); ?></p>
                                <div><?php the_content(); ?></div>
                            </div>
                        <?php endwhile; wp_reset_postdata(); else : echo '<p>No communications yet.</p>'; endif; ?>
                    </div>
                    <form id="pafNewCommunicationForm" method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="paf_submit_communication">
                        <input type="hidden" name="deal_id" value="<?php echo esc_attr( $deal_id ); ?>">
                        <?php wp_nonce_field( 'paf_submit_communication_nonce', 'paf_comm_nonce' ); ?>
                        <textarea name="message_content" rows="4" placeholder="Enter new message..." required></textarea>
                        <button type="submit" class="paf-button">Send Message</button>
                    </form>
                </div>
            </div>
            <div class="paf-grid-col-4">
                <div class="paf-section">
                    <h3>Deal Information (from DealerTrack)</h3>
                    <p><strong>Collateral Type:</strong> <?php echo esc_html( $deal_jacket_data['collateralType'] ?? ( $vehicle_data['type'] ?? 'N/A' ) ); ?></p>
                    <p><strong>Selling Price:</strong> $<?php echo esc_html( number_format( floatval( $deal_jacket_data['sellingPrice'] ?? ( $financial_data['sellingPrice'] ?? 0 ) ), 2 ) ); ?></p>
                    <p><strong>Sales Tax:</strong> $<?php echo esc_html( number_format( floatval( $deal_jacket_data['salesTax'] ?? ( $financial_data['taxes'] ?? 0 ) ), 2 ) ); ?></p>
                    <p><strong>Total Down:</strong> $<?php echo esc_html( number_format( floatval( $deal_jacket_data['totalDown'] ?? ( $financial_data['totalCashDown'] ?? 0 ) ), 2 ) ); ?></p>
                    <p><strong>Amount Requested:</strong> $<?php echo esc_html( number_format( floatval( $deal_jacket_data['amountRequested'] ?? ( $financial_data['amountFinanced'] ?? 0 ) ), 2 ) ); ?></p>
                    <p><strong>Term:</strong> <?php echo esc_html( $deal_jacket_data['term'] ?? ( $financial_data['termMonths'] ?? 'N/A' ) ); ?> months</p>
                    <p><strong>VIN:</strong> <?php echo esc_html( $deal_jacket_data['vin'] ?? ( $vehicle_data['vin'] ?? 'N/A' ) ); ?></p>
                </div>
                <div class="paf-section">
                    <h3>Documents</h3>
                    <?php echo paf_render_deal_documents_shortcode_content( $deal_id ); // Direct function call ?>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var viewDetailsBtn = document.getElementById('viewCreditAppDetailsBtn');
                if(viewDetailsBtn) {
                    viewDetailsBtn.addEventListener('click', function() {
                        var detailsDiv = document.getElementById('fullCreditAppDetails');
                        if(detailsDiv) {
                            detailsDiv.style.display = detailsDiv.style.display === 'none' ? 'block' : 'none';
                        }
                    });
                }
            });
        </script>
    </div>
    <?php
    return ob_get_clean();
}

// Renamed the function to avoid conflict and allow direct call.
function paf_render_deal_documents_shortcode_content( $deal_id ) {
    if ( ! $deal_id ) return '<p>No deal ID provided for documents.</p>';

    $docs_args = ['post_type' => 'paf_document', 'meta_key' => '_deal_post_id', 'meta_value' => $deal_id, 'posts_per_page' => -1];
    $docs_query = new WP_Query( $docs_args );
    ob_start();
    if ( $docs_query->have_posts() ) : ?>
        <ul class="paf-document-list">
        <?php while ( $docs_query->have_posts() ) : $docs_query->the_post();
            $file_id = get_post_meta( get_the_ID(), '_file_id', true );
            $file_url = $file_id ? wp_get_attachment_url( $file_id ) : '#';
            $doc_type = get_post_meta( get_the_ID(), '_document_type', true );
            $custom_name = get_post_meta( get_the_ID(), '_custom_document_name', true );
            $display_name = ( $doc_type === 'Other' && $custom_name ) ? $custom_name : $doc_type;
        ?>
            <li><a href="<?php echo esc_url( $file_url ); ?>" target="_blank"><?php echo esc_html( get_the_title() . ' (' . $display_name . ')' ); ?></a></li>
        <?php endwhile; wp_reset_postdata(); ?>
        </ul>
    <?php else : echo '<p>No documents uploaded for this deal yet.</p>'; endif; ?>
    <h4>Upload Document</h4>
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" class="paf-upload-form">
        <input type="hidden" name="action" value="paf_upload_deal_document">
        <input type="hidden" name="deal_id" value="<?php echo esc_attr( $deal_id ); ?>">
        <div class="paf-form-field"><label for="deal_document_file_<?php echo esc_attr($deal_id); ?>">File:</label><input id="deal_document_file_<?php echo esc_attr($deal_id); ?>" type="file" name="deal_document_file" required></div>
        <div class="paf-form-field">
            <label for="document_type_<?php echo esc_attr($deal_id); ?>">Type:</label>
            <select id="document_type_<?php echo esc_attr($deal_id); ?>" name="document_type" required>
                <option value="">Select Document Type</option>
                <option value="Bill of Sale">Bill of Sale</option><option value="Title">Title</option>
                <option value="Insurance">Insurance</option><option value="Other">Other</option>
            </select>
        </div>
        <div class="paf-form-field"><label for="custom_document_name_<?php echo esc_attr($deal_id); ?>">Custom Name (if Other):</label><input id="custom_document_name_<?php echo esc_attr($deal_id); ?>" type="text" name="custom_document_name" placeholder="Custom Name"></div>
        <?php wp_nonce_field( 'paf_upload_deal_document_nonce', 'paf_upload_doc_nonce' ); ?>
        <button type="submit" class="paf-button">Upload Document</button>
    </form>
    <?php
    return ob_get_clean();
}
// Keep the shortcode registration if you still want to use [paf_deal_documents] elsewhere.
add_shortcode( 'paf_deal_documents', 'paf_render_deal_documents_shortcode_wrapper' );
function paf_render_deal_documents_shortcode_wrapper($atts){
    $atts = shortcode_atts( [ 'deal_id' => 0 ], $atts );
    return paf_render_deal_documents_shortcode_content(intval($atts['deal_id']));
}


// --- paf_render_application_confirmation_page() function remains unchanged ---
// --- (Copied from your provided file for completeness) ---
function paf_render_application_confirmation_page() {
    if ( ! isset( $_GET['app_ref'] ) ) { // Changed from app_id to app_ref to match form handler
        return '<p class="paf-alert paf-alert-warning">No application reference provided.</p>';
    }

    $app_id = intval( $_GET['app_ref'] ); // Changed from app_id
    $app    = get_post( $app_id );

    if ( ! $app || $app->post_type !== 'paf_credit_app' ) {
        return '<p class="paf-alert paf-alert-warning">Application not found.</p>';
    }
    
    $primary_borrower_json = get_post_meta( $app_id, '_primary_borrower', true );
    $primary_borrower_data = $primary_borrower_json ? json_decode( paf_decrypt_borrower_data($primary_borrower_json), true ) : [];
    $applicant_name = esc_html( $primary_borrower_data['personalInformation']['applicantName'] ?? 'N/A' );
    // Using post ID as ref for now, unless you set a specific _application_reference meta
    $app_ref_display = esc_html( $app_id ); 

    ob_start();
    ?>
    <div class="paf-app-confirmation">
        <h2>Application Submitted Successfully</h2>
        <div class="paf-app-confirmation-details">
            <p>Thank you, <strong><?php echo $applicant_name; ?></strong>!</p>
            <p>Your credit application has been successfully submitted to our financing team.</p>
            <p>Your Pinnacle Application ID: <strong><?php echo $app_ref_display; ?></strong></p>
            <p>Please save this reference number for your records. You will receive updates regarding your DealerTrack submission shortly.</p>
            <p>What happens next?</p>
            <ol>
                <li>Your application is being prepared for submission to DealerTrack.</li>
                <li>Once submitted to DealerTrack, our financing team will review your application promptly.</li>
                <li>You will receive an email with the decision.</li>
                <li>If approved, a representative will contact you to discuss next steps.</li>
            </ol>
            <p>If you have any questions, please contact our customer service team.</p>
            <p><a href="<?php echo esc_url( um_get_core_page('user') ? um_get_core_page('user') : home_url( '/' ) ); ?>" class="paf-button">Return to Dashboard</a></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

?>
