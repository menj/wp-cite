<?php
/*
Plugin Name: Cite
Plugin URI: https://github.com/menj/cite
Description: Professional academic citation plugin with BibTeX/RIS/CSL-JSON/CFF/Dublin Core export, analytics, JSON-LD structured data, and 20 citation formats including Wikipedia.
Version: 2.8.0
Author: MENJ
Author URI: https://menj.org
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPCP_VERSION', '2.8.0');
define('WPCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPCP_ASSETS_URL', WPCP_PLUGIN_URL . 'assets/');

// Localization / Internationalization
add_action('plugins_loaded', 'wpcp_load_textdomain');

function wpcp_load_textdomain() {
    load_plugin_textdomain('cite', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

/**
 * Handle automatic data retention purge.
 */
function wpcp_schedule_purge() {
    if (!wp_next_scheduled('wpcp_daily_purge_event')) {
        wp_schedule_event(time(), 'daily', 'wpcp_daily_purge_event');
    }
}
add_action('wp', 'wpcp_schedule_purge');

function wpcp_do_daily_purge() {
    global $wpdb, $wpcp_setting;
    
    $days = isset($wpcp_setting['analytics_retention_days']) ? intval($wpcp_setting['analytics_retention_days']) : 90;
    
    if ($days > 0) {
        $table_name = $wpdb->prefix . 'cite_analytics';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
}
add_action('wpcp_daily_purge_event', 'wpcp_do_daily_purge');

/**
 * Clean up cron on deactivation
 */
function wpcp_deactivate() {
    wp_clear_scheduled_hook('wpcp_daily_purge_event');
}
register_deactivation_hook(__FILE__, 'wpcp_deactivate');

/**
 * Register privacy policy content for GDPR compliance.
 * Uses WordPress's built-in privacy policy guide system.
 */
function wpcp_add_privacy_policy_content() {
    if (!function_exists('wp_add_privacy_policy_content')) {
        return;
    }
    
    $content = sprintf(
        '<h2>%s</h2>
        <p>%s</p>
        
        <h3>%s</h3>
        <p>%s</p>
        <ul>
            <li><strong>%s</strong> – %s</li>
            <li><strong>%s</strong> – %s</li>
            <li><strong>%s</strong> – %s</li>
        </ul>
        
        <h3>%s</h3>
        <p>%s</p>
        
        <h3>%s</h3>
        <p>%s</p>
        
        <h3>%s</h3>
        <p>%s</p>',
        
        // Header
        esc_html__('Citation Analytics (Cite Plugin)', 'cite'),
        esc_html__('This website uses the Cite plugin to provide academic citation functionality. When citation analytics are enabled, we collect limited data about how visitors interact with citations.', 'cite'),
        
        // What we collect
        esc_html__('What We Collect', 'cite'),
        esc_html__('When analytics are enabled, the following events may be recorded:', 'cite'),
        
        esc_html__('View', 'cite'),
        esc_html__('When a citation box is displayed on a page.', 'cite'),
        
        esc_html__('Copy', 'cite'),
        esc_html__('When a visitor copies a citation to their clipboard.', 'cite'),
        
        esc_html__('Export', 'cite'),
        esc_html__('When a visitor downloads a citation in BibTeX, RIS, EndNote, CSL-JSON, CFF, or Dublin Core format.', 'cite'),
        
        // Consent requirement
        esc_html__('Consent Requirement', 'cite'),
        esc_html__('Analytics tracking requires explicit consent. No data is collected until consent is granted through our cookie consent mechanism. You may decline analytics tracking and still use all citation features.', 'cite'),
        
        // Data retention
        esc_html__('Data Retention', 'cite'),
        esc_html__('Analytics data is automatically deleted after the configured retention period (default: 90 days). No personally identifiable information such as IP addresses or names is stored when session-based tracking is enabled.', 'cite'),
        
        // Opt-out
        esc_html__('Opt-Out', 'cite'),
        esc_html__('You can opt out of citation analytics at any time by declining cookies or using your browser\'s Do Not Track setting. Site administrators can also disable analytics entirely from the plugin settings.', 'cite')
    );
    
    wp_add_privacy_policy_content(
        'Cite',
        wp_kses_post($content)
    );
}
add_action('admin_init', 'wpcp_add_privacy_policy_content');

/**
 * Register personal data exporter for GDPR compliance.
 * Uses identity resolution filter to allow custom mapping of email to analytics data.
 */
function wpcp_register_data_exporter($exporters) {
    $exporters['cite-analytics'] = array(
        'exporter_friendly_name' => __('Cite Analytics Data', 'cite'),
        'callback' => 'wpcp_personal_data_exporter',
    );
    return $exporters;
}
add_filter('wp_privacy_personal_data_exporters', 'wpcp_register_data_exporter');

/**
 * Personal data exporter callback.
 * 
 * @param string $email_address The user's email address.
 * @param int    $page          Page number for batch processing.
 * @return array Export data.
 */
function wpcp_personal_data_exporter($email_address, $page = 1) {
    $export_items = array();
    
    /**
     * Filter to resolve user identity from email to analytics identifier.
     * 
     * By default, Cite uses session-based tracking and does not store
     * personally identifiable information. Return an array of post_ids
     * or other identifiers if you have custom tracking that links to users.
     * 
     * @param mixed  $identifier    Default null - no personal data stored.
     * @param string $email_address The user's email address.
     * @return mixed Array of identifiers or null if no personal data.
     */
    $user_identifier = apply_filters('wpcp_analytics_identity_resolver', null, $email_address);
    
    if (empty($user_identifier)) {
        // No personal data to export - session-based tracking doesn't store PII
        return array(
            'data' => $export_items,
            'done' => true,
        );
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'cite_analytics';
    $items_per_page = 100;
    $offset = ($page - 1) * $items_per_page;
    
    // If identifier is provided, query for matching data
    if (is_array($user_identifier) && isset($user_identifier['post_ids'])) {
        $post_ids = array_map('intval', $user_identifier['post_ids']);
        $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id IN ($placeholders) LIMIT %d OFFSET %d",
            array_merge($post_ids, array($items_per_page, $offset))
        ));
        
        foreach ($results as $row) {
            $export_items[] = array(
                'group_id' => 'cite-analytics',
                'group_label' => __('Citation Analytics', 'cite'),
                'item_id' => 'cite-analytics-' . $row->id,
                'data' => array(
                    array(
                        'name' => __('Post ID', 'cite'),
                        'value' => $row->post_id,
                    ),
                    array(
                        'name' => __('Citation Style', 'cite'),
                        'value' => $row->citation_style,
                    ),
                    array(
                        'name' => __('Action Type', 'cite'),
                        'value' => $row->action_type,
                    ),
                    array(
                        'name' => __('Timestamp', 'cite'),
                        'value' => $row->timestamp,
                    ),
                ),
            );
        }
        
        $done = count($results) < $items_per_page;
    } else {
        $done = true;
    }
    
    return array(
        'data' => $export_items,
        'done' => $done,
    );
}

/**
 * Register personal data eraser for GDPR compliance.
 * Uses identity resolution filter to allow custom mapping of email to analytics data.
 */
function wpcp_register_data_eraser($erasers) {
    $erasers['cite-analytics'] = array(
        'eraser_friendly_name' => __('Cite Analytics Data', 'cite'),
        'callback' => 'wpcp_personal_data_eraser',
    );
    return $erasers;
}
add_filter('wp_privacy_personal_data_erasers', 'wpcp_register_data_eraser');

/**
 * Personal data eraser callback.
 * 
 * @param string $email_address The user's email address.
 * @param int    $page          Page number for batch processing.
 * @return array Erase result.
 */
function wpcp_personal_data_eraser($email_address, $page = 1) {
    $items_removed = 0;
    $items_retained = 0;
    $messages = array();
    
    /**
     * Filter to resolve user identity from email to analytics identifier.
     * 
     * @param mixed  $identifier    Default null - no personal data stored.
     * @param string $email_address The user's email address.
     * @return mixed Array of identifiers or null if no personal data.
     */
    $user_identifier = apply_filters('wpcp_analytics_identity_resolver', null, $email_address);
    
    if (empty($user_identifier)) {
        // No personal data to erase - session-based tracking doesn't store PII
        $messages[] = __('Cite plugin uses privacy-preserving session-based tracking. No personally identifiable analytics data is stored.', 'cite');
        
        return array(
            'items_removed' => $items_removed,
            'items_retained' => $items_retained,
            'messages' => $messages,
            'done' => true,
        );
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'cite_analytics';
    
    // If identifier is provided, delete matching data
    if (is_array($user_identifier) && isset($user_identifier['post_ids'])) {
        $post_ids = array_map('intval', $user_identifier['post_ids']);
        $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
        
        $items_removed = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE post_id IN ($placeholders)",
            $post_ids
        ));
        
        if ($items_removed > 0) {
            $messages[] = sprintf(
                __('%d citation analytics records were removed.', 'cite'),
                $items_removed
            );
        }
    }
    
    return array(
        'items_removed' => $items_removed,
        'items_retained' => $items_retained,
        'messages' => $messages,
        'done' => true,
    );
}

// Create database table for analytics on plugin activation
register_activation_hook(__FILE__, 'wpcp_create_analytics_table');

function wpcp_create_analytics_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cite_analytics';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        citation_style varchar(50) NOT NULL,
        action_type varchar(20) NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY citation_style (citation_style),
        KEY action_type (action_type)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Default settings
$wpcp_default = apply_filters('wpcp_default_setting', array(
    'auto_display' => 'no',
    'display_position' => 'bottom',
    'show_toggle' => 'yes',
    'enable_analytics' => 'no',
    'require_consent_for_analytics' => 'yes',
    'analytics_cooldown_mode' => 'session',
    'analytics_retention_days' => '90',
    'post_types' => array('post'),
    'excluded_posts' => '',
    'show_google_scholar' => 'yes',
    'show_jsonld' => 'yes',
    'show_og_academic' => 'yes',
    'et_al_threshold' => '3',
    'format_display_mode' => 'dropdown',
    
    // NEW SETTING (v2.7.0) - Export formats enabled by default
    'enabled_export_formats' => array(
        'bibtex', 'ris', 'endnote', 'csl-json', 'cff', 'dublin-core'
    ),
    
    // NEW SETTING (v2.4.0) - All 20 formats enabled by default
    'enabled_formats' => array(
        'apa', 'mla', 'ieee', 'harvard', 'chicago', 'vancouver',
        'ama', 'asa', 'turabian', 'wikipedia',
        'acs', 'aip', 'nlm', 'aaa', 'apsa', 'oscola',
        'nature', 'acm', 'bluebook', 'iso690'
    )
));

// Pulling the default settings from DB + Fallback
$wpcp_setting = wp_parse_args(get_option('wpcp_setting'), $wpcp_default);

/**
 * Handle analytics purge action via admin_init hook
 */
function wpcp_handle_analytics_purge() {
    if (!isset($_POST['wpcp_purge_analytics'])) {
        return;
    }
    
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Verify nonce for security
    if (!isset($_POST['wpcp_purge_nonce']) || !wp_verify_nonce($_POST['wpcp_purge_nonce'], 'wpcp_purge_analytics_action')) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'cite_analytics';
    
    // Check if table exists before attempting to delete
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    
    if ($table_exists) {
        $wpdb->query("DELETE FROM $table_name");
    }
    
    // Redirect with success message
    wp_safe_redirect(add_query_arg(array('page' => 'wp-cite', 'wpcp_purged' => '1'), admin_url('admin.php')));
    exit;
}
add_action('admin_init', 'wpcp_handle_analytics_purge');

// Register settings
add_action('admin_init', 'wpcp_register_setting');

function wpcp_register_setting() {
    register_setting('wpcp_setting', 'wpcp_setting', 'wpcp_sanitize_settings');
}

// Sanitize settings with enhanced validation
function wpcp_sanitize_settings($input) {
    $sanitized = array();
    
    $sanitized['auto_display'] = (isset($input['auto_display']) && in_array($input['auto_display'], array('yes', 'no'), true)) ? $input['auto_display'] : 'no';
    $sanitized['display_position'] = (isset($input['display_position']) && in_array($input['display_position'], array('top', 'bottom'), true)) ? $input['display_position'] : 'bottom';
    $sanitized['show_toggle'] = (isset($input['show_toggle']) && in_array($input['show_toggle'], array('yes', 'no'), true)) ? $input['show_toggle'] : 'yes';
    $sanitized['enable_analytics'] = (isset($input['enable_analytics']) && in_array($input['enable_analytics'], array('yes', 'no'), true)) ? $input['enable_analytics'] : 'no';
    $sanitized['require_consent_for_analytics'] = (isset($input['require_consent_for_analytics']) && in_array($input['require_consent_for_analytics'], array('yes', 'no'), true)) ? $input['require_consent_for_analytics'] : 'yes';
    $sanitized['analytics_cooldown_mode'] = (isset($input['analytics_cooldown_mode']) && in_array($input['analytics_cooldown_mode'], array('session', 'ip_hash', 'none'), true)) ? $input['analytics_cooldown_mode'] : 'session';
    $sanitized['analytics_retention_days'] = (isset($input['analytics_retention_days']) && in_array($input['analytics_retention_days'], array('0', '30', '90', '180', '365'), true)) ? $input['analytics_retention_days'] : '90';
    $sanitized['show_google_scholar'] = (isset($input['show_google_scholar']) && in_array($input['show_google_scholar'], array('yes', 'no'), true)) ? $input['show_google_scholar'] : 'yes';
    $sanitized['show_jsonld'] = (isset($input['show_jsonld']) && in_array($input['show_jsonld'], array('yes', 'no'), true)) ? $input['show_jsonld'] : 'yes';
    $sanitized['show_og_academic'] = (isset($input['show_og_academic']) && in_array($input['show_og_academic'], array('yes', 'no'), true)) ? $input['show_og_academic'] : 'yes';
    
    if (isset($input['post_types']) && is_array($input['post_types'])) {
        $valid_post_types = get_post_types(array('public' => true));
        $sanitized['post_types'] = array_intersect($input['post_types'], $valid_post_types);
    } else {
        $sanitized['post_types'] = array('post');
    }
    
    if (isset($input['excluded_posts']) && !empty($input['excluded_posts'])) {
        $excluded_ids = array_map('trim', explode(',', $input['excluded_posts']));
        $excluded_ids = array_map('absint', $excluded_ids);
        $excluded_ids = array_filter($excluded_ids);
        $sanitized['excluded_posts'] = implode(',', $excluded_ids);
    } else {
        $sanitized['excluded_posts'] = '';
    }
    
    $threshold = isset($input['et_al_threshold']) ? absint($input['et_al_threshold']) : 3;
    $sanitized['et_al_threshold'] = max(1, min(999, $threshold));
    
    $sanitized['format_display_mode'] = (isset($input['format_display_mode']) && in_array($input['format_display_mode'], array('dropdown', 'tabs', 'buttons'), true)) ? $input['format_display_mode'] : 'dropdown';
    
    // NEW (v2.7.0): Validate enabled_export_formats
    $all_export_formats = array('bibtex', 'ris', 'endnote', 'csl-json', 'cff', 'dublin-core');
    if (isset($input['enabled_export_formats']) && is_array($input['enabled_export_formats'])) {
        $sanitized['enabled_export_formats'] = array();
        foreach ($input['enabled_export_formats'] as $ef) {
            $ef = sanitize_text_field($ef);
            if (in_array($ef, $all_export_formats, true)) {
                $sanitized['enabled_export_formats'][] = $ef;
            }
        }
        if (empty($sanitized['enabled_export_formats'])) {
            add_settings_error(
                'wpcp_messages',
                'wpcp_export_format_error',
                __('At least one export format must be enabled. BibTeX has been enabled by default.', 'cite'),
                'error'
            );
            $sanitized['enabled_export_formats'] = array('bibtex');
        }
    } else {
        $sanitized['enabled_export_formats'] = $all_export_formats;
    }
    
    // NEW (v2.4.0): Validate enabled_formats
    if (isset($input['enabled_formats']) && is_array($input['enabled_formats'])) {
        $all_formats = array_keys(get_citation_styles());
        $sanitized['enabled_formats'] = array();
        
        foreach ($input['enabled_formats'] as $format) {
            $format = sanitize_text_field($format);
            if (in_array($format, $all_formats, true)) {
                $sanitized['enabled_formats'][] = $format;
            }
        }
        
        // Ensure at least one format is enabled
        if (empty($sanitized['enabled_formats'])) {
            add_settings_error(
                'wpcp_messages',
                'wpcp_format_error',
                __('At least one citation format must be enabled. APA format has been enabled by default.', 'cite'),
                'error'
            );
            $sanitized['enabled_formats'] = array('apa');
        }
    } else {
        // If no formats submitted, enable all by default
        $sanitized['enabled_formats'] = array_keys(get_citation_styles());
    }
    
    return $sanitized;
}

