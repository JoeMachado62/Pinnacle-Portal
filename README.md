# Pinnacle Auto Finance Dealer Portal

## Project Overview
Pinnacle Auto Finance Dealer Portal is a comprehensive system that enables independent dealerships to access broker financing solutions. The system combines:

*   **The WordPress Portal (VPS-hosted)**: Manages dealer accounts (via Ultimate Member), loan applications, deal tracking, and customer data using Custom Post Types (CPTs) and custom plugin logic.
*   **The Local Automation Client (Dealership PC)**: Handles real-time interaction with DealerTrack through a native browser (details of this client are separate from the WordPress plugin).

This hybrid approach leverages the strengths of both environments: centralized data management and user interface via WordPress, and reliable browser automation on a local machine with authenticated DealerTrack access.

## WordPress Plugin Architecture (`pinnacle-auto-finance-core`)

The core functionality within WordPress is managed by the `pinnacle-auto-finance-core` plugin.

### Main Plugin File: `pinnacle-auto-finance-core.php`
*   **Entry Point**: Initializes the plugin, defines constants (`PAF_CORE_PLUGIN_DIR`, `PAF_CORE_PLUGIN_URL`).
*   **Includes**: Loads all PHP modules from the `includes/` directory.
*   **Activation/Deactivation**: Handles plugin setup (CPT registration, role capabilities like `paf_view_dealer_dashboard`) and cleanup.
*   **Asset Enqueuing**: Manages loading of CSS and JavaScript files for frontend forms and the dealer dashboard. Uses `wp_localize_script` to pass PHP data (like AJAX URLs and nonces) to JavaScript.
*   **Initialization**: Hooks various functions into WordPress core actions (`init`, `admin_init`, `rest_api_init`, `wp_dashboard_setup`).
*   **Admin Features**: Adds a settings page ("Settings" > "Pinnacle Auto") and admin dashboard widgets/metaboxes for dealer management.

### Key PHP Modules in `includes/` Directory:

1.  **`helper-functions.php`**:
    *   Provides general utility functions used throughout the plugin (e.g., `paf_get_deal_status_label`, `paf_get_deal_status_bar_html`, `paf_mask_ssn`, `paf_generate_unique_id`).
    *   `paf_get_current_user_dealer_id()`: Crucial function to retrieve the `paf_dealer` CPT ID associated with the currently logged-in WordPress user. This is used extensively to fetch dealer-specific data.

2.  **`encryption-functions.php`**:
    *   Contains functions for encrypting and decrypting sensitive data (e.g., borrower information). Relies on `openssl_encrypt` and `openssl_decrypt`.
    *   (Assumed to be used when handling `_primary_borrower`, `_co_borrower` meta for credit applications).

3.  **`cpt-definitions.php` (`paf_core_register_cpts`)**:
    *   Registers all Custom Post Types (CPTs) used by the portal:
        *   `paf_dealer`: Stores dealership information, status, and association with a WordPress user.
        *   `paf_credit_app`: Stores submitted credit application data.
        *   `paf_deal`: Tracks the status and details of a financing deal.
        *   `paf_communication`: Logs messages related to deals.
        *   `paf_document`: Manages documents uploaded for deals.
        *   `paf_app_history`: Logs actions and changes related to applications/deals.
    *   Defines CPT arguments, labels, capabilities, and `supports` features.

4.  **`acf-fields.php`**:
    *   Intended for Advanced Custom Fields definitions. (Note: Current dashboard logic seems to use direct `get_post_meta` and `update_post_meta` rather than relying heavily on ACF getter/setter functions for core display, but ACF might be used for admin input).

5.  **`ultimate-member-integration.php` (`paf_core_register_um_hooks`)**:
    *   Integrates with the Ultimate Member plugin for user management.
    *   `paf_add_dealer_registration_fields_filter`, `paf_force_add_dealer_fields_to_form`: Adds custom dealer-specific fields (e.g., "Dealership Legal Name", "Dealer License #") to UM registration and profile forms.
    *   `paf_create_dealer_cpt_on_um_approval`, `paf_create_dealer_cpt_on_registration_complete`: Hooks into UM actions (user approval, registration completion) to automatically create a corresponding `paf_dealer` CPT for new dealer users and copies relevant profile data from user meta to post meta.
    *   `paf_handle_dealer_status_change`: Updates user capabilities when a dealer's status is changed (e.g., via ACF field in admin).
    *   Admin filters for `paf_dealer` CPT list table.

