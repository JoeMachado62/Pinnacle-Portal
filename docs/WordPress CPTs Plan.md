# WordPress CPTs and Integrations Plan

After reviewing the project documentation and understanding the need to shift from Strapi to WordPress with a Puppeteer automation approach, I've developed a comprehensive plan for implementing the Pinnacle Auto Finance Dealer Portal using WordPress Custom Post Types (CPTs) and server-side automation.

## 1. WordPress Data Architecture Overview

The data architecture will leverage WordPress's native capabilities, combining user management through Ultimate Member with custom post types for application data.

### 1.1 User Management

We'll extend WordPress users through Ultimate Member for role-based functionality:

- **User Roles:**
  - `dealer`: Dealership owners/managers
  - `dealer_staff`: Staff members at dealerships
  - `finance_admin`: Pinnacle financial administrators
  - `admin`: System administrators

- **Ultimate Member Extensions:**
  - Custom registration forms for dealers
  - Role-based content restrictions
  - Custom user profile fields
  - Dashboard integration via template overrides

### 1.2 Custom Post Types

We'll create the following custom post types to replace what would have been Strapi content types:

1. **Dealers CPT**
2. **Credit Applications CPT**
3. **Documents CPT**
4. **Communications CPT**
5. **Application History CPT**
6. **Automation Jobs CPT**

## 2. Custom Post Types Implementation Details

### 2.1 Dealers CPT

```php
register_post_type('paf_dealer', [
    'labels' => [
        'name' => 'Dealers',
        'singular_name' => 'Dealer',
        // Additional labels...
    ],
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_icon' => 'dashicons-store',
    'capability_type' => 'post',
    'map_meta_cap' => true,
    'hierarchical' => false,
    'supports' => ['title', 'thumbnail', 'custom-fields'],
    'has_archive' => false,
    'show_in_rest' => true,
    'rest_base' => 'dealers',
]);
```

**Meta Fields:**
- `address` (Text)
- `city` (Text)
- `state` (Text)
- `zip` (Text)
- `phone` (Text)
- `status` (Select: pending, approved, suspended, inactive)
- `approval_date` (Date)
- `paf_dealer_id` (Text)
- `associated_user_id` (Number, references WP User ID)

*Relationship with Users:* Each Dealer post will be linked to a WordPress user via the `associated_user_id` meta field. This allows us to query a dealer's information based on the logged-in user.

### 2.2 Credit Applications CPT

```php
register_post_type('paf_credit_app', [
    'labels' => [
        'name' => 'Credit Applications',
        'singular_name' => 'Credit Application',
        // Additional labels...
    ],
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_icon' => 'dashicons-clipboard',
    'capability_type' => 'post',
    'map_meta_cap' => true,
    'hierarchical' => false,
    'supports' => ['title', 'author', 'custom-fields'],
    'has_archive' => false,
    'show_in_rest' => true,
    'rest_base' => 'credit-applications',
]);
```

**Meta Fields:**
- `status` (Select: draft, pending_review, processing, approved, declined, completed)
- `primary_borrower` (JSON - contains all primary borrower details)
- `co_borrower` (JSON - contains all co-borrower details if applicable)
- `vehicle_data` (JSON - year, make, model, trim, mileage, VIN)
- `financial_data` (JSON - selling price, warranty, fees, taxes, down payment, etc.)
- `submission_date` (DateTime)
- `last_processed_date` (DateTime)
- `processing_status` (Select: pending, processing, submitted, error)
- `processing_notes` (Text)
- `dt_reference_number` (Text - DealerTrack reference ID)
- `dt_status` (Text - Status from DealerTrack)

*Relationship with User:* Credit Applications will use WordPress's native `post_author` field to associate each application with the dealer user who created it. This enables easy querying of a dealer's applications.

### 2.3 Documents CPT

```php
register_post_type('paf_document', [
    'labels' => [
        'name' => 'Documents',
        'singular_name' => 'Document',
        // Additional labels...
    ],
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_icon' => 'dashicons-media-document',
    'capability_type' => 'post',
    'map_meta_cap' => true,
    'hierarchical' => false,
    'supports' => ['title', 'custom-fields'],
    'has_archive' => false,
    'show_in_rest' => true,
    'rest_base' => 'documents',
]);
```

