<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function paf_core_register_action_scheduler_hooks() {
    add_action( 'paf_process_automation_job_hook', 'paf_execute_scheduled_automation_job', 10, 1 );
    add_action( 'paf_check_deal_statuses_cron_hook', 'paf_schedule_deal_status_checks' );

    // Schedule the cron for checking deal statuses if not already scheduled
    if ( false === as_next_scheduled_action( 'paf_check_deal_statuses_cron_hook' ) ) {
        as_schedule_recurring_action( time() + 60, 15 * MINUTE_IN_SECONDS, 'paf_check_deal_statuses_cron_hook' ); // Every 15 mins
    }
}

/**
 * Schedule an automation job CPT to be processed.
 * This function creates the job CPT and schedules an Action Scheduler action.
 */
function paf_schedule_automation_job( $app_id, $deal_id, $job_type, $scheduled_time = null, $args = [] ) {
    if ( ! function_exists('as_schedule_single_action') ) {
        error_log('PAF Error: Action Scheduler not active or function as_schedule_single_action not found.');
        // Potentially update app/deal status to an error state or log prominently
        if ($app_id) update_post_meta($app_id, '_processing_notes', 'Error: Automation system unavailable.');
        if ($deal_id) update_post_meta($deal_id, '_processing_notes', 'Error: Automation system unavailable for status check.');
        return false;
    }

    $title_app_part = $app_id ? "App #{$app_id}" : "";
    $title_deal_part = $deal_id ? "Deal #{$deal_id}" : "";
    $job_title = "Automation: {$job_type} for {$title_app_part} {$title_deal_part}";

    $job_cpt_id = wp_insert_post([
        'post_type' => 'paf_automation_job',
        'post_title' => sanitize_text_field($job_title),
        'post_status' => 'publish', // Job CPT is always publish, its meta status tracks execution
    ]);

    if ( is_wp_error( $job_cpt_id ) ) {
        error_log("PAF Error: Could not create automation job CPT for {$job_type}. App: {$app_id}, Deal: {$deal_id}. Error: " . $job_cpt_id->get_error_message());
        return false;
    }

    update_post_meta( $job_cpt_id, '_job_type', $job_type );
    if ($app_id) update_post_meta( $job_cpt_id, '_credit_application_id', $app_id );
    if ($deal_id) update_post_meta( $job_cpt_id, '_deal_id', $deal_id );
    update_post_meta( $job_cpt_id, '_status', 'pending' );
    update_post_meta( $job_cpt_id, '_scheduled_time', $scheduled_time ? $scheduled_time : current_time('mysql') );
    update_post_meta( $job_cpt_id, '_attempt_count', 0 );
    update_post_meta( $job_cpt_id, '_job_args', wp_json_encode($args) ); // Store any specific args for the job

    $timestamp = $scheduled_time ? strtotime($scheduled_time) : time();
    as_schedule_single_action( $timestamp, 'paf_process_automation_job_hook', ['job_cpt_id' => $job_cpt_id] );
    
    // Update originating CPT status if applicable
    if ( $job_type === 'submit_credit_app_to_dealertrack' && $app_id ) {
        update_post_meta( $app_id, '_status', 'pending_submission_to_dealertrack' ); // Or a 'queued_for_submission' status
        update_post_meta( $app_id, '_processing_notes', 'Queued for submission to DealerTrack.' );
    }

    return $job_cpt_id;
}

/**
 * Executes a scheduled automation job by calling the local Puppeteer client.
 */
