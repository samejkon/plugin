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
            <div class="stc-user-widget stc-user-widget--logged">
                <span class="stc-user-widget__username">
                    <?php echo esc_html($user_name); ?>
                </span>
                <a href="<?php echo esc_url($logout_url); ?>" class="stc-user-widget__logout-btn" title="<?php echo esc_attr__('ログアウト', 'sale-time-checker'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L160 96C107 96 64 139 64 192L64 448C64 501 107 544 160 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480C142.3 480 128 465.7 128 448L128 192C128 174.3 142.3 160 160 160L224 160zM566.6 342.6C579.1 330.1 579.1 309.8 566.6 297.3L438.6 169.3C426.1 156.8 405.8 156.8 393.3 169.3C380.8 181.8 380.8 202.1 393.3 214.6L466.7 288L256 288C238.3 288 224 302.3 224 320C224 337.7 238.3 352 256 352L466.7 352L393.3 425.4C380.8 437.9 380.8 458.2 393.3 470.7C405.8 483.2 426.1 483.2 438.6 470.7L566.6 342.7z"/></svg>
                </a>
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

/**
 * Shortcode: [logout_button]
 * Display logout button only (for menu)
 */
if (!function_exists('stc_logout_button_shortcode')) {
    function stc_logout_button_shortcode()
    {
        $current_user = stc_get_current_user();
        
        if (!$current_user) {
            return '';
        }
        
        $logout_url = add_query_arg('stc_logout', '1');
        
        ob_start();
        ?>
        <a href="<?php echo esc_url($logout_url); ?>" class="stc-logout-menu-btn" title="<?php echo esc_attr__('ログアウト', 'sale-time-checker'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M224 160C241.7 160 256 145.7 256 128C256 110.3 241.7 96 224 96L160 96C107 96 64 139 64 192L64 448C64 501 107 544 160 544L224 544C241.7 544 256 529.7 256 512C256 494.3 241.7 480 224 480L160 480C142.3 480 128 465.7 128 448L128 192C128 174.3 142.3 160 160 160L224 160zM566.6 342.6C579.1 330.1 579.1 309.8 566.6 297.3L438.6 169.3C426.1 156.8 405.8 156.8 393.3 169.3C380.8 181.8 380.8 202.1 393.3 214.6L466.7 288L256 288C238.3 288 224 302.3 224 320C224 337.7 238.3 352 256 352L466.7 352L393.3 425.4C380.8 437.9 380.8 458.2 393.3 470.7C405.8 483.2 426.1 483.2 438.6 470.7L566.6 342.7z"/></svg>
        </a>
        <?php
        return ob_get_clean();
    }
}
add_shortcode('logout_button', 'stc_logout_button_shortcode');
