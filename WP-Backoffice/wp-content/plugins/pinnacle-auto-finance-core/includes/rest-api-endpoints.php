<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function paf_core_register_rest_routes() {
    $namespace = 'paf/v1';

    // --- Automation Job Endpoints ---
    register_rest_route( $namespace, '/jobs/pending', [
        'methods' => WP_REST_Server::READABLE, // GET
        'callback' => 'paf_api_get_pending_jobs',
        'permission_callback' => 'paf_api_permission_check',
    ]);

    register_rest_route( $namespace, '/jobs/(?P<id>\d+)/update', [
        'methods' => WP_REST_Server::CREATABLE, // POST
        'callback' => 'paf_api_update_job_status',
        'permission_callback' => 'paf_api_permission_check',
        'args' => [
            'id' => ['required' => true, 'validate_callback' => 'is_numeric'],
            'status' => ['required' => true, 'type' => 'string', 'enum' => ['processing', 'completed', 'failed']],
            'dt_reference_number' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'dt_deal_jacket_data' => ['type' => 'object_or_array'], // For paf_deal specific data
            'dt_status_text' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'], // For paf_deal status
            'error_message' => ['type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'],
            'result_log' => ['type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'],
        ],
    ]);

    // --- Credit Application Data Endpoint (for Puppeteer to get data for submission) ---
    register_rest_route( $namespace, '/credit-applications/(?P<app_id>\d+)/data_for_submission', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'paf_api_get_credit_app_submission_data',
        'permission_callback' => 'paf_api_permission_check',
        'args' => ['app_id' => ['required' => true, 'validate_callback' => 'is_numeric']],
    ]);

    // --- Deal Endpoints (Puppeteer interacts with these after app submission or for status checks) ---
    register_rest_route( $namespace, '/deals/create_from_app/(?P<app_id>\d+)', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'paf_api_create_deal_from_app',
        'permission_callback' => 'paf_api_permission_check',
        'args' => [
            'app_id' => ['required' => true, 'validate_callback' => 'is_numeric'],
            'dt_reference_number' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
        ],
    ]);
    
    register_rest_route( $namespace, '/deals/(?P<deal_id>\d+)/update_from_dealertrack', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'paf_api_update_deal_from_dealertrack',
        'permission_callback' => 'paf_api_permission_check',
        'args' => [
            'deal_id' => ['required' => true, 'validate_callback' => 'is_numeric'],
            'dt_status_key' => ['required' => true, 'type' => 'string'], // e.g., 'conditional_approval'
            'dt_deal_jacket_data' => ['type' => 'object_or_array'], // Raw or structured data from DT deal jacket
            'notes' => ['type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'],
        ],
    ]);

    register_rest_route( $namespace, '/deals/for_status_check', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'paf_api_get_deals_for_status_check',
        'permission_callback' => 'paf_api_permission_check',
    ]);
}

// API Permission Check
function paf_api_permission_check( WP_REST_Request $request ) {
    $provided_secret = $request->get_header('X-Paf-Api-Secret');
    $stored_secret = get_option('paf_wp_api_shared_secret');

    if ( empty($stored_secret) ) { // If no secret is set in WP, deny access.
        error_log('PAF API Access Denied: WP API Shared Secret not configured.');
        return new WP_Error( 'rest_forbidden', 'API secret not configured on server.', array( 'status' => 403 ) );
    }
    if ( empty($provided_secret) || !hash_equals($stored_secret, $provided_secret) ) {
         error_log('PAF API Access Denied: Invalid or missing X-Paf-Api-Secret header.');
        return new WP_Error( 'rest_forbidden', esc_html__( 'Invalid API secret.', 'paf-core' ), array( 'status' => 403 ) );
    }
    return true;
}


// --- Callback Functions ---

function paf_api_get_pending_jobs( WP_REST_Request $request ) {
    $jobs_query_args = [
        'post_type' => 'paf_automation_job',
        'posts_per_page' => 5, // Process a few at a time
        'meta_query' => [
            [
                'key' => '_status',
                'value' => 'pending',
            ]
        ],
        'orderby' => 'date', // Prioritize older pending jobs
        'order' => 'ASC',
    ];
    $jobs_posts = get_posts( $jobs_query_args );
    $job_data = [];

    foreach ( $jobs_posts as $job_post ) {
        $app_id = get_post_meta( $job_post->ID, '_credit_application_id', true );
        $deal_id = get_post_meta( $job_post->ID, '_deal_id', true );
        $job_data[] = [
            'job_id' => $job_post->ID,
            'job_type' => get_post_meta( $job_post->ID, '_job_type', true ),
            'credit_application_id' => $app_id ? intval($app_id) : null,
            'deal_id' => $deal_id ? intval($deal_id) : null,
            'dt_reference_number' => $deal_id ? get_post_meta( $deal_id, '_dt_reference_number', true ) : null,
        ];
    }
    return new WP_REST_Response( $job_data, 200 );
}