6.  **`frontend-forms.php` (`paf_core_register_form_actions_and_shortcodes`)**:
    *   Handles frontend form submissions (e.g., initial dealer profile, credit application form).
    *   Registers shortcodes for these forms (e.g., `[paf_credit_application_form]`).
    *   Contains `admin-post.php` action handlers (e.g., `paf_handle_initial_dealer_profile_submission`, `paf_handle_credit_application_submission`).

7.  **`frontend-views.php` (`paf_core_register_view_shortcodes`)**:
    *   Registers shortcodes for displaying content on the frontend.
    *   `[paf_dealer_dashboard]` (`paf_render_dealer_dashboard`): The primary function responsible for rendering the dealer dashboard. It checks user login, dealer status, capabilities, and then calls partial rendering functions.
    *   `[paf_deal_jacket_view]` (`paf_render_deal_jacket_view`): Renders the detailed view for a specific deal.
    *   `[paf_application_confirmation]` (`paf_render_application_confirmation_page`): Displays a confirmation page after an application is submitted.

8.  **`dashboard-partials.php`**:
    *   Contains functions that render individual sections (partials) of the dealer dashboard. These are called by `paf_render_dealer_dashboard` in `frontend-views.php`.
        *   `paf_render_dashboard_profile_section` (Note: this seems to be for an older design or a different part of the profile, the main dashboard uses `paf_render_dashboard_account_manager_section`).
        *   `paf_render_dashboard_account_manager_section`: Renders the account manager's contact details.
        *   `paf_render_dashboard_prequal_image_section`: Renders the "Submit New Deal" section.
        *   `paf_render_dashboard_pipeline_section`: Renders the table of active deals.
    *   Also includes `paf_render_advertising_sidebar` (though this might be better placed in `advertising-management.php` for separation of concerns, it's currently called from `frontend-views.php`).

9.  **`dashboard-ajax.php`**:
    *   Defines WordPress AJAX action handlers for dashboard interactions initiated by `assets/js/paf-dashboard.js`.
    *   Example: `wp_ajax_paf_update_profile_field` for handling inline profile edits.
    *   Uses nonces for security.

10. **`advertising-management.php`**:
    *   `paf_render_advertising_sidebar`: Generates the HTML for the featured inventory/advertising sidebar on the dashboard. (Currently, this function is in `frontend-views.php` but logically belongs here or in `dashboard-partials.php`).

11. **`rest-api-endpoints.php` (`paf_core_register_rest_routes`)**:
    *   Registers custom WordPress REST API endpoints (e.g., `/pinnacle/v1/applications/pending`, `/pinnacle/v1/applications/(?P<id>\d+)/status`).
    *   Used for communication between the WordPress portal and the local DealerTrack client or other external services.
    *   Includes permission callbacks for security.

12. **`action-scheduler-jobs.php` (`paf_core_register_action_scheduler_hooks`)**:
    *   Integrates with the Action Scheduler library for handling background tasks or scheduled jobs.

### Assets (`assets/` directory):
*   **CSS (`assets/css/`)**:
    *   `paf-frontend.css`: General frontend styles.
    *   `paf-dashboard.css`: Specific styles for the dealer dashboard layout and components.
    *   `um-form-styling.css`: Custom styles for Ultimate Member forms.
*   **JavaScript (`assets/js/`)**:
    *   `paf-frontend-credit-app.js`: JavaScript for the credit application form.
    *   `paf-dashboard.js`: JavaScript for dealer dashboard interactivity (profile editing, table sorting, AJAX calls).

## Data Flow Highlights:

*   **Dealer Onboarding**: User registers via UM -> UM hooks in `ultimate-member-integration.php` create `paf_dealer` CPT -> Admin approves dealer -> User gains dashboard access.
*   **Dashboard View**: User visits page with `[paf_dealer_dashboard]` -> `frontend-views.php` and `dashboard-partials.php` render the view -> `assets/css/paf-dashboard.css` styles it -> `assets/js/paf-dashboard.js` adds interactivity.
*   **Credit Application**: User fills form (`[paf_credit_application_form]`) -> `frontend-forms.php` handles submission -> Data saved to `paf_credit_app` CPT -> Notification potentially sent via REST API (`rest-api-endpoints.php`) to local client.

## Dealer Dashboard (`[paf_dealer_dashboard]`) Troubleshooting (Ongoing)

**Issue**: The `[paf_dealer_dashboard]` shortcode is not rendering the full dashboard interface as designed (with three columns: advertising, account manager + submit deal, and pipeline). Instead, it's showing a basic interface, likely the `paf_render_initial_dealer_profile_form()` output or an early error message.

**Current Status**:
- The diagnostic script (`test_dashboard_functionality.php`) confirms that all core PHP functions, CPTs, user capabilities, and dealer data seem correct. The script can successfully execute `do_shortcode('[paf_dealer_dashboard]')` and generate the full, expected HTML.
- However, when the shortcode is placed on a live WordPress page (either the Ultimate Member User page or a standard WordPress page), the full dashboard does not render.
- The `paf_render_dealer_dashboard` function in `frontend-views.php` has been simplified to return a basic debug string: `DEBUG_SHORTCODE_EXECUTED_SUCCESSFULLY_LEVEL_1`. This string is **not** appearing on the live frontend pages, indicating the shortcode function itself is likely not being executed or is exiting before this return statement.

**Hypothesis**:
The issue lies in the WordPress frontend's processing of the shortcode. The `paf_render_dealer_dashboard` function is not being called, or something is preventing its output from being displayed. This could be due to:
1.  The theme's content rendering process (e.g., `the_content` filter issues).
2.  A conflict with another plugin altering shortcode behavior or page content.
3.  A fatal PHP error occurring very early in the page load specifically in the web server context, but not in the CLI context used by the diagnostic script.
4.  Caching mechanisms (though PHP-FPM restart and hard refresh were attempted).

**Troubleshooting Steps Taken**:
1.  **Verified Shortcode Registration**: Confirmed `add_shortcode('paf_dealer_dashboard', 'paf_render_dealer_dashboard')` is correctly placed and executed in `paf_core_register_view_shortcodes` (which is hooked to `init`).
2.  **Checked Rendering Functions**: Ensured all partial rendering functions exist and are included.
3.  **Asset Enqueuing**: Corrected asset enqueuing in `pinnacle-auto-finance-core.php`.
4.  **Diagnostic Script**: Created `test_dashboard_functionality.php`.
5.  **Debug Code in Shortcode Function**: Added detailed debug `echo` statements (later simplified to a single `return` statement) within `paf_render_dealer_dashboard()`. This debug output is not appearing on live pages.
6.  **Tested on Non-UM Page**: Confirmed the issue persists on a standard WordPress page.
7.  **PHP-FPM Restart**: User restarted `php8.3-fpm`.

**Next Steps in Troubleshooting**:
1.  **Verify `paf_core_register_view_shortcodes` Execution**: Add a simple `error_log("Shortcodes registered");` or a visible marker in this function to ensure it's running on `init`.
2.  **Check `the_content` Filter**: Investigate if `the_content` filter (which processes shortcodes) is being applied correctly on the target pages.
3.  **Enable `WP_DEBUG`**: Set `WP_DEBUG`, `WP_DEBUG_LOG`, and `WP_DEBUG_DISPLAY` to true in `wp-config.php` to catch any PHP errors or notices on the frontend.
4.  **Theme/Plugin Conflict Test**: Systematically disable other plugins and switch to a default theme.
5.  **Alternative Rendering Approach (Plan B)**: Implement a dedicated page template (`page-dealer-dashboard.php`).