// Adding settings page in wp menu
add_action('admin_menu', 'wpcp_setting_menu');

function wpcp_setting_menu() {
    // Minimalist citation/quote icon as SVG data URI
    $icon_svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#a7aaad"><path d="M8.5 5.5C8.5 4.12 7.38 3 6 3S3.5 4.12 3.5 5.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5zm8 0C16.5 4.12 15.38 3 14 3s-2.5 1.12-2.5 2.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5z"/></svg>');
    
    add_menu_page(
        __('Cite Settings', 'cite'),
        __('Cite', 'cite'),
        'manage_options',
        'wp-cite',
        'wpcp_setting_page',
        $icon_svg,
        55
    );
    
    add_submenu_page(
        'wp-cite',
        __('Analytics', 'cite'),
        __('Analytics', 'cite'),
        'manage_options',
        'wp-cite-analytics',
        'wpcp_analytics_page'
    );
}

// Display admin page
function wpcp_setting_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'cite'));
    }
    
    if (isset($_GET['settings-updated'])) {
        add_settings_error('wpcp_messages', 'wpcp_message', __('Settings Saved', 'cite'), 'updated');
    }
    
    if (isset($_GET['wpcp_purged'])) {
        add_settings_error('wpcp_messages', 'wpcp_purged', __('All analytics data has been purged.', 'cite'), 'updated');
    }
    
    settings_errors('wpcp_messages');
    
    ?>
    <div class="wrap">
        <div class="wpcp-page-header">
            <div class="wpcp-page-header-content">
                <h1 class="wpcp-page-title">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    <?php echo esc_html(get_admin_page_title()); ?>
                </h1>
                <span class="wpcp-version-badge"><?php echo 'v' . esc_html(WPCP_VERSION); ?></span>
            </div>
            <p class="wpcp-page-description"><?php esc_html_e('Professional academic citation management with multiple formats and export options', 'cite'); ?></p>
        </div>
        
        <div class="wpcp-admin-container">
            <form method="post" action="options.php">
                <?php
                settings_fields('wpcp_setting');
                wpcp_admin_form();
                submit_button(__('Save Changes', 'cite'));
                ?>
            </form>
        </div>
    </div>
    <?php
}

