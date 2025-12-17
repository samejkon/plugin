<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [stc_my_page]
 *
 */
if (!function_exists('stc_my_page_shortcode')) {
    function stc_my_page_shortcode()
    {
        $current_user = stc_get_current_user();
        $user_name = $current_user ? $current_user['name'] : 'Guest';

    ob_start();
?>
    <div class="stc-wrapper">
        <div class="stc-my-page-container">
            <div class="stc-header">
                <p class="stc-title"><?php echo esc_html__('マイページ', 'sale-time-checker'); ?></p>
                <a href="#" class="stc-live-list"><?php echo esc_html__('ライバーリスト', 'sale-time-checker'); ?></a>
            </div>

            <div class="stc-img-my-page-container">
                <img
                    src="<?php echo esc_url('https://api.dicebear.com/8.x/lorelei/svg?seed=lorelei'); ?>"
                    alt="<?php esc_attr_e('my-page Image', 'sale-time-checker'); ?>"
                    class="stc-my-page-image">
            </div>

            <p class="stc-my-page-name"><?php echo esc_html($user_name); ?></p>

            <p class="stc-monthly-stats-title"><?php echo esc_html__('月間実績', 'sale-time-checker'); ?></p>

            <div class="stc-stats-grid">
                <div class="stc-stat-item">
                    <p class="stc-stat-value">¥0,000万</p>
                    <span class="stc-stat-label"><?php echo esc_html__('売上', 'sale-time-checker'); ?></span>
                </div>
                <div class="stc-stat-item">
                    <p class="stc-stat-value">000</p>
                    <span class="stc-stat-label"><?php echo esc_html__('配信時間', 'sale-time-checker'); ?></span>
                </div>
                <div class="stc-stat-item">
                    <p class="stc-stat-value">000</p>
                    <span class="stc-stat-label"><?php echo esc_html__('累計配信時間', 'sale-time-checker'); ?></span>
                </div>
            </div>

            <div class="btn-history">
                <a
                    href="<?php echo esc_url(add_query_arg('view', 'create')); ?>"
                    class="stc-history-button">
                    <?php echo esc_html__('配信内容の記録', 'sale-time-checker'); ?>
                </a>
            </div>

            <p class="stc-history-title"><?php echo esc_html__('配信履歴', 'sale-time-checker'); ?></p>

            <div class="stc-history">
                <div class="stc-history-head">
                    <div class="stc-history-col"><?php echo esc_html__('配信日', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"><?php echo esc_html__('時間', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"><?php echo esc_html__('売上合計', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"></div>
                </div>

                <div class="stc-history-body">
                    <div class="stc-history-item">
                        <div class="stc-history-date">2025/12/25</div>
                        <div class="stc-history-time">12:00～14:00</div>
                        <div class="stc-history-sales">¥2,000,000</div>
                        <div class="stc-history-action">
                            <a href="#" class="stc-detail-button">
                                <?php echo esc_html__('詳細', 'sale-time-checker'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php

    return ob_get_clean();
    }
    add_shortcode('stc_my_page', 'stc_my_page_shortcode');
}

/**
 * Shortcode tổng: [stc_manager]
 */
if (!function_exists('stc_manager_shortcode')) {
    function stc_manager_shortcode()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $view = 'mypage';
        if (isset($_GET['view'])) {
            $view = sanitize_text_field(wp_unslash($_GET['view']));
        }

        if (!stc_is_user_logged_in() && $view !== 'login') {
            $view = 'login';
        }

    $is_active = function ($key) use ($view) {
        return $view === $key ? 'style="background:#fff;color:#000;"' : '';
    };

    ob_start();
?>
    <div class="stc-app">
        <?php
        switch ($view) {
            case 'login':
                echo do_shortcode('[stc_login]');
                break;

            case 'create':
                echo do_shortcode('[stc_create]');
                break;

            case 'confirm':
                echo do_shortcode('[stc_confirm]');
                break;

            case 'update':
                echo do_shortcode('[stc_update]');
                break;

            case 'mypage':
            default:
                echo do_shortcode('[stc_my_page]');
                break;
        }
        ?>
    </div>
<?php

    return ob_get_clean();
    }
    add_shortcode('stc_manager', 'stc_manager_shortcode');
}
