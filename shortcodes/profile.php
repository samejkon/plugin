<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [stc_profile]
 * View-only profile page for other users
 */
if (!function_exists('stc_profile_shortcode')) {
    function stc_profile_shortcode()
    {
        $viewed_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        if (!$viewed_user_id) {
            return '<p>ユーザーが見つかりません。</p>';
        }
        
        $user_post = get_post($viewed_user_id);
        if (!$user_post || $user_post->post_type !== 'stc_user') {
            return '<p>ユーザーが見つかりません。</p>';
        }
        
        $user_name = get_post_meta($viewed_user_id, 'user_name', true);
        
        $total_sales = 0;
        $max_hours = 0;
        $total_hours = 0;
        
        $all_deliveries = new WP_Query(array(
            'post_type' => 'stc_delivery',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'user_id',
                    'value' => $viewed_user_id,
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

        $max_hours_formatted = number_format($max_hours, 1);
        $total_hours_formatted = number_format($total_hours, 1);
        
        $back_url = add_query_arg('view', 'rankings', strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?'));

    ob_start();
?>
    <div class="stc-record-page">
        <div class="stc-profile-container">
            <div class="stc-profile-back-header">
                <a href="<?php echo esc_url($back_url); ?>" class="stc-back">
                    ← 戻る
                </a>
            </div>

            <div class="stc-img-my-page-container">
                <img
                    src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/img/default.jpg'); ?>"
                    alt="<?php echo esc_attr($user_name); ?>"
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
                    $args = array(
                        'post_type' => 'stc_delivery',
                        'posts_per_page' => -1,
                        'meta_query' => array(
                            array(
                                'key' => 'user_id',
                                'value' => $viewed_user_id,
                                'compare' => '='
                            )
                        ),
                        'orderby' => 'meta_value',
                        'meta_key' => 'delivery_date',
                        'order' => 'DESC'
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
                            
                            $detail_url = add_query_arg(array('view' => 'detail', 'id' => $post_id, 'readonly' => '1'), strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?'));
                            
                            $item_class = $index >= 10 ? 'stc-history-item stc-history-item-hidden' : 'stc-history-item';
                            $index++;
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
                <a href="<?php echo esc_url(add_query_arg('view', 'rankings')); ?>" class="stc-profile-back-btn">
                    <?php echo esc_html__('ライバーリストへ戻る', 'sale-time-checker'); ?>
                </a>
            </div>
        </div>
    </div>
<?php

    return ob_get_clean();
    }
}
add_shortcode('stc_profile', 'stc_profile_shortcode');