// Admin form display function
function wpcp_admin_form() {
    global $wpcp_setting;
    $post_types = get_post_types(array('public' => true), 'objects');
    ?>
    <div class="wpcp-admin">
        <div class="wpcp-tabs">
            <button type="button" class="wpcp-tab-button active" data-tab="general">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v10M1 12h6m6 0h10"/></svg>
                <?php esc_html_e('General', 'cite'); ?>
            </button>
            <button type="button" class="wpcp-tab-button" data-tab="formats">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <?php esc_html_e('Formats', 'cite'); ?>
            </button>
            <button type="button" class="wpcp-tab-button" data-tab="display">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                <?php esc_html_e('Display', 'cite'); ?>
            </button>
            <button type="button" class="wpcp-tab-button" data-tab="metadata">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6m-4 5H8m8 4H8m2-8H8"/></svg>
                <?php esc_html_e('Metadata & SEO', 'cite'); ?>
            </button>
            <button type="button" class="wpcp-tab-button" data-tab="preview">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <?php esc_html_e('Preview', 'cite'); ?>
            </button>
        </div>
        
        <!-- General Settings Tab -->
        <div class="wpcp-tab-content active" id="general-tab">
            <div class="wpcp-section-header">
                <h2><?php esc_html_e('Citation Formats', 'cite'); ?></h2>
                <p><?php esc_html_e('20 professional citation formats available. Users can switch formats using the dropdown.', 'cite'); ?></p>
            </div>
            
            <div class="wpcp-formats-grid">
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">📚</div>
                    <div class="wpcp-format-info">
                        <h4>APA</h4>
                        <span>American Psychological Association</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">📖</div>
                    <div class="wpcp-format-info">
                        <h4>MLA</h4>
                        <span>Modern Language Association</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">⚡</div>
                    <div class="wpcp-format-info">
                        <h4>IEEE</h4>
                        <span>Engineering & Electronics</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">🎓</div>
                    <div class="wpcp-format-info">
                        <h4>Harvard</h4>
                        <span>Harvard Referencing</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">📜</div>
                    <div class="wpcp-format-info">
                        <h4>Chicago</h4>
                        <span>Chicago Manual of Style</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">🏥</div>
                    <div class="wpcp-format-info">
                        <h4>Vancouver</h4>
                        <span>Medical & Health Sciences</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">⚕️</div>
                    <div class="wpcp-format-info">
                        <h4>AMA</h4>
                        <span>American Medical Association</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">👥</div>
                    <div class="wpcp-format-info">
                        <h4>ASA</h4>
                        <span>American Sociological Association</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">✍️</div>
                    <div class="wpcp-format-info">
                        <h4>Turabian</h4>
                        <span>Turabian Style</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">🌐</div>
                    <div class="wpcp-format-info">
                        <h4>Wikipedia</h4>
                        <span>{{cite web}} Template</span>
                    </div>
                </div>
                <!-- NEW FORMAT CARDS (v2.4.0) -->
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">🧪</div>
                    <div class="wpcp-format-info">
                        <h4>ACS</h4>
                        <span>American Chemical Society</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">⚛️</div>
                    <div class="wpcp-format-info">
                        <h4>AIP</h4>
                        <span>American Institute of Physics</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">🏥</div>
                    <div class="wpcp-format-info">
                        <h4>NLM</h4>
                        <span>National Library of Medicine</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">🌍</div>
                    <div class="wpcp-format-info">
                        <h4>AAA</h4>
                        <span>American Anthropological Assoc.</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">🗳️</div>
                    <div class="wpcp-format-info">
                        <h4>APSA</h4>
                        <span>American Political Science Assoc.</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">⚖️</div>
                    <div class="wpcp-format-info">
                        <h4>OSCOLA</h4>
                        <span>Oxford Legal Citations</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">🔬</div>
                    <div class="wpcp-format-info">
                        <h4>Nature</h4>
                        <span>Nature Journal Style</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">💻</div>
                    <div class="wpcp-format-info">
                        <h4>ACM</h4>
                        <span>Association for Computing Machinery</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">⚖️</div>
                    <div class="wpcp-format-info">
                        <h4>Bluebook</h4>
                        <span>US Legal Citation</span>
                    </div>
                </div>
                <div class="wpcp-format-card">
                    <div class="wpcp-format-icon">🌐</div>
                    <div class="wpcp-format-info">
                        <h4>ISO 690</h4>
                        <span>International Standard</span>
                    </div>
                </div>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3><?php esc_html_e('Multiple Authors', 'cite'); ?></h3>
                </div>
                <div class="wpcp-card-body">
                    <label class="wpcp-inline-label">
                        <?php esc_html_e('Show "et al." after', 'cite'); ?>
                        <input type="number" name="wpcp_setting[et_al_threshold]" value="<?php echo esc_attr($wpcp_setting['et_al_threshold']); ?>" min="1" max="999" class="wpcp-input-small" />
                        <?php esc_html_e('authors', 'cite'); ?>
                    </label>
                    <p class="wpcp-help-text"><?php esc_html_e('For Co-Authors Plus compatibility. Enter 999 to always show all authors.', 'cite'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Formats Tab (NEW in v2.4.0) -->
        <div class="wpcp-tab-content" id="formats-tab">
            <div class="wpcp-section-header">
                <h2><?php esc_html_e('Available Citation Formats', 'cite'); ?></h2>
                <p><?php esc_html_e('Select which citation formats should be available to users. Disabled formats will not appear in the citation dropdown. At least one format must remain enabled.', 'cite'); ?></p>
            </div>
            
            <?php
            $format_metadata = wpcp_get_format_metadata();
            $enabled_formats = wpcp_get_enabled_formats();
            
            // Group formats by category
            $categories = array(
                'general' => array('title' => __('General Purpose', 'cite'), 'formats' => array()),
                'sciences' => array('title' => __('Sciences', 'cite'), 'formats' => array()),
                'medical' => array('title' => __('Medical & Health', 'cite'), 'formats' => array()),
                'social-sciences' => array('title' => __('Social Sciences', 'cite'), 'formats' => array()),
                'engineering' => array('title' => __('Engineering & Technology', 'cite'), 'formats' => array()),
                'humanities' => array('title' => __('Humanities', 'cite'), 'formats' => array()),
                'legal' => array('title' => __('Legal', 'cite'), 'formats' => array()),
                'web' => array('title' => __('Web & Digital', 'cite'), 'formats' => array())
            );
            
            // Organize formats into categories
            foreach ($format_metadata as $format_id => $metadata) {
                $category = $metadata['category'];
                if (isset($categories[$category])) {
                    $categories[$category]['formats'][$format_id] = $metadata;
                }
            }
            ?>
            
            <div class="wpcp-formats-selection">
                <div class="wpcp-bulk-actions">
                    <button type="button" class="wpcp-btn wpcp-btn-secondary wpcp-select-all">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                        </svg>
                        <?php esc_html_e('Select All', 'cite'); ?>
                    </button>
                    <button type="button" class="wpcp-btn wpcp-btn-secondary wpcp-select-none">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                        </svg>
                        <?php esc_html_e('Clear All', 'cite'); ?>
                    </button>
                </div>
                
                <?php foreach ($categories as $category_id => $category_data): ?>
                    <?php if (!empty($category_data['formats'])): ?>
                        <div class="wpcp-card wpcp-format-category">
                            <div class="wpcp-card-header">
                                <h3><?php echo esc_html($category_data['title']); ?></h3>
                                <span class="wpcp-format-count">
                                    <?php 
                                    $category_enabled = count(array_intersect(array_keys($category_data['formats']), $enabled_formats));
                                    $category_total = count($category_data['formats']);
                                    printf(
                                        esc_html__('%d of %d enabled', 'cite'),
                                        $category_enabled,
                                        $category_total
                                    );
                                    ?>
                                </span>
                            </div>
                            <div class="wpcp-card-body">
                                <div class="wpcp-format-checkboxes">
                                    <?php foreach ($category_data['formats'] as $format_id => $metadata): ?>
                                        <label class="wpcp-format-checkbox">
                                            <input 
                                                type="checkbox" 
                                                name="wpcp_setting[enabled_formats][]" 
                                                value="<?php echo esc_attr($format_id); ?>"
                                                <?php checked(in_array($format_id, $enabled_formats)); ?>
                                                class="wpcp-format-toggle"
                                            />
                                            <span class="wpcp-format-checkbox-content">
                                                <span class="wpcp-format-icon"><?php echo esc_html($metadata['icon']); ?></span>
                                                <span class="wpcp-format-info">
                                                    <strong><?php echo esc_html($metadata['name']); ?></strong>
                                                    <span class="wpcp-format-description"><?php echo esc_html($metadata['full_name']); ?></span>
                                                </span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="wpcp-notice wpcp-notice-info" style="margin-top: 24px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <div>
                    <strong><?php esc_html_e('Note:', 'cite'); ?></strong>
                    <?php esc_html_e('Disabling a format will hide it from the citation dropdown on your site. Users will only see enabled formats. At least one format must remain enabled.', 'cite'); ?>
                </div>
            </div>
        </div>
        
        <!-- Display Options Tab -->
        <div class="wpcp-tab-content" id="display-tab">
            <div class="wpcp-section-header">
                <h2><?php esc_html_e('Display Settings', 'cite'); ?></h2>
                <p><?php esc_html_e('Control how and where the citation box appears on your site.', 'cite'); ?></p>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3><?php esc_html_e('Auto Display', 'cite'); ?></h3>
                </div>
                <div class="wpcp-card-body">
                    <label class="wpcp-toggle">
                        <input type="checkbox" name="wpcp_setting[auto_display]" value="yes" <?php checked($wpcp_setting['auto_display'], 'yes'); ?> />
                        <span class="wpcp-toggle-slider"></span>
                        <span class="wpcp-toggle-label"><?php esc_html_e('Automatically display citation box', 'cite'); ?></span>
                    </label>
                    <p class="wpcp-help-text"><?php esc_html_e('When enabled, the citation box appears automatically without using a shortcode.', 'cite'); ?></p>
                </div>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3><?php esc_html_e('Position', 'cite'); ?></h3>
                </div>
                <div class="wpcp-card-body">
                    <div class="wpcp-radio-group">
                        <label class="wpcp-radio-card <?php echo $wpcp_setting['display_position'] === 'top' ? 'selected' : ''; ?>">
                            <input type="radio" name="wpcp_setting[display_position]" value="top" <?php checked($wpcp_setting['display_position'], 'top'); ?> />
                            <span class="wpcp-radio-icon">↑</span>
                            <span class="wpcp-radio-text"><?php esc_html_e('Top of content', 'cite'); ?></span>
                        </label>
                        <label class="wpcp-radio-card <?php echo $wpcp_setting['display_position'] === 'bottom' ? 'selected' : ''; ?>">
                            <input type="radio" name="wpcp_setting[display_position]" value="bottom" <?php checked($wpcp_setting['display_position'], 'bottom'); ?> />
                            <span class="wpcp-radio-icon">↓</span>
                            <span class="wpcp-radio-text"><?php esc_html_e('Bottom of content', 'cite'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3><?php esc_html_e('Post Types', 'cite'); ?></h3>
                </div>
                <div class="wpcp-card-body">
                    <div class="wpcp-checkbox-grid">
                        <?php foreach ($post_types as $post_type): ?>
                        <label class="wpcp-checkbox-card">
                            <input type="checkbox" name="wpcp_setting[post_types][]" value="<?php echo esc_attr($post_type->name); ?>" 
                                <?php checked(in_array($post_type->name, (array)$wpcp_setting['post_types'])); ?> />
                            <span class="wpcp-checkbox-indicator"></span>
                            <span class="wpcp-checkbox-text"><?php echo esc_html($post_type->label); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3><?php esc_html_e('Exclude Posts', 'cite'); ?></h3>
                </div>
                <div class="wpcp-card-body">
                    <textarea name="wpcp_setting[excluded_posts]" rows="2" class="wpcp-textarea" placeholder="1, 5, 10"><?php echo esc_textarea($wpcp_setting['excluded_posts']); ?></textarea>
                    <p class="wpcp-help-text"><?php esc_html_e('Enter post IDs to exclude, separated by commas.', 'cite'); ?></p>
                </div>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3><?php esc_html_e('Toggle Button', 'cite'); ?></h3>
                </div>
                <div class="wpcp-card-body">
                    <label class="wpcp-toggle">
                        <input type="checkbox" name="wpcp_setting[show_toggle]" value="yes" <?php checked($wpcp_setting['show_toggle'], 'yes'); ?> />
                        <span class="wpcp-toggle-slider"></span>
                        <span class="wpcp-toggle-label"><?php esc_html_e('Show expand/collapse toggle', 'cite'); ?></span>
                    </label>
                    <p class="wpcp-help-text"><?php esc_html_e('Allow users to hide/show the citation box.', 'cite'); ?></p>
                </div>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <?php esc_html_e('Format Selector Style', 'cite'); ?>
                    </h3>
                </div>
                <div class="wpcp-card-body">
                    <div class="wpcp-radio-group">
                        <label class="wpcp-radio-card <?php echo $wpcp_setting['format_display_mode'] === 'dropdown' ? 'selected' : ''; ?>">
                            <input type="radio" class="wpcp-display-mode-radio" name="wpcp_setting[format_display_mode]" value="dropdown" <?php checked($wpcp_setting['format_display_mode'], 'dropdown'); ?> />
                            <span class="wpcp-radio-icon">▾</span>
                            <span class="wpcp-radio-text"><?php esc_html_e('Dropdown', 'cite'); ?></span>
                            <span class="wpcp-radio-desc"><?php esc_html_e('Compact menu, best for many formats', 'cite'); ?></span>
                        </label>
                        <label class="wpcp-radio-card <?php echo $wpcp_setting['format_display_mode'] === 'tabs' ? 'selected' : ''; ?>">
                            <input type="radio" class="wpcp-display-mode-radio" name="wpcp_setting[format_display_mode]" value="tabs" <?php checked($wpcp_setting['format_display_mode'], 'tabs'); ?> />
                            <span class="wpcp-radio-icon">☰</span>
                            <span class="wpcp-radio-text"><?php esc_html_e('Tabs', 'cite'); ?></span>
                            <span class="wpcp-radio-desc"><?php esc_html_e('Connected horizontal tabs, best for 3–8 formats', 'cite'); ?></span>
                        </label>
                        <label class="wpcp-radio-card <?php echo $wpcp_setting['format_display_mode'] === 'buttons' ? 'selected' : ''; ?>">
                            <input type="radio" class="wpcp-display-mode-radio" name="wpcp_setting[format_display_mode]" value="buttons" <?php checked($wpcp_setting['format_display_mode'], 'buttons'); ?> />
                            <span class="wpcp-radio-icon">●</span>
                            <span class="wpcp-radio-text"><?php esc_html_e('Pill Buttons', 'cite'); ?></span>
                            <span class="wpcp-radio-desc"><?php esc_html_e('Modern pill-style, best for 3–6 formats', 'cite'); ?></span>
                        </label>
                    </div>
                    <p class="wpcp-help-text"><?php esc_html_e('Choose how citation format options appear on the frontend. Dropdown is recommended when many formats are enabled.', 'cite'); ?></p>
                </div>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <?php esc_html_e('Export Formats', 'cite'); ?>
                    </h3>
                </div>
                <div class="wpcp-card-body">
                    <p class="wpcp-help-text" style="margin-top:0;margin-bottom:16px;"><?php esc_html_e('Select which export formats are available in the citation box dropdown.', 'cite'); ?></p>
                    <?php
                    $export_format_labels = array(
                        'bibtex'      => array('name' => 'BibTeX',      'ext' => '.bib',  'desc' => __('LaTeX / Overleaf', 'cite')),
                        'ris'         => array('name' => 'RIS',         'ext' => '.ris',  'desc' => __('Universal legacy format', 'cite')),
                        'endnote'     => array('name' => 'EndNote',     'ext' => '.enw',  'desc' => __('EndNote desktop', 'cite')),
                        'csl-json'    => array('name' => 'CSL-JSON',    'ext' => '.json', 'desc' => __('Modern reference managers (native)', 'cite')),
                        'cff'         => array('name' => 'CFF',         'ext' => '.cff',  'desc' => __('GitHub CITATION.cff', 'cite')),
                        'dublin-core' => array('name' => 'Dublin Core', 'ext' => '.xml',  'desc' => __('Institutional repositories', 'cite')),
                    );
                    $enabled_exports = isset($wpcp_setting['enabled_export_formats']) ? $wpcp_setting['enabled_export_formats'] : array_keys($export_format_labels);
                    ?>
                    <div class="wpcp-export-format-grid">
                        <?php foreach ($export_format_labels as $ef_id => $ef_data): ?>
                        <label class="wpcp-export-format-item">
                            <input type="checkbox" 
                                name="wpcp_setting[enabled_export_formats][]" 
                                value="<?php echo esc_attr($ef_id); ?>" 
                                class="wpcp-export-format-toggle"
                                <?php checked(in_array($ef_id, $enabled_exports)); ?> />
                            <span class="wpcp-export-format-info">
                                <span class="wpcp-export-format-label">
                                    <span class="wpcp-export-format-name"><?php echo esc_html($ef_data['name']); ?></span>
                                    <span class="wpcp-export-format-ext"><?php echo esc_html($ef_data['ext']); ?></span>
                                </span>
                                <span class="wpcp-export-format-desc"><?php echo esc_html($ef_data['desc']); ?></span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Metadata & SEO Tab -->
        <div class="wpcp-tab-content" id="metadata-tab">
            <div class="wpcp-section-header">
                <h2><?php esc_html_e('Metadata & SEO', 'cite'); ?></h2>
                <p><?php esc_html_e('Improve discoverability and tracking for your academic content.', 'cite'); ?></p>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                        <?php esc_html_e('Google Scholar', 'cite'); ?>
                    </h3>
                </div>
                <div class="wpcp-card-body">
                    <label class="wpcp-toggle">
                        <input type="checkbox" name="wpcp_setting[show_google_scholar]" value="yes" <?php checked($wpcp_setting['show_google_scholar'], 'yes'); ?> />
                        <span class="wpcp-toggle-slider"></span>
                        <span class="wpcp-toggle-label"><?php esc_html_e('Add Google Scholar meta tags', 'cite'); ?></span>
                    </label>
                    
                    <div class="wpcp-feature-box">
                        <h4><?php esc_html_e('Meta tags added:', 'cite'); ?></h4>
                        <div class="wpcp-meta-tags">
                            <code>citation_title</code>
                            <code>citation_author</code>
                            <code>citation_publication_date</code>
                            <code>citation_journal_title</code>
                            <code>citation_fulltext_html_url</code>
                            <code>citation_author_orcid</code>
                        </div>
                        <ul class="wpcp-benefits">
                            <li><?php esc_html_e('Improves discoverability in Google Scholar', 'cite'); ?></li>
                            <li><?php esc_html_e('Helps researchers find and cite your work', 'cite'); ?></li>
                            <li><?php esc_html_e('Invisible to visitors - only for search engines', 'cite'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 14l2 2 4-4"/></svg>
                        <?php esc_html_e('JSON-LD Structured Data', 'cite'); ?>
                    </h3>
                </div>
                <div class="wpcp-card-body">
                    <label class="wpcp-toggle">
                        <input type="checkbox" name="wpcp_setting[show_jsonld]" value="yes" <?php checked($wpcp_setting['show_jsonld'], 'yes'); ?> />
                        <span class="wpcp-toggle-slider"></span>
                        <span class="wpcp-toggle-label"><?php esc_html_e('Output ScholarlyArticle schema in JSON-LD format', 'cite'); ?></span>
                    </label>
                    
                    <div class="wpcp-feature-box">
                        <h4><?php esc_html_e('Schema properties output:', 'cite'); ?></h4>
                        <div class="wpcp-meta-tags">
                            <code>@type: ScholarlyArticle</code>
                            <code>headline</code>
                            <code>author</code>
                            <code>datePublished</code>
                            <code>dateModified</code>
                            <code>publisher</code>
                            <code>mainEntityOfPage</code>
                        </div>
                        <ul class="wpcp-benefits">
                            <li><?php esc_html_e('Google strongly prefers JSON-LD for rich results eligibility', 'cite'); ?></li>
                            <li><?php esc_html_e('Enables enhanced search snippets for academic content', 'cite'); ?></li>
                            <li><?php esc_html_e('Complementary to Google Scholar meta tags', 'cite'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        <?php esc_html_e('Open Graph Academic Tags', 'cite'); ?>
                    </h3>
                </div>
                <div class="wpcp-card-body">
                    <label class="wpcp-toggle">
                        <input type="checkbox" name="wpcp_setting[show_og_academic]" value="yes" <?php checked($wpcp_setting['show_og_academic'], 'yes'); ?> />
                        <span class="wpcp-toggle-slider"></span>
                        <span class="wpcp-toggle-label"><?php esc_html_e('Add Open Graph article meta tags', 'cite'); ?></span>
                    </label>
                    
                    <div class="wpcp-feature-box">
                        <h4><?php esc_html_e('OG tags added:', 'cite'); ?></h4>
                        <div class="wpcp-meta-tags">
                            <code>og:type</code>
                            <code>article:published_time</code>
                            <code>article:modified_time</code>
                            <code>article:author</code>
                            <code>article:section</code>
                        </div>
                        <ul class="wpcp-benefits">
                            <li><?php esc_html_e('Enhances link previews on social media platforms', 'cite'); ?></li>
                            <li><?php esc_html_e('Provides publication metadata for content aggregators', 'cite'); ?></li>
                            <li><?php esc_html_e('Compatible with existing SEO plugins (no duplication)', 'cite'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="wpcp-card">
                <div class="wpcp-card-header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                        <?php esc_html_e('Analytics', 'cite'); ?>
                    </h3>
                </div>
                <div class="wpcp-card-body">
                    <label class="wpcp-toggle">
                        <input type="checkbox" name="wpcp_setting[enable_analytics]" value="yes" <?php checked($wpcp_setting['enable_analytics'], 'yes'); ?> />
                        <span class="wpcp-toggle-slider"></span>
                        <span class="wpcp-toggle-label"><?php esc_html_e('Enable citation analytics', 'cite'); ?></span>
                    </label>

                    <label class="wpcp-toggle" style="margin-top: 15px;">
                        <input type="checkbox" name="wpcp_setting[require_consent_for_analytics]" value="yes" <?php checked($wpcp_setting['require_consent_for_analytics'], 'yes'); ?> />
                        <span class="wpcp-toggle-slider"></span>
                        <span class="wpcp-toggle-label"><?php esc_html_e('Require user consent for analytics tracking', 'cite'); ?></span>
                    </label>

                    <div class="wpcp-card" style="margin-top: 20px;">
                        <div class="wpcp-card-header">
                            <h3><?php esc_html_e('Analytics Cooldown Mode', 'cite'); ?></h3>
                        </div>
                        <div class="wpcp-card-body">
                            <div class="wpcp-radio-group">
                                <label class="wpcp-radio-card <?php echo $wpcp_setting['analytics_cooldown_mode'] === 'session' ? 'selected' : ''; ?>">
                                    <input type="radio" name="wpcp_setting[analytics_cooldown_mode]" value="session" <?php checked($wpcp_setting['analytics_cooldown_mode'], 'session'); ?> />
                                    <span class="wpcp-radio-icon">🔑</span>
                                    <span class="wpcp-radio-text"><?php esc_html_e('Session-based', 'cite'); ?></span>
                                </label>
                                <label class="wpcp-radio-card <?php echo $wpcp_setting['analytics_cooldown_mode'] === 'ip_hash' ? 'selected' : ''; ?>">
                                    <input type="radio" name="wpcp_setting[analytics_cooldown_mode]" value="ip_hash" <?php checked($wpcp_setting['analytics_cooldown_mode'], 'ip_hash'); ?> />
                                    <span class="wpcp-radio-icon">🌐</span>
                                    <span class="wpcp-radio-text"><?php esc_html_e('IP Hash (Legacy)', 'cite'); ?></span>
                                </label>
                                <label class="wpcp-radio-card <?php echo $wpcp_setting['analytics_cooldown_mode'] === 'none' ? 'selected' : ''; ?>">
                                    <input type="radio" name="wpcp_setting[analytics_cooldown_mode]" value="none" <?php checked($wpcp_setting['analytics_cooldown_mode'], 'none'); ?> />
                                    <span class="wpcp-radio-icon">⚡</span>
                                    <span class="wpcp-radio-text"><?php esc_html_e('No Cooldown', 'cite'); ?></span>
                                </label>
                            </div>
                            <p class="wpcp-help-text">
                                <?php esc_html_e('Select how to identify users for rate-limiting. Session-based is recommended for GDPR.', 'cite'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="wpcp-card" style="margin-top: 20px;">
                        <div class="wpcp-card-header">
                            <h3>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                <?php esc_html_e('Data Retention', 'cite'); ?>
                            </h3>
                        </div>
                        <div class="wpcp-card-body">
                            <div class="wpcp-inline-label">
                                <span><?php esc_html_e('Delete data older than:', 'cite'); ?></span>
                                <select name="wpcp_setting[analytics_retention_days]" class="wpcp-select" style="min-width: 150px;">
                                    <option value="0" <?php selected($wpcp_setting['analytics_retention_days'], '0'); ?>><?php esc_html_e('None (Keep Forever)', 'cite'); ?></option>
                                    <option value="30" <?php selected($wpcp_setting['analytics_retention_days'], '30'); ?>><?php esc_html_e('30 Days', 'cite'); ?></option>
                                    <option value="90" <?php selected($wpcp_setting['analytics_retention_days'], '90'); ?>><?php esc_html_e('90 Days', 'cite'); ?></option>
                                    <option value="180" <?php selected($wpcp_setting['analytics_retention_days'], '180'); ?>><?php esc_html_e('180 Days', 'cite'); ?></option>
                                    <option value="365" <?php selected($wpcp_setting['analytics_retention_days'], '365'); ?>><?php esc_html_e('365 Days', 'cite'); ?></option>
                                </select>
                            </div>
                            <p class="wpcp-help-text">
                                <?php esc_html_e('Automatically delete analytics data older than this period to comply with storage limitation principles.', 'cite'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="wpcp-feature-box" style="margin-top: 24px;">
                        <h4><?php esc_html_e('What gets tracked:', 'cite'); ?></h4>
                        <ul class="wpcp-benefits">
                            <li><?php esc_html_e('Citation format selections (APA, MLA, Wikipedia, etc.)', 'cite'); ?></li>
                            <li><?php esc_html_e('Copy to clipboard actions', 'cite'); ?></li>
                            <li><?php esc_html_e('Export downloads (BibTeX, RIS, EndNote, CSL-JSON, CFF, Dublin Core)', 'cite'); ?></li>
                            <li><?php esc_html_e('Per-post citation statistics', 'cite'); ?></li>
                        </ul>
                        <?php if ($wpcp_setting['enable_analytics'] === 'yes'): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-cite-analytics')); ?>" class="wpcp-btn wpcp-btn-secondary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                            <?php esc_html_e('View Analytics Dashboard', 'cite'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Preview Tab -->
        <div class="wpcp-tab-content" id="preview-tab">
            <div class="wpcp-section-header">
                <h2><?php esc_html_e('Preview', 'cite'); ?></h2>
                <p><?php esc_html_e('See how the citation box will appear on your site.', 'cite'); ?></p>
            </div>
            
            <div class="wpcp-preview-wrapper">
                <div class="wpcp-preview-header">
                    <span class="wpcp-preview-dot"></span>
                    <span class="wpcp-preview-dot"></span>
                    <span class="wpcp-preview-dot"></span>
                    <span class="wpcp-preview-title"><?php esc_html_e('Live Preview', 'cite'); ?></span>
                </div>
                <div class="wpcp-preview-content">
                    <?php
                    $sample_author = wp_get_current_user()->display_name ?: 'Author Name';
                    $sample_date = date_i18n(get_option('date_format'));
                    $sample_title = __('Sample Article Title', 'cite');
                    $sample_site = get_bloginfo('name');
                    $sample_url = home_url('/sample-article/');
                    ?>
                    <div class="wpcp-citation-box wpcp-preview-box" role="region" aria-label="<?php esc_attr_e('Citation Preview', 'cite'); ?>">
                        <div class="citation-header">
                            <div class="citation-title">
                                <svg class="wpcp-cite-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M8.5 5.5C8.5 4.12 7.38 3 6 3S3.5 4.12 3.5 5.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5zm8 0C16.5 4.12 15.38 3 14 3s-2.5 1.12-2.5 2.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5z"/></svg>
                                <span><?php esc_html_e('Cite This As:', 'cite'); ?></span>
                                <?php
                                $enabled_formats = wpcp_get_enabled_formats();
                                $format_metadata = wpcp_get_format_metadata();
                                $first_format = !empty($enabled_formats) ? $enabled_formats[0] : 'apa';
                                ?>
                                <?php
                                $preview_display_mode = isset($wpcp_setting['format_display_mode']) ? $wpcp_setting['format_display_mode'] : 'dropdown';
                                ?>
                                <!-- Dropdown mode -->
                                <select class="citation-style-select wpcp-preview-select wpcp-preview-selector" data-mode="dropdown" aria-label="<?php esc_attr_e('Select citation style', 'cite'); ?>"<?php echo $preview_display_mode !== 'dropdown' ? ' style="display:none"' : ''; ?>>
                                    <?php foreach ($enabled_formats as $format_id): ?>
                                        <?php if (isset($format_metadata[$format_id])): ?>
                                            <option value="<?php echo esc_attr($format_id); ?>" <?php selected($format_id, $first_format); ?>>
                                                <?php echo esc_html($format_metadata[$format_id]['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <!-- Tabs mode -->
                                <div class="wpcp-style-tabs wpcp-preview-selector" data-mode="tabs" role="tablist"<?php echo $preview_display_mode !== 'tabs' ? ' style="display:none"' : ''; ?>>
                                    <?php foreach ($enabled_formats as $i => $format_id): ?>
                                        <?php if (isset($format_metadata[$format_id])): ?>
                                            <button type="button" class="wpcp-style-tab<?php echo $i === 0 ? ' wpcp-style-tab--active' : ''; ?>" role="tab" data-style="<?php echo esc_attr($format_id); ?>" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"><?php echo esc_html($format_metadata[$format_id]['name']); ?></button>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Buttons mode -->
                                <div class="wpcp-style-buttons wpcp-preview-selector" data-mode="buttons" role="radiogroup"<?php echo $preview_display_mode !== 'buttons' ? ' style="display:none"' : ''; ?>>
                                    <?php foreach ($enabled_formats as $i => $format_id): ?>
                                        <?php if (isset($format_metadata[$format_id])): ?>
                                            <button type="button" class="wpcp-style-btn<?php echo $i === 0 ? ' wpcp-style-btn--active' : ''; ?>" role="radio" data-style="<?php echo esc_attr($format_id); ?>" aria-checked="<?php echo $i === 0 ? 'true' : 'false'; ?>"><?php echo esc_html($format_metadata[$format_id]['name']); ?></button>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <button class="copy-button" type="button" title="<?php esc_attr_e('Copy citation', 'cite'); ?>" aria-label="<?php esc_attr_e('Copy citation to clipboard', 'cite'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M13.5 2H6.5C5.67 2 5 2.67 5 3.5V10.5C5 11.33 5.67 12 6.5 12H13.5C14.33 12 15 11.33 15 10.5V3.5C15 2.67 14.33 2 13.5 2ZM13.5 10.5H6.5V3.5H13.5V10.5ZM2.5 6H4V7.5H2.5V12.5H9.5V11H11V12.5C11 13.33 10.33 14 9.5 14H2.5C1.67 14 1 13.33 1 12.5V7.5C1 6.67 1.67 6 2.5 6Z" fill="currentColor"/>
                                    </svg>
                                </button>
                                <div class="export-menu">
                                    <button class="export-button" type="button" title="<?php esc_attr_e('Export citation', 'cite'); ?>" aria-label="<?php esc_attr_e('Export citation', 'cite'); ?>" aria-expanded="false" aria-haspopup="true">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M8 12L3 7L4.4 5.6L7 8.2V0H9V8.2L11.6 5.6L13 7L8 12ZM2 16C1.45 16 0.979 15.804 0.587 15.412C0.195 15.02 -0.000667 14.5493 7.78539e-07 14V11H2V14H14V11H16V14C16 14.55 15.804 15.021 15.412 15.413C15.02 15.805 14.5493 16.0007 14 16H2Z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <div class="export-dropdown" role="menu" aria-label="<?php esc_attr_e('Export options', 'cite'); ?>">
                                        <?php
                                        $preview_export_labels = array(
                                            'bibtex'      => __('Export as BibTeX', 'cite'),
                                            'ris'         => __('Export as RIS', 'cite'),
                                            'endnote'     => __('Export as EndNote', 'cite'),
                                            'csl-json'    => __('Export as CSL-JSON', 'cite'),
                                            'cff'         => __('Export as CFF', 'cite'),
                                            'dublin-core' => __('Export as Dublin Core', 'cite'),
                                        );
                                        $preview_enabled_exports = isset($wpcp_setting['enabled_export_formats']) ? $wpcp_setting['enabled_export_formats'] : array_keys($preview_export_labels);
                                        foreach ($preview_export_labels as $pef_id => $pef_label):
                                            $pef_visible = in_array($pef_id, $preview_enabled_exports);
                                        ?>
                                        <button class="export-option wpcp-preview-export-option" type="button" data-export-format="<?php echo esc_attr($pef_id); ?>" role="menuitem"<?php echo !$pef_visible ? ' style="display:none"' : ''; ?>><?php echo esc_html($pef_label); ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button class="toggle-button" type="button" aria-label="<?php esc_attr_e('Toggle citation visibility', 'cite'); ?>" aria-expanded="true">
                                    <svg class="toggle-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M8 11L3 6L4.4 4.6L8 8.2L11.6 4.6L13 6L8 11Z" fill="currentColor"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="citation-content">
                            <div class="citation-output wpcp-preview-citation">
                                <?php echo esc_html($sample_author); ?>. (<?php echo esc_html($sample_date); ?>). <?php echo esc_html($sample_title); ?>. <em><?php echo esc_html($sample_site); ?></em>. <?php echo esc_url($sample_url); ?>
                            </div>
                        </div>
                        <div class="copy-notification" role="status" aria-live="polite"><?php esc_html_e('Citation copied!', 'cite'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="wpcp-shortcode-info">
                <h4><?php esc_html_e('Shortcode Usage', 'cite'); ?></h4>
                <div class="wpcp-code-block">
                    <code>[cite]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Basic usage', 'cite'); ?></span>
                </div>
                <div class="wpcp-code-block">
                    <code>[cite style="wikipedia"]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Default to Wikipedia format', 'cite'); ?></span>
                </div>
                <div class="wpcp-code-block">
                    <code>[cite mode="inline"]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Inline parenthetical citation', 'cite'); ?></span>
                </div>
                <div class="wpcp-code-block">
                    <code>[cite mode="inline" page="42"]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Inline with page number', 'cite'); ?></span>
                </div>
                <div class="wpcp-code-block">
                    <code>[cite formats="apa,mla,chicago"]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Show only specific formats', 'cite'); ?></span>
                </div>
                <div class="wpcp-code-block">
                    <code>[cite exclude_formats="wikipedia"]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Hide specific formats', 'cite'); ?></span>
                </div>
                <div class="wpcp-code-block">
                    <code>[cite show_export="false"]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Hide export buttons', 'cite'); ?></span>
                </div>
                <div class="wpcp-code-block">
                    <code>[cite link="false"]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Disable clickable links in inline citations', 'cite'); ?></span>
                </div>
                <div class="wpcp-code-block">
                    <code>[cite_bibliography]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Numbered reference list', 'cite'); ?></span>
                </div>
                <div class="wpcp-code-block">
                    <code>[cite_bibliography heading="Sources" numbered="false"]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Custom heading, unnumbered', 'cite'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Localize preview data for JavaScript
    $preview_data = array(
        'author' => $sample_author,
        'date' => $sample_date,
        'dateDMY' => wpcp_format_date_dmy(),
        'title' => $sample_title,
        'site' => $sample_site,
        'url' => $sample_url,
        'language' => wpcp_get_language_code()
    );
    wp_localize_script('wpcp-admin', 'wpcpPreviewData', $preview_data);
    ?>
    <?php
}

// Analytics page
function wpcp_analytics_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'cite'));
    }
    
    global $wpdb, $wpcp_setting;
    $table_name = $wpdb->prefix . 'cite_analytics';
    
    // Check if analytics is disabled
    $analytics_disabled = !isset($wpcp_setting['enable_analytics']) || $wpcp_setting['enable_analytics'] !== 'yes';
    
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
        echo '<div class="wrap wpcp-admin-container"><div class="wpcp-admin"><div class="wpcp-tab-content active">';
        echo '<div class="wpcp-section-header"><h2>' . esc_html__('Citation Analytics', 'cite') . '</h2>';
        echo '<p>' . esc_html__('Analytics table not found. Please deactivate and reactivate the plugin.', 'cite') . '</p></div>';
        echo '</div></div></div>';
        return;
    }
    
    $style_stats = $wpdb->get_results($wpdb->prepare(
        "SELECT citation_style, COUNT(*) as count FROM {$table_name} WHERE action_type IN (%s, %s) GROUP BY citation_style ORDER BY count DESC",
        'view', 'copy'
    ));
    
    $post_stats = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, COUNT(*) as count FROM {$table_name} WHERE action_type IN (%s, %s) GROUP BY post_id ORDER BY count DESC LIMIT 10",
        'view', 'copy'
    ));
    
    $export_stats = $wpdb->get_results($wpdb->prepare(
        "SELECT citation_style as format, COUNT(*) as count FROM {$table_name} WHERE action_type = %s GROUP BY citation_style ORDER BY count DESC",
        'export'
    ));
    
    // Calculate totals
    $total_views = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE action_type = %s", 'view'));
    $total_copies = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE action_type = %s", 'copy'));
    $total_exports = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE action_type = %s", 'export'));
    
    ?>
    <div class="wrap">
        <div class="wpcp-page-header">
            <div class="wpcp-page-header-content">
                <h1 class="wpcp-page-title">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    <?php esc_html_e('Citation Analytics', 'cite'); ?>
                </h1>
                <span class="wpcp-version-badge"><?php echo 'v' . esc_html(WPCP_VERSION); ?></span>
            </div>
            <p class="wpcp-page-description"><?php esc_html_e('Track how visitors interact with your citations and monitor engagement metrics', 'cite'); ?></p>
        </div>
        
        <div class="wpcp-admin-container">
            <div class="wpcp-admin">
                <div class="wpcp-tab-content active" style="display: block;">
                
                <?php if ($analytics_disabled): ?>
                <div class="wpcp-analytics-notice">
                    <div class="wpcp-analytics-notice-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="16" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                    </div>
                    <div class="wpcp-analytics-notice-content">
                        <h4><?php esc_html_e('Analytics Tracking is Currently Disabled', 'cite'); ?></h4>
                        <p>
                            <?php
                            printf(
                                /* translators: %s: URL to settings page */
                                esc_html__('Citation analytics tracking is disabled. Enable it in %s to start collecting data.', 'cite'),
                                '<a href="' . esc_url(admin_url('admin.php?page=wp-cite')) . '">' . esc_html__('Settings', 'cite') . '</a>'
                            );
                            ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Stats Overview -->
                <div class="wpcp-stats-overview">
                    <div class="wpcp-stat-card">
                        <div class="wpcp-stat-icon wpcp-stat-views">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </div>
                        <div class="wpcp-stat-info">
                            <span class="wpcp-stat-number"><?php echo number_format(intval($total_views)); ?></span>
                            <span class="wpcp-stat-label"><?php esc_html_e('Total Views', 'cite'); ?></span>
                        </div>
                    </div>
                    <div class="wpcp-stat-card">
                        <div class="wpcp-stat-icon wpcp-stat-copies">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </div>
                        <div class="wpcp-stat-info">
                            <span class="wpcp-stat-number"><?php echo number_format(intval($total_copies)); ?></span>
                            <span class="wpcp-stat-label"><?php esc_html_e('Citations Copied', 'cite'); ?></span>
                        </div>
                    </div>
                    <div class="wpcp-stat-card">
                        <div class="wpcp-stat-icon wpcp-stat-exports">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </div>
                        <div class="wpcp-stat-info">
                            <span class="wpcp-stat-number"><?php echo number_format(intval($total_exports)); ?></span>
                            <span class="wpcp-stat-label"><?php esc_html_e('File Exports', 'cite'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Grid -->
                <div class="wpcp-analytics-grid">
                    <div class="wpcp-card">
                        <div class="wpcp-card-header">
                            <h3>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                                <?php esc_html_e('Popular Citation Styles', 'cite'); ?>
                            </h3>
                        </div>
                        <div class="wpcp-card-body wpcp-card-body-table">
                            <?php if (!empty($style_stats)): ?>
                            <table class="wpcp-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Style', 'cite'); ?></th>
                                        <th><?php esc_html_e('Usage', 'cite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($style_stats as $stat): ?>
                                    <tr>
                                        <td>
                                            <span class="wpcp-style-badge"><?php echo esc_html(strtoupper($stat->citation_style)); ?></span>
                                        </td>
                                        <td>
                                            <span class="wpcp-count"><?php echo number_format(absint($stat->count)); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="wpcp-empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                                <p><?php esc_html_e('No data available yet.', 'cite'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="wpcp-card">
                        <div class="wpcp-card-header">
                            <h3>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                <?php esc_html_e('Most Cited Posts', 'cite'); ?>
                            </h3>
                        </div>
                        <div class="wpcp-card-body wpcp-card-body-table">
                            <?php if (!empty($post_stats)): ?>
                            <table class="wpcp-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Post', 'cite'); ?></th>
                                        <th><?php esc_html_e('Citations', 'cite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($post_stats as $stat): ?>
                                        <?php $post_title = get_the_title($stat->post_id); ?>
                                        <?php if ($post_title): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo esc_url(get_edit_post_link($stat->post_id)); ?>" class="wpcp-post-link"><?php echo esc_html(wp_trim_words($post_title, 8)); ?></a>
                                            </td>
                                            <td>
                                                <span class="wpcp-count"><?php echo number_format(absint($stat->count)); ?></span>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="wpcp-empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                <p><?php esc_html_e('No data available yet.', 'cite'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="wpcp-card">
                        <div class="wpcp-card-header">
                            <h3>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <?php esc_html_e('Export Statistics', 'cite'); ?>
                            </h3>
                        </div>
                        <div class="wpcp-card-body wpcp-card-body-table">
                            <?php if (!empty($export_stats)): ?>
                            <table class="wpcp-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Format', 'cite'); ?></th>
                                        <th><?php esc_html_e('Downloads', 'cite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($export_stats as $stat): ?>
                                    <tr>
                                        <td>
                                            <span class="wpcp-format-badge"><?php echo esc_html(strtoupper($stat->format)); ?></span>
                                        </td>
                                        <td>
                                            <span class="wpcp-count"><?php echo number_format(absint($stat->count)); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="wpcp-empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <p><?php esc_html_e('No export data available yet.', 'cite'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Privacy & Data Management -->
                <div class="wpcp-privacy-management-section">
                    <div class="wpcp-section-header" style="margin-top: 40px;">
                        <h2><?php esc_html_e('Data Management', 'cite'); ?></h2>
                        <p><?php esc_html_e('Manage your citation analytics data and privacy settings', 'cite'); ?></p>
                    </div>
                    
                    <div class="wpcp-analytics-grid">
                        <!-- Data Overview Card -->
                        <div class="wpcp-card">
                            <div class="wpcp-card-header">
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                    <?php esc_html_e('Data Privacy', 'cite'); ?>
                                </h3>
                            </div>
                            <div class="wpcp-card-body">
                                <div class="wpcp-privacy-info">
                                    <h4><?php esc_html_e('What Gets Tracked', 'cite'); ?></h4>
                                    <ul>
                                        <li><?php esc_html_e('Citation format selections (APA, MLA, Wikipedia, etc.)', 'cite'); ?></li>
                                        <li><?php esc_html_e('Copy to clipboard actions', 'cite'); ?></li>
                                        <li><?php esc_html_e('Export downloads (BibTeX, RIS, EndNote, CSL-JSON, CFF, Dublin Core)', 'cite'); ?></li>
                                        <li><?php esc_html_e('Per-post citation statistics', 'cite'); ?></li>
                                    </ul>
                                    <p class="wpcp-privacy-note">
                                        <?php esc_html_e('All data is stored locally in your WordPress database and never shared with third parties.', 'cite'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Purge Data Card -->
                        <div class="wpcp-card wpcp-card-danger">
                            <div class="wpcp-card-header">
                                <h3>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                    <?php esc_html_e('Purge Analytics Data', 'cite'); ?>
                                </h3>
                            </div>
                            <div class="wpcp-card-body">
                                <div class="wpcp-purge-info">
                                    <p class="wpcp-purge-warning">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                            <line x1="12" y1="9" x2="12" y2="13"/>
                                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                                        </svg>
                                        <?php esc_html_e('Permanently delete all recorded analytics data from your database. This action cannot be undone.', 'cite'); ?>
                                    </p>
                                    <form method="post" action="" class="wpcp-purge-form-analytics">
                                        <?php wp_nonce_field('wpcp_purge_analytics_action', 'wpcp_purge_nonce'); ?>
                                        <button type="submit" name="wpcp_purge_analytics" value="1" class="wpcp-btn wpcp-btn-danger-solid" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete ALL analytics data? This action cannot be undone.', 'cite'); ?>');">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                <line x1="10" y1="11" x2="10" y2="17"/>
                                                <line x1="14" y1="11" x2="14" y2="17"/>
                                            </svg>
                                            <?php esc_html_e('Purge All Data', 'cite'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        </div>
    </div>
    <?php
}

// Helper function to get the author name
function wpcp_get_author_name() {
    global $post, $wpcp_setting;
    
    if (!isset($post) || !is_object($post)) {
        return '';
    }
    
    $authors = array();
    $guest_author = get_post_meta($post->ID, 'guest-author', true);
    
    if (!empty($guest_author)) {
        $authors[] = sanitize_text_field($guest_author);
    } elseif (function_exists('get_coauthors')) {
        $coauthors = get_coauthors($post->ID);
        foreach ($coauthors as $author) {
            $authors[] = sanitize_text_field($author->display_name);
        }
    } else {
        $author_id = absint($post->post_author);
        $authors[] = sanitize_text_field(get_the_author_meta('display_name', $author_id));
    }
    
    $et_al_threshold = isset($wpcp_setting['et_al_threshold']) ? absint($wpcp_setting['et_al_threshold']) : 3;
    
    if (count($authors) > $et_al_threshold) {
        return $authors[0] . ' et al.';
    }
    
    if (count($authors) > 1) {
        $last_author = array_pop($authors);
        return implode(', ', $authors) . ' & ' . $last_author;
    }
    
    return !empty($authors[0]) ? $authors[0] : '';
}

// Get ORCID if available
function wpcp_get_orcid() {
    global $post;
    if (!isset($post) || !is_object($post)) {
        return '';
    }
    return sanitize_text_field(get_post_meta($post->ID, 'orcid', true));
}

/**
 * Get authors as an array for Wikipedia citation format
 * Returns array of author names (not combined with "et al." or "&")
 */
function wpcp_get_authors_array() {
    global $post, $wpcp_setting;
    
    if (!isset($post) || !is_object($post)) {
        return array();
    }
    
    $authors = array();
    $guest_author = get_post_meta($post->ID, 'guest-author', true);
    
    if (!empty($guest_author)) {
        $authors[] = sanitize_text_field($guest_author);
    } elseif (function_exists('get_coauthors')) {
        $coauthors = get_coauthors($post->ID);
        foreach ($coauthors as $author) {
            $authors[] = sanitize_text_field($author->display_name);
        }
    } else {
        $author_id = absint($post->post_author);
        $authors[] = sanitize_text_field(get_the_author_meta('display_name', $author_id));
    }
    
    return $authors;
}

/**
 * Get site language code (e.g., "en" from "en_US")
 */
function wpcp_get_language_code() {
    $locale = get_locale();
    return substr($locale, 0, 2);
}

/**
 * Format date as "D Month YYYY" for Wikipedia (e.g., "31 January 2026")
 */
function wpcp_format_date_dmy($date_string = null) {
    if ($date_string) {
        return date_i18n('j F Y', strtotime($date_string));
    }
    return date_i18n('j F Y');
}

// Define citation styles
function get_citation_styles() {
    $styles = array(
        // Original 10 formats
        'apa' => '{author}. ({publication_date}). {title}. <em>{sitename}</em>. {permalink}',
        'mla' => '{author}. "{title}." <em>{sitename}</em>, {publication_date}, {permalink}',
        'chicago' => '{author}. "{title}." <em>{sitename}</em>. Last modified {date}. {permalink}',
        'harvard' => '{author}. ({publication_date}). {title}. <em>{sitename}</em>. Available at: {permalink} (Accessed: {date})',
        'vancouver' => '{author}. {title}. <em>{sitename}</em>. {publication_date}; [cited {date}]. Available from: {permalink}',
        'ieee' => '{author}, "{title}," <em>{sitename}</em>, {publication_date}. [Online]. Available: {permalink}. [Accessed: {date}]',
        'ama' => '{author}. {title}. <em>{sitename}</em>. Published {publication_date}. Accessed {date}. {permalink}',
        'asa' => '{author}. {publication_date}. "{title}." <em>{sitename}</em>. Retrieved {date} ({permalink})',
        'turabian' => '{author}. "{title}." <em>{sitename}</em>. {publication_date}. {permalink}',
        'wikipedia' => '{wikipedia_cite_web}',
        
        // New 10 formats (v2.4.0)
        'acs' => '{author}. {title}. <em>{sitename}</em> [Online] {publication_date}. {permalink} (accessed {date}).',
        'aip' => '{author}, "{title}," <em>{sitename}</em> {publication_year}. [Online]. Available: {permalink}',
        'nlm' => '{author}. {title}. <em>{sitename}</em> [Internet]. {publication_date} [cited {date}]. Available from: {permalink}',
        'aaa' => '{author} {publication_date} {title}. <em>{sitename}</em>, electronic document, {permalink}, accessed {date}.',
        'apsa' => '{author}. {publication_date}. "{title}." <em>{sitename}</em>. {permalink} (accessed {date}).',
        'oscola' => '{author}, \'{title}\' (<em>{sitename}</em>, {publication_date}) &lt;{permalink}&gt; accessed {date}',
        'nature' => '{author}. {title}. <em>{sitename}</em> {permalink} ({publication_date}).',
        'acm' => '{author}. {publication_date}. {title}. <em>{sitename}</em>. Retrieved {date} from {permalink}',
        'bluebook' => '{author}, <em>{title}</em>, {sitename} ({publication_date}), {permalink}.',
        'iso690' => '{author}. {title}. <em>{sitename}</em> [online]. {publication_date} [viewed {date}]. Available from: {permalink}'
    );
    
    return apply_filters('wpcp_citation_styles', $styles);
}

/**
 * Get list of enabled citation formats based on admin settings
 * @return array List of enabled format IDs
 */
function wpcp_get_enabled_formats() {
    global $wpcp_setting;
    
    $enabled = isset($wpcp_setting['enabled_formats']) && is_array($wpcp_setting['enabled_formats']) 
        ? $wpcp_setting['enabled_formats'] 
        : array_keys(get_citation_styles());
    
    // Ensure at least one format is enabled
    if (empty($enabled)) {
        $enabled = array('apa'); // Fallback to APA if nothing selected
    }
    
    return $enabled;
}

/**
 * Get format metadata (display name, description, icon, category)
 * @return array Format metadata indexed by format ID
 */
function wpcp_get_format_metadata() {
    return array(
        // Original formats
        'apa' => array(
            'name' => __('APA', 'cite'),
            'full_name' => __('American Psychological Association', 'cite'),
            'icon' => '📚',
            'category' => 'social-sciences'
        ),
        'mla' => array(
            'name' => __('MLA', 'cite'),
            'full_name' => __('Modern Language Association', 'cite'),
            'icon' => '📖',
            'category' => 'humanities'
        ),
        'ieee' => array(
            'name' => __('IEEE', 'cite'),
            'full_name' => __('Institute of Electrical and Electronics Engineers', 'cite'),
            'icon' => '⚡',
            'category' => 'engineering'
        ),
        'harvard' => array(
            'name' => __('Harvard', 'cite'),
            'full_name' => __('Harvard Referencing', 'cite'),
            'icon' => '🎓',
            'category' => 'general'
        ),
        'chicago' => array(
            'name' => __('Chicago', 'cite'),
            'full_name' => __('Chicago Manual of Style', 'cite'),
            'icon' => '📜',
            'category' => 'humanities'
        ),
        'vancouver' => array(
            'name' => __('Vancouver', 'cite'),
            'full_name' => __('Vancouver Style', 'cite'),
            'icon' => '🏥',
            'category' => 'medical'
        ),
        'ama' => array(
            'name' => __('AMA', 'cite'),
            'full_name' => __('American Medical Association', 'cite'),
            'icon' => '⚕️',
            'category' => 'medical'
        ),
        'asa' => array(
            'name' => __('ASA', 'cite'),
            'full_name' => __('American Sociological Association', 'cite'),
            'icon' => '👥',
            'category' => 'social-sciences'
        ),
        'turabian' => array(
            'name' => __('Turabian', 'cite'),
            'full_name' => __('Turabian Style', 'cite'),
            'icon' => '✍️',
            'category' => 'general'
        ),
        'wikipedia' => array(
            'name' => __('Wikipedia', 'cite'),
            'full_name' => __('Wikipedia {{cite web}} Template', 'cite'),
            'icon' => '🌐',
            'category' => 'web'
        ),
        
        // New formats (v2.4.0)
        'acs' => array(
            'name' => __('ACS', 'cite'),
            'full_name' => __('American Chemical Society', 'cite'),
            'icon' => '🧪',
            'category' => 'sciences'
        ),
        'aip' => array(
            'name' => __('AIP', 'cite'),
            'full_name' => __('American Institute of Physics', 'cite'),
            'icon' => '⚛️',
            'category' => 'sciences'
        ),
        'nlm' => array(
            'name' => __('NLM', 'cite'),
            'full_name' => __('National Library of Medicine', 'cite'),
            'icon' => '🏥',
            'category' => 'medical'
        ),
        'aaa' => array(
            'name' => __('AAA', 'cite'),
            'full_name' => __('American Anthropological Association', 'cite'),
            'icon' => '🌍',
            'category' => 'social-sciences'
        ),
        'apsa' => array(
            'name' => __('APSA', 'cite'),
            'full_name' => __('American Political Science Association', 'cite'),
            'icon' => '🗳️',
            'category' => 'social-sciences'
        ),
        'oscola' => array(
            'name' => __('OSCOLA', 'cite'),
            'full_name' => __('Oxford Standard for Citation of Legal Authorities', 'cite'),
            'icon' => '⚖️',
            'category' => 'legal'
        ),
        'nature' => array(
            'name' => __('Nature', 'cite'),
            'full_name' => __('Nature Journal Style', 'cite'),
            'icon' => '🔬',
            'category' => 'sciences'
        ),
        'acm' => array(
            'name' => __('ACM', 'cite'),
            'full_name' => __('Association for Computing Machinery', 'cite'),
            'icon' => '💻',
            'category' => 'engineering'
        ),
        'bluebook' => array(
            'name' => __('Bluebook', 'cite'),
            'full_name' => __('Bluebook Legal Citation', 'cite'),
            'icon' => '⚖️',
            'category' => 'legal'
        ),
        'iso690' => array(
            'name' => __('ISO 690', 'cite'),
            'full_name' => __('ISO 690 International Standard', 'cite'),
            'icon' => '🌐',
            'category' => 'general'
        )
    );
}

// Global array to store citation data for JavaScript
global $wpcp_citation_data;
$wpcp_citation_data = array();

// Global array to track cited posts for bibliography (Feature #02)
global $wpcp_cited_posts;
$wpcp_cited_posts = array();

// Global counter for citation reference numbering
global $wpcp_ref_counter;
$wpcp_ref_counter = 0;

// Global flag for bibliography mode detection
global $wpcp_bibliography_mode;
$wpcp_bibliography_mode = false;

// Global instance counter for unique citation box IDs
global $wpcp_instance_counter;
$wpcp_instance_counter = 0;

/**
 * Resolve which formats to display for a given shortcode instance.
 * Implements per-post shortcode overrides (Feature #05).
 *
 * @param array $atts Shortcode attributes (may contain 'formats' and 'exclude_formats')
 * @return array Array of validated format IDs
 */
function wpcp_resolve_formats($atts) {
    $all_styles = array_keys(get_citation_styles());
    
    if (!empty($atts['formats'])) {
        $formats = array_map('trim', explode(',', sanitize_text_field($atts['formats'])));
        $formats = array_intersect($formats, $all_styles);
    } else {
        $formats = wpcp_get_enabled_formats();
    }
    
    if (!empty($atts['exclude_formats'])) {
        $exclude = array_map('trim', explode(',', sanitize_text_field($atts['exclude_formats'])));
        $formats = array_diff($formats, $exclude);
    }
    
    $formats = array_values($formats);
    
    if (empty($formats)) {
        $formats = array('apa');
    }
    
    return $formats;
}

/**
 * Get inline citation templates for parenthetical in-text citations (Feature #03).
 *
 * @return array Format ID => inline template string
 */
function wpcp_get_inline_templates() {
    $templates = array(
        'apa'       => '({author}, {publication_year})',
        'mla'       => '({author}{page})',
        'chicago'   => '({author} {publication_year}{page})',
        'harvard'   => '({author} {publication_year})',
        'vancouver' => '({ref_number})',
        'ieee'      => '[{ref_number}]',
        'ama'       => '({ref_number})',
        'asa'       => '({author} {publication_year})',
        'turabian'  => '({author} {publication_year}{page})',
        'wikipedia' => '({author} {publication_year})',
        'acs'       => '({ref_number})',
        'aip'       => '{ref_number}',
        'nlm'       => '({ref_number})',
        'aaa'       => '({author} {publication_year})',
        'apsa'      => '({author} {publication_year})',
        'oscola'    => '(n {ref_number})',
        'nature'    => '{ref_number}',
        'acm'       => '[{ref_number}]',
        'bluebook'  => '({author} {publication_year})',
        'iso690'    => '({author}, {publication_year})',
    );
    
    return apply_filters('wpcp_inline_templates', $templates);
}

/**
 * Check if the current post content contains a [cite_bibliography] shortcode.
 * Used for two-pass detection to enable footnote mode.
 *
 * @return bool True if bibliography shortcode is present
 */
function wpcp_has_bibliography_shortcode() {
    global $post;
    if (!isset($post) || !is_object($post)) {
        return false;
    }
    return has_shortcode($post->post_content, 'cite_bibliography');
}

/**
 * Get the author surname(s) for inline citations.
 * Single: "Smith", Two: "Smith & Jones", 3+: "Smith et al."
 *
 * @param string $custom_author Optional custom author override
 * @return string Formatted author surname(s)
 */
function wpcp_get_inline_author($custom_author = '') {
    global $wpcp_setting;
    
    if (!empty($custom_author)) {
        // Extract surname from custom author
        $parts = explode(' ', trim($custom_author));
        return end($parts);
    }
    
    $authors = wpcp_get_authors_array();
    $et_al_threshold = isset($wpcp_setting['et_al_threshold']) ? absint($wpcp_setting['et_al_threshold']) : 3;
    
    // Extract surnames (last word of each name)
    $surnames = array();
    foreach ($authors as $name) {
        $parts = explode(' ', trim($name));
        $surnames[] = end($parts);
    }
    
    if (empty($surnames)) {
        return html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    if (count($surnames) > $et_al_threshold) {
        return $surnames[0] . ' et al.';
    }
    
    if (count($surnames) === 2) {
        return $surnames[0] . ' & ' . $surnames[1];
    }
    
    if (count($surnames) > 2) {
        $last = array_pop($surnames);
        return implode(', ', $surnames) . ' & ' . $last;
    }
    
    return $surnames[0];
}

// Shortcode function
function display_citation($atts = array()) {
    global $post, $wpcp_setting, $wpcp_citation_data, $wpcp_cited_posts, $wpcp_ref_counter, $wpcp_bibliography_mode, $wpcp_instance_counter;
    
    if (!isset($post) || !is_object($post)) {
        return '';
    }
    
    // Unique instance ID for this citation box
    $wpcp_instance_counter++;
    $instance_id = $wpcp_instance_counter;
    
    $atts = shortcode_atts(array(
        'style' => 'apa',
        'show_copy' => 'true',
        'show_export' => 'true',
        'show_toggle' => 'true',
        'custom_author' => '',
        'mode' => 'box',
        'formats' => '',
        'exclude_formats' => '',
        'page' => '',
        'link' => 'true',
    ), $atts);
    
    // Validate mode
    $allowed_modes = array('box', 'inline', 'bibliography');
    if (!in_array($atts['mode'], $allowed_modes, true)) {
        $atts['mode'] = 'box';
    }
    
    // Resolve which formats to display (Feature #05)
    $active_formats = wpcp_resolve_formats($atts);
    
    $allowed_styles = array_keys(get_citation_styles());
    if (!in_array($atts['style'], $allowed_styles, true)) {
        $atts['style'] = 'apa';
    }
    
    // Ensure default style is in the active formats list
    if (!in_array($atts['style'], $active_formats, true)) {
        $atts['style'] = $active_formats[0];
    }
    
    $atts['custom_author'] = sanitize_text_field($atts['custom_author']);
    $styles = get_citation_styles();
    
    if (!empty($wpcp_setting['excluded_posts'])) {
        $excluded = array_map('absint', array_map('trim', explode(',', $wpcp_setting['excluded_posts'])));
        if (in_array(absint($post->ID), $excluded, true)) {
            return '';
        }
    }
    
    // Detect bibliography mode (two-pass: Feature #02)
    if (!$wpcp_bibliography_mode) {
        $wpcp_bibliography_mode = wpcp_has_bibliography_shortcode();
    }
    
    // =========================================================================
    // MODE: INLINE — parenthetical in-text citation (Feature #03)
    // =========================================================================
    if ($atts['mode'] === 'inline') {
        $inline_templates = wpcp_get_inline_templates();
        $inline_author = wpcp_get_inline_author($atts['custom_author']);
        $publication_year = get_the_date('Y');
        $permalink = get_permalink();
        $page_str = '';
        
        if (!empty($atts['page'])) {
            $page_str = ', p. ' . esc_html(sanitize_text_field($atts['page']));
        }
        
        // Determine reference number for numeric styles
        $ref_number = '';
        if ($wpcp_bibliography_mode) {
            // Register in bibliography tracking
            $wpcp_ref_counter++;
            $current_ref = $wpcp_ref_counter;
            
            // Check for deduplication (same post cited multiple times)
            $existing_ref = null;
            foreach ($wpcp_cited_posts as $entry) {
                if ($entry['post_id'] === $post->ID) {
                    $existing_ref = $entry['ref_number'];
                    break;
                }
            }
            
            if ($existing_ref !== null) {
                $ref_number = $existing_ref;
            } else {
                $ref_number = count(array_unique(array_column($wpcp_cited_posts, 'post_id'))) + 1;
            }
            
            $wpcp_cited_posts[] = array(
                'post_id' => $post->ID,
                'ref_number' => $ref_number,
                'marker_id' => 'wpcp-marker-' . $wpcp_ref_counter,
                'anchor_id' => 'wpcp-ref-' . $ref_number,
                'style' => $atts['style'],
            );
        }
        
        $template = isset($inline_templates[$atts['style']]) ? $inline_templates[$atts['style']] : '({author}, {publication_year})';
        
        /**
         * Filter the inline citation template for a specific style.
         *
         * @param string $template The template string.
         * @param string $style    The citation style ID.
         */
        $template = apply_filters('wpcp_inline_template', $template, $atts['style']);
        
        // For numeric styles without bibliography, fall back to author-year
        $is_numeric_style = (strpos($template, '{ref_number}') !== false);
        if ($is_numeric_style && !$wpcp_bibliography_mode) {
            $template = '({author}, {publication_year})';
        }
        
        $citation_text = str_replace(
            array('{author}', '{publication_year}', '{ref_number}', '{page}'),
            array(esc_html($inline_author), esc_html($publication_year), esc_html($ref_number), $page_str),
            $template
        );
        
        $show_link = ($atts['link'] === 'true' || $atts['link'] === '1');
        
        $html = '<span class="wpcp-inline-citation"';
        if ($wpcp_bibliography_mode) {
            $html .= ' id="' . esc_attr('wpcp-marker-' . $wpcp_ref_counter) . '"';
        }
        $html .= '>';
        
        if ($show_link) {
            $href = $wpcp_bibliography_mode ? '#wpcp-ref-' . intval($ref_number) : esc_url($permalink);
            $html .= '<a href="' . $href . '"';
            if ($wpcp_bibliography_mode) {
                $html .= ' role="doc-noteref" aria-label="' . esc_attr(sprintf(__('See reference %d', 'cite'), $ref_number)) . '"';
            }
            $html .= '>' . $citation_text . '</a>';
        } else {
            $html .= $citation_text;
        }
        
        $html .= '</span>';
        
        /**
         * Filter the complete inline citation HTML.
         *
         * @param string  $html The inline citation HTML.
         * @param array   $atts The shortcode attributes.
         * @param WP_Post $post The current post.
         */
        return apply_filters('wpcp_inline_citation', $html, $atts, $post);
    }
    
    // =========================================================================
    // MODE: BIBLIOGRAPHY — plain text citation without box wrapper (Feature #03)
    // =========================================================================
    if ($atts['mode'] === 'bibliography') {
        $author = !empty($atts['custom_author']) ? $atts['custom_author'] : wpcp_get_author_name();
        $template = isset($styles[$atts['style']]) ? $styles[$atts['style']] : $styles['apa'];
        
        // Handle Wikipedia format specially
        if ($atts['style'] === 'wikipedia') {
            return '<span class="wpcp-bibliography-citation">' . esc_html(wpcp_build_wikipedia_citation_php()) . '</span>';
        }
        
        $citation = str_replace(
            array('{author}', '{sitename}', '{title}', '{date}', '{publication_date}', '{publication_year}', '{permalink}'),
            array(
                esc_html($author),
                esc_html(html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                esc_html(html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                esc_html(date_i18n(get_option('date_format'))),
                esc_html(get_the_date()),
                esc_html(get_the_date('Y')),
                '<a href="' . esc_url(get_permalink()) . '">' . esc_html(get_permalink()) . '</a>'
            ),
            $template
        );
        
        $citation = preg_replace('/\s\./', '.', $citation);
        $citation = preg_replace('/\s,/', ',', $citation);
        
        return '<span class="wpcp-bibliography-citation">' . $citation . '</span>';
    }
    
    // =========================================================================
    // MODE: BOX — full citation box with selector (default, Feature #04/#05)
    // =========================================================================
    
    // If bibliography mode is active and mode is box, render as footnote marker instead
    if ($wpcp_bibliography_mode && $atts['mode'] === 'box') {
        $wpcp_ref_counter++;
        
        // Check for deduplication
        $existing_ref = null;
        foreach ($wpcp_cited_posts as $entry) {
            if ($entry['post_id'] === $post->ID) {
                $existing_ref = $entry['ref_number'];
                break;
            }
        }
        
        if ($existing_ref !== null) {
            $ref_number = $existing_ref;
        } else {
            $ref_number = count(array_unique(array_column($wpcp_cited_posts, 'post_id'))) + 1;
        }
        
        $wpcp_cited_posts[] = array(
            'post_id' => $post->ID,
            'ref_number' => $ref_number,
            'marker_id' => 'wpcp-marker-' . $wpcp_ref_counter,
            'anchor_id' => 'wpcp-ref-' . $ref_number,
            'style' => $atts['style'],
        );
        
        return '<sup class="wpcp-ref-marker" id="' . esc_attr('wpcp-marker-' . $wpcp_ref_counter) . '">'
            . '<a href="#wpcp-ref-' . intval($ref_number) . '" role="doc-noteref" aria-label="' . esc_attr(sprintf(__('See reference %d', 'cite'), $ref_number)) . '">'
            . '[' . intval($ref_number) . ']</a></sup>';
    }
    
    $show_toggle = ($atts['show_toggle'] === 'true' || $atts['show_toggle'] === '1') && 
                   isset($wpcp_setting['show_toggle']) && $wpcp_setting['show_toggle'] === 'yes';
    $show_copy = ($atts['show_copy'] === 'true' || $atts['show_copy'] === '1');
    $show_export = ($atts['show_export'] === 'true' || $atts['show_export'] === '1');
    
    // Get display mode
    $display_mode = isset($wpcp_setting['format_display_mode']) ? $wpcp_setting['format_display_mode'] : 'dropdown';
    
    // Prepare data for JavaScript
    $citation_data = array(
        'postId' => absint($post->ID),
        'styles' => $styles,
        'author' => !empty($atts['custom_author']) ? $atts['custom_author'] : wpcp_get_author_name(),
        'authors' => !empty($atts['custom_author']) ? array($atts['custom_author']) : wpcp_get_authors_array(),
        'siteName' => html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'title' => html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'dateAccessed' => date_i18n(get_option('date_format')),
        'dateAccessedDMY' => wpcp_format_date_dmy(),
        'publicationDate' => get_the_date(),
        'publicationDateDMY' => wpcp_format_date_dmy(get_the_date('Y-m-d')),
        'publicationYear' => get_the_date('Y'),
        'publicationDateISO' => get_the_date('Y-m-d'),
        'permalink' => get_permalink(),
        'permalinkUrl' => esc_url(get_permalink()),
        'language' => wpcp_get_language_code(),
        'ajaxUrl' => esc_url(admin_url('admin-ajax.php')),
        'nonce' => wp_create_nonce('wpcp_analytics_' . $post->ID),
        'enableAnalytics' => (isset($wpcp_setting['enable_analytics']) && $wpcp_setting['enable_analytics'] === 'yes'),
        'requireConsent' => (isset($wpcp_setting['require_consent_for_analytics']) && $wpcp_setting['require_consent_for_analytics'] === 'yes'),
        'displayMode' => $display_mode,
        'activeFormats' => $active_formats,
        'instance' => $instance_id,
    );
    
    $wpcp_citation_data[] = $citation_data;
    
    $format_metadata = wpcp_get_format_metadata();
    $show_selector = (count($active_formats) > 1);
    
    ob_start();
    ?>
    <div id="citation-box-<?php echo absint($post->ID); ?>-<?php echo absint($instance_id); ?>" class="wpcp-citation-box" itemscope itemtype="http://schema.org/ScholarlyArticle" role="region" aria-label="<?php esc_attr_e('Citation Information', 'cite'); ?>" data-display-mode="<?php echo esc_attr($display_mode); ?>">
        <div class="citation-header">
            <div class="citation-title">
                <svg class="wpcp-cite-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M8.5 5.5C8.5 4.12 7.38 3 6 3S3.5 4.12 3.5 5.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5zm8 0C16.5 4.12 15.38 3 14 3s-2.5 1.12-2.5 2.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5z"/></svg>
                <span><?php esc_html_e('Cite This As:', 'cite'); ?></span>
                <?php if ($show_selector): ?>
                    <?php if ($display_mode === 'tabs'): ?>
                    <div class="wpcp-style-tabs" role="tablist" aria-label="<?php esc_attr_e('Citation format', 'cite'); ?>">
                        <?php foreach ($active_formats as $format_id): ?>
                            <?php if (isset($format_metadata[$format_id])): ?>
                                <button type="button" class="wpcp-style-tab<?php echo ($atts['style'] === $format_id) ? ' wpcp-style-tab--active' : ''; ?>" role="tab" aria-selected="<?php echo ($atts['style'] === $format_id) ? 'true' : 'false'; ?>" data-style="<?php echo esc_attr($format_id); ?>" data-post-id="<?php echo absint($post->ID); ?>" tabindex="<?php echo ($atts['style'] === $format_id) ? '0' : '-1'; ?>">
                                    <?php echo esc_html($format_metadata[$format_id]['name']); ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php elseif ($display_mode === 'buttons'): ?>
                    <div class="wpcp-style-buttons" role="radiogroup" aria-label="<?php esc_attr_e('Citation format', 'cite'); ?>">
                        <?php foreach ($active_formats as $format_id): ?>
                            <?php if (isset($format_metadata[$format_id])): ?>
                                <button type="button" class="wpcp-style-btn<?php echo ($atts['style'] === $format_id) ? ' wpcp-style-btn--active' : ''; ?>" role="radio" aria-checked="<?php echo ($atts['style'] === $format_id) ? 'true' : 'false'; ?>" data-style="<?php echo esc_attr($format_id); ?>" data-post-id="<?php echo absint($post->ID); ?>">
                                    <?php echo esc_html($format_metadata[$format_id]['name']); ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <select class="citation-style-select" data-post-id="<?php echo absint($post->ID); ?>" aria-label="<?php esc_attr_e('Select citation style', 'cite'); ?>" data-default-style="<?php echo esc_attr($atts['style']); ?>">
                        <?php foreach ($active_formats as $format_id): ?>
                            <?php if (isset($format_metadata[$format_id])): ?>
                                <option value="<?php echo esc_attr($format_id); ?>" <?php selected($atts['style'], $format_id); ?>>
                                    <?php echo esc_html($format_metadata[$format_id]['name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="wpcp-single-format-label"><?php echo isset($format_metadata[$active_formats[0]]) ? esc_html($format_metadata[$active_formats[0]]['name']) : ''; ?></span>
                <?php endif; ?>
                <?php if ($show_copy): ?>
                <button class="copy-button" data-post-id="<?php echo absint($post->ID); ?>" title="<?php esc_attr_e('Copy citation', 'cite'); ?>" aria-label="<?php esc_attr_e('Copy citation to clipboard', 'cite'); ?>">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M13.5 2H6.5C5.67 2 5 2.67 5 3.5V10.5C5 11.33 5.67 12 6.5 12H13.5C14.33 12 15 11.33 15 10.5V3.5C15 2.67 14.33 2 13.5 2ZM13.5 10.5H6.5V3.5H13.5V10.5ZM2.5 6H4V7.5H2.5V12.5H9.5V11H11V12.5C11 13.33 10.33 14 9.5 14H2.5C1.67 14 1 13.33 1 12.5V7.5C1 6.67 1.67 6 2.5 6Z" fill="currentColor"/>
                    </svg>
                </button>
                <?php endif; ?>
                <?php if ($show_export): ?>
                <div class="export-menu">
                    <button class="export-button" data-post-id="<?php echo absint($post->ID); ?>" title="<?php esc_attr_e('Export citation', 'cite'); ?>" aria-label="<?php esc_attr_e('Export citation in various formats', 'cite'); ?>" aria-expanded="false" aria-haspopup="true">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M8 12L3 7L4.4 5.6L7 8.2V0H9V8.2L11.6 5.6L13 7L8 12ZM2 16C1.45 16 0.979 15.804 0.587 15.412C0.195 15.02 -0.000667 14.5493 7.78539e-07 14V11H2V14H14V11H16V14C16 14.55 15.804 15.021 15.412 15.413C15.02 15.805 14.5493 16.0007 14 16H2Z" fill="currentColor"/>
                        </svg>
                    </button>
                    <div class="export-dropdown" role="menu" aria-label="<?php esc_attr_e('Export options', 'cite'); ?>">
                        <?php
                        $export_labels = array(
                            'bibtex'      => __('Export as BibTeX', 'cite'),
                            'ris'         => __('Export as RIS', 'cite'),
                            'endnote'     => __('Export as EndNote', 'cite'),
                            'csl-json'    => __('Export as CSL-JSON', 'cite'),
                            'cff'         => __('Export as CFF', 'cite'),
                            'dublin-core' => __('Export as Dublin Core', 'cite'),
                        );
                        $active_exports = isset($wpcp_setting['enabled_export_formats']) ? $wpcp_setting['enabled_export_formats'] : array_keys($export_labels);
                        foreach ($active_exports as $ef_id):
                            if (isset($export_labels[$ef_id])):
                        ?>
                        <button class="export-option" data-format="<?php echo esc_attr($ef_id); ?>" data-post-id="<?php echo absint($post->ID); ?>" role="menuitem"><?php echo esc_html($export_labels[$ef_id]); ?></button>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($show_toggle): ?>
                <button class="toggle-button" data-post-id="<?php echo absint($post->ID); ?>" aria-label="<?php esc_attr_e('Toggle citation visibility', 'cite'); ?>" aria-expanded="true">
                    <svg class="toggle-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M8 11L3 6L4.4 4.6L8 8.2L11.6 4.6L13 6L8 11Z" fill="currentColor"/>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="citation-content" data-post-id="<?php echo absint($post->ID); ?>">
            <div class="citation-output" data-post-id="<?php echo absint($post->ID); ?>"></div>
        </div>
        <div class="copy-notification" data-post-id="<?php echo absint($post->ID); ?>" role="status" aria-live="polite" aria-atomic="true"><?php esc_html_e('Citation copied!', 'cite'); ?></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cite', 'display_citation');

/**
 * Build Wikipedia {{cite web}} template in PHP (for bibliography mode).
 *
 * @return string Wikipedia cite web template text
 */
function wpcp_build_wikipedia_citation_php() {
    global $post;
    
    $authors = wpcp_get_authors_array();
    $parts = array('{{cite web');
    $parts[] = ' |url=' . get_permalink();
    $parts[] = ' |title=' . html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    if (count($authors) === 1) {
        $parts[] = ' |author=' . $authors[0];
    } else {
        for ($i = 0; $i < count($authors); $i++) {
            $parts[] = ' |author' . ($i + 1) . '=' . $authors[$i];
        }
    }
    
    $parts[] = ' |date=' . wpcp_format_date_dmy(get_the_date('Y-m-d'));
    $parts[] = ' |website=' . html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $parts[] = ' |language=' . wpcp_get_language_code();
    $parts[] = ' |access-date=' . wpcp_format_date_dmy();
    $parts[] = '}}';
    
    return implode('', $parts);
}

// =============================================================================
// BIBLIOGRAPHY SHORTCODE (Feature #02)
// =============================================================================

/**
 * [cite_bibliography] shortcode — Renders a collected reference list.
 *
 * Collects all [cite] instances registered in $wpcp_cited_posts and generates
 * a formatted, numbered reference list (bibliography) at the shortcode location.
 *
 * @param array $atts Shortcode attributes.
 * @return string Bibliography HTML
 */
function wpcp_display_bibliography($atts = array()) {
    global $post, $wpcp_setting, $wpcp_cited_posts;
    
    if (!isset($post) || !is_object($post)) {
        return '';
    }
    
    $atts = shortcode_atts(array(
        'style'       => '',
        'heading'     => __('References', 'cite'),
        'heading_tag' => 'h3',
        'numbered'    => 'true',
        'link_back'   => 'true',
    ), $atts);
    
    // If no citations were collected, output nothing
    if (empty($wpcp_cited_posts)) {
        return '';
    }
    
    // Validate heading tag
    $allowed_heading_tags = array('h2', 'h3', 'h4', 'h5', 'h6');
    if (!in_array($atts['heading_tag'], $allowed_heading_tags, true)) {
        $atts['heading_tag'] = 'h3';
    }
    
    $numbered = ($atts['numbered'] === 'true' || $atts['numbered'] === '1');
    $link_back = ($atts['link_back'] === 'true' || $atts['link_back'] === '1');
    
    // Determine bibliography style
    if (!empty($atts['style'])) {
        $bib_style = sanitize_text_field($atts['style']);
        $all_styles = array_keys(get_citation_styles());
        if (!in_array($bib_style, $all_styles, true)) {
            $bib_style = 'apa';
        }
    } else {
        $enabled = wpcp_get_enabled_formats();
        $bib_style = !empty($enabled[0]) ? $enabled[0] : 'apa';
    }
    
    $styles = get_citation_styles();
    $template = isset($styles[$bib_style]) ? $styles[$bib_style] : $styles['apa'];
    
    // Deduplicate: build unique entries by post_id preserving first-appearance order
    $unique_entries = array();
    $marker_map = array(); // ref_number => array of marker_ids
    
    foreach ($wpcp_cited_posts as $entry) {
        $pid = $entry['post_id'];
        $rn = $entry['ref_number'];
        
        if (!isset($unique_entries[$pid])) {
            $unique_entries[$pid] = array(
                'post_id' => $pid,
                'ref_number' => $rn,
                'style' => $entry['style'],
            );
        }
        
        if (!isset($marker_map[$rn])) {
            $marker_map[$rn] = array();
        }
        $marker_map[$rn][] = $entry['marker_id'];
    }
    
    // Build output
    /**
     * Filter the bibliography heading text.
     *
     * @param string $heading The heading text.
     */
    $heading_text = apply_filters('wpcp_bibliography_heading', $atts['heading']);
    
    $output = '<div class="wpcp-bibliography" role="doc-endnotes">';
    
    if (!empty($heading_text)) {
        $output .= '<' . $atts['heading_tag'] . ' class="wpcp-bibliography-heading">' . esc_html($heading_text) . '</' . $atts['heading_tag'] . '>';
    }
    
    $list_tag = $numbered ? 'ol' : 'ul';
    $output .= '<' . $list_tag . ' class="wpcp-bibliography-list">';
    
    foreach ($unique_entries as $entry) {
        $ref_post = get_post($entry['post_id']);
        if (!$ref_post) {
            continue;
        }
        
        // Temporarily set global $post to generate citation for this entry
        $original_post = $GLOBALS['post'];
        $GLOBALS['post'] = $ref_post;
        setup_postdata($ref_post);
        
        $author = wpcp_get_author_name();
        $title = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $permalink = get_permalink();
        $date_accessed = date_i18n(get_option('date_format'));
        $pub_date = get_the_date();
        $pub_year = get_the_date('Y');
        
        if ($bib_style === 'wikipedia') {
            $citation_html = esc_html(wpcp_build_wikipedia_citation_php());
        } else {
            $citation_html = str_replace(
                array('{author}', '{sitename}', '{title}', '{date}', '{publication_date}', '{publication_year}', '{permalink}'),
                array(
                    esc_html($author),
                    esc_html($site_name),
                    esc_html($title),
                    esc_html($date_accessed),
                    esc_html($pub_date),
                    esc_html($pub_year),
                    '<a href="' . esc_url($permalink) . '">' . esc_html($permalink) . '</a>'
                ),
                $template
            );
            $citation_html = preg_replace('/\s\./', '.', $citation_html);
            $citation_html = preg_replace('/\s,/', ',', $citation_html);
        }
        
        // Restore original post
        $GLOBALS['post'] = $original_post;
        setup_postdata($original_post);
        
        $rn = $entry['ref_number'];
        
        // Build backlinks
        $backlinks = '';
        if ($link_back && isset($marker_map[$rn])) {
            $bl_parts = array();
            foreach ($marker_map[$rn] as $idx => $marker_id) {
                $label = (count($marker_map[$rn]) > 1) ? chr(97 + $idx) : '↩';
                $bl_parts[] = '<a href="#' . esc_attr($marker_id) . '" class="wpcp-bibliography-backlink" aria-label="' . esc_attr(sprintf(__('Back to citation %d in text', 'cite'), $rn)) . '">' . $label . '</a>';
            }
            $backlinks = ' ' . implode(' ', $bl_parts);
        }
        
        $entry_html = '<li id="wpcp-ref-' . intval($rn) . '" class="wpcp-bibliography-entry" role="doc-endnote">'
            . '<span class="wpcp-bibliography-text">' . $citation_html . '</span>'
            . $backlinks
            . '</li>';
        
        /**
         * Filter an individual bibliography entry.
         *
         * @param string $entry_html The entry HTML.
         * @param int    $ref_number The reference number.
         * @param array  $entry      The entry data.
         */
        $output .= apply_filters('wpcp_bibliography_entry', $entry_html, $rn, $entry);
    }
    
    $output .= '</' . $list_tag . '>';
    $output .= '</div>';
    
    /**
     * Filter the complete bibliography output HTML.
     *
     * @param string $output The complete bibliography HTML.
     * @param array  $unique_entries The deduplicated citation entries.
     */
    return apply_filters('wpcp_bibliography_output', $output, $unique_entries);
}
add_shortcode('cite_bibliography', 'wpcp_display_bibliography');

// =============================================================================
// ENQUEUE SCRIPTS AND STYLES
// =============================================================================

// Enqueue frontend styles
add_action('wp_enqueue_scripts', 'wpcp_enqueue_frontend_styles');

function wpcp_enqueue_frontend_styles() {
    if (is_singular()) {
        wp_enqueue_style(
            'wpcp-frontend',
            WPCP_ASSETS_URL . 'css/frontend.css',
            array(),
            WPCP_VERSION
        );
    }
}

// Enqueue frontend scripts
add_action('wp_enqueue_scripts', 'wpcp_enqueue_frontend_scripts');

function wpcp_enqueue_frontend_scripts() {
    if (is_singular()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'wpcp-frontend',
            WPCP_ASSETS_URL . 'js/frontend.js',
            array('jquery'),
            WPCP_VERSION,
            true
        );
    }
}

// Output citation data in footer
add_action('wp_footer', 'wpcp_output_citation_data');

function wpcp_output_citation_data() {
    global $wpcp_citation_data;
    
    if (empty($wpcp_citation_data)) {
        return;
    }
    
    ?>
    <script>
        window.wpcpCitationData = <?php echo wp_json_encode($wpcp_citation_data); ?>;
    </script>
    <?php
}

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'wpcp_enqueue_admin_assets');

function wpcp_enqueue_admin_assets($hook) {
    if ('toplevel_page_wp-cite' !== $hook && 'cite_page_wp-cite-analytics' !== $hook) {
        return;
    }
    
    wp_enqueue_style(
        'wpcp-admin',
        WPCP_ASSETS_URL . 'css/admin.css',
        array(),
        WPCP_VERSION
    );
    
    wp_enqueue_script('jquery');
    
    // Enqueue admin JavaScript
    wp_enqueue_script(
        'wpcp-admin',
        WPCP_ASSETS_URL . 'js/admin.js',
        array('jquery'),
        WPCP_VERSION,
        true
    );
    
    // Also load frontend styles for preview
    wp_enqueue_style(
        'wpcp-frontend',
        WPCP_ASSETS_URL . 'css/frontend.css',
        array(),
        WPCP_VERSION
    );
    
    wp_enqueue_script(
        'wpcp-frontend',
        WPCP_ASSETS_URL . 'js/frontend.js',
        array('jquery'),
        WPCP_VERSION,
        true
    );
}

// Enqueue block editor assets
add_action('enqueue_block_editor_assets', 'wpcp_enqueue_block_editor_assets');

function wpcp_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'wpcp-block',
        WPCP_ASSETS_URL . 'js/block.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
        WPCP_VERSION,
        true
    );
    
    // Pass format options to block JS
    $format_metadata = wpcp_get_format_metadata();
    $block_formats = array();
    foreach ($format_metadata as $key => $meta) {
        $block_formats[] = array(
            'label' => $meta['name'],
            'value' => $key,
        );
    }
    wp_localize_script('wpcp-block', 'wpcpBlockData', array(
        'formats' => $block_formats,
    ));
    
    wp_enqueue_style(
        'wpcp-block-editor',
        WPCP_ASSETS_URL . 'css/block-editor.css',
        array('wp-edit-blocks'),
        WPCP_VERSION
    );
}

// Register block type
add_action('init', 'wpcp_register_block_type');

function wpcp_register_block_type() {
    register_block_type('cite/block', array(
        'editor_script'   => 'wpcp-block',
        'style'           => 'wpcp-block-editor',
        'attributes'      => array(
            'citationStyle'  => array('type' => 'string',  'default' => 'apa'),
            'mode'           => array('type' => 'string',  'default' => 'box'),
            'showExport'     => array('type' => 'boolean', 'default' => true),
            'showToggle'     => array('type' => 'boolean', 'default' => true),
            'showCopy'       => array('type' => 'boolean', 'default' => true),
            'showLink'       => array('type' => 'boolean', 'default' => true),
            'customAuthor'   => array('type' => 'string',  'default' => ''),
            'formats'        => array('type' => 'string',  'default' => ''),
            'excludeFormats' => array('type' => 'string',  'default' => ''),
            'page'           => array('type' => 'string',  'default' => ''),
        ),
        'render_callback' => 'wpcp_render_citation_block',
    ));
}

/**
 * Server-side render callback for the Citation Block.
 * Translates block attributes into shortcode atts and calls display_citation().
 */
function wpcp_render_citation_block($attributes) {
    $atts = array(
        'style'           => isset($attributes['citationStyle']) ? $attributes['citationStyle'] : 'apa',
        'mode'            => isset($attributes['mode']) ? $attributes['mode'] : 'box',
        'show_copy'       => (!isset($attributes['showCopy']) || $attributes['showCopy']) ? 'true' : 'false',
        'show_export'     => (!isset($attributes['showExport']) || $attributes['showExport']) ? 'true' : 'false',
        'show_toggle'     => (!isset($attributes['showToggle']) || $attributes['showToggle']) ? 'true' : 'false',
        'link'            => (!isset($attributes['showLink']) || $attributes['showLink']) ? 'true' : 'false',
        'custom_author'   => isset($attributes['customAuthor']) ? $attributes['customAuthor'] : '',
        'formats'         => isset($attributes['formats']) ? $attributes['formats'] : '',
        'exclude_formats' => isset($attributes['excludeFormats']) ? $attributes['excludeFormats'] : '',
        'page'            => isset($attributes['page']) ? $attributes['page'] : '',
    );

    return display_citation($atts);
}

// =============================================================================
// AUTO-DISPLAY AND META TAGS
// =============================================================================

// Auto-display citation box
add_filter('the_content', 'wpcp_auto_display_citation');

function wpcp_auto_display_citation($content) {
    global $post, $wpcp_setting;
    
    if (!is_main_query()) {
        return $content;
    }
    
    if (!isset($wpcp_setting['auto_display']) || $wpcp_setting['auto_display'] !== 'yes') {
        return $content;
    }
    
    $allowed_types = isset($wpcp_setting['post_types']) ? (array)$wpcp_setting['post_types'] : array('post');
    if (!in_array(get_post_type(), $allowed_types, true)) {
        return $content;
    }
    
    if (!empty($wpcp_setting['excluded_posts'])) {
        $excluded = array_map('absint', array_map('trim', explode(',', $wpcp_setting['excluded_posts'])));
        if (in_array(absint($post->ID), $excluded, true)) {
            return $content;
        }
    }
    
    if (!is_singular()) {
        return $content;
    }
    
    $citation = do_shortcode('[cite]');
    $position = isset($wpcp_setting['display_position']) ? $wpcp_setting['display_position'] : 'bottom';
    
    if ($position === 'top') {
        return $citation . $content;
    } else {
        return $content . $citation;
    }
}

// Add Google Scholar meta tags
add_action('wp_head', 'wpcp_add_google_scholar_tags', 5);

function wpcp_add_google_scholar_tags() {
    global $post, $wpcp_setting;
    
    if (!is_singular() || !isset($wpcp_setting['show_google_scholar']) || $wpcp_setting['show_google_scholar'] !== 'yes') {
        return;
    }
    
    if (!isset($post) || !is_object($post)) {
        return;
    }
    
    // Only output on enabled post types
    $allowed_types = isset($wpcp_setting['post_types']) ? (array)$wpcp_setting['post_types'] : array('post');
    if (!in_array(get_post_type(), $allowed_types, true)) {
        return;
    }
    
    $authors = wpcp_get_authors_array();
    $title = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $date = get_the_date('Y/m/d');
    $url = get_permalink();
    $orcid = wpcp_get_orcid();
    $site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $locale = get_locale();
    $language = substr($locale, 0, 2);
    
    /**
     * Filter Google Scholar meta tag data before output.
     *
     * @param array $meta_data Array of meta tag key => value pairs.
     * @param WP_Post $post The current post object.
     */
    $meta_data = apply_filters('wpcp_scholar_meta', array(
        'citation_title' => $title,
        'citation_authors' => $authors,
        'citation_publication_date' => $date,
        'citation_journal_title' => $site_name,
        'citation_public_url' => $url,
        'citation_language' => $language,
        'citation_author_orcid' => $orcid,
    ), $post);
    
    echo "\n<!-- Cite Plugin: Google Scholar Meta Tags -->\n";
    echo '<meta name="citation_title" content="' . esc_attr($meta_data['citation_title']) . '">' . "\n";
    
    // Output one citation_author tag per author
    if (!empty($meta_data['citation_authors']) && is_array($meta_data['citation_authors'])) {
        foreach ($meta_data['citation_authors'] as $author) {
            echo '<meta name="citation_author" content="' . esc_attr($author) . '">' . "\n";
        }
    }
    
    echo '<meta name="citation_publication_date" content="' . esc_attr($meta_data['citation_publication_date']) . '">' . "\n";
    echo '<meta name="citation_journal_title" content="' . esc_attr($meta_data['citation_journal_title']) . '">' . "\n";
    echo '<meta name="citation_public_url" content="' . esc_url($meta_data['citation_public_url']) . '">' . "\n";
    echo '<meta name="citation_language" content="' . esc_attr($meta_data['citation_language']) . '">' . "\n";
    
    if (!empty($meta_data['citation_author_orcid'])) {
        echo '<meta name="citation_author_orcid" content="' . esc_attr($meta_data['citation_author_orcid']) . '">' . "\n";
    }
    
    echo '<meta name="citation_fulltext_html_url" content="' . esc_url($url) . '">' . "\n";
    echo "<!-- End Google Scholar Meta Tags -->\n\n";
}

// Add JSON-LD ScholarlyArticle structured data
add_action('wp_head', 'wpcp_add_jsonld_structured_data', 10);

function wpcp_add_jsonld_structured_data() {
    global $post, $wpcp_setting;
    
    if (!is_singular() || !isset($wpcp_setting['show_jsonld']) || $wpcp_setting['show_jsonld'] !== 'yes') {
        return;
    }
    
    if (!isset($post) || !is_object($post)) {
        return;
    }
    
    // Only output on enabled post types
    $allowed_types = isset($wpcp_setting['post_types']) ? (array)$wpcp_setting['post_types'] : array('post');
    if (!in_array(get_post_type(), $allowed_types, true)) {
        return;
    }
    
    $authors = wpcp_get_authors_array();
    $title = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $permalink = get_permalink();
    $site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $date_published = get_the_date('c');
    $date_modified = get_the_modified_date('c');
    
    // Build author array for schema
    $schema_authors = array();
    foreach ($authors as $author_name) {
        $schema_authors[] = array(
            '@type' => 'Person',
            'name' => $author_name,
        );
    }
    // Single author: use object; multiple: use array
    $author_schema = (count($schema_authors) === 1) ? $schema_authors[0] : $schema_authors;
    
    // Build the JSON-LD data
    $jsonld = array(
        '@context' => 'https://schema.org',
        '@type' => 'ScholarlyArticle',
        'headline' => $title,
        'author' => $author_schema,
        'datePublished' => $date_published,
        'dateModified' => $date_modified,
        'publisher' => array(
            '@type' => 'Organization',
            'name' => $site_name,
        ),
        'url' => $permalink,
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id' => $permalink,
        ),
        'isPartOf' => array(
            '@type' => 'WebSite',
            'name' => $site_name,
            'url' => home_url('/'),
        ),
    );
    
    // Add optional description from excerpt
    $excerpt = get_the_excerpt($post);
    if (!empty($excerpt)) {
        $jsonld['description'] = wp_strip_all_tags($excerpt);
    }
    
    // Add featured image if available
    $thumbnail_id = get_post_thumbnail_id($post->ID);
    if ($thumbnail_id) {
        $image_url = wp_get_attachment_url($thumbnail_id);
        if ($image_url) {
            $jsonld['image'] = $image_url;
        }
    }
    
    // Add publisher logo if available (custom logo)
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_url = wp_get_attachment_url($custom_logo_id);
        if ($logo_url) {
            $jsonld['publisher']['logo'] = array(
                '@type' => 'ImageObject',
                'url' => $logo_url,
            );
        }
    }
    
    // Add language
    $locale = get_locale();
    $jsonld['inLanguage'] = substr($locale, 0, 2);
    
    /**
     * Filter JSON-LD structured data before output.
     *
     * @param array   $jsonld The JSON-LD data array.
     * @param WP_Post $post   The current post object.
     */
    $jsonld = apply_filters('wpcp_jsonld_data', $jsonld, $post);
    
    $output = wp_json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if ($output) {
        echo "\n<!-- Cite Plugin: JSON-LD Structured Data -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo $output . "\n";
        echo '</script>' . "\n";
        echo "<!-- End JSON-LD Structured Data -->\n\n";
    }
}

// Add Open Graph academic tags
add_action('wp_head', 'wpcp_add_og_academic_tags', 5);

function wpcp_add_og_academic_tags() {
    global $post, $wpcp_setting;
    
    if (!is_singular() || !isset($wpcp_setting['show_og_academic']) || $wpcp_setting['show_og_academic'] !== 'yes') {
        return;
    }
    
    if (!isset($post) || !is_object($post)) {
        return;
    }
    
    // Only output on enabled post types
    $allowed_types = isset($wpcp_setting['post_types']) ? (array)$wpcp_setting['post_types'] : array('post');
    if (!in_array(get_post_type(), $allowed_types, true)) {
        return;
    }
    
    $date_published = get_the_date('c');
    $date_modified = get_the_modified_date('c');
    $author_id = absint($post->post_author);
    $author_url = get_author_posts_url($author_id);
    
    // Get primary category
    $categories = get_the_category($post->ID);
    $primary_category = !empty($categories) ? $categories[0]->name : '';
    
    /**
     * Filter Open Graph academic tag data before output.
     *
     * @param array   $og_data Array of OG property => content pairs.
     * @param WP_Post $post    The current post object.
     */
    $og_data = apply_filters('wpcp_og_academic_data', array(
        'og:type' => 'article',
        'article:published_time' => $date_published,
        'article:modified_time' => $date_modified,
        'article:author' => $author_url,
        'article:section' => $primary_category,
    ), $post);
    
    echo "\n<!-- Cite Plugin: Open Graph Academic Tags -->\n";
    
    foreach ($og_data as $property => $content) {
        if (!empty($content)) {
            echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
        }
    }
    
    echo "<!-- End Open Graph Academic Tags -->\n\n";
}

// =============================================================================
// AJAX HANDLER FOR ANALYTICS
// =============================================================================

add_action('wp_ajax_wpcp_track_analytics', 'wpcp_track_analytics');
add_action('wp_ajax_nopriv_wpcp_track_analytics', 'wpcp_track_analytics');

/**
 * Detect user consent from common cookie consent plugin cookies.
 *
 * Checks cookies set by popular GDPR/cookie consent plugins:
 * CookieYes, Complianz, CookieBot, GDPR Cookie Consent, and
 * the frontend consent signal sent by this plugin.
 *
 * @return bool True if analytics consent is detected, false otherwise.
 */
function wpcp_detect_consent_from_cookies() {
    // Check frontend consent signal (sent via AJAX from our own JS)
    if (isset($_POST['consent_granted']) && $_POST['consent_granted'] === '1') {
        return true;
    }

    // CookieYes / Cookie Law Info
    if (isset($_COOKIE['cookieyes-consent'])) {
        return (strpos($_COOKIE['cookieyes-consent'], 'analytics:yes') !== false);
    }

    // Complianz
    if (isset($_COOKIE['cmplz_statistics'])) {
        return ($_COOKIE['cmplz_statistics'] === 'allow');
    }

    // CookieBot
    if (isset($_COOKIE['CookieConsent'])) {
        return (strpos($_COOKIE['CookieConsent'], 'statistics:true') !== false);
    }

    // GDPR Cookie Consent (moove)
    if (isset($_COOKIE['moove_gdpr_popup'])) {
        $moove = json_decode(stripslashes($_COOKIE['moove_gdpr_popup']), true);
        return (isset($moove['thirdparty']) && $moove['thirdparty'] === '1');
    }

    return false;
}


function wpcp_track_analytics() {
    global $wpdb, $wpcp_setting;

    // Check if analytics is enabled first (most basic check)
    if (!isset($wpcp_setting['enable_analytics']) || $wpcp_setting['enable_analytics'] !== 'yes') {
        wp_send_json_success(array('message' => 'Analytics disabled'));
    }

    // GDPR Opt-out Gate
    if (apply_filters('wpcp_analytics_optout_active', false)) {
        wp_send_json_success(array('message' => 'Tracking blocked: Opt-out active'));
    }

    // GDPR Consent Gate
    if (isset($wpcp_setting['require_consent_for_analytics']) && $wpcp_setting['require_consent_for_analytics'] === 'yes') {
        /**
         * Filter to allow developers or cookie consent plugins to grant tracking permission.
         * Default checks for common consent cookies from popular plugins.
         */
        $consent_granted = apply_filters('wpcp_analytics_consent_granted', null);

        // If no filter has explicitly set consent, check common cookie consent plugins
        if ($consent_granted === null) {
            $consent_granted = wpcp_detect_consent_from_cookies();
        }

        if (!$consent_granted) {
            wp_send_json_success(array('message' => 'Tracking blocked: Consent not granted'));
        }
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'), 400);
    }
    
    check_ajax_referer('wpcp_analytics_' . $post_id, 'nonce');
    
    if (!get_post($post_id)) {
        wp_send_json_error(array('message' => 'Post not found'), 404);
    }
    
    $citation_style = isset($_POST['citation_style']) ? sanitize_text_field($_POST['citation_style']) : '';
    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    
    if ($action_type === 'export') {
        $allowed_formats = array('bibtex', 'ris', 'endnote', 'csl-json', 'cff', 'dublin-core');
        if (!in_array($citation_style, $allowed_formats, true)) {
            wp_send_json_error(array('message' => 'Invalid export format'), 400);
        }
    } else {
        $allowed_styles = array_keys(get_citation_styles());
        if (!in_array($citation_style, $allowed_styles, true)) {
            wp_send_json_error(array('message' => 'Invalid citation style'), 400);
        }
    }
    
    $allowed_actions = array('view', 'copy', 'export');
    if (!in_array($action_type, $allowed_actions, true)) {
        wp_send_json_error(array('message' => 'Invalid action type'), 400);
    }

    $cooldown_mode = isset($wpcp_setting['analytics_cooldown_mode']) ? $wpcp_setting['analytics_cooldown_mode'] : 'session';
    $identifier = '';

    if ($cooldown_mode === 'session') {
        $identifier = wp_get_session_token();
        // Fallback to cookie if no session token (for non-logged in users)
        if (!$identifier && isset($_COOKIE[USER_COOKIE])) {
            $identifier = md5($_COOKIE[USER_COOKIE]);
        }
    } elseif ($cooldown_mode === 'ip_hash') {
        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $identifier = md5($remote_addr);
    }

    $transient_key = 'wpcp_track_' . $action_type . '_' . $post_id . '_' . $identifier;

    if ($cooldown_mode !== 'none' && get_transient($transient_key)) {
        wp_send_json_success(array('message' => 'Already tracked'));
    }

    $cooldown = ($action_type === 'view') ? MINUTE_IN_SECONDS : 10;
    if ($cooldown_mode !== 'none') {
        set_transient($transient_key, 1, $cooldown);
    }
    
    $table_name = $wpdb->prefix . 'cite_analytics';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'post_id' => $post_id,
            'citation_style' => $citation_style,
            'action_type' => $action_type
        ),
        array('%d', '%s', '%s')
    );
    
    if (false === $result) {
        wp_send_json_error(array('message' => 'Failed to track analytics'), 500);
    }
    
    wp_send_json_success(array('message' => 'Analytics tracked successfully'));
}