**Meta Fields:**
- `credit_application_id` (Number - references Credit Application CPT)
- `document_type` (Select: id, proof_of_income, proof_of_residence, vehicle_title, bill_of_sale, etc.)
- `status` (Select: pending, approved, rejected)
- `file_id` (Number - WordPress attachment ID)
- `thumbnail_id` (Number - WordPress attachment ID for thumbnail)

*Relationship:* Documents will be linked to Credit Applications via the `credit_application_id` meta field. This allows for querying all documents belonging to a specific application.

### 2.4 Communications CPT

```php
register_post_type('paf_communication', [
    'labels' => [
        'name' => 'Communications',
        'singular_name' => 'Communication',
        // Additional labels...
    ],
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_icon' => 'dashicons-email',
    'capability_type' => 'post',
    'map_meta_cap' => true,
    'hierarchical' => false,
    'supports' => ['title', 'editor', 'author', 'custom-fields'],
    'has_archive' => false,
    'show_in_rest' => true,
    'rest_base' => 'communications',
]);
```

**Meta Fields:**
- `credit_application_id` (Number - references Credit Application CPT)
- `sender_id` (Number - WordPress user ID)
- `sender_type` (Select: dealer, admin, system)
- `attachments` (JSON - array of attachment IDs)
- `read_status` (Boolean)

*Relationship:* Communications will be linked to Credit Applications via the `credit_application_id` meta field and to users via the `sender_id` field.

### 2.5 Application History CPT

```php
register_post_type('paf_app_history', [
    'labels' => [
        'name' => 'Application History',
        'singular_name' => 'History Entry',
        // Additional labels...
    ],
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_icon' => 'dashicons-backup',
    'capability_type' => 'post',
    'map_meta_cap' => true,
    'hierarchical' => false,
    'supports' => ['title', 'custom-fields'],
    'has_archive' => false,
    'show_in_rest' => true,
    'rest_base' => 'app-history',
]);
```

**Meta Fields:**
- `credit_application_id` (Number - references Credit Application CPT)
- `user_id` (Number - WordPress user ID)
- `action` (Text)
- `details` (JSON)

*Relationship:* History entries will be linked to Credit Applications via the `credit_application_id` meta field and to users via the `user_id` field.

### 2.6 Automation Jobs CPT

```php
register_post_type('paf_automation_job', [
    'labels' => [
        'name' => 'Automation Jobs',
        'singular_name' => 'Automation Job',
        // Additional labels...
    ],
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_icon' => 'dashicons-controls-play',
    'capability_type' => 'post',
    'map_meta_cap' => true,
    'hierarchical' => false,
    'supports' => ['title', 'custom-fields'],
    'has_archive' => false,
    'show_in_rest' => true,
    'rest_base' => 'automation-jobs',
]);
```

**Meta Fields:**
- `credit_application_id` (Number - references Credit Application CPT)
- `status` (Select: pending, processing, completed, failed)
- `scheduled_time` (DateTime)
- `completed_time` (DateTime)
- `attempt_count` (Number)
- `last_error` (Text)
- `result_log` (Text)
- `job_type` (Select: submit_application, update_status, fetch_status)

*Relationship:* Automation Jobs will be linked to Credit Applications via the `credit_application_id` meta field.

## 3. Integration with Ultimate Member

### 3.1 Registration and Profile Extensions

1. **Custom Registration Fields:**
   - Add business fields to the UM registration form
   - Store dealer-specific information in user meta

```php
function paf_add_um_registration_fields() {
    // Add dealer information fields to registration form
    UM()->form()->add_field('dealer_business_name', [
        'title' => 'Business Name',
        'metakey' => 'dealer_business_name',
        'type' => 'text',
        'label' => 'Business Name',
        'required' => 1,
        'public' => 1,
        'editable' => 1,
    ]);
    
    // Add additional fields (address, phone, etc.)
    // ...
}
add_action('um_before_form_is_loaded', 'paf_add_um_registration_fields');
```

2. **User Approval Process:**
   - Implement custom approval workflow for dealer registrations
   - Create dealer CPT entry when user is approved