function paf_execute_scheduled_automation_job( $job_cpt_id ) {
    $job_post = get_post( $job_cpt_id );
    if ( ! $job_post || $job_post->post_type !== 'paf_automation_job' ) {
        error_log("PAF Error: Invalid job_cpt_id {$job_cpt_id} in paf_execute_scheduled_automation_job.");
        return;
    }

    $job_status = get_post_meta( $job_cpt_id, '_status', true );
    if ( $job_status !== 'pending' ) { // Only process pending jobs, could be retrying
        // error_log("PAF Info: Job {$job_cpt_id} is not pending, current status: {$job_status}. Skipping.");
        return;
    }
    
    update_post_meta( $job_cpt_id, '_status', 'processing_by_wp' ); // WP is attempting to dispatch it

    $local_client_webhook_url = get_option('paf_local_client_webhook_url');
    $local_client_api_key = get_option('paf_local_client_api_key');

    if ( empty($local_client_webhook_url) ) {
        error_log("PAF Error: Local client webhook URL is not configured. Cannot process job {$job_cpt_id}.");
        update_post_meta( $job_cpt_id, '_status', 'failed' );
        update_post_meta( $job_cpt_id, '_last_error', 'Local client webhook URL not configured in WordPress.' );
        return;
    }

    $job_type = get_post_meta($job_cpt_id, '_job_type', true);
    $app_id = get_post_meta($job_cpt_id, '_credit_application_id', true);
    $deal_id = get_post_meta($job_cpt_id, '_deal_id', true);
    $job_args = json_decode(get_post_meta($job_cpt_id, '_job_args', true), true) ?: [];


    $payload = [
        'job_id' => $job_cpt_id, // WordPress job CPT ID
        'job_type' => $job_type,
        'data' => array_merge($job_args, [ // Merge general job args with specific IDs
            'credit_application_id' => $app_id ? intval($app_id) : null,
            'deal_id' => $deal_id ? intval($deal_id) : null,
            'dt_reference_number' => $deal_id ? get_post_meta( $deal_id, '_dt_reference_number', true ) : null,
        ])
    ];

    $response = wp_remote_post( $local_client_webhook_url, [
        'method'    => 'POST',
        'timeout'   => 45, // Increased timeout for potentially long operations
        'headers'   => [
            'Content-Type' => 'application/json',
            'X-Paf-WpJob-Api-Key' => $local_client_api_key, // Key for local client to verify WP
        ],
        'body'      => wp_json_encode( $payload ),
    ]);

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        error_log("PAF Error: Failed to notify local client for job {$job_cpt_id}. Error: {$error_message}");
        update_post_meta( $job_cpt_id, '_status', 'failed' ); // Will be retried by Action Scheduler
        update_post_meta( $job_cpt_id, '_last_error', 'WP dispatch error: ' . $error_message );
        // Action Scheduler will retry this hook automatically if it throws an exception or returns WP_Error.
        // Let's re-schedule for a later time to avoid immediate rapid retries by AS if URL is bad.
        as_schedule_single_action( time() + (5 * MINUTE_IN_SECONDS), 'paf_process_automation_job_hook', ['job_cpt_id' => $job_cpt_id] );

    } else {
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if ( $response_code >= 200 && $response_code < 300 ) {
            // Local client acknowledged. It will update the job CPT status via REST API.
            update_post_meta( $job_cpt_id, '_status', 'dispatched_to_local_client' );
            update_post_meta( $job_cpt_id, '_result_log', 'Job dispatched to local client. Waiting for update. Response: ' . $response_body);
        } else {
            error_log("PAF Error: Local client returned error for job {$job_cpt_id}. Code: {$response_code}. Body: {$response_body}");
            update_post_meta( $job_cpt_id, '_status', 'failed' );
            update_post_meta( $job_cpt_id, '_last_error', "Local client error (Code {$response_code}): " . $response_body );
            as_schedule_single_action( time() + (5 * MINUTE_IN_SECONDS), 'paf_process_automation_job_hook', ['job_cpt_id' => $job_cpt_id] );
        }
    }
}

/**
 * Cron job to find deals that need status checks and schedule automation jobs for them.
 */
function paf_schedule_deal_status_checks() {
    $deals_query_args = [
        'post_type' => 'paf_deal',
        'posts_per_page' => 50, // Batch size
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => '_dt_reference_number',
                'compare' => 'EXISTS',
            ],
            [
                'key' => '_dt_reference_number',
                'value' => '',
                'compare' => '!=',
            ],
            [
                'key' => '_status',
                'value' => ['deal_funded', 'deal_declined', 'completed_archived'],
                'compare' => 'NOT IN',
            ],
            // Optional: Add a check for _last_dt_status_check_timestamp to avoid too frequent checks
            // Example: only check if not checked in the last X minutes/hours.
            // [
            //    'relation' => 'OR',
            //    [
            //        'key' => '_last_dt_status_check_timestamp',
            //        'compare' => 'NOT EXISTS'
            //    ],
            //    [
            //        'key' => '_last_dt_status_check_timestamp',
            //        'value' => date('Y-m-d H:i:s', strtotime('-30 minutes')), // Check every 30 mins max
            //        'compare' => '<=',
            //        'type' => 'DATETIME'
            //    ]
            // ]
        ],
    ];
    $deal_posts = get_posts( $deals_query_args );

    foreach ( $deal_posts as $deal_post ) {
        // Check if a 'check_dealertrack_deal_status' job is already pending for this deal
        $existing_jobs = get_posts([
            'post_type' => 'paf_automation_job',
            'meta_query' => [
                'relation' => 'AND',
                ['_key' => '_deal_id', 'value' => $deal_post->ID],
                ['_key' => '_job_type', 'value' => 'check_dealertrack_deal_status'],
                ['_key' => '_status', 'value' => ['pending', 'processing_by_wp', 'dispatched_to_local_client'], 'compare' => 'IN']
            ],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);

        if (empty($existing_jobs)) {
            paf_schedule_automation_job( null, $deal_post->ID, 'check_dealertrack_deal_status' );
        }
    }
}
?>