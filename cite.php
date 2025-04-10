<?php
/*
Plugin Name: Cite
Plugin URI: http://wordpress.org/plugins/cite
Description: Help readers know how to cite your article correctly - use Cite plugin to display a box at the bottom of each page/post with reference information.
Version: 1.2.3
Author: Enigma Plugins
Author URI: http://enigmaplugins.com/
Text Domain: cite
*/

// Security: Prevent direct access
defined('ABSPATH') || exit;

// Localization
add_action('plugins_loaded', 'cite_load_textdomain');
function cite_load_textdomain() {
    load_plugin_textdomain('cite', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

// Default settings with filter
function cite_default_settings() {
    return apply_filters('cite_default_settings', array(
        'setting' => __('Cite this article as: {author}, "{title}," in <em>{sitename}</em>, {publication_date}, {permalink}.', 'cite')
    ));
}

// Settings initialization
add_action('admin_init', 'cite_register_settings');
function cite_register_settings() {
    register_setting('cite_settings', 'cite_settings', array(
        'sanitize_callback' => 'cite_sanitize_settings'
    ));
    
    add_option('cite_settings', cite_default_settings());
}

// Sanitization callback
function cite_sanitize_settings($input) {
    $input['setting'] = wp_kses_post($input['setting'] ?? '');
    return $input;
}

// Admin menu
add_action('admin_menu', 'cite_admin_menu');
function cite_admin_menu() {
    add_menu_page(
        __('Cite Settings', 'cite'),
        __('Cite', 'cite'),
        'manage_options',
        'cite-settings',
        'cite_settings_page',
        plugin_dir_url(__FILE__) . 'cite-icon.png',
        55
    );
}

// Settings page
function cite_settings_page() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Cite Settings', 'cite'); ?></h1>
        <?php settings_errors(); ?>
        <form method="post" action="options.php">
            <?php 
            settings_fields('cite_settings');
            $settings = get_option('cite_settings', cite_default_settings());
            ?>
            <div class="cite-admin-settings">
                <p><?php esc_html_e('Help readers know how to cite your article correctly. Use the template tags below:', 'cite'); ?></p>
                
                <textarea name="cite_settings[setting]" 
                    id="cite_settings_setting" 
                    class="large-text code" 
                    rows="5"><?php echo esc_textarea($settings['setting']); ?></textarea>

                <div class="cite-template-tags">
                    <p><strong><?php esc_html_e('Available template tags:', 'cite'); ?></strong></p>
                    <ul>
                        <li><code>{author}</code> - <?php esc_html_e('Post/page author', 'cite'); ?></li>
                        <li><code>{title}</code> - <?php esc_html_e('Post/page title', 'cite'); ?></li>
                        <li><code>{sitename}</code> - <?php esc_html_e('Site name', 'cite'); ?></li>
                        <li><code>{publication_date}</code> - <?php esc_html_e('Publish date', 'cite'); ?></li>
                        <li><code>{permalink}</code> - <?php esc_html_e('Full URL to post', 'cite'); ?></li>
                        <li><code>{date}</code> - <?php esc_html_e('Current date', 'cite'); ?></li>
                    </ul>
                </div>
                
                <?php submit_button(); ?>
            </div>
        </form>
    </div>
    <?php
}

// Shortcode implementation
add_shortcode('cite', 'cite_shortcode');
function cite_shortcode() {
    $settings = get_option('cite_settings', cite_default_settings());
    $template = $settings['setting'] ?? '';
    
    // Replacement data
    $replacements = array(
        '{author}' => get_the_author(),
        '{title}' => get_the_title(),
        '{sitename}' => get_bloginfo('name'),
        '{publication_date}' => get_the_date(),
        '{permalink}' => esc_url(get_permalink()),
        '{date}' => cite_current_date()
    );
    
    // Process template
    $output = str_replace(array_keys($replacements), array_values($replacements), $template);
    
    return sprintf(
        '<div class="cite-box">%s</div>',
        wp_kses_post($output)
    );
}

// Helper function for current date
function cite_current_date() {
    return date_i18n(get_option('date_format'));
}

// Enqueue styles
add_action('wp_enqueue_scripts', 'cite_frontend_styles');
function cite_frontend_styles() {
    wp_register_style('cite-styles', false);
    wp_enqueue_style('cite-styles');
    
    $css = "
        .cite-box {
            background: #f7f7f7;
            padding: 1.5rem;
            margin: 2rem 0;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
            font-size: 0.9em;
        }
        .cite-box em {
            font-style: italic;
        }
    ";
    
    wp_add_inline_style('cite-styles', $css);
}

// Admin styles
add_action('admin_enqueue_scripts', 'cite_admin_styles');
function cite_admin_styles($hook) {
    if ($hook !== 'toplevel_page_cite-settings') return;
    
    wp_register_style('cite-admin', false);
    wp_enqueue_style('cite-admin');
    
    $css = "
        .cite-admin-settings {
            max-width: 700px;
            margin-top: 2rem;
        }
        .cite-template-tags {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f7f7f7;
            border-radius: 4px;
        }
        .cite-template-tags code {
            background: #fff;
            padding: 2px 4px;
            border-radius: 3px;
        }
    ";
    
    wp_add_inline_style('cite-admin', $css);
}