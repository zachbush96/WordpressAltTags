<?php
/*
Plugin Name: Accessibility & AI WordPress Plugin
Description: Enhances website accessibility by generating and managing alt text for images using a locally hosted LLVM model.
Version: 1.0
Author: Your Name
Text Domain: accessibility-ai
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AccessibilityAI {
    public function __construct() {
        // Include necessary files
        require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

        // Hook to add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Register settings
        add_action('admin_init', [$this, 'register_settings']);

        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_generate_alt_text', [$this, 'ajax_generate_alt_text']);
        add_action('wp_ajax_save_alt_text', [$this, 'ajax_save_alt_text']);
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Accessibility & AI', 'accessibility-ai'),
            __('Accessibility & AI', 'accessibility-ai'),
            'manage_options',
            'accessibility-ai',
            [$this, 'admin_page'],
            'dashicons-format-image',
            20
        );

        // Submenu for settings
        add_submenu_page(
            'accessibility-ai',
            __('Settings', 'accessibility-ai'),
            __('Settings', 'accessibility-ai'),
            'manage_options',
            'accessibility-ai-settings',
            [$this, 'settings_page']
        );
    }

    public function register_settings() {
        register_setting('accessibility_ai_settings_group', 'accessibility_ai_server_ip', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'localhost',
        ]);

        add_settings_section(
            'accessibility_ai_settings_section',
            __('LLVM Server Settings', 'accessibility-ai'),
            null,
            'accessibility-ai-settings'
        );

        add_settings_field(
            'accessibility_ai_server_ip',
            __('LLVM Server IP Address', 'accessibility-ai'),
            [$this, 'server_ip_field_callback'],
            'accessibility-ai-settings',
            'accessibility_ai_settings_section'
        );
    }

    public function server_ip_field_callback() {
        $ip = get_option('accessibility_ai_server_ip', 'localhost');
        echo '<input type="text" id="accessibility_ai_server_ip" name="accessibility_ai_server_ip" value="' . esc_attr($ip) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter the IP address hosting the LLVM model.', 'accessibility-ai') . '</p>';
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_accessibility-ai') {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style('accessibility-ai-admin-css', plugin_dir_url(__FILE__) . 'admin/css/admin.css', [], '1.0');

        // Enqueue JS
        wp_enqueue_script('accessibility-ai-admin-js', plugin_dir_url(__FILE__) . 'admin/js/admin.js', ['jquery'], '1.0', true);

        // Localize script with AJAX URL and nonce
        wp_localize_script('accessibility-ai-admin-js', 'accessibilityAI', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('accessibility_ai_nonce'),
        ]);
    }

    public function admin_page() {
        // Fetch all images
        $images = get_all_images();

        ?>
        <div class="wrap">
            <h1><?php _e('Accessibility & AI - Image Alt Text Manager', 'accessibility-ai'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Image', 'accessibility-ai'); ?></th>
                        <th><?php _e('Current Alt Text', 'accessibility-ai'); ?></th>
                        <th><?php _e('Actions', 'accessibility-ai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($images as $image): ?>
                        <tr data-image-id="<?php echo esc_attr($image->image_id); ?>">
                            <td>
                                <img src="<?php echo esc_url($image->image_path); ?>" alt="" width="100">
                            </td>
                            <td>
                                <input type="text" class="alt-text-input" value="<?php echo esc_attr($image->alt_text); ?>" style="width: 100%;">
                            </td>
                            <td>
                                <button class="button generate-alt-text" data-image-id="<?php echo esc_attr($image->image_id); ?>"><?php _e('Generate Alt Text', 'accessibility-ai'); ?></button>
                                <button class="button button-primary save-alt-text" data-image-id="<?php echo esc_attr($image->image_id); ?>"><?php _e('Save', 'accessibility-ai'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Accessibility & AI Settings', 'accessibility-ai'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('accessibility_ai_settings_group');
                do_settings_sections('accessibility-ai-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function ajax_generate_alt_text() {
        check_ajax_referer('accessibility_ai_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'accessibility-ai')]);
            wp_die();
        }

        $image_id = intval($_POST['image_id']);
        $image_src = wp_get_attachment_url($image_id);

        if (!$image_src) {
            wp_send_json_error(['message' => __('Image not found.', 'accessibility-ai')]);
            wp_die();
        }

        // Get LLVM server IP from settings
        $server_ip = get_option('accessibility_ai_server_ip', 'localhost');

        // Fetch the image data
        $image_data = file_get_contents($image_src);
        if ($image_data === false) {
            wp_send_json_error(['message' => __('Failed to read image.', 'accessibility-ai')]);
            wp_die();
        }

        $base64Image = base64_encode($image_data);

        // Prepare the API request
        //$api_url = 'http://' . esc_attr($server_ip) . ':1234/v1/chat/completions';
        $api_url = ' https://408a-98-24-246-193.ngrok-free.app/v1/chat/completions';

        $payload = [
            'model' => 'xtuner/llava-llama-3-8b-v1_1-gguf',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'This is a chat between a user and an assistant. The assistant is helping the user to describe an image.'
                ],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'What’s in this image?'],
                        ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,' . $base64Image]]
                    ]
                ]
            ],
            'temperature' => .2,
            'max_tokens' => 250


        ];

        $response = wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode($payload),
            'timeout' => 600,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
            wp_die();
        }

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => __('Invalid JSON response from LLVM server.', 'accessibility-ai')]);
            wp_die();
        }

        if (!isset($json['choices'][0]['message']['content'])) {
            wp_send_json_error(['message' => __('Unexpected response format from LLVM server.', 'accessibility-ai')]);
            wp_die();
        }

        $generated_alt = sanitize_text_field($json['choices'][0]['message']['content']);

        wp_send_json_success(['alt_text' => $generated_alt]);
        wp_die();
    }

    public function ajax_save_alt_text() {
        check_ajax_referer('accessibility_ai_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'accessibility-ai')]);
            wp_die();
        }

        $image_id = intval($_POST['image_id']);
        $alt_text = sanitize_text_field($_POST['alt_text']);

        $updated = update_post_meta($image_id, '_wp_attachment_image_alt', $alt_text);

        if ($updated === false) {
            wp_send_json_error(['message' => __('Failed to update alt text.', 'accessibility-ai')]);
            wp_die();
        }

        wp_send_json_success(['message' => __('Alt text updated successfully.', 'accessibility-ai')]);
        wp_die();
    }
}

new AccessibilityAI();
