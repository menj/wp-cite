<?php
/*
Plugin Name: Cite
Description: Professional academic citation plugin with BibTeX/RIS export, analytics, and multiple citation formats including Wikipedia.
Version: 2.1.5
Author: MENJ
Author URI: https://menj.org
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPCP_VERSION', '2.1.5');
define('WPCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPCP_ASSETS_URL', WPCP_PLUGIN_URL . 'assets/');

// Localization / Internationalization
add_action('plugins_loaded', 'wpcp_load_textdomain');

function wpcp_load_textdomain() {
    load_plugin_textdomain('cite', false, dirname(plugin_basename(__FILE__)) . '/languages/');
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
    'enable_analytics' => 'yes',
    'post_types' => array('post'),
    'excluded_posts' => '',
    'show_google_scholar' => 'yes',
    'et_al_threshold' => '3'
));

// Pulling the default settings from DB + Fallback
$wpcp_setting = wp_parse_args(get_option('wpcp_setting'), $wpcp_default);

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
    $sanitized['enable_analytics'] = (isset($input['enable_analytics']) && in_array($input['enable_analytics'], array('yes', 'no'), true)) ? $input['enable_analytics'] : 'yes';
    $sanitized['show_google_scholar'] = (isset($input['show_google_scholar']) && in_array($input['show_google_scholar'], array('yes', 'no'), true)) ? $input['show_google_scholar'] : 'yes';
    
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
    
    settings_errors('wpcp_messages');
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
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
                <p><?php esc_html_e('10 professional citation formats available. Users can switch formats using the dropdown.', 'cite'); ?></p>
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
                    
                    <div class="wpcp-feature-box">
                        <h4><?php esc_html_e('What gets tracked:', 'cite'); ?></h4>
                        <ul class="wpcp-benefits">
                            <li><?php esc_html_e('Citation format selections (APA, MLA, Wikipedia, etc.)', 'cite'); ?></li>
                            <li><?php esc_html_e('Copy to clipboard actions', 'cite'); ?></li>
                            <li><?php esc_html_e('Export downloads (BibTeX, RIS, EndNote)', 'cite'); ?></li>
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
                    global $post;
                    $temp_post = $post;
                    $post = (object) array(
                        'ID' => 0,
                        'post_title' => __('Sample Article Title', 'cite'),
                        'post_author' => get_current_user_id(),
                        'post_date' => current_time('mysql')
                    );
                    echo do_shortcode('[cite]');
                    $post = $temp_post;
                    ?>
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
                    <code>[cite show_export="false"]</code>
                    <span class="wpcp-code-desc"><?php esc_html_e('Hide export buttons', 'cite'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        'use strict';
        $('.wpcp-tab-button').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            $('.wpcp-tab-button').removeClass('active');
            $('.wpcp-tab-content').removeClass('active');
            $(this).addClass('active');
            $('#' + tab + '-tab').addClass('active');
        });
        
        $('.wpcp-radio-card input[type="radio"]').on('change', function() {
            $(this).closest('.wpcp-radio-group').find('.wpcp-radio-card').removeClass('selected');
            $(this).closest('.wpcp-radio-card').addClass('selected');
        });
    });
    </script>
    <?php
}

// Analytics page
function wpcp_analytics_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'cite'));
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'cite_analytics';
    
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
    <div class="wrap wpcp-admin-container">
        <div class="wpcp-admin">
            <div class="wpcp-tab-content active" style="display: block;">
                <div class="wpcp-section-header">
                    <h2><?php esc_html_e('Citation Analytics', 'cite'); ?></h2>
                    <p><?php esc_html_e('Track how visitors interact with your citations.', 'cite'); ?></p>
                </div>
                
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
        'apa' => '{author}. ({publication_date}). {title}. <em>{sitename}</em>. {permalink}',
        'mla' => '{author}. "{title}." <em>{sitename}</em>, {publication_date}, {permalink}',
        'chicago' => '{author}. "{title}." <em>{sitename}</em>. Last modified {date}. {permalink}',
        'harvard' => '{author}. ({publication_date}). {title}. <em>{sitename}</em>. Available at: {permalink} (Accessed: {date})',
        'vancouver' => '{author}. {title}. <em>{sitename}</em>. {publication_date}; [cited {date}]. Available from: {permalink}',
        'ieee' => '{author}, "{title}," <em>{sitename}</em>, {publication_date}. [Online]. Available: {permalink}. [Accessed: {date}]',
        'ama' => '{author}. {title}. <em>{sitename}</em>. Published {publication_date}. Accessed {date}. {permalink}',
        'asa' => '{author}. {publication_date}. "{title}." <em>{sitename}</em>. Retrieved {date} ({permalink})',
        'turabian' => '{author}. "{title}." <em>{sitename}</em>. {publication_date}. {permalink}',
        'wikipedia' => '{wikipedia_cite_web}'
    );
    
    return apply_filters('wpcp_citation_styles', $styles);
}

// Global array to store citation data for JavaScript
global $wpcp_citation_data;
$wpcp_citation_data = array();

// Shortcode function
function display_citation($atts = array()) {
    global $post, $wpcp_setting, $wpcp_citation_data;
    
    if (!isset($post) || !is_object($post)) {
        return '';
    }
    
    $atts = shortcode_atts(array(
        'style' => 'apa',
        'show_copy' => 'true',
        'show_export' => 'true',
        'show_toggle' => 'true',
        'custom_author' => ''
    ), $atts);
    
    $allowed_styles = array('apa', 'mla', 'ieee', 'harvard', 'chicago', 'vancouver', 'ama', 'asa', 'turabian', 'wikipedia');
    if (!in_array($atts['style'], $allowed_styles, true)) {
        $atts['style'] = 'apa';
    }
    
    $atts['custom_author'] = sanitize_text_field($atts['custom_author']);
    $styles = get_citation_styles();
    
    if (!empty($wpcp_setting['excluded_posts'])) {
        $excluded = array_map('absint', array_map('trim', explode(',', $wpcp_setting['excluded_posts'])));
        if (in_array(absint($post->ID), $excluded, true)) {
            return '';
        }
    }
    
    $show_toggle = ($atts['show_toggle'] === 'true' || $atts['show_toggle'] === '1') && 
                   isset($wpcp_setting['show_toggle']) && $wpcp_setting['show_toggle'] === 'yes';
    $show_copy = ($atts['show_copy'] === 'true' || $atts['show_copy'] === '1');
    $show_export = ($atts['show_export'] === 'true' || $atts['show_export'] === '1');
    
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
        'permalink' => get_permalink(),
        'permalinkUrl' => esc_url(get_permalink()),
        'language' => wpcp_get_language_code(),
        'ajaxUrl' => esc_url(admin_url('admin-ajax.php')),
        'nonce' => wp_create_nonce('wpcp_analytics_' . $post->ID)
    );
    
    $wpcp_citation_data[] = $citation_data;
    
    ob_start();
    ?>
    <div id="citation-box-<?php echo absint($post->ID); ?>" class="wpcp-citation-box" itemscope itemtype="http://schema.org/ScholarlyArticle" role="region" aria-label="<?php esc_attr_e('Citation Information', 'cite'); ?>">
        <div class="citation-header">
            <div class="citation-title">
                <svg class="wpcp-cite-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M8.5 5.5C8.5 4.12 7.38 3 6 3S3.5 4.12 3.5 5.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5zm8 0C16.5 4.12 15.38 3 14 3s-2.5 1.12-2.5 2.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5z"/></svg>
                <span><?php esc_html_e('Cite This As:', 'cite'); ?></span>
                <select class="citation-style-select" data-post-id="<?php echo absint($post->ID); ?>" aria-label="<?php esc_attr_e('Select citation style', 'cite'); ?>" data-default-style="<?php echo esc_attr($atts['style']); ?>">
                    <option value="apa" <?php selected($atts['style'], 'apa'); ?>>APA</option>
                    <option value="mla" <?php selected($atts['style'], 'mla'); ?>>MLA</option>
                    <option value="ieee" <?php selected($atts['style'], 'ieee'); ?>>IEEE</option>
                    <option value="harvard" <?php selected($atts['style'], 'harvard'); ?>>Harvard</option>
                    <option value="chicago" <?php selected($atts['style'], 'chicago'); ?>>Chicago</option>
                    <option value="vancouver" <?php selected($atts['style'], 'vancouver'); ?>>Vancouver</option>
                    <option value="ama" <?php selected($atts['style'], 'ama'); ?>>AMA</option>
                    <option value="asa" <?php selected($atts['style'], 'asa'); ?>>ASA</option>
                    <option value="turabian" <?php selected($atts['style'], 'turabian'); ?>>Turabian</option>
                    <option value="wikipedia" <?php selected($atts['style'], 'wikipedia'); ?>>Wikipedia</option>
                </select>
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
                        <button class="export-option" data-format="bibtex" data-post-id="<?php echo absint($post->ID); ?>" role="menuitem"><?php esc_html_e('Export as BibTeX', 'cite'); ?></button>
                        <button class="export-option" data-format="ris" data-post-id="<?php echo absint($post->ID); ?>" role="menuitem"><?php esc_html_e('Export as RIS', 'cite'); ?></button>
                        <button class="export-option" data-format="endnote" data-post-id="<?php echo absint($post->ID); ?>" role="menuitem"><?php esc_html_e('Export as EndNote', 'cite'); ?></button>
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
        'editor_script' => 'wpcp-block',
        'style' => 'wpcp-block-editor',
    ));
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
add_action('wp_head', 'wpcp_add_google_scholar_tags');

function wpcp_add_google_scholar_tags() {
    global $post, $wpcp_setting;
    
    if (!is_singular() || !isset($wpcp_setting['show_google_scholar']) || $wpcp_setting['show_google_scholar'] !== 'yes') {
        return;
    }
    
    if (!isset($post) || !is_object($post)) {
        return;
    }
    
    $author = wpcp_get_author_name();
    $title = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $date = get_the_date('Y/m/d');
    $url = get_permalink();
    $orcid = wpcp_get_orcid();
    $site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    echo "\n<!-- Google Scholar Meta Tags -->\n";
    echo '<meta name="citation_title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="citation_author" content="' . esc_attr($author) . '">' . "\n";
    echo '<meta name="citation_publication_date" content="' . esc_attr($date) . '">' . "\n";
    echo '<meta name="citation_journal_title" content="' . esc_attr($site_name) . '">' . "\n";
    
    if ($orcid) {
        echo '<meta name="citation_author_orcid" content="' . esc_attr($orcid) . '">' . "\n";
    }
    
    echo '<meta name="citation_fulltext_html_url" content="' . esc_url($url) . '">' . "\n";
    echo "<!-- End Google Scholar Meta Tags -->\n\n";
}

// =============================================================================
// AJAX HANDLER FOR ANALYTICS
// =============================================================================

add_action('wp_ajax_wpcp_track_analytics', 'wpcp_track_analytics');
add_action('wp_ajax_nopriv_wpcp_track_analytics', 'wpcp_track_analytics');

function wpcp_track_analytics() {
    global $wpdb, $wpcp_setting;
    
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'), 400);
    }
    
    check_ajax_referer('wpcp_analytics_' . $post_id, 'nonce');
    
    if (!isset($wpcp_setting['enable_analytics']) || $wpcp_setting['enable_analytics'] !== 'yes') {
        wp_send_json_success(array('message' => 'Analytics disabled'));
    }
    
    if (!get_post($post_id)) {
        wp_send_json_error(array('message' => 'Post not found'), 404);
    }
    
    $citation_style = isset($_POST['citation_style']) ? sanitize_text_field($_POST['citation_style']) : '';
    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    
    if ($action_type === 'export') {
        $allowed_formats = array('bibtex', 'ris', 'endnote');
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
    
    $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
    $transient_key = 'wpcp_track_' . $action_type . '_' . $post_id . '_' . md5($remote_addr);
    if (get_transient($transient_key)) {
        wp_send_json_success(array('message' => 'Already tracked'));
    }
    
    $cooldown = ($action_type === 'view') ? MINUTE_IN_SECONDS : 10;
    set_transient($transient_key, 1, $cooldown);
    
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
