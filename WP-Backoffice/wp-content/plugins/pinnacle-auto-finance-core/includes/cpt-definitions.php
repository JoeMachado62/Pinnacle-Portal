<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function paf_core_register_cpts() {

    // Dealers CPT
    $dealer_args = [
        'labels' => [
            'name' => __( 'Dealers', 'paf-core' ),
            'singular_name' => __( 'Dealer', 'paf-core' ),
            'add_new_item' => __( 'Add New Dealer', 'paf-core' ),
            'edit_item' => __( 'Edit Dealer', 'paf-core' ),
            'view_item' => __( 'View Dealer', 'paf-core' ),
            'search_items' => __( 'Search Dealers', 'paf-core' ),
        ],
        'public' => false, // Not publicly queryable, admin UI only unless exposed via REST
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-store',
        'capability_type' => 'post', // Consider custom capabilities: 'paf_dealer'
        'map_meta_cap' => true,
        'hierarchical' => false,
        'supports' => ['title', 'custom-fields', 'author'], // Author can link to admin who created/manages
        'has_archive' => false,
        'show_in_rest' => true,
        'rest_base' => 'paf-dealers',
        'rewrite' => false,
    ];
    register_post_type( 'paf_dealer', $dealer_args );

    // Credit Applications CPT
    $credit_app_args = [
        'labels' => [
            'name' => __( 'Credit Applications (Data)', 'paf-core' ),
            'singular_name' => __( 'Credit Application (Data)', 'paf-core' ),
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-media-text',
        'capability_type' => 'post', // Consider 'paf_credit_app'
        'map_meta_cap' => true,
        'supports' => ['title', 'author', 'custom-fields'],
        'show_in_rest' => true,
        'rest_base' => 'paf-credit-applications',
        'rewrite' => false,
    ];
    register_post_type( 'paf_credit_app', $credit_app_args );

    // Deals CPT
    $deal_args = [
        'labels' => [
            'name' => __( 'Deals (Jackets)', 'paf-core' ),
            'singular_name' => __( 'Deal (Jacket)', 'paf-core' ),
        ],
        'public' => false, // Will be exposed via custom frontend views
        'show_ui' => true,
        'menu_icon' => 'dashicons-portfolio',
        'capability_type' => 'post', // Consider 'paf_deal'
        'map_meta_cap' => true,
        'supports' => ['title', 'author', 'custom-fields'], // Author is the dealer
        'show_in_rest' => true,
        'rest_base' => 'paf-deals',
        'rewrite' => false,
    ];
    register_post_type( 'paf_deal', $deal_args );

    // Documents CPT
    $document_args = [
        'labels' => [
            'name' => __( 'Documents', 'paf-core' ),
            'singular_name' => __( 'Document', 'paf-core' ),
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-media-document',
        'capability_type' => 'post', // Consider 'paf_document'
        'map_meta_cap' => true,
        'supports' => ['title', 'custom-fields', 'author'], // Author is uploader
        'show_in_rest' => true,
        'rest_base' => 'paf-documents',
        'rewrite' => false,
    ];
    register_post_type( 'paf_document', $document_args );

    // Communications CPT
    $communication_args = [
        'labels' => [
            'name' => __( 'Communications', 'paf-core' ),
            'singular_name' => __( 'Communication', 'paf-core' ),
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-format-chat',
        'capability_type' => 'post', // Consider 'paf_communication'
        'map_meta_cap' => true,
        'supports' => ['title', 'editor', 'author', 'custom-fields'], // Editor for message content
        'show_in_rest' => true,
        'rest_base' => 'paf-communications',
        'rewrite' => false,
    ];
    register_post_type( 'paf_communication', $communication_args );

    // Application History CPT
    $app_history_args = [
        'labels' => [
            'name' => __( 'Application History', 'paf-core' ),
            'singular_name' => __( 'History Entry', 'paf-core' ),
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-backup',
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'supports' => ['title', 'custom-fields'],
        'show_in_rest' => true,
        'rest_base' => 'paf-app-history',
        'rewrite' => false,
    ];
    register_post_type( 'paf_app_history', $app_history_args );

    // Automation Jobs CPT
    $automation_job_args = [
        'labels' => [
            'name' => __( 'Automation Jobs', 'paf-core' ),
            'singular_name' => __( 'Automation Job', 'paf-core' ),
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-controls-play',
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'supports' => ['title', 'custom-fields'],
        'show_in_rest' => true,
        'rest_base' => 'paf-automation-jobs',
        'rewrite' => false,
    ];
    register_post_type( 'paf_automation_job', $automation_job_args );
}

// Meta field registration (example for one CPT, repeat for others)
// WordPress 5.5+ allows direct registration, otherwise use ACF or CMB2.
// For simplicity here, we'll assume direct registration or manual handling in save_post.

function paf_core_register_meta_fields() {
    // Example for paf_dealer
    register_post_meta('paf_dealer', '_dealership_legal_name', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_post_meta('paf_dealer', '_federal_tax_id', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    // ... other paf_dealer fields ...
    register_post_meta('paf_dealer', '_associated_user_id', [
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
    ]);
     register_post_meta('paf_dealer', '_status', [ // pending, approved, suspended, inactive
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);


    // paf_credit_app
    register_post_meta('paf_credit_app', '_status', [ // draft, pending_submission_to_dealertrack, submitted_to_dealertrack, submission_error
        'type' => 'string', 'single' => true, 'show_in_rest' => true,
    ]);
    register_post_meta('paf_credit_app', '_primary_borrower', [ // JSON
        'type' => 'string', 'single' => true, 'show_in_rest' => true,
    ]);
    // ... other paf_credit_app fields (vehicle_data, financial_data etc. as JSON strings)

    // paf_deal
    register_post_meta('paf_deal', '_credit_application_post_id', [
        'type' => 'integer', 'single' => true, 'show_in_rest' => true,
    ]);
    register_post_meta('paf_deal', '_dt_reference_number', [
        'type' => 'string', 'single' => true, 'show_in_rest' => true,
    ]);
    register_post_meta('paf_deal', '_status', [ // deal_submitted, conditional_approval, etc.
        'type' => 'string', 'single' => true, 'show_in_rest' => true,
    ]);
    register_post_meta('paf_deal', '_next_action_text', [
        'type' => 'string', 'single' => true, 'show_in_rest' => true,
    ]);
    register_post_meta('paf_deal', '_deal_jacket_data', [ // JSON
        'type' => 'string', 'single' => true, 'show_in_rest' => true,
    ]);


    // paf_document
    register_post_meta('paf_document', '_deal_post_id', [
        'type' => 'integer', 'single' => true, 'show_in_rest' => true,
    ]);
    register_post_meta('paf_document', '_document_type', [
        'type' => 'string', 'single' => true, 'show_in_rest' => true,
    ]);
    // ... other paf_document fields

    // paf_communication
    register_post_meta('paf_communication', '_deal_post_id', [
        'type' => 'integer', 'single' => true, 'show_in_rest' => true,
    ]);
    // ... other paf_communication fields

    // paf_app_history
    register_post_meta('paf_app_history', '_credit_application_id', [ // Or _deal_id if more relevant
        'type' => 'integer', 'single' => true, 'show_in_rest' => true,
    ]);
    // ... other paf_app_history fields

    // paf_automation_job
    register_post_meta('paf_automation_job', '_credit_application_id', [
        'type' => 'integer', 'single' => true, 'show_in_rest' => true,
    ]);
     register_post_meta('paf_automation_job', '_deal_id', [ // For status check jobs
        'type' => 'integer', 'single' => true, 'show_in_rest' => true,
    ]);
    register_post_meta('paf_automation_job', '_status', [ // pending, processing, completed, failed
        'type' => 'string', 'single' => true, 'show_in_rest' => true,
    ]);
    // ... other paf_automation_job fields
}
// add_action( 'init', 'paf_core_register_meta_fields' ); // Enable if using direct meta registration
// For now, meta fields will be handled manually via update_post_meta and get_post_meta.
// Using ACF or CMB2 is highly recommended for easier meta box UI in admin.

?>