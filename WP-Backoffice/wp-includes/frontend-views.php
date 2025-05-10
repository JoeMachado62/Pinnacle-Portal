<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function paf_core_register_view_shortcodes() {
    add_shortcode( 'paf_dealer_dashboard', 'paf_render_dealer_dashboard' );
    add_shortcode( 'paf_deal_jacket_view', 'paf_render_deal_jacket_view' );
    add_shortcode( 'paf_application_confirmation', 'paf_render_application_confirmation_page' );
}

function paf_render_dealer_dashboard() {
    if ( ! is_user_logged_in() || ! current_user_can('paf_view_dealer_dashboard') ) { // Define this capability
        return '<p class="paf-alert paf-alert-warning">Access Denied. Please log in as a dealer.</p>';
    }

    $dealer_cpt_id = paf_get_current_user_dealer_id();
    if ( ! $dealer_cpt_id ) {
        return '<p class="paf-alert paf-alert-info">Your dealer profile is not yet fully set up. Please contact support.</p>';
    }
    $dealer_status = get_post_meta($dealer_cpt_id, '_status', true);
    if ($dealer_status !== 'approved') {
         return '<p class="paf-alert paf-alert-info">Your dealer account is currently '.esc_html($dealer_status).'. You cannot access the dashboard until approved.</p>';
    }


    ob_start();
    ?>
    <div class="paf-dealer-dashboard">
        <h2>Dealer Dashboard</h2>

        <div class="paf-dashboard-actions">
             <a href="<?php echo esc_url(home_url('/submit-new-deal/')); ?>" class="paf-button">Submit New Deal</a>
             <a href="<?php echo esc_url(home_url('/dealer-profile/')); ?>" class="paf-button">Edit Profile</a>
        </div>

        <h3>Your Active Deals</h3>
        <?php
        $current_user_id = get_current_user_id();
        $args = [
            'post_type' => 'paf_deal',
            'author' => $current_user_id, // Assuming dealer user is the author of paf_deal
            'posts_per_page' => 20, // Add pagination later
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        $deals_query = new WP_Query( $args );

        if ( $deals_query->have_posts() ) :
        ?>
            <table class="paf-table paf-deals-pipeline">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Vehicle</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th>Next Action</th>
                        <th>Loan # (DT Ref)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ( $deals_query->have_posts() ) : $deals_query->the_post(); ?>
                        <?php
                        $deal_id = get_the_ID();
                        $credit_app_id = get_post_meta( $deal_id, '_credit_application_post_id', true );
                        $client_name = 'N/A';
                        $vehicle_info_display = 'N/A';

                        if ( $credit_app_id ) {
                            $primary_borrower_json = get_post_meta( $credit_app_id, '_primary_borrower', true );
                            $vehicle_data_json = get_post_meta( $credit_app_id, '_vehicle_data', true );

                            if ( $primary_borrower_json ) {
                                $primary_borrower = json_decode( $primary_borrower_json, true );
                                $client_name = esc_html( ($primary_borrower['personalInformation']['applicantName'] ?? 'N/A') );
                            }
                            if ( $vehicle_data_json ) {
                                $vehicle_data = json_decode( $vehicle_data_json, true );
                                $vehicle_info_display = esc_html( ($vehicle_data['year'] ?? '') . ' ' . ($vehicle_data['makeAndModel'] ?? '') );
                            }
                        }
                        $dt_ref_num = get_post_meta( $deal_id, '_dt_reference_number', true );
                        $deal_status_key = get_post_meta( $deal_id, '_status', true );
                        $deal_status_label = paf_get_deal_status_label($deal_status_key); // Helper function
                        $next_action = get_post_meta( $deal_id, '_next_action_text', true );
                        ?>
                        <tr>
                            <td><?php echo $client_name; ?></td>
                            <td><?php echo $vehicle_info_display; ?></td>
                            <td><?php echo get_the_date( 'm/d/Y', $deal_id ); ?></td>
                            <td><?php echo esc_html( $deal_status_label ); ?></td>
                            <td><?php echo esc_html( $next_action ); ?></td>
                            <td><?php echo esc_html( $dt_ref_num ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( 'deal_id', $deal_id, home_url('/deal-jacket/') ) ); ?>">View Deal</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p>No deals found.</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}


function paf_render_deal_jacket_view() {
    if ( ! is_user_logged_in() || ! isset($_GET['deal_id']) ) {
        return '<p class="paf-alert paf-alert-warning">Invalid request or insufficient permissions.</p>';
    }

    $deal_id = intval($_GET['deal_id']);
    $deal = get_post($deal_id);

    if ( ! $deal || $deal->post_type !== 'paf_deal' || $deal->post_author != get_current_user_id() && !current_user_can('manage_options') ) {
         // Also check if admin/finance manager has rights to view any deal
        return '<p class="paf-alert paf-alert-warning">Deal not found or you do not have permission to view it.</p>';
    }

    $credit_app_id = get_post_meta( $deal_id, '_credit_application_post_id', true );
    $primary_borrower = [];
    $co_borrower = [];
    $vehicle_data = [];
    $financial_data = [];
    $applicant_name_display = "N/A";

    if ($credit_app_id) {
        $app_post = get_post($credit_app_id);
        $primary_borrower_json = get_post_meta( $credit_app_id, '_primary_borrower', true );
        $co_borrower_json = get_post_meta( $credit_app_id, '_co_borrower', true );
        $vehicle_data_json = get_post_meta( $credit_app_id, '_vehicle_data', true );
        $financial_data_json = get_post_meta( $credit_app_id, '_financial_data', true ); // from credit app

        if ($primary_borrower_json) $primary_borrower = json_decode(paf_decrypt_borrower_data($primary_borrower_json), true); // Decrypt for display
        if ($co_borrower_json) $co_borrower = json_decode(paf_decrypt_borrower_data($co_borrower_json), true);
        if ($vehicle_data_json) $vehicle_data = json_decode($vehicle_data_json, true);
        if ($financial_data_json) $financial_data = json_decode($financial_data_json, true); // financial data from initial app

        $applicant_name_display = esc_html( $primary_borrower['personalInformation']['applicantName'] ?? 'N/A' );
    }
    
    $deal_jacket_data_json = get_post_meta($deal_id, '_deal_jacket_data', true);
    $deal_jacket_data = $deal_jacket_data_json ? json_decode($deal_jacket_data_json, true) : []; // financial data from DT deal jacket

    $current_deal_status_key = get_post_meta( $deal_id, '_status', true );

    ob_start();
    ?>
    <div class="paf-deal-jacket-view">
        <h2>Deal Jacket: <?php echo $applicant_name_display; ?> - <?php echo esc_html(get_post_meta($deal_id, '_dt_reference_number', true)); ?></h2>

        <!-- DEAL STATUS BAR (matches mockup) -->
        <div class="deal-status-bar">
            <?php echo paf_get_deal_status_bar_html($current_deal_status_key); // Helper to generate the visual bar ?>
        </div>

        <div class="paf-grid">
            <div class="paf-grid-col-8">
                <!-- Customer & Deal Information (from paf_credit_app primarily) -->
                <div class="paf-section">
                    <h3>Customer & Deal Information <button id="viewCreditAppDetailsBtn" class="paf-button-small">View Full Application</button></h3>
                    <div id="fullCreditAppDetails" style="display:none; border:1px solid #ccc; padding:15px; margin-top:10px;">
                        <h4>Primary Borrower</h4>
                        <p>Name: <?php echo esc_html($primary_borrower['personalInformation']['applicantName'] ?? 'N/A'); ?></p>
                        <p>SSN: <?php echo esc_html(paf_mask_ssn($primary_borrower['personalInformation']['ssn_decrypted'] ?? '')); // Assuming decryption adds _decrypted suffix ?></p>
                        <p>DOB: <?php echo esc_html($primary_borrower['personalInformation']['dateOfBirth'] ?? 'N/A'); ?></p>
                        <p>Address: <?php echo esc_html($primary_borrower['addressInformation']['current']['address'] ?? 'N/A'); ?></p>
                        <!-- ... more primary borrower fields from paf_credit_app ... -->
                        
                        <?php if ($co_borrower): ?>
                        <h4>Co-Borrower</h4>
                        <p>Name: <?php echo esc_html($co_borrower['personalInformation']['applicantName'] ?? 'N/A'); ?></p>
                        <!-- ... more co-borrower fields ... -->
                        <?php endif; ?>

                        <h4>Vehicle Information (from Application)</h4>
                        <p>Year: <?php echo esc_html($vehicle_data['year'] ?? 'N/A'); ?></p>
                        <p>Make/Model: <?php echo esc_html($vehicle_data['makeAndModel'] ?? 'N/A'); ?></p>
                        <!-- ... more vehicle fields ... -->

                        <h4>Financial Information (from Application)</h4>
                        <p>Selling Price: $<?php echo esc_html(number_format(floatval($financial_data['sellingPrice'] ?? 0), 2)); ?></p>
                        <!-- ... more financial fields from app ... -->
                    </div>
                    <!-- Display key summary info directly if needed, matching mockup page 3 top left -->
                     <p><strong>Applicant:</strong> <?php echo esc_html($primary_borrower['personalInformation']['applicantName'] ?? 'N/A'); ?></p>
                     <p><strong>Address:</strong> <?php echo esc_html($primary_borrower['addressInformation']['current']['address'] ?? 'N/A'); ?></p>
                     <p><strong>SSN:</strong> <?php echo esc_html(paf_mask_ssn($primary_borrower['personalInformation']['ssn_decrypted'] ?? '')); ?></p>
                     <p><strong>Annual Income:</strong> $<?php /* Calculate from monthly if stored that way */ echo esc_html(number_format(floatval($primary_borrower['employmentInformation']['current']['income'] ?? 0) * ($primary_borrower['employmentInformation']['current']['payFrequency'] == 'A' ? 1 : 12),2) ); ?></p>
                </div>

                <!-- Conversations History -->
                <div class="paf-section">
                    <h3>Conversations History</h3>
                    <div class="paf-communications-log">
                        <?php
                        $comms_args = [
                            'post_type' => 'paf_communication',
                            'meta_key' => '_deal_post_id',
                            'meta_value' => $deal_id,
                            'orderby' => 'date',
                            'order' => 'DESC'
                        ];
                        $comms_query = new WP_Query($comms_args);
                        if ($comms_query->have_posts()):
                            while ($comms_query->have_posts()): $comms_query->the_post();
                                $sender = get_the_author_meta('display_name');
                                $sender_type = get_post_meta(get_the_ID(), '_sender_type', true);
                        ?>
                                <div class="paf-comm-entry">
                                    <p><strong><?php echo esc_html($sender); ?> (<?php echo esc_html($sender_type); ?>)</strong> - <?php echo get_the_date(); ?></p>
                                    <div><?php the_content(); ?></div>
                                    <?php // Add attachment display logic here ?>
                                </div>
                        <?php
                            endwhile;
                            wp_reset_postdata();
                        else:
                            echo '<p>No communications yet.</p>';
                        endif;
                        ?>
                    </div>
                    <form id="pafNewCommunicationForm" method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="paf_submit_communication">
                        <input type="hidden" name="deal_id" value="<?php echo esc_attr($deal_id); ?>">
                        <?php wp_nonce_field('paf_submit_communication_nonce', 'paf_comm_nonce'); ?>
                        <textarea name="message_content" rows="4" placeholder="Enter new message..." required></textarea>
                        <?php // Add file upload for attachments here if needed (more complex) ?>
                        <button type="submit" class="paf-button">Send Message</button>
                    </form>
                </div>
            </div>

            <div class="paf-grid-col-4">
                <!-- Deal Information (from paf_deal _deal_jacket_data, matching mockup page 3 right side) -->
                <div class="paf-section">
                    <h3>Deal Information (from DealerTrack)</h3>
                    <p><strong>Collateral Type:</strong> <?php echo esc_html($deal_jacket_data['collateralType'] ?? ($vehicle_data['type'] ?? 'N/A')); ?></p>
                    <p><strong>Selling Price:</strong> $<?php echo esc_html(number_format(floatval($deal_jacket_data['sellingPrice'] ?? ($financial_data['sellingPrice'] ?? 0)), 2)); ?></p>
                    <p><strong>Sales Tax:</strong> $<?php echo esc_html(number_format(floatval($deal_jacket_data['salesTax'] ?? ($financial_data['taxes'] ?? 0)), 2)); ?></p>
                    <p><strong>Total Down:</strong> $<?php echo esc_html(number_format(floatval($deal_jacket_data['totalDown'] ?? ($financial_data['totalCashDown'] ?? 0)), 2)); ?></p>
                    <p><strong>Amount Requested:</strong> $<?php echo esc_html(number_format(floatval($deal_jacket_data['amountRequested'] ?? ($financial_data['amountFinanced'] ?? 0)), 2)); ?></p>
                    <p><strong>Term:</strong> <?php echo esc_html($deal_jacket_data['term'] ?? ($financial_data['termMonths'] ?? 'N/A')); ?> months</p>
                    <p><strong>VIN:</strong> <?php echo esc_html($deal_jacket_data['vin'] ?? ($vehicle_data['vin'] ?? 'N/A')); ?></p>
                    <!-- ... more fields from deal_jacket_data ... -->
                </div>

                <!-- Documents -->
                 <div class="paf-section">
                    <h3>Documents</h3>
                    <?php echo do_shortcode('[paf_deal_documents deal_id="' . $deal_id . '"]'); ?>
                </div>
            </div>
        </div>
        <script>
            document.getElementById('viewCreditAppDetailsBtn').addEventListener('click', function() {
                var detailsDiv = document.getElementById('fullCreditAppDetails');
                detailsDiv.style.display = detailsDiv.style.display === 'none' ? 'block' : 'none';
            });
        </script>
    </div>
    <?php
    return ob_get_clean();
}

// Shortcode to display documents for a deal
function paf_render_deal_documents_shortcode($atts) {
    $atts = shortcode_atts(['deal_id' => 0], $atts);
    $deal_id = intval($atts['deal_id']);

    if (!$deal_id) return '<p>No deal ID provided.</p>';

    $docs_args = [
        'post_type' => 'paf_document',
        'meta_key' => '_deal_post_id',
        'meta_value' => $deal_id,
        'posts_per_page' => -1,
    ];
    $docs_query = new WP_Query($docs_args);

    ob_start();
    if ($docs_query->have_posts()): ?>
        <ul class="paf-document-list">
        <?php while ($docs_query->have_posts()): $docs_query->the_post();
            $file_id = get_post_meta(get_the_ID(), '_file_id', true);
            $file_url = $file_id ? wp_get_attachment_url($file_id) : '#';
            $doc_type = get_post_meta(get_the_ID(), '_document_type', true);
            $custom_name = get_post_meta(get_the_ID(), '_custom_document_name', true);
            $display_name = ($doc_type === 'Other' && $custom_name) ? $custom_name : $doc_type;
        ?>
            <li>
                <a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html(get_the_title() . ' (' . $display_name . ')'); ?></a>
            </li>
        <?php endwhile; wp_reset_postdata(); ?>
        </ul>
    <?php else: ?>
        <p>No documents uploaded for this deal yet.</p>
    <?php endif; 
    
    // Simple document upload form
    ?>
    <h4>Upload Document</h4>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="paf_upload_deal_document">
        <input type="hidden" name="deal_id" value="<?php echo esc_attr($deal_id); ?>">
        <?php wp_nonce_field('paf_upload_document_nonce', 'paf_doc_upload_nonce'); ?>
        <p><label for="paf_doc_file">Select file:</label><br/><input type="file" name="paf_doc_file" id="paf_doc_file" required></p>
        <p><label for="paf_doc_title">Document Title:</label><br/><input type="text" name="paf_doc_title" required></p>
        <p>
            <label for="paf_doc_type">Document Type:</label><br/>
            <select name="paf_doc_type" id="paf_doc_type" required>
                <option value="Paystub">Paystub</option>
                <option value="Bank Statement">Bank Statement</option>
                <option value="Driver's License">Driver's License</option>
                <option value="Insurance Binder">Insurance Binder</option>
                <option value="Loan Closing Package">Loan Closing Package</option>
                <option value="Signed Docs">Signed Docs</option>
                <option value="Delivery Confirmation">Delivery Confirmation</option>
                <option value="Other">Other</option>
            </select>
        </p>
        <p id="paf_custom_doc_name_wrapper" style="display:none;">
            <label for="paf_custom_doc_name">Custom Document Name (if Other):</label><br/>
            <input type="text" name="paf_custom_doc_name">
        </p>
        <p><input type="submit" value="Upload Document" class="paf-button"></p>
    </form>
    <script>
        document.getElementById('paf_doc_type').addEventListener('change', function() {
            document.getElementById('paf_custom_doc_name_wrapper').style.display = (this.value === 'Other') ? 'block' : 'none';
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('paf_deal_documents', 'paf_render_deal_documents_shortcode');


// Handle document upload
add_action('admin_post_paf_upload_deal_document', 'paf_handle_deal_document_upload');
function paf_handle_deal_document_upload() {
    if ( !isset($_POST['paf_doc_upload_nonce']) || !wp_verify_nonce($_POST['paf_doc_upload_nonce'], 'paf_upload_document_nonce') ) {
        wp_die('Security check failed.');
    }
    if ( !is_user_logged_in() || !current_user_can('upload_files') ) { // Adjust capability
        wp_die('Permission denied.');
    }

    $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
    if (!$deal_id || get_post_type($deal_id) !== 'paf_deal') {
        wp_die('Invalid deal ID.');
    }

    if ( !empty($_FILES['paf_doc_file']['name']) ) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('paf_doc_file', 0); // 0 means not attached to any specific post initially

        if (is_wp_error($attachment_id)) {
            wp_die('Error uploading file: ' . $attachment_id->get_error_message());
        }

        // Create paf_document CPT
        $doc_title = isset($_POST['paf_doc_title']) ? sanitize_text_field($_POST['paf_doc_title']) : 'Uploaded Document';
        $doc_type = isset($_POST['paf_doc_type']) ? sanitize_text_field($_POST['paf_doc_type']) : 'Other';
        $custom_doc_name = ($doc_type === 'Other' && isset($_POST['paf_custom_doc_name'])) ? sanitize_text_field($_POST['paf_custom_doc_name']) : '';

        $doc_post_id = wp_insert_post([
            'post_title' => $doc_title,
            'post_type' => 'paf_document',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);

        if (!is_wp_error($doc_post_id)) {
            update_post_meta($doc_post_id, '_deal_post_id', $deal_id);
            update_post_meta($doc_post_id, '_file_id', $attachment_id);
            update_post_meta($doc_post_id, '_document_type', $doc_type);
            if ($custom_doc_name) {
                update_post_meta($doc_post_id, '_custom_document_name', $custom_doc_name);
            }
            update_post_meta($doc_post_id, '_status', 'pending'); // Or 'approved' if no review needed

            paf_add_history_entry(0, $deal_id, 'document_uploaded', "Document '{$doc_title}' uploaded.", ['user_id' => get_current_user_id()]);
        } else {
             wp_delete_attachment($attachment_id, true); // Clean up orphaned attachment
             wp_die('Error creating document record: ' . $doc_post_id->get_error_message());
        }
    } else {
        wp_die('No file selected for upload.');
    }
    wp_redirect(add_query_arg('deal_id', $deal_id, home_url('/deal-jacket/')) . '#paf-documents-section'); // Redirect back
    exit;
}


// Handle new communication submission
add_action('admin_post_paf_submit_communication', 'paf_handle_communication_submission');
function paf_handle_communication_submission() {
     if ( !isset($_POST['paf_comm_nonce']) || !wp_verify_nonce($_POST['paf_comm_nonce'], 'paf_submit_communication_nonce') ) {
        wp_die('Security check failed.');
    }
    if ( !is_user_logged_in() ) { // Add capability check
        wp_die('Permission denied.');
    }

    $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
    $message_content = isset($_POST['message_content']) ? wp_kses_post($_POST['message_content']) : '';

    if (!$deal_id || get_post_type($deal_id) !== 'paf_deal' || empty($message_content) ) {
        wp_die('Invalid data.');
    }
    // Check if current user is author of deal or an admin
    $deal_post = get_post($deal_id);
    if ($deal_post->post_author != get_current_user_id() && !current_user_can('manage_options')) {
         wp_die('Permission denied to comment on this deal.');
    }


    $comm_id = wp_insert_post([
        'post_title' => 'Communication for Deal #' . $deal_id . ' - ' . date('Y-m-d H:i'),
        'post_content' => $message_content,
        'post_type' => 'paf_communication',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]);

    if (!is_wp_error($comm_id)) {
        update_post_meta($comm_id, '_deal_post_id', $deal_id);
        update_post_meta($comm_id, '_sender_type', current_user_can('manage_options') ? 'admin' : 'dealer'); // Simple role check
        update_post_meta($comm_id, '_read_status', false);
        // Add history entry if needed
        paf_add_history_entry(0, $deal_id, 'communication_added', "New message added.", ['user_id' => get_current_user_id()]);

    } else {
        wp_die('Error saving communication.');
    }

    wp_redirect(add_query_arg('deal_id', $deal_id, home_url('/deal-jacket/')) . '#paf-communications-log');
    exit;
}


function paf_render_application_confirmation_page() {
    if ( !isset($_GET['app_ref']) ) {
        return "<p>Invalid application reference.</p>";
    }
    $app_id = intval($_GET['app_ref']);
    $app_post = get_post($app_id);

    if (!$app_post || $app_post->post_type !== 'paf_credit_app') {
        return "<p>Application not found.</p>";
    }
    // Optionally, check if current user is author or admin
    // if ($app_post->post_author != get_current_user_id() && !current_user_can('manage_options')) {
    //    return "<p>Access denied.</p>";
    // }

    $primary_borrower_json = get_post_meta( $app_id, '_primary_borrower', true );
    $applicant_name = 'Applicant';
    if ( $primary_borrower_json ) {
        $primary_borrower = json_decode( $primary_borrower_json, true );
        $applicant_name = esc_html( ($primary_borrower['personalInformation']['applicantName'] ?? 'Applicant') );
    }


    ob_start();
    ?>
    <div class="paf-confirmation-page">
        <h1>Application Submitted Successfully!</h1>
        <p>Thank you, <?php echo $applicant_name; ?>. Your credit application has been received.</p>
        <p>Application Reference ID: <strong>PAF-APP-<?php echo esc_html($app_id); ?></strong></p>
        <p>We will begin processing it shortly. You can track the status of your application in your <a href="<?php echo esc_url(home_url('/dealer-dashboard/')); ?>">Dealer Dashboard</a>.</p>
        <p>The application will be submitted to DealerTrack, and a DealerTrack Reference Number will be available on the Deal Jacket page once processed.</p>
    </div>
    <?php
    return ob_get_clean();
}


?>