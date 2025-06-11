<?php
/**
 * Diagnostic script to test the Pinnacle Auto Finance Dashboard functionality
 * Run this script to verify that the dashboard shortcode works properly
 */

// Include WordPress
require_once('WP-Backoffice/wp-config.php');

echo "<!DOCTYPE html><html><head><title>PAF Dashboard Test</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; } .success { background: #d4edda; border-color: #c3e6cb; } .error { background: #f8d7da; border-color: #f5c6cb; } .warning { background: #fff3cd; border-color: #ffeaa7; }</style>";
echo "</head><body>";

echo "<h1>Pinnacle Auto Finance Dashboard Diagnostic Test</h1>";

// Test 1: Check if plugin is active
echo "<div class='test-section'>";
echo "<h2>Test 1: Plugin Status</h2>";
if (is_plugin_active('pinnacle-auto-finance-core/pinnacle-auto-finance-core.php')) {
    echo "<div class='success'>✓ Pinnacle Auto Finance Core plugin is active</div>";
} else {
    echo "<div class='error'>✗ Pinnacle Auto Finance Core plugin is NOT active</div>";
}
echo "</div>";

// Test 2: Check if shortcode is registered
echo "<div class='test-section'>";
echo "<h2>Test 2: Shortcode Registration</h2>";
if (shortcode_exists('paf_dealer_dashboard')) {
    echo "<div class='success'>✓ 'paf_dealer_dashboard' shortcode is registered</div>";
} else {
    echo "<div class='error'>✗ 'paf_dealer_dashboard' shortcode is NOT registered</div>";
}
echo "</div>";

// Test 3: Check if required functions exist
echo "<div class='test-section'>";
echo "<h2>Test 3: Required Functions</h2>";
$required_functions = [
    'paf_render_dealer_dashboard',
    'paf_render_dashboard_account_manager_section',
    'paf_render_dashboard_prequal_image_section', 
    'paf_render_dashboard_pipeline_section',
    'paf_render_advertising_sidebar',
    'paf_get_current_user_dealer_id',
    'paf_get_deal_status_label'
];

foreach ($required_functions as $function) {
    if (function_exists($function)) {
        echo "<div class='success'>✓ Function '$function' exists</div>";
    } else {
        echo "<div class='error'>✗ Function '$function' does NOT exist</div>";
    }
}
echo "</div>";

// Test 4: Test user login simulation
echo "<div class='test-section'>";
echo "<h2>Test 4: User Authentication Test</h2>";

// Get a test user (preferably one with dealer capabilities)
$test_users = get_users([
    'meta_key' => 'wp_capabilities',
    'meta_value' => 'dealer',
    'meta_compare' => 'LIKE',
    'number' => 1
]);

if (empty($test_users)) {
    // Try to find any user with paf capabilities
    $all_users = get_users(['number' => 10]);
    foreach ($all_users as $user) {
        if ($user->has_cap('paf_view_dealer_dashboard')) {
            $test_users = [$user];
            break;
        }
    }
}

if (!empty($test_users)) {
    $test_user = $test_users[0];
    wp_set_current_user($test_user->ID);
    echo "<div class='success'>✓ Simulating login as user: {$test_user->user_login} (ID: {$test_user->ID})</div>";
    
    // Check capabilities
    if (current_user_can('paf_view_dealer_dashboard')) {
        echo "<div class='success'>✓ User has 'paf_view_dealer_dashboard' capability</div>";
    } else {
        echo "<div class='error'>✗ User does NOT have 'paf_view_dealer_dashboard' capability</div>";
    }
    
    // Check dealer CPT
    if (function_exists('paf_get_current_user_dealer_id')) {
        $dealer_id = paf_get_current_user_dealer_id();
        if ($dealer_id) {
            echo "<div class='success'>✓ User has associated dealer CPT (ID: $dealer_id)</div>";
            $dealer_status = get_post_meta($dealer_id, '_status', true);
            echo "<div class='warning'>ℹ Dealer status: " . ($dealer_status ? $dealer_status : 'not set') . "</div>";
        } else {
            echo "<div class='error'>✗ User does NOT have associated dealer CPT</div>";
        }
    }
} else {
    echo "<div class='error'>✗ No suitable test user found</div>";
}
echo "</div>";

