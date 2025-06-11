<?php
// /wp-content/plugins/pinnacle-auto-finance-core/includes/advertising-management.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Advertising Management System for Dealer Dashboard Sidebar
 * Allows admins to manage advertising content displayed in dealer dashboards
 */

/**
 * Create database table for advertising content
 */
function paf_create_advertising_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'paf_dashboard_ads';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text,
        image_url varchar(500),
        link_url varchar(500),
        button_text varchar(100) DEFAULT 'Learn More',
        ad_type varchar(50) DEFAULT 'product',
        status varchar(20) DEFAULT 'active',
        sort_order int(11) DEFAULT 0,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

/**
 * Add sample advertising data
 */
function paf_add_sample_ads() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'paf_dashboard_ads';
    
    // Check if we already have data
    $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    
    if ($existing_count == 0) {
        $sample_ads = [
            [
                'title' => 'CERTIFIED USED 2.4L 2021 ACURA ILX...',
                'description' => '2.4L ● 2021 ● Manual',
                'image_url' => 'https://via.placeholder.com/200x150/0073AA/FFFFFF?text=Acura+ILX',
                'link_url' => '#',
                'button_text' => '$12,000',
                'ad_type' => 'vehicle',
                'status' => 'active',
                'sort_order' => 1
            ],
            [
                'title' => 'USED 2.0L 2020 KIA SPORTAGE',
                'description' => '2.0L ● 2020 ● Automatic',
                'image_url' => 'https://via.placeholder.com/200x150/DC3545/FFFFFF?text=Kia+Sportage',
                'link_url' => '#',
                'button_text' => '$24,000',
                'ad_type' => 'vehicle',
                'status' => 'active',
                'sort_order' => 2
            ],
            [
                'title' => 'NEW ELECTRICAL 2022 TESLA ROADSTER...',
                'description' => 'Electrical ● 2022 ● Automatic',
                'image_url' => 'https://via.placeholder.com/200x150/28A745/FFFFFF?text=Tesla+Roadster',
                'link_url' => '#',
                'button_text' => '$120,000',
                'ad_type' => 'vehicle',
                'status' => 'active',
                'sort_order' => 3
            ]
        ];
        
        foreach ($sample_ads as $ad) {
            $wpdb->insert($table_name, $ad);
        }
    }
}

/**
 * Get all active ads for display
 */
function paf_get_dashboard_ads($limit = 10) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'paf_dashboard_ads';
    
    $ads = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE status = 'active' ORDER BY sort_order ASC, id ASC LIMIT %d",
        $limit
    ));
    
    return $ads;
}

/**
 * Render the advertising sidebar for the dashboard (database version)
 */
