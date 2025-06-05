<?php
/**
 * Pinnacle Portal functions and definitions
 */

// Theme setup
function pinnacle_portal_setup() {
    // Add default posts and comments RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages
    add_theme_support('post-thumbnails');

    // Register navigation menus
    register_nav_menus(array(
        'primary' => esc_html__('Primary Menu', 'pinnacle-portal'),
        'footer' => esc_html__('Footer Menu', 'pinnacle-portal'),
    ));

    // Switch default core markup to output valid HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    // Add theme support for selective refresh for widgets
    add_theme_support('customize-selective-refresh-widgets');
}
add_action('after_setup_theme', 'pinnacle_portal_setup');

// Enqueue scripts and styles
function pinnacle_portal_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style('pinnacle-portal-style', get_stylesheet_uri(), array(), '1.0.0');

    // Enqueue custom CSS
    wp_enqueue_style('pinnacle-portal-custom', get_template_directory_uri() . '/css/custom.css', array(), '1.0.0');

    // Enqueue custom JavaScript
    wp_enqueue_script('pinnacle-portal-custom', get_template_directory_uri() . '/js/custom.js', array('jquery'), '1.0.0', true);

    // Enqueue comment reply script if needed
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'pinnacle_portal_scripts');

/**
 * Custom Post Types
 */