// Test 5: Test shortcode execution
echo "<div class='test-section'>";
echo "<h2>Test 5: Shortcode Execution</h2>";

if (is_user_logged_in()) {
    echo "<div class='success'>✓ User is logged in, testing shortcode...</div>";
    
    // Test the shortcode
    $shortcode_output = do_shortcode('[paf_dealer_dashboard]');
    
    if (!empty($shortcode_output) && !contains_error_messages($shortcode_output)) {
        echo "<div class='success'>✓ Shortcode executed successfully</div>";
        echo "<div style='margin-top: 10px;'><strong>Shortcode Output Preview:</strong></div>";
        echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 200px; overflow-y: auto; font-size: 12px;'>";
        echo "<pre>" . esc_html(substr($shortcode_output, 0, 500)) . (strlen($shortcode_output) > 500 ? '...' : '') . "</pre>";
        echo "</div>";
    } else {
        echo "<div class='error'>✗ Shortcode execution failed or returned error</div>";
        if (!empty($shortcode_output)) {
            echo "<div style='margin-top: 10px;'><strong>Error Output:</strong></div>";
            echo "<div style='border: 1px solid #f00; padding: 10px; background: #ffe6e6;'>";
            echo $shortcode_output;
            echo "</div>";
        }
    }
} else {
    echo "<div class='error'>✗ No user logged in for shortcode test</div>";
}
echo "</div>";

// Test 6: Check assets
echo "<div class='test-section'>";
echo "<h2>Test 6: Asset Files</h2>";

$asset_files = [
    'CSS' => 'WP-Backoffice/wp-content/plugins/pinnacle-auto-finance-core/assets/css/paf-dashboard.css',
    'JavaScript' => 'WP-Backoffice/wp-content/plugins/pinnacle-auto-finance-core/assets/js/paf-dashboard.js'
];

foreach ($asset_files as $type => $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<div class='success'>✓ $type file exists ($size bytes)</div>";
    } else {
        echo "<div class='error'>✗ $type file does NOT exist: $file</div>";
    }
}
echo "</div>";

// Test 7: Database checks
echo "<div class='test-section'>";
echo "<h2>Test 7: Database Checks</h2>";

global $wpdb;

// Check for dealer CPTs
$dealer_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'paf_dealer'");
echo "<div class='success'>ℹ Found $dealer_count dealer CPT records</div>";

// Check for approved dealers
$approved_dealers = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->posts} p 
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
    WHERE p.post_type = 'paf_dealer' 
    AND pm.meta_key = '_status' 
    AND pm.meta_value = 'approved'
");
echo "<div class='success'>ℹ Found $approved_dealers approved dealers</div>";

echo "</div>";

// Helper function
function contains_error_messages($content) {
    $error_indicators = [
        'You must be logged in',
        'insufficient permissions',
        'section unavailable',
        'not yet fully enabled',
        'contact support'
    ];
    
    foreach ($error_indicators as $indicator) {
        if (stripos($content, $indicator) !== false) {
            return true;
        }
    }
    return false;
}

echo "<h2>Summary</h2>";
echo "<p>This diagnostic test checks the key components of the PAF dashboard system. If any tests show errors, those issues need to be resolved for the dashboard to work properly.</p>";

// Full render test
if (is_user_logged_in() && current_user_can('paf_view_dealer_dashboard')) {
    echo "<h2>Full Dashboard Render Test</h2>";
    echo "<div style='border: 2px solid #007cba; padding: 20px; margin: 20px 0;'>";
    echo do_shortcode('[paf_dealer_dashboard]');
    echo "</div>";
}

echo "</body></html>";
?>
