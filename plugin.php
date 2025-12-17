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
                array(),
                $version,
                true
            );
        }
    }
    add_action('wp_enqueue_scripts', 'stc_enqueue_assets');
}

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