```php
function paf_create_dealer_cpt_on_approval($user_id) {
    // Get user data
    $user = get_userdata($user_id);
    
    // Check if user has dealer role
    if (in_array('dealer', $user->roles)) {
        // Create dealer CPT
        $dealer_id = wp_insert_post([
            'post_type' => 'paf_dealer',
            'post_title' => get_user_meta($user_id, 'dealer_business_name', true),
            'post_status' => 'publish',
        ]);
        
        // Add meta fields from user meta
        update_post_meta($dealer_id, 'associated_user_id', $user_id);
        update_post_meta($dealer_id, 'address', get_user_meta($user_id, 'dealer_address', true));
        update_post_meta($dealer_id, 'city', get_user_meta($user_id, 'dealer_city', true));
        // Add additional meta fields...
        
        // Set the dealer to pending status
        update_post_meta($dealer_id, 'status', 'pending');
    }
}
add_action('um_after_user_is_approved', 'paf_create_dealer_cpt_on_approval');
```

### 3.2 Dashboard Integration

1. **Custom Dealer Dashboard:**
   - Extend the UM profile template to include credit application data
   - Create shortcodes for displaying the dealer's applications

```php
// Shortcode to display dealer's credit applications
function paf_my_applications_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your applications.</p>';
    }
    
    $current_user_id = get_current_user_id();
    
    // Get applications authored by this user
    $applications = get_posts([
        'post_type' => 'paf_credit_app',
        'author' => $current_user_id,
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);
    
    if (empty($applications)) {
        return '<p>You have not submitted any credit applications yet.</p>';
    }
    
    $output = '<div class="paf-applications-table">';
    $output .= '<table class="paf-table">';
    $output .= '<thead><tr>';
    $output .= '<th>Date</th>';
    $output .= '<th>Customer</th>';
    $output .= '<th>Vehicle</th>';
    $output .= '<th>Status</th>';
    $output .= '<th>DT Reference</th>';
    $output .= '<th>Actions</th>';
    $output .= '</tr></thead>';
    $output .= '<tbody>';
    
    foreach ($applications as $app) {
        $vehicle_data = json_decode(get_post_meta($app->ID, 'vehicle_data', true), true);
        $dt_ref = get_post_meta($app->ID, 'dt_reference_number', true);
        $status = get_post_meta($app->ID, 'status', true);
        $customer_data = json_decode(get_post_meta($app->ID, 'primary_borrower', true), true);
        
        $output .= '<tr>';
        $output .= '<td>' . get_the_date('m/d/Y', $app) . '</td>';
        $output .= '<td>' . esc_html($customer_data['first_name'] . ' ' . $customer_data['last_name']) . '</td>';
        $output .= '<td>' . esc_html($vehicle_data['year'] . ' ' . $vehicle_data['make'] . ' ' . $vehicle_data['model']) . '</td>';
        $output .= '<td>' . esc_html($status) . '</td>';
        $output .= '<td>' . esc_html($dt_ref) . '</td>';
        $output .= '<td><a href="' . esc_url(get_permalink($app->ID)) . '">View</a></td>';
        $output .= '</tr>';
    }
    
    $output .= '</tbody></table></div>';
    
    return $output;
}
add_shortcode('paf_my_applications', 'paf_my_applications_shortcode');
```

2. **Application Form Integration:**
   - Create a custom credit application form that creates a CPT on submission

