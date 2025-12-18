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

        $total_sales = 0;
        $max_hours = 0;
        $total_hours = 0;

        if ($current_user) {
            $user_id = $current_user['id'];
            
            $all_deliveries = new WP_Query(array(
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

            if ($all_deliveries->have_posts()) {
                while ($all_deliveries->have_posts()) {
                    $all_deliveries->the_post();
                    $post_id = get_the_ID();
                    
                    $start_time = get_post_meta($post_id, 'start_time', true);
                    $end_time = get_post_meta($post_id, 'end_time', true);
                    $sales = get_post_meta($post_id, 'total_sales', true);
                    
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
            }
        }

        $max_hours_formatted = number_format($max_hours, 1);
        $total_hours_formatted = number_format($total_hours, 1);

    ob_start();
?>
    <div class="stc-wrapper">
        <div class="stc-my-page-container">
            <div class="stc-header">
                <p class="stc-title"><?php echo esc_html__('マイページ', 'sale-time-checker'); ?></p>
                <a href="<?php echo esc_url(add_query_arg('view', 'rankings')); ?>" class="stc-live-list"><?php echo esc_html__('ライバーリスト', 'sale-time-checker'); ?></a>
            </div>

            <div class="stc-img-my-page-container">
                <img
                    src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/img/default.jpg'); ?>"
                    alt="<?php esc_attr_e('my-page Image', 'sale-time-checker'); ?>"
                    class="stc-my-page-image">
            </div>

            <p class="stc-my-page-name"><?php echo esc_html($user_name); ?></p>

            <p class="stc-monthly-stats-title"><?php echo esc_html__('月間実績', 'sale-time-checker'); ?></p>

            <div class="stc-stats-grid">
                <div class="stc-stat-item">
                    <p class="stc-stat-value">¥<?php echo esc_html(number_format($total_sales)); ?></p>
                    <span class="stc-stat-label"><?php echo esc_html__('売上', 'sale-time-checker'); ?></span>
                </div>
                <div class="stc-stat-item">
                    <p class="stc-stat-value"><?php echo esc_html($max_hours_formatted); ?></p>
                    <span class="stc-stat-label"><?php echo esc_html__('配信時間', 'sale-time-checker'); ?></span>
                </div>
                <div class="stc-stat-item">
                    <p class="stc-stat-value"><?php echo esc_html($total_hours_formatted); ?></p>
                    <span class="stc-stat-label"><?php echo esc_html__('累計配信時間', 'sale-time-checker'); ?></span>
                </div>
            </div>

            <div class="stc-live-list-button-container">
                <a
                    href="<?php echo esc_url(add_query_arg('view', 'create')); ?>"
                    class="stc-live-list-button">
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
                    <?php
                    $current_user = stc_get_current_user();
                    if ($current_user) {
                        $user_id = $current_user['id'];
                        
                        $args = array(
                            'post_type' => 'stc_delivery',
                            'posts_per_page' => -1,
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
                        $total_count = $deliveries->found_posts;
                        
                        if ($deliveries->have_posts()) {
                            $index = 0;
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
                                
                                $detail_url = add_query_arg(array('view' => 'detail', 'id' => $post_id));
                                
                                // First 10 items (index 0-9) are visible, rest are hidden
                                $item_class = ($index < 10) ? 'stc-history-item' : 'stc-history-item stc-history-item-hidden';
                                ?>
                                <div class="<?php echo esc_attr($item_class); ?>">
                                    <div class="stc-history-date"><?php echo esc_html($formatted_date); ?></div>
                                    <div class="stc-history-time"><?php echo esc_html($time_range); ?></div>
                                    <div class="stc-history-sales"><?php echo esc_html($formatted_sales); ?></div>
                                    <div class="stc-history-action">
                                        <a href="<?php echo esc_url($detail_url); ?>" class="stc-detail-button">
                                            <?php echo esc_html__('詳細', 'sale-time-checker'); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php
                                $index++;
                            }
                            wp_reset_postdata();
                        } else {
                            $total_count = 0;
                            ?>
                            <div class="stc-history-empty">
                                <p><?php echo esc_html__('配信履歴がありません。', 'sale-time-checker'); ?></p>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
            
            <?php if (isset($total_count) && $total_count > 10) : ?>
            <div class="stc-view-more-container">
                <button class="stc-view-more-btn" id="stc-view-more-btn">
                    VIEW MORE
                </button>
            </div>
            <?php endif; ?>

            <div class="stc-live-list-button-container">
                <a href="<?php echo esc_url(add_query_arg('view', 'rankings')); ?>" class="stc-live-list-button">
                    <?php echo esc_html__('ライバーリスト', 'sale-time-checker'); ?>
                </a>
            </div>
        </div>
    </div>
<?php

    return ob_get_clean();
    }
    add_shortcode('stc_my_page', 'stc_my_page_shortcode');
}

/**
 * Shortcode total: [stc_manager]
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

            case 'detail':
                echo do_shortcode('[stc_detail]');
                break;

            case 'update':
                echo do_shortcode('[stc_update]');
                break;

            case 'rankings':
                echo do_shortcode('[stc_rankings]');
                break;

            case 'profile':
                echo do_shortcode('[stc_profile]');
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
