<?php

/**
 * Plugin Name: Manager my page
 * Description: Manager my page crud record live.
 * Version: 1.0
 * Author: Samejkon
 * Text Domain: sale-time-checker
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register post type for user
 */
if (!function_exists('stc_register_user_post_type')) {
    function stc_register_user_post_type()
    {
        $labels = array(
            'name'               => 'Users',
            'singular_name'      => 'User',
            'menu_name'          => 'Users',
            'name_admin_bar'     => 'User',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New User',
            'edit_item'          => 'Edit User',
            'new_item'           => 'New User',
            'all_items'          => 'All Users',
            'view_item'          => 'View User',
            'search_items'       => 'Search Users',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-admin-users',
            'supports'           => array('title'),
            'capability_type'    => 'post',
            'has_archive'        => false,
        );

        register_post_type('stc_user', $args);
    }
    add_action('init', 'stc_register_user_post_type');
}

/**
 * Register post type for delivery records
 */
if (!function_exists('stc_register_delivery_post_type')) {
    function stc_register_delivery_post_type()
    {
        $labels = array(
            'name'               => 'Delivery Records',
            'singular_name'      => 'Delivery Record',
            'menu_name'          => 'Delivery Records',
            'name_admin_bar'     => 'Delivery Record',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Delivery Record',
            'edit_item'          => 'Edit Delivery Record',
            'new_item'           => 'New Delivery Record',
            'all_items'          => 'All Delivery Records',
            'view_item'          => 'View Delivery Record',
            'search_items'       => 'Search Delivery Records',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-video-alt3',
            'supports'           => array('title'),
            'capability_type'    => 'post',
            'has_archive'        => false,
        );

        register_post_type('stc_delivery', $args);
    }
    add_action('init', 'stc_register_delivery_post_type');
}

/**
 * Start session cho login
 */
if (!function_exists('stc_start_session')) {
    function stc_start_session()
    {
        if (!session_id()) {
            session_start();
        }
    }
    add_action('init', 'stc_start_session');
}

/**
 * Kiểm tra user đã login chưa
 */
if (!function_exists('stc_is_user_logged_in')) {
    function stc_is_user_logged_in()
    {
        return isset($_SESSION['stc_user_id']) && !empty($_SESSION['stc_user_id']);
    }
}

if (!function_exists('stc_get_current_user')) {
    function stc_get_current_user()
    {
        if (!stc_is_user_logged_in()) {
            return null;
        }

        $user_id = intval($_SESSION['stc_user_id']);
        $user = get_post($user_id);

        if (!$user || $user->post_type !== 'stc_user') {
            return null;
        }

        return array(
            'id'    => $user->ID,
            'name'  => get_post_meta($user->ID, 'user_name', true),
            'email' => get_post_meta($user->ID, 'user_email', true),
        );
    }
}

/**
 * Logout user
 */
if (!function_exists('stc_logout_user')) {
    function stc_logout_user()
    {
        if (isset($_SESSION['stc_user_id'])) {
            unset($_SESSION['stc_user_id']);
        }
        session_destroy();
    }
}

$shortcode_files = array(
    __DIR__ . '/shortcodes/login.php',
    __DIR__ . '/shortcodes/my-page.php',
    __DIR__ . '/shortcodes/create.php',
    __DIR__ . '/shortcodes/confirm.php',
    __DIR__ . '/shortcodes/detail.php',
    __DIR__ . '/shortcodes/update.php',
    __DIR__ . '/shortcodes/rankings.php',
    __DIR__ . '/shortcodes/profile.php',
    __DIR__ . '/shortcodes/user-widget.php',
);

foreach ($shortcode_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

if (!function_exists('stc_enqueue_assets')) {
    function stc_enqueue_assets()
    {
        $plugin_url = plugin_dir_url(__FILE__);
        $plugin_dir = plugin_dir_path(__FILE__);
        $version = '1.0.0';

        $css_file = $plugin_dir . 'assets/style.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'stc-style',
                $plugin_url . 'assets/style.css',
                array(),
                $version
            );
        }

        $js_file = $plugin_dir . 'assets/script.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'stc-script',
                $plugin_url . 'assets/script.js',
                array('jquery'),
                $version,
                true
            );
            wp_localize_script('stc-script', 'stcAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('stc_ajax_nonce')
            ));
        }
    }
    add_action('wp_enqueue_scripts', 'stc_enqueue_assets');
}