```php
function paf_process_credit_app_form() {
    // Verify nonce for security
    if (!isset($_POST['paf_app_nonce']) || !wp_verify_nonce($_POST['paf_app_nonce'], 'paf_app_submit')) {
        return;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    // Sanitize and validate form data
    $primary_borrower = [
        'first_name' => sanitize_text_field($_POST['borrower_first_name']),
        'last_name' => sanitize_text_field($_POST['borrower_last_name']),
        // Additional fields...
    ];
    
    $vehicle_data = [
        'year' => sanitize_text_field($_POST['vehicle_year']),
        'make' => sanitize_text_field($_POST['vehicle_make']),
        'model' => sanitize_text_field($_POST['vehicle_model']),
        // Additional fields...
    ];
    
    $financial_data = [
        'selling_price' => sanitize_text_field($_POST['selling_price']),
        // Additional fields...
    ];
    
    // Create credit application CPT
    $app_id = wp_insert_post([
        'post_type' => 'paf_credit_app',
        'post_title' => $primary_borrower['first_name'] . ' ' . $primary_borrower['last_name'] . ' - ' . date('m/d/Y'),
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ]);
    
    // Save meta fields
    update_post_meta($app_id, 'primary_borrower', json_encode($primary_borrower));
    update_post_meta($app_id, 'vehicle_data', json_encode($vehicle_data));
    update_post_meta($app_id, 'financial_data', json_encode($financial_data));
    update_post_meta($app_id, 'status', 'pending_review');
    update_post_meta($app_id, 'submission_date', current_time('mysql'));
    
    // Create history entry
    wp_insert_post([
        'post_type' => 'paf_app_history',
        'post_title' => 'Application Created',
        'post_status' => 'publish',
        'meta_input' => [
            'credit_application_id' => $app_id,
            'user_id' => get_current_user_id(),
            'action' => 'create',
            'details' => json_encode([
                'timestamp' => current_time('mysql'),
                'note' => 'Application submitted by dealer',
            ]),
        ],
    ]);
    
    // Queue automation job
    schedule_dealertrack_submission($app_id);
    
    // Redirect to confirmation page
    wp_redirect(add_query_arg('app_id', $app_id, home_url('/application-submitted/')));
    exit;
}
add_action('admin_post_paf_submit_app', 'paf_process_credit_app_form');
```

## 4. Puppeteer Integration for DealerTrack Automation

### 4.1 Job Scheduling

We'll use WordPress Action Scheduler to queue automation jobs:

```php
function schedule_dealertrack_submission($app_id) {
    // Create automation job record
    $job_id = wp_insert_post([
        'post_type' => 'paf_automation_job',
        'post_title' => 'Submit App #' . $app_id . ' to DealerTrack',
        'post_status' => 'publish',
        'meta_input' => [
            'credit_application_id' => $app_id,
            'status' => 'pending',
            'scheduled_time' => current_time('mysql'),
            'attempt_count' => 0,
            'job_type' => 'submit_application',
        ],
    ]);
    
    // Schedule job using Action Scheduler
    as_schedule_single_action(time(), 'paf_process_dealertrack_job', ['job_id' => $job_id]);
    
    // Update credit application status
    update_post_meta($app_id, 'processing_status', 'pending');
    
    return $job_id;
}
```

### 4.2 REST API Endpoints

Create custom REST API endpoints for the Puppeteer service to interact with:

```php
function paf_register_api_routes() {
    register_rest_route('paf/v1', '/jobs/pending', [
        'methods' => 'GET',
        'callback' => 'paf_get_pending_jobs',
        'permission_callback' => 'paf_api_permissions',
    ]);
    
    register_rest_route('paf/v1', '/jobs/(?P<id>\d+)/update', [
        'methods' => 'POST',
        'callback' => 'paf_update_job_status',
        'permission_callback' => 'paf_api_permissions',
        'args' => [
            'status' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return in_array($param, ['processing', 'completed', 'failed']);
                },
            ],
        ],
    ]);
    
    register_rest_route('paf/v1', '/applications/(?P<id>\d+)/data', [
        'methods' => 'GET',
        'callback' => 'paf_get_application_data',
        'permission_callback' => 'paf_api_permissions',
    ]);
    
    register_rest_route('paf/v1', '/applications/(?P<id>\d+)/update', [
        'methods' => 'POST',
        'callback' => 'paf_update_application_status',
        'permission_callback' => 'paf_api_permissions',
    ]);
}
add_action('rest_api_init', 'paf_register_api_routes');

// API Permission callback
function paf_api_permissions() {
    // Implement authentication for API access
    // This could use application passwords or custom token authentication
    return true; // Temporary for development
}

// Get pending jobs endpoint
function paf_get_pending_jobs() {
    $jobs = get_posts([
        'post_type' => 'paf_automation_job',
        'posts_per_page' => 5,
        'meta_query' => [
            [
                'key' => 'status',
                'value' => 'pending',
            ],
        ],
    ]);
    
    $job_data = [];
    foreach ($jobs as $job) {
        $job_data[] = [
            'id' => $job->ID,
            'type' => get_post_meta($job->ID, 'job_type', true),
            'application_id' => get_post_meta($job->ID, 'credit_application_id', true),
        ];
    }
    
    return new WP_REST_Response($job_data, 200);
}

// Update job status endpoint
function paf_update_job_status($request) {
    $job_id = $request['id'];
    $status = $request['status'];
    $result = $request['result'] ?? '';
    $error = $request['error'] ?? '';
    $dt_reference = $request['dt_reference'] ?? '';
    
    // Update job status
    update_post_meta($job_id, 'status', $status);
    update_post_meta($job_id, 'completed_time', current_time('mysql'));
    
    if ($status === 'failed') {
        update_post_meta($job_id, 'last_error', $error);
        
        // Increment attempt count
        $attempts = intval(get_post_meta($job_id, 'attempt_count', true)) + 1;
        update_post_meta($job_id, 'attempt_count', $attempts);
        
        // If fewer than 3 attempts, reschedule
        if ($attempts < 3) {
            as_schedule_single_action(time() + 300, 'paf_process_dealertrack_job', ['job_id' => $job_id]);
            update_post_meta($job_id, 'status', 'pending');
        }
    }
    
    if ($status === 'completed') {
        update_post_meta($job_id, 'result_log', $result);
        
        // Update the credit application with DealerTrack reference
        $app_id = get_post_meta($job_id, 'credit_application_id', true);
        if ($app_id && !empty($dt_reference)) {
            update_post_meta($app_id, 'dt_reference_number', $dt_reference);
            update_post_meta($app_id, 'processing_status', 'submitted');
            update_post_meta($app_id, 'status', 'processing');
        }
    }
    
    return new WP_REST_Response(['success' => true], 200);
}

// Get application data endpoint
function paf_get_application_data($request) {
    $app_id = $request['id'];
    $app = get_post($app_id);
    
    if (!$app || $app->post_type !== 'paf_credit_app') {
        return new WP_Error('not_found', 'Application not found', ['status' => 404]);
    }
    
    $primary_borrower = json_decode(get_post_meta($app_id, 'primary_borrower', true), true);
    $vehicle_data = json_decode(get_post_meta($app_id, 'vehicle_data', true), true);
    $financial_data = json_decode(get_post_meta($app_id, 'financial_data', true), true);
    
    $data = [
        'id' => $app_id,
        'primary_borrower' => $primary_borrower,
        'vehicle_data' => $vehicle_data,
        'financial_data' => $financial_data,
    ];
    
    return new WP_REST_Response($data, 200);
}

// Update application status endpoint
function paf_update_application_status($request) {
    $app_id = $request['id'];
    $dt_status = $request['dt_status'] ?? '';
    $dt_reference = $request['dt_reference'] ?? '';
    $notes = $request['notes'] ?? '';
    
    if (!empty($dt_status)) {
        update_post_meta($app_id, 'dt_status', $dt_status);
    }
    
    if (!empty($dt_reference)) {
        update_post_meta($app_id, 'dt_reference_number', $dt_reference);
    }
    
    if (!empty($notes)) {
        update_post_meta($app_id, 'processing_notes', $notes);
    }
    
    // Create history entry
    wp_insert_post([
        'post_type' => 'paf_app_history',
        'post_title' => 'DealerTrack Status Updated',
        'post_status' => 'publish',
        'meta_input' => [
            'credit_application_id' => $app_id,
            'user_id' => 0, // System update
            'action' => 'update_dt_status',
            'details' => json_encode([
                'timestamp' => current_time('mysql'),
                'dt_status' => $dt_status,
                'notes' => $notes,
            ]),
        ],
    ]);
    
    return new WP_REST_Response(['success' => true], 200);
}
```

### 4.3 Puppeteer Service Architecture

The Node.js Puppeteer service will:

1. Poll the WordPress API for pending jobs
2. Process each job by launching a headless Chrome instance
3. Log into DealerTrack using the finance admin credentials
4. Perform the requested operation (submit application, fetch status)
5. Update the job status and application data via the WordPress API

