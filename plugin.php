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

    /**
     * AJAX: Load more deliveries for profile view (10 per page)
     */
    if (!function_exists('stc_ajax_load_more_deliveries')) {
        function stc_ajax_load_more_deliveries()
        {
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

            if (!$user_id) {
                wp_die();
            }

            $args = array(
                'post_type' => 'stc_delivery',
                'posts_per_page' => 10,
                'paged' => $page,
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

            if ($deliveries->have_posts()) {
                while ($deliveries->have_posts()) {
                    $deliveries->the_post();
                    $post_id = get_the_ID();

                    $delivery_date = get_post_meta($post_id, 'delivery_date', true);
                    $start_time = get_post_meta($post_id, 'start_time', true);
                    $end_time = get_post_meta($post_id, 'end_time', true);
                    $total_sales = get_post_meta($post_id, 'total_sales', true);

                    $formatted_date = $delivery_date ? date('Y/m/d', strtotime($delivery_date)) : '';
                    $time_range = $start_time && $end_time ? $start_time . '～' . $end_time : '';
                    $formatted_sales = $total_sales ? '¥' . number_format($total_sales) : '¥0';

                    $detail_url = add_query_arg(array('view' => 'detail', 'id' => $post_id, 'readonly' => '1'), strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?'));

                    // Output the same item markup as profile.php
                    echo '<div class="stc-history-item">';
                    echo '<div class="stc-history-date">' . esc_html($formatted_date) . '</div>';
                    echo '<div class="stc-history-time">' . esc_html($time_range) . '</div>';
                    echo '<div class="stc-history-sales">' . esc_html($formatted_sales) . '</div>';
                    echo '<div class="stc-history-action">';
                    echo '<a href="' . esc_url($detail_url) . '" class="stc-detail-button">' . esc_html__('詳細', 'sale-time-checker') . '</a>';
                    echo '</div>';
                    echo '</div>';
                }
                wp_reset_postdata();
            }

            wp_die();
        }
        add_action('wp_ajax_stc_load_more_deliveries', 'stc_ajax_load_more_deliveries');
        add_action('wp_ajax_nopriv_stc_load_more_deliveries', 'stc_ajax_load_more_deliveries');
    }

    /**
     * AJAX: Load more rankings (5 users per page) sorted by provided metric
     */
    if (!function_exists('stc_ajax_load_more_rankings')) {
        function stc_ajax_load_more_rankings()
        {
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $sort = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'sales';

            // Build users stats as in rankings shortcode
            $users_query = new WP_Query(array(
                'post_type' => 'stc_user',
                'posts_per_page' => -1,
            ));

            $users_stats = array();

            if ($users_query->have_posts()) {
                while ($users_query->have_posts()) {
                    $users_query->the_post();
                    $user_id = get_the_ID();
                    $user_name = get_post_meta($user_id, 'user_name', true);

                    $total_sales = 0;
                    $max_hours = 0;
                    $total_hours = 0;

                    $deliveries = new WP_Query(array(
                        'post_type' => 'stc_delivery',
                        'posts_per_page' => -1,
                        'meta_query' => array(
                            array(
                                'key' => 'user_id',
                                'value' => $user_id,
                                'compare' => '='
                            )
                        ),
                    ));

                    if ($deliveries->have_posts()) {
                        while ($deliveries->have_posts()) {
                            $deliveries->the_post();
                            $delivery_id = get_the_ID();

                            $start_time = get_post_meta($delivery_id, 'start_time', true);
                            $end_time = get_post_meta($delivery_id, 'end_time', true);
                            $sales = get_post_meta($delivery_id, 'total_sales', true);

                            $total_sales += intval($sales);

                            if ($start_time && $end_time) {
                                $start = strtotime($start_time);
                                $end = strtotime($end_time);
                                $hours = ($end - $start) / 3600;

                                $total_hours += $hours;

                                if ($hours > $max_hours) {
                                    $max_hours = $hours;
                                }
                            }
                        }
                        wp_reset_postdata();

                        $users_stats[] = array(
                            'user_id' => $user_id,
                            'name' => $user_name,
                            'total_sales' => $total_sales,
                            'max_hours' => $max_hours,
                            'total_hours' => $total_hours,
                        );
                    }
                }
                wp_reset_postdata();
            }

            // Sort
            if ($sort === 'max_hours') {
                usort($users_stats, function ($a, $b) {
                    return $b['max_hours'] - $a['max_hours'];
                });
            } elseif ($sort === 'total_hours') {
                usort($users_stats, function ($a, $b) {
                    return $b['total_hours'] - $a['total_hours'];
                });
            } else {
                usort($users_stats, function ($a, $b) {
                    return $b['total_sales'] - $a['total_sales'];
                });
            }

            $per_page = 5;
            $offset = ($page - 1) * $per_page;
            $slice = array_slice($users_stats, $offset, $per_page);

            $plugin_url = plugin_dir_url(__FILE__);

            foreach ($slice as $index => $user_stat) {
                $rank = $offset + $index + 1;
                $avatar_url = plugin_dir_url(dirname(__FILE__)) . 'shortcodes/assets/img/default.jpg';
                $profile_url = add_query_arg(array('view' => 'profile', 'user_id' => $user_stat['user_id']), strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?'));

                echo '<div class="rankings_item" onclick="window.location.href=\'' . esc_url($profile_url) . '\'" style="cursor: pointer;">';
                echo '<div class="stc-rankings-grid">';
                echo '<div class="stc-rankings-grid-item">';
                if ($rank == 1) {
                    echo '<img src="' . esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/img/rank1.svg') . '" alt="Rank 1" class="stc-rankings-rank-icon">';
                } elseif ($rank == 2) {
                    echo '<img src="' . esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/img/rank2.svg') . '" alt="Rank 2" class="stc-rankings-rank-icon">';
                } elseif ($rank == 3) {
                    echo '<img src="' . esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/img/rank3.svg') . '" alt="Rank 3" class="stc-rankings-rank-icon">';
                } else {
                    echo $rank;
                }
                echo '</div>';
                echo '<div class="stc-rankings-grid-item">';
                echo '<div class="stc-img-my-page-container">';
                echo '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($user_stat['name']) . '" class="stc-rankings-grid-item__image">';
                echo '</div>';
                echo '</div>';
                echo '<div class="stc-rankings-grid-item">' . esc_html($user_stat['name']) . '</div>';
                echo '</div>';
                echo '<div class="stc-stats-grid">';
                echo '<div class="stc-stat-item"><p class="stc-stat-value">¥' . esc_html(number_format($user_stat['total_sales'])) . '</p><span class="stc-stat-label">売上</span></div>';
                echo '<div class="stc-stat-item"><p class="stc-stat-value">' . esc_html(number_format($user_stat['max_hours'], 1)) . '</p><span class="stc-stat-label">配信時間</span></div>';
                echo '<div class="stc-stat-item"><p class="stc-stat-value">' . esc_html(number_format($user_stat['total_hours'], 1)) . '</p><span class="stc-stat-label">累計配信時間</span></div>';
                echo '</div>';
                echo '</div>';
            }

            wp_die();
        }
        add_action('wp_ajax_stc_load_more_rankings', 'stc_ajax_load_more_rankings');
        add_action('wp_ajax_nopriv_stc_load_more_rankings', 'stc_ajax_load_more_rankings');
    }