function paf_render_advertising_sidebar_from_db() {
    $ads = paf_get_dashboard_ads(6); // Limit to 6 ads
    
    if (empty($ads)) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="paf-advertising-sidebar">
        <h3 class="paf-sidebar-title">Featured Inventory</h3>
        <div class="paf-ads-container">
            <?php foreach ($ads as $ad): ?>
                <div class="paf-ad-card">
                    <?php if ($ad->image_url): ?>
                        <div class="paf-ad-image">
                            <img src="<?php echo esc_url($ad->image_url); ?>" alt="<?php echo esc_attr($ad->title); ?>">
                            <?php if ($ad->ad_type === 'vehicle'): ?>
                                <div class="paf-ad-badge"><?php echo strpos($ad->title, 'CERTIFIED') !== false ? 'FEATURED CLASSIFIED' : (strpos($ad->title, 'USED') !== false ? 'SPECIAL' : 'NEW'); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="paf-ad-content">
                        <h4 class="paf-ad-title"><?php echo esc_html($ad->title); ?></h4>
                        <?php if ($ad->description): ?>
                            <p class="paf-ad-description"><?php echo esc_html($ad->description); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($ad->link_url): ?>
                            <a href="<?php echo esc_url($ad->link_url); ?>" class="paf-ad-button" target="_blank">
                                <?php echo esc_html($ad->button_text); ?>
                            </a>
                        <?php else: ?>
                            <div class="paf-ad-button"><?php echo esc_html($ad->button_text); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Admin page for managing advertisements
 */
function paf_add_advertising_admin_page() {
    add_submenu_page(
        'edit.php?post_type=paf_dealer',
        'Dashboard Advertising',
        'Dashboard Ads',
        'manage_options',
        'paf-dashboard-ads',
        'paf_render_advertising_admin_page'
    );
}

/**
 * Render the admin page for managing ads
 */
function paf_render_advertising_admin_page() {
    // Handle form submissions
    if (isset($_POST['paf_add_ad']) && wp_verify_nonce($_POST['paf_ads_nonce'], 'paf_manage_ads')) {
        paf_handle_add_ad();
    }
    
    if (isset($_POST['paf_delete_ad']) && wp_verify_nonce($_POST['paf_ads_nonce'], 'paf_manage_ads')) {
        paf_handle_delete_ad();
    }
    
    $ads = paf_get_all_ads();
    ?>
    <div class="wrap">
        <h1>Dashboard Advertising Management</h1>
        <p>Manage advertising content displayed in the dealer dashboard sidebar.</p>
        
        <h2>Add New Advertisement</h2>
        <form method="post" action="">
            <?php wp_nonce_field('paf_manage_ads', 'paf_ads_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ad_title">Title</label></th>
                    <td><input type="text" id="ad_title" name="ad_title" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ad_description">Description</label></th>
                    <td><textarea id="ad_description" name="ad_description" rows="3" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ad_image_url">Image URL</label></th>
                    <td><input type="url" id="ad_image_url" name="ad_image_url" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ad_link_url">Link URL</label></th>
                    <td><input type="url" id="ad_link_url" name="ad_link_url" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ad_button_text">Button Text</label></th>
                    <td><input type="text" id="ad_button_text" name="ad_button_text" class="regular-text" value="Learn More"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ad_type">Ad Type</label></th>
                    <td>
                        <select id="ad_type" name="ad_type">
                            <option value="product">Product</option>
                            <option value="vehicle">Vehicle</option>
                            <option value="service">Service</option>
                            <option value="promotion">Promotion</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ad_sort_order">Sort Order</label></th>
                    <td><input type="number" id="ad_sort_order" name="ad_sort_order" min="0" value="0"></td>
                </tr>
            </table>
            <?php submit_button('Add Advertisement', 'primary', 'paf_add_ad'); ?>
        </form>
        
        <h2>Existing Advertisements</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Sort Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($ads): ?>
                    <?php foreach ($ads as $ad): ?>
                        <tr>
                            <td><?php echo esc_html($ad->title); ?></td>
                            <td><?php echo esc_html(ucfirst($ad->ad_type)); ?></td>
                            <td><?php echo esc_html(ucfirst($ad->status)); ?></td>
                            <td><?php echo esc_html($ad->sort_order); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('paf_manage_ads', 'paf_ads_nonce'); ?>
                                    <input type="hidden" name="ad_id" value="<?php echo esc_attr($ad->id); ?>">
                                    <input type="submit" name="paf_delete_ad" class="button button-small" value="Delete" onclick="return confirm('Are you sure?')">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No advertisements found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Get all ads for admin management
 */
function paf_get_all_ads() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'paf_dashboard_ads';
    
    return $wpdb->get_results("SELECT * FROM $table_name ORDER BY sort_order ASC, id ASC");
}

/**
 * Handle adding new advertisement
 */
function paf_handle_add_ad() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'paf_dashboard_ads';
    
    $data = [
        'title' => sanitize_text_field($_POST['ad_title']),
        'description' => sanitize_textarea_field($_POST['ad_description']),
        'image_url' => esc_url_raw($_POST['ad_image_url']),
        'link_url' => esc_url_raw($_POST['ad_link_url']),
        'button_text' => sanitize_text_field($_POST['ad_button_text']),
        'ad_type' => sanitize_text_field($_POST['ad_type']),
        'sort_order' => intval($_POST['ad_sort_order']),
        'status' => 'active'
    ];
    
    $result = $wpdb->insert($table_name, $data);
    
    if ($result) {
        echo '<div class="notice notice-success"><p>Advertisement added successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error adding advertisement.</p></div>';
    }
}

/**
 * Handle deleting advertisement
 */
function paf_handle_delete_ad() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'paf_dashboard_ads';
    $ad_id = intval($_POST['ad_id']);
    
    $result = $wpdb->delete($table_name, ['id' => $ad_id], ['%d']);
    
    if ($result) {
        echo '<div class="notice notice-success"><p>Advertisement deleted successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error deleting advertisement.</p></div>';
    }
}

// Hook into activation to create table
register_activation_hook(PAF_CORE_PLUGIN_DIR . 'pinnacle-auto-finance-core.php', 'paf_create_advertising_table');
register_activation_hook(PAF_CORE_PLUGIN_DIR . 'pinnacle-auto-finance-core.php', 'paf_add_sample_ads');

// Add admin menu
add_action('admin_menu', 'paf_add_advertising_admin_page');