```javascript
// Simplified example of the Puppeteer job processor
async function processPendingJobs() {
    try {
        // Fetch pending jobs
        const response = await axios.get(`${WP_API_BASE}/jobs/pending`, {
            headers: { Authorization: `Bearer ${API_TOKEN}` }
        });
        
        const jobs = response.data;
        
        for (const job of jobs) {
            console.log(`Processing job ${job.id} of type ${job.type}`);
            
            // Update job to processing
            await axios.post(`${WP_API_BASE}/jobs/${job.id}/update`, {
                status: 'processing'
            }, {
                headers: { Authorization: `Bearer ${API_TOKEN}` }
            });
            
            // Get application data
            const appData = await axios.get(`${WP_API_BASE}/applications/${job.application_id}/data`, {
                headers: { Authorization: `Bearer ${API_TOKEN}` }
            });
            
            try {
                // Launch browser
                const browser = await puppeteer.launch({
                    headless: true,
                    args: ['--no-sandbox', '--disable-setuid-sandbox']
                });
                
                const page = await browser.newPage();
                
                // Login to DealerTrack
                await page.goto('https://dealertrack.com/login');
                await page.type('#username', DEALERTRACK_USERNAME);
                await page.type('#password', DEALERTRACK_PASSWORD);
                await page.click('#loginButton');
                await page.waitForNavigation();
                
                if (job.type === 'submit_application') {
                    // Navigate to credit application form
                    await page.goto('https://dealertrack.com/new-application');
                    
                    // Fill borrower information
                    await page.type('#firstName', appData.data.primary_borrower.first_name);
                    await page.type('#lastName', appData.data.primary_borrower.last_name);
                    // Fill additional fields...
                    
                    // Fill vehicle information
                    await page.type('#vehicleYear', appData.data.vehicle_data.year);
                    await page.type('#vehicleMake', appData.data.vehicle_data.make);
                    // Fill additional fields...
                    
                    // Submit the form
                    await page.click('#submitButton');
                    await page.waitForNavigation();
                    
                    // Get DealerTrack reference number
                    const referenceElement = await page.$('#referenceNumber');
                    const dtReference = await page.evaluate(el => el.textContent, referenceElement);
                    
                    // Update job as completed
                    await axios.post(`${WP_API_BASE}/jobs/${job.id}/update`, {
                        status: 'completed',
                        dt_reference: dtReference,
                        result: 'Application submitted successfully'
                    }, {
                        headers: { Authorization: `Bearer ${API_TOKEN}` }
                    });
                    
                    // Update application status
                    await axios.post(`${WP_API_BASE}/applications/${job.application_id}/update`, {
                        dt_status: 'Submitted',
                        dt_reference: dtReference
                    }, {
                        headers: { Authorization: `Bearer ${API_TOKEN}` }
                    });
                }
                
                await browser.close();
            } catch (error) {
                console.error(`Error processing job ${job.id}:`, error);
                
                // Update job as failed
                await axios.post(`${WP_API_BASE}/jobs/${job.id}/update`, {
                    status: 'failed',
                    error: error.message
                }, {
                    headers: { Authorization: `Bearer ${API_TOKEN}` }
                });
            }
        }
    } catch (error) {
        console.error('Error fetching pending jobs:', error);
    }
    
    // Schedule next poll
    setTimeout(processPendingJobs, 30000);
}

// Start job processing
processPendingJobs();

## 6. Implementation Steps

To implement this plan, we will follow these steps in order:

### 6.1 WordPress Custom Plugin Development

1. Create a custom plugin to register all CPTs and meta fields
2. Register REST API endpoints for Puppeteer integration
3. Add Action Scheduler integration for job queuing
4. Create admin screens for managing data

### 6.2 Ultimate Member Integration

1. Create custom registration and profile fields for dealers
2. Set up dealer approval workflow
3. Add custom dashboard components using shortcodes
4. Configure profile templates and dashboard pages

### 6.3 Credit Application Form Development

1. Create custom form for credit application submission
2. Add validation and formatting for all required fields
3. Implement AJAX form submission for improved UX
4. Add confirmation flow and status tracking

### 6.4 Puppeteer Service Development

1. Create Node.js service for Puppeteer automation
2. Implement DealerTrack login and navigation logic
3. Develop form filling based on field mapping configuration
4. Add error handling and retry mechanisms
5. Set up secure communication with WordPress API
6. Deploy service using Docker container

### 6.5 Testing and Deployment

1. Create test dealer accounts and sample applications
2. Verify data flow from form submission to DealerTrack
3. Test error scenarios and recovery mechanisms
4. Monitor performance and adjust as needed
5. Document system for administrators and users

## 7. Security Considerations

### 7.1 WordPress Data Security

1. All CPTs should be marked as `public => false` to prevent front-end exposure
2. Custom capabilities should be used to restrict access to sensitive data
3. Meta fields containing sensitive information (SSN, financial data) should be encrypted at rest
4. Data validation and sanitization should be used for all form inputs

### 7.2 API Security

1. REST API endpoints should use application passwords or JWT authentication
2. Rate limiting should be implemented to prevent abuse
3. Request validation should ensure only authorized access to data
4. All API communications should use HTTPS

### 7.3 DealerTrack Credentials

1. DealerTrack admin credentials should be stored securely (encrypted)
2. The Puppeteer service should run in a secure environment with limited access
3. Session management should ensure proper logout after each operation
4. Monitor for any unusual activity or login attempts

## 8. Conclusion

This implementation plan provides a comprehensive approach to building the Pinnacle Auto Finance Dealer Portal using WordPress Custom Post Types and server-side Puppeteer automation. By leveraging WordPress's robust content management capabilities and extending it with custom functionality, we can create a system that meets all the requirements while maintaining scalability and security.

The shift from Strapi to WordPress simplifies the architecture by using a single system for both user management and data storage. The move from Chrome Extensions to server-side Puppeteer automation improves reliability and user experience by removing the need for dealers to install additional software.

This approach addresses the core functional requirements:
- Dealer management through Ultimate Member
- Credit application submission and tracking
- Document management
- DealerTrack integration
- Communication and status updates

Once implemented, this system will provide a seamless experience for dealers while giving Pinnacle Auto Finance administrators full visibility and control over the financing process.
```