function paf_api_update_job_status( WP_REST_Request $request ) {
    $job_id = $request->get_param('id');
    $job_post = get_post($job_id);

    if ( !$job_post || $job_post->post_type !== 'paf_automation_job' ) {
        return new WP_Error( 'paf_job_not_found', 'Automation job not found.', ['status' => 404] );
    }

    $status = $request->get_param('status');
    $error_message = $request->get_param('error_message');
    $result_log = $request->get_param('result_log');

    update_post_meta( $job_id, '_status', $status );
    update_post_meta( $job_id, '_completed_time', current_time('mysql') );

    $attempt_count = (int) get_post_meta( $job_id, '_attempt_count', true );
    update_post_meta( $job_id, '_attempt_count', $attempt_count + 1 );

    if ( $error_message ) {
        update_post_meta( $job_id, '_last_error', $error_message );
    }
    if ( $result_log ) {
        update_post_meta( $job_id, '_result_log', $result_log );
    }
    
    // If job failed, and attempts < max, reschedule (Action Scheduler will handle this logic internally now)
    // This REST endpoint just marks the job based on the client's report.
    // Rescheduling is handled by `paf_process_automation_job` if it calls this API and gets a failure.

    return new WP_REST_Response( ['success' => true, 'message' => 'Job status updated.'], 200 );
}

function paf_api_get_credit_app_submission_data( WP_REST_Request $request ) {
    $app_id = $request->get_param('app_id');
    $app_post = get_post($app_id);

    if ( !$app_post || $app_post->post_type !== 'paf_credit_app' ) {
        return new WP_Error( 'paf_app_not_found', 'Credit application not found.', ['status' => 404] );
    }

    $encryption_key = paf_get_encryption_key(); // Get your key

    $primary_borrower_json = get_post_meta( $app_id, '_primary_borrower', true );
    $co_borrower_json = get_post_meta( $app_id, '_co_borrower', true );

    $primary_borrower = json_decode( $primary_borrower_json, true );
    $co_borrower = $co_borrower_json ? json_decode( $co_borrower_json, true ) : null;

    // Decrypt sensitive fields before sending
    if (isset($primary_borrower['personalInformation']['ssn'])) {
        $primary_borrower['personalInformation']['ssn'] = paf_decrypt_data($primary_borrower['personalInformation']['ssn'], $encryption_key);
    }
    if (isset($primary_borrower['personalInformation']['driversLicense'])) {
        $primary_borrower['personalInformation']['driversLicense'] = paf_decrypt_data($primary_borrower['personalInformation']['driversLicense'], $encryption_key);
    }
    if ($co_borrower && isset($co_borrower['personalInformation']['ssn'])) {
        $co_borrower['personalInformation']['ssn'] = paf_decrypt_data($co_borrower['personalInformation']['ssn'], $encryption_key);
    }
     if ($co_borrower && isset($co_borrower['personalInformation']['driversLicense'])) {
        $co_borrower['personalInformation']['driversLicense'] = paf_decrypt_data($co_borrower['personalInformation']['driversLicense'], $encryption_key);
    }

    $data = [
        'id' => $app_id,
        'primary_borrower' => $primary_borrower,
        'co_borrower' => $co_borrower,
        'vehicle_data' => json_decode( get_post_meta( $app_id, '_vehicle_data', true ), true ),
        'financial_data' => json_decode( get_post_meta( $app_id, '_financial_data', true ), true ),
        // Add other top-level form fields if Puppeteer needs them (dealer name, etc.)
        'dealer_name' => get_post_meta( $app_id, '_dealer_name', true ),
        'dealer_telephone' => get_post_meta( $app_id, '_dealer_telephone', true ),
        'dealer_contact' => get_post_meta( $app_id, '_dealer_contact', true ),
    ];
    return new WP_REST_Response( $data, 200 );
}