// Register Dealer CPT
function pinnacle_portal_register_dealer_cpt() {
    $labels = array(
        'name'                  => _x('Dealers', 'Post type general name', 'pinnacle-portal'),
        'singular_name'         => _x('Dealer', 'Post type singular name', 'pinnacle-portal'),
        'menu_name'             => _x('Dealers', 'Admin Menu text', 'pinnacle-portal'),
        'name_admin_bar'        => _x('Dealer', 'Add New on Toolbar', 'pinnacle-portal'),
        'add_new'               => __('Add New', 'pinnacle-portal'),
        'add_new_item'          => __('Add New Dealer', 'pinnacle-portal'),
        'new_item'              => __('New Dealer', 'pinnacle-portal'),
        'edit_item'             => __('Edit Dealer', 'pinnacle-portal'),
        'view_item'             => __('View Dealer', 'pinnacle-portal'),
        'all_items'             => __('All Dealers', 'pinnacle-portal'),
        'search_items'          => __('Search Dealers', 'pinnacle-portal'),
        'parent_item_colon'     => __('Parent Dealers:', 'pinnacle-portal'),
        'not_found'             => __('No dealers found.', 'pinnacle-portal'),
        'not_found_in_trash'    => __('No dealers found in Trash.', 'pinnacle-portal'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'dealer'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
        'menu_icon'          => 'dashicons-businessman',
    );

    register_post_type('dealer', $args);
}
add_action('init', 'pinnacle_portal_register_dealer_cpt');

// Register Deal CPT
function pinnacle_portal_register_deal_cpt() {
    $labels = array(
        'name'                  => _x('Deals', 'Post type general name', 'pinnacle-portal'),
        'singular_name'         => _x('Deal', 'Post type singular name', 'pinnacle-portal'),
        'menu_name'             => _x('Deals', 'Admin Menu text', 'pinnacle-portal'),
        'name_admin_bar'        => _x('Deal', 'Add New on Toolbar', 'pinnacle-portal'),
        'add_new'               => __('Add New', 'pinnacle-portal'),
        'add_new_item'          => __('Add New Deal', 'pinnacle-portal'),
        'new_item'              => __('New Deal', 'pinnacle-portal'),
        'edit_item'             => __('Edit Deal', 'pinnacle-portal'),
        'view_item'             => __('View Deal', 'pinnacle-portal'),
        'all_items'             => __('All Deals', 'pinnacle-portal'),
        'search_items'          => __('Search Deals', 'pinnacle-portal'),
        'parent_item_colon'     => __('Parent Deals:', 'pinnacle-portal'),
        'not_found'             => __('No deals found.', 'pinnacle-portal'),
        'not_found_in_trash'    => __('No deals found in Trash.', 'pinnacle-portal'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'deal'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
        'menu_icon'          => 'dashicons-money-alt',
    );

    register_post_type('deal', $args);
}
add_action('init', 'pinnacle_portal_register_deal_cpt');

// Register Deal Status Taxonomy
function pinnacle_portal_register_deal_status_taxonomy() {
    $labels = array(
        'name'              => _x('Deal Statuses', 'taxonomy general name', 'pinnacle-portal'),
        'singular_name'     => _x('Deal Status', 'taxonomy singular name', 'pinnacle-portal'),
        'search_items'      => __('Search Deal Statuses', 'pinnacle-portal'),
        'all_items'         => __('All Deal Statuses', 'pinnacle-portal'),
        'parent_item'       => __('Parent Deal Status', 'pinnacle-portal'),
        'parent_item_colon' => __('Parent Deal Status:', 'pinnacle-portal'),
        'edit_item'         => __('Edit Deal Status', 'pinnacle-portal'),
        'update_item'       => __('Update Deal Status', 'pinnacle-portal'),
        'add_new_item'      => __('Add New Deal Status', 'pinnacle-portal'),
        'new_item_name'     => __('New Deal Status Name', 'pinnacle-portal'),
        'menu_name'         => __('Deal Status', 'pinnacle-portal'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'deal-status'),
    );

    register_taxonomy('deal_status', array('deal'), $args);
    
    // Add default deal statuses
    if (!term_exists('pending', 'deal_status')) {
        wp_insert_term('Pending', 'deal_status', array('slug' => 'pending'));
    }
    if (!term_exists('approved', 'deal_status')) {
        wp_insert_term('Approved', 'deal_status', array('slug' => 'approved'));
    }
    if (!term_exists('rejected', 'deal_status')) {
        wp_insert_term('Rejected', 'deal_status', array('slug' => 'rejected'));
    }
    if (!term_exists('completed', 'deal_status')) {
        wp_insert_term('Completed', 'deal_status', array('slug' => 'completed'));
    }
}
add_action('init', 'pinnacle_portal_register_deal_status_taxonomy');

/**
 * Custom Dashboard Widget
 */
function pinnacle_portal_add_dashboard_widgets() {
    wp_add_dashboard_widget(
        'pinnacle_portal_dashboard_widget',
        'Pinnacle Portal Overview',
        'pinnacle_portal_dashboard_widget_callback'
    );
}
add_action('wp_dashboard_setup', 'pinnacle_portal_add_dashboard_widgets');

function pinnacle_portal_dashboard_widget_callback() {
    // Count dealers
    $dealer_count = wp_count_posts('dealer')->publish;
    
    // Count deals by status
    $pending_deals = get_terms_post_count('deal_status', 'pending', 'deal');
    $approved_deals = get_terms_post_count('deal_status', 'approved', 'deal');
    $rejected_deals = get_terms_post_count('deal_status', 'rejected', 'deal');
    $completed_deals = get_terms_post_count('deal_status', 'completed', 'deal');
    
    echo '<div class="dashboard-overview">';
    echo '<h3>Dealers</h3>';
    echo '<p>Total Dealers: ' . $dealer_count . '</p>';
    
    echo '<h3>Deals</h3>';
    echo '<p>Pending Deals: ' . $pending_deals . '</p>';
    echo '<p>Approved Deals: ' . $approved_deals . '</p>';
    echo '<p>Rejected Deals: ' . $rejected_deals . '</p>';
    echo '<p>Completed Deals: ' . $completed_deals . '</p>';
    
    echo '<p><a href="' . admin_url('edit.php?post_type=deal') . '" class="button button-primary">View All Deals</a></p>';
    echo '</div>';
}

// Helper function to count posts by taxonomy term
function get_terms_post_count($taxonomy, $term_slug, $post_type) {
    $term = get_term_by('slug', $term_slug, $taxonomy);
    if (!$term) {
        return 0;
    }
    
    $args = array(
        'post_type' => $post_type,
        'post_status' => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $term_slug,
            ),
        ),
    );
    
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Include additional files
 */
// Custom ACF fields
if (function_exists('acf_add_local_field_group')) {
    require_once get_template_directory() . '/inc/acf-fields.php';
}

// Custom template functions
require_once get_template_directory() . '/inc/template-functions.php';
