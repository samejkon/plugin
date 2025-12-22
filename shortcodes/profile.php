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
        $user_avatar = get_post_meta($viewed_user_id, 'user_avatar', true);
        $avatar_url = $user_avatar ? $user_avatar : plugin_dir_url(dirname(__FILE__)) . 'assets/img/default.jpg';
        
        // Get current month
        $current_month = date('Y-m');
        
        $total_sales = 0;
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
                
                $delivery_date = get_post_meta($post_id, 'delivery_date', true);
                $end_date = get_post_meta($post_id, 'end_date', true);
                $start_time = get_post_meta($post_id, 'start_time', true);
                $end_time = get_post_meta($post_id, 'end_time', true);
                $sales = get_post_meta($post_id, 'total_sales', true);
                
                // Use end_date if available, otherwise use delivery_date
                $actual_end_date = $end_date ? $end_date : $delivery_date;
                
                // Check if delivery belongs to current month (based on end_date)
                $end_month = $actual_end_date ? date('Y-m', strtotime($actual_end_date)) : '';
                
                // Only count deliveries from current month
                if ($end_month === $current_month) {
                    $total_sales += intval($sales);
                    
                    if ($start_time && $end_time && $delivery_date) {
                        // Combine date and time for accurate calculation
                        $start_datetime = strtotime($delivery_date . ' ' . $start_time);
                        $end_datetime = strtotime($actual_end_date . ' ' . $end_time);
                        
                        // If end time is earlier than start time on the same day, it means it's next day
                        if ($end_datetime < $start_datetime && $actual_end_date === $delivery_date) {
                            // Add 24 hours if end is before start on same day
                            $end_datetime = strtotime($actual_end_date . ' ' . $end_time . ' +1 day');
                        }
                        
                        $hours = ($end_datetime - $start_datetime) / 3600;
                        $total_hours += $hours;
                    }
                }
            }
            wp_reset_postdata();
        }

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
                    src="<?php echo esc_url($avatar_url); ?>"
                    alt="<?php echo esc_attr($user_name); ?>"
                    class="stc-my-page-image">
            </div>

            <p class="stc-my-page-name"><?php echo esc_html($user_name); ?></p>

            <p class="stc-monthly-stats-title"><?php echo esc_html__('当月実績', 'sale-time-checker'); ?></p>

            <div class="stc-stats-grid">
                <div class="stc-stat-item">
                    <p class="stc-stat-value">¥<?php echo esc_html(number_format($total_sales)); ?></p>
                    <span class="stc-stat-label"><?php echo esc_html__('売上', 'sale-time-checker'); ?></span>
                </div>
                <div class="stc-stat-item">
                    <p class="stc-stat-value"><?php echo esc_html($total_hours_formatted); ?></p>
                    <span class="stc-stat-label"><?php echo esc_html__('累計配信時間', 'sale-time-checker'); ?></span>
                </div>
            </div>

            <p class="stc-history-title"><?php echo esc_html__('配信履歴', 'sale-time-checker'); ?></p>

            <div class="stc-history">
                <div class="stc-history-head">
                    <div class="stc-history-col"><?php echo esc_html__('開始', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"><?php echo esc_html__('終了', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"><?php echo esc_html__('売上合計', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"></div>
                </div>

                <div class="stc-history-body">
                    <?php
                    // Get total count first
                    $count_args = array(
                        'post_type' => 'stc_delivery',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                        'meta_query' => array(
                            array(
                                'key' => 'user_id',
                                'value' => $viewed_user_id,
                                'compare' => '='
                            )
                        ),
                    );
                    $count_query = new WP_Query($count_args);
                    $total_count = $count_query->found_posts;
                    wp_reset_postdata();
                    
                    // Get first 10 records for display
                    $args = array(
                        'post_type' => 'stc_delivery',
                        'posts_per_page' => 10,
                        'meta_query' => array(
                            array(
                                'key' => 'user_id',
                                'value' => $viewed_user_id,
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
                            $end_date = get_post_meta($post_id, 'end_date', true);
                            $start_time = get_post_meta($post_id, 'start_time', true);
                            $end_time = get_post_meta($post_id, 'end_time', true);
                            $total_sales = get_post_meta($post_id, 'total_sales', true);
                            
                            $formatted_start_date = $delivery_date ? stc_format_date_with_day($delivery_date) : '';
                            $actual_end_date = $end_date ? $end_date : $delivery_date;
                            $formatted_end_date = $actual_end_date ? stc_format_date_with_day($actual_end_date) : '';
                            
                            $start_datetime = $formatted_start_date && $start_time ? $formatted_start_date . ' ' . $start_time : '';
                            $end_datetime = $formatted_end_date && $end_time ? $formatted_end_date . ' ' . $end_time : '';
                            
                            $formatted_sales = $total_sales ? '¥' . number_format($total_sales) : '¥0';
                            
                            $detail_url = add_query_arg(array('view' => 'detail', 'id' => $post_id, 'readonly' => '1'), strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?'));
                            ?>
                            <div class="stc-history-item">
                                <div class="stc-history-start"><?php echo esc_html($start_datetime); ?></div>
                                <div>~</div>
                                <div class="stc-history-end"><?php echo esc_html($end_datetime); ?></div>
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
                <button class="stc-view-more-btn" 
                    data-page="1"
                    data-per-page="10"
                    data-user-id="<?php echo esc_attr($viewed_user_id); ?>"
                    data-total="<?php echo esc_attr($total_count); ?>"
                    data-type="profile">
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