## 5. Field Mapping Configuration

Create a field mapping configuration file to map WordPress CPT fields to DealerTrack form fields:

```javascript
// Field mapping between CPT meta and DealerTrack selectors
const fieldMapping = {
    // Borrower Information
    'primary_borrower.first_name': '#firstName',
    'primary_borrower.last_name': '#lastName',
    'primary_borrower.ssn': '#ssn',
    'primary_borrower.dob': '#dateOfBirth',
    'primary_borrower.email': '#email',
    'primary_borrower.phone': '#phoneNumber',
    
    // Address Information
    'primary_borrower.address': '#street',
    'primary_borrower.city': '#city',
    'primary_borrower.state': '#state',
    'primary_borrower.zip': '#zipCode',
    'primary_borrower.residence_years': '#yearsAtAddress',
    'primary_borrower.residence_months': '#monthsAtAddress',
    
    // Employment Information
    'primary_borrower.employer': '#employerName',
    'primary_borrower.employment_years': '#yearsEmployed',
    'primary_borrower.employment_months': '#monthsEmployed',
    'primary_borrower.position': '#position',
    'primary_borrower.income': '#monthlyIncome',
    
    // Vehicle Information
    'vehicle_data.year': '#vehicleYear',
    'vehicle_data.make': '#vehicleMake',
    'vehicle_data.model': '#vehicleModel',
    'vehicle_data.trim': '#vehicleTrim',
    'vehicle_data.mileage': '#vehicleMileage',
    'vehicle_data.vin': '#vin',
    
    // Financial Information
    'financial_data.selling_price': '#sellingPrice',
    'financial_data.down_payment': '#cashDown',
    'financial_data.trade_value': '#tradeInValue',
    'financial_data.trade_payoff': '#tradeInPayoff',
    'financial_data.tax': '#taxAmount',
    'financial_data.warranty': '#warrantyAmount',
    'financial_data.gap': '#gapAmount',
    'financial_data.doc_fees': '#documentationFee',
    'financial_data.title_fees': '#titleFee',
    'financial_data.registration_fees': '#registrationFee',
    'financial_data.amount_financed': '#amountToFinance',
    
    // Additional selectors for multi-step navigation
    'submitButton': '#submitApplication',
    'continueButton': '.continue-button',
    'referenceNumber': '#referenceNumber',
    'statusIndicator': '.status-indicator',
};