/**
 * AJAX Handler: Load more delivery records
 */
if (!function_exists('stc_load_more_deliveries')) {
    function stc_load_more_deliveries()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'stc_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : 'mypage';
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Invalid user ID'));
            return;
        }
        
        $offset = ($page - 1) * $per_page;
        
        $args = array(
            'post_type' => 'stc_delivery',
            'posts_per_page' => $per_page,
            'offset' => $offset,
            'meta_query' => array(
                array(
                    'key' => 'user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'orderby' => 'meta_value_date',
            'meta_key' => 'delivery_date',
            'order' => 'DESC',
            'meta_type' => 'DATE'
        );
        
        $deliveries = new WP_Query($args);
        
        $html = '';
        $has_more = false;
        
        if ($deliveries->have_posts()) {
            while ($deliveries->have_posts()) {
                $deliveries->the_post();
                $post_id = get_the_ID();
                
                $delivery_date = get_post_meta($post_id, 'delivery_date', true);
                $end_date = get_post_meta($post_id, 'end_date', true);
                $start_time = get_post_meta($post_id, 'start_time', true);
                $end_time = get_post_meta($post_id, 'end_time', true);
                $total_sales = get_post_meta($post_id, 'total_sales', true);
                
                $formatted_start_date = $delivery_date ? date('Y/m/d', strtotime($delivery_date)) : '';
                $actual_end_date = $end_date ? $end_date : $delivery_date;
                $formatted_end_date = $actual_end_date ? date('Y/m/d', strtotime($actual_end_date)) : '';
                
                $start_datetime = $formatted_start_date && $start_time ? $formatted_start_date . ' ' . $start_time : '';
                $end_datetime = $formatted_end_date && $end_time ? $formatted_end_date . ' ' . $end_time : '';
                
                $formatted_sales = $total_sales ? '¥' . number_format($total_sales) : '¥0';
                
                // Build detail URL
                $base_url = home_url('/');
                if ($type === 'profile') {
                    $detail_url = add_query_arg(array('view' => 'detail', 'id' => $post_id, 'readonly' => '1'), $base_url);
                } else {
                    $detail_url = add_query_arg(array('view' => 'detail', 'id' => $post_id), $base_url);
                }
                
                $html .= '<div class="stc-history-item">';
                $html .= '<div class="stc-history-start">' . esc_html($start_datetime) . '</div>';
                $html .= '<div class="stc-history-end">' . esc_html($end_datetime) . '</div>';
                $html .= '<div class="stc-history-sales">' . esc_html($formatted_sales) . '</div>';
                $html .= '<div class="stc-history-action">';
                $html .= '<a href="' . esc_url($detail_url) . '" class="stc-detail-button">' . esc_html__('詳細', 'sale-time-checker') . '</a>';
                $html .= '</div>';
                $html .= '</div>';
            }
            wp_reset_postdata();
            
            // Check if there are more records
            $total_count = $deliveries->found_posts;
            $total_loaded = $offset + $deliveries->post_count;
            $has_more = ($total_loaded < $total_count);
        }
        
        wp_send_json_success(array(
            'html' => $html,
            'has_more' => $has_more,
            'next_page' => $has_more ? ($page + 1) : null
        ));
    }
}
add_action('wp_ajax_stc_load_more_deliveries', 'stc_load_more_deliveries');
add_action('wp_ajax_nopriv_stc_load_more_deliveries', 'stc_load_more_deliveries');

if (!function_exists('stc_plugin_activate')) {
    function stc_plugin_activate()
    {
        flush_rewrite_rules();
    }
    register_activation_hook(__FILE__, 'stc_plugin_activate');
}

if (!function_exists('stc_plugin_deactivate')) {
    function stc_plugin_deactivate()
    {
        flush_rewrite_rules();
    }
    register_deactivation_hook(__FILE__, 'stc_plugin_deactivate');
}
