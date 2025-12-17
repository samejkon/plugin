<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle logout
 */
if (!function_exists('stc_handle_logout')) {
    function stc_handle_logout()
    {
        if (isset($_GET['stc_logout']) && $_GET['stc_logout'] === '1') {
            if (isset($_SESSION['stc_user_id'])) {
                unset($_SESSION['stc_user_id']);
            }
            
            $redirect_url = remove_query_arg('stc_logout');
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
}
add_action('init', 'stc_handle_logout');

/**
 * Shortcode: [get_user_name]
 * Display login/username with logout dropdown
 */
if (!function_exists('stc_user_widget_shortcode')) {
    function stc_user_widget_shortcode()
    {
        $current_user = stc_get_current_user();
        
        ob_start();
        
        if ($current_user) {
            $user_name = $current_user['name'];
            $logout_url = add_query_arg('stc_logout', '1');
            ?>
            <div class="stc-user-widget">
                <div class="stc-user-widget__trigger">
                    <?php echo esc_html($user_name); ?>
                </div>
                <div class="stc-user-widget__dropdown">
                    <a href="<?php echo esc_url($logout_url); ?>" class="stc-user-widget__logout">
                        <?php echo esc_html__('ログアウト', 'sale-time-checker'); ?>
                    </a>
                </div>
            </div>
            <?php
        } else {
            if (function_exists('get_permalink') && get_the_ID()) {
                $current_url = get_permalink();
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $current_url = home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
            } else {
                $current_url = home_url('/');
            }
            $login_url = add_query_arg('view', 'login', $current_url);
            ?>
            <div class="stc-user-widget">
                <a href="/my-page" class="stc-user-widget__login">
                    <?php echo esc_html__('ログイン', 'sale-time-checker'); ?>
                </a>
            </div>
            <?php
        }
        
        return ob_get_clean();
    }
}
add_shortcode('get_user_name', 'stc_user_widget_shortcode');
