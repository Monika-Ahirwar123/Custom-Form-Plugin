<?php
/*
Plugin Name: CustomFormPlugin
Description: A custom WordPress plugin to manage form submissions and display entries.
Version: 1.0
Author: Monika Ahirwar
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class CustomFormPlugin {
    public function __construct() {
        
        $this->load_dependencies();
        // Hook to initialize custom post type
        add_action('init', [$this, 'register_custom_post_type']);
        // Hook for the [custom_form] shortcode
        add_shortcode('custom_form', [$this, 'render_form']);
        // Hook for AJAX form submission
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_submit_form', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_submit_form', [$this, 'handle_form_submission']);
        // Hook for [form_entries] shortcode
        add_shortcode('form_entries', [$this, 'render_form_entries']);
        // Hook to add and render custom admin columns
        add_filter('manage_formentries_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_formentries_posts_custom_column', [$this, 'render_custom_columns'], 10, 2);
      

    }

    // Add columns to the admin table
    public function add_custom_columns($columns) {
        $columns['email'] = 'Email';
        $columns['message'] = 'Message';
        $columns['timestamp'] = 'Date Submitted';
        return $columns;
    }

    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'email':
                echo esc_html(get_post_meta($post_id, 'email', true));
                break;
            case 'message':
                echo esc_html(get_post_meta($post_id, 'message', true));
                break;
            case 'timestamp':
                echo esc_html(get_post_meta($post_id, 'timestamp', true));
                break;
        }
    }
    

    public function register_custom_post_type() {
        register_post_type('formentries', [
            'labels' => [
                'name' => 'Form Entries',
                'singular_name' => 'Form Entry',
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => 'post',
        ]);
    }

    
    public function render_form() {
        ob_start();
        ?>
        <div>
        <form id="customForm" method="post">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br><br>
            <label for="message">Message:</label>
            <textarea id="message" name="message" required></textarea><br><br>
            <button type="submit">Submit</button>
        </form>
        </div>
        <div id="formResponse"></div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('custom-form-plugin-script', plugin_dir_url(__FILE__) . 'custom-form.js', ['jquery'], null, true);
        wp_localize_script('custom-form-plugin-script', 'customFormAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }

    public function handle_form_submission() {
        if (isset($_POST['name'], $_POST['email'], $_POST['message'])) {
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $message = sanitize_textarea_field($_POST['message']);

            if (empty($name) || empty($email) || empty($message) || !is_email($email)) {
                wp_send_json_error(['message' => 'Invalid input.']);
            } else {
                $post_id = wp_insert_post([
                    'post_type' => 'formentries',
                    'post_title' => $name,
                    'post_status' => 'publish',
                    'meta_input' => [
                        'email' => $email,
                        'message' => $message,
                        'timestamp' => current_time('mysql'),
                    ],
                ]);

                if ($post_id) {
                    wp_send_json_success(['message' => 'Form submitted successfully.']);
                } else {
                    wp_send_json_error(['message' => 'Failed to save the form data.']);
                }
            }
        } else {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        wp_die();
    }

    public function render_form_entries() {
        $entries = new WP_Query(['post_type' => 'formentries', 'posts_per_page' => -1]);
        ob_start();
        ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody style="text-align: center;>
                <?php if ($entries->have_posts()) : while ($entries->have_posts()) : $entries->the_post(); ?>
                    <tr>
                        <td><?php the_title(); ?></td>
                        <td><?php echo get_post_meta(get_the_ID(), 'email', true); ?></td>
                        <td><?php echo get_post_meta(get_the_ID(), 'message', true); ?></td>
                        <td><?php echo get_post_meta(get_the_ID(), 'timestamp', true); ?></td>
                    </tr>
                <?php endwhile; endif; ?>
                <?php wp_reset_postdata(); ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
    private function load_dependencies() {
        // Include the external file.
        require_once plugin_dir_path(__FILE__) . 'template.php';
    }
}

new CustomFormPlugin();