function paf_api_create_deal_from_app( WP_REST_Request $request ) {
    $app_id = $request->get_param('app_id');
    $dt_reference_number = $request->get_param('dt_reference_number');
    $app_post = get_post($app_id);

    if ( !$app_post || $app_post->post_type !== 'paf_credit_app' ) {
        return new WP_Error( 'paf_app_not_found', 'Originating credit application not found.', ['status' => 404] );
    }

    $deal_title = 'Deal for App #' . $app_id . ' - DT Ref: ' . $dt_reference_number;
    $deal_post_data = [
        'post_title' => sanitize_text_field($deal_title),
        'post_type' => 'paf_deal',
        'post_status' => 'publish',
        'post_author' => $app_post->post_author, // Assign to the same dealer
    ];
    $deal_id = wp_insert_post( $deal_post_data );

    if ( is_wp_error( $deal_id ) ) {
        return new WP_Error( 'paf_deal_creation_failed', 'Could not create deal CPT.', ['status' => 500] );
    }

    update_post_meta( $deal_id, '_credit_application_post_id', $app_id );
    update_post_meta( $deal_id, '_dt_reference_number', $dt_reference_number );
    update_post_meta( $deal_id, '_status', 'deal_submitted' ); // Initial status for paf_deal

    // Update original credit app status
    update_post_meta( $app_id, '_status', 'submitted_to_dealertrack' );
    update_post_meta( $app_id, '_processing_notes', 'Successfully submitted to DealerTrack. Ref: ' . $dt_reference_number );
    
    paf_add_history_entry(
    'deal_created_from_app', 
    "Deal created from app submission. DT Ref: {$dt_reference_number}", 
    $app_id, 
    $deal_id,
    ['dt_reference_number' => $dt_reference_number] // Adding dt_ref to details as well
);

    return new WP_REST_Response( ['success' => true, 'deal_id' => $deal_id, 'message' => 'Deal created successfully.'], 201 );
}

function paf_api_update_deal_from_dealertrack( WP_REST_Request $request ) {
    $deal_id = $request->get_param('deal_id');
    $deal_post = get_post($deal_id);

    if ( !$deal_post || $deal_post->post_type !== 'paf_deal' ) {
        return new WP_Error( 'paf_deal_not_found', 'Deal not found.', ['status' => 404] );
    }

    $dt_status_key = $request->get_param('dt_status_key');
    $dt_deal_jacket_data = $request->get_param('dt_deal_jacket_data'); // This is an object/array
    $notes = $request->get_param('notes');

    // Update paf_deal status
    update_post_meta( $deal_id, '_status', sanitize_key($dt_status_key) );
    
    if ( !empty($dt_deal_jacket_data) && (is_array($dt_deal_jacket_data) || is_object($dt_deal_jacket_data)) ) {
        // Sanitize $dt_deal_jacket_data if needed before encoding
        update_post_meta( $deal_id, '_deal_jacket_data', wp_json_encode( $dt_deal_jacket_data ) );
    }
    if ( $notes ) {
        update_post_meta( $deal_id, '_processing_notes', // Could append to existing notes
            (get_post_meta($deal_id, '_processing_notes', true) ? get_post_meta($deal_id, '_processing_notes', true) . "\n" : '') . "DT Update: " . $notes
        );
    }
    update_post_meta( $deal_id, '_last_dt_status_check_timestamp', current_time('mysql') );

    $history_details = ['dt_status_key' => $dt_status_key, 'notes' => $notes, 'source' => 'puppeteer_client'];
paf_add_history_entry(
    'dealertrack_status_update', 
    "DealerTrack status updated to: " . paf_get_deal_status_label($dt_status_key), 
    0, // $app_id is 0 here, or you could fetch it from the deal if needed for history
    $deal_id, 
    $history_details 
);
    // Potentially trigger notifications to dealer (email, etc.) based on status change
    // For example, if $dt_status_key is 'final_approval' or 'deal_funded'

    return new WP_REST_Response( ['success' => true, 'message' => 'Deal status and data updated from DealerTrack.'], 200 );
}

function paf_api_get_deals_for_status_check( WP_REST_Request $request ) {
    $deals_query_args = [
        'post_type' => 'paf_deal',
        'posts_per_page' => -1, // Get all relevant deals
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => '_dt_reference_number', // Must have a DT ref
                'compare' => 'EXISTS',
            ],
            [
                'key' => '_dt_reference_number',
                'value' => '',
                'compare' => '!=',
            ],
            [
                'key' => '_status', // Not in a final state
                'value' => ['deal_funded', 'deal_declined', 'completed_archived', 'submission_error'], // Error state for app, not deal
                'compare' => 'NOT IN',
            ]
        ],
    ];
    $deal_posts = get_posts( $deals_query_args );
    $deal_data = [];

    foreach ( $deal_posts as $deal_post ) {
        $deal_data[] = [
            'deal_id' => $deal_post->ID,
            'dt_reference_number' => get_post_meta( $deal_post->ID, '_dt_reference_number', true ),
            'current_wp_status' => get_post_meta( $deal_post->ID, '_status', true ),
        ];
    }
    return new WP_REST_Response( $deal_data, 200 );
}

?>