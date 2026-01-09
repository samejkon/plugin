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
        
        // Get selected month/year for stats and history filter from URL or use current month/year
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $selected_year = isset($_GET['history_year']) ? intval($_GET['history_year']) : date('Y');
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $selected_month = isset($_GET['history_month']) ? intval($_GET['history_month']) : date('n');
        
        // Calculate target month for filtering
        $target_month = sprintf('%04d-%02d', $selected_year, $selected_month);
        
        // Get available years for history dropdown (from deliveries)
        $history_available_years = array();
        
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
                
                // Check which month this delivery belongs to (based on end_date)
                $end_month = $actual_end_date ? date('Y-m', strtotime($actual_end_date)) : '';
                
                // Track available years
                if ($actual_end_date) {
                    $year = date('Y', strtotime($actual_end_date));
                    if (!in_array($year, $history_available_years)) {
                        $history_available_years[] = $year;
                    }
                }
                
                // Only count deliveries from selected month
                if ($end_month === $target_month) {
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
        
        // Sort years descending
        rsort($history_available_years);
        
        // If no years found, add current year
        if (empty($history_available_years)) {
            $history_available_years[] = date('Y');
        }
        
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

            <p class="stc-monthly-stats-title"><?php echo esc_html__('選択月実績', 'sale-time-checker'); ?></p>

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
            <div class="stc-history-header">    
                <p class="stc-history-title"><?php echo esc_html__('配信履歴', 'sale-time-checker'); ?></p>
                <button type="button" class="stc-history-date-trigger" id="stc-history-date-trigger">
                    <?php 
                    $month_names = array(
                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar',
                        4 => 'Apr', 5 => 'May', 6 => 'Jun',
                        7 => 'Jul', 8 => 'Aug', 9 => 'Sep',
                        10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                    );
                    ?>
                    <?php echo esc_html($selected_year . '年 ' . $selected_month . '月'. '▼'); ?>
                </button>
                <input type="hidden" id="stc-history-year-select" value="<?php echo esc_attr($selected_year); ?>">
                <input type="hidden" id="stc-history-month-select" value="<?php echo esc_attr($selected_month); ?>">
            </div>

            <!-- Calendar Picker Modal -->
            <div class="stc-date-picker-modal" id="stc-date-picker-modal">
                <div class="stc-date-picker-modal__backdrop"></div>
                <div class="stc-date-picker-modal__content">
                    <button type="button" class="stc-date-picker-modal__close" id="stc-date-picker-close" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <div class="stc-history-date-picker">
                        <div class="stc-date-picker-year">
                            <button type="button" class="stc-year-nav stc-year-prev" aria-label="Previous year">◀</button>
                            <span class="stc-year-display" id="stc-year-display"><?php echo esc_html($selected_year); ?></span>
                            <button type="button" class="stc-year-nav stc-year-next" aria-label="Next year">▶</button>
                        </div>
                        <div class="stc-date-picker-months" id="stc-month-grid">
                            <?php
                            foreach ($month_names as $m => $month_name):
                            ?>
                                <button type="button" 
                                        class="stc-month-btn <?php echo ($m == $selected_month) ? 'active' : ''; ?>" 
                                        data-month="<?php echo esc_attr($m); ?>"
                                        data-year="<?php echo esc_attr($selected_year); ?>">
                                    [ <?php echo esc_html($month_name); ?> ]
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stc-history">
                <div class="stc-history-head">
                    <div class="stc-history-col"><?php echo esc_html__('開始', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"><?php echo esc_html__('終了', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"><?php echo esc_html__('配信時間', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"><?php echo esc_html__('売上合計', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"></div>
                </div>

                <div class="stc-history-body">
                    <?php
                    // Get all deliveries for this user
                    $all_deliveries_args = array(
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
                        'order' => 'DESC',
                        'meta_type' => 'DATE'
                    );
                    
                    $all_deliveries_query = new WP_Query($all_deliveries_args);
                    
                    // Filter deliveries by selected month/year
                    $filtered_deliveries = array();
                    $total_count = 0;
                    
                    if ($all_deliveries_query->have_posts()) {
                        while ($all_deliveries_query->have_posts()) {
                            $all_deliveries_query->the_post();
                            $post_id = get_the_ID();
                            
                            $delivery_date = get_post_meta($post_id, 'delivery_date', true);
                            $end_date = get_post_meta($post_id, 'end_date', true);
                            $actual_end_date = $end_date ? $end_date : $delivery_date;
                            
                            // Check if this delivery belongs to selected month/year
                            if ($actual_end_date) {
                                $delivery_month = date('Y-m', strtotime($actual_end_date));
                                if ($delivery_month === $target_month) {
                                    $filtered_deliveries[] = $post_id;
                                    $total_count++;
                                }
                            }
                        }
                        wp_reset_postdata();
                    }
                    
                    // Get first 10 records for display
                    $delivery_ids = array_slice($filtered_deliveries, 0, 10);
                    
                    // Query only the filtered IDs
                    if (!empty($delivery_ids)) {
                        $args = array(
                            'post_type' => 'stc_delivery',
                            'post__in' => $delivery_ids,
                            'posts_per_page' => 10,
                            'orderby' => 'post__in',
                            'order' => 'DESC'
                        );
                    } else {
                        // No deliveries found for this month/year
                        $args = array(
                            'post_type' => 'stc_delivery',
                            'post__in' => array(0), // Return no results
                            'posts_per_page' => 10
                        );
                    }
                    
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
                            
                            // Calculate livestream hours
                            $livestream_hours = 0;
                            $formatted_hours = '0.0';
                            if ($start_time && $end_time && $delivery_date) {
                                $start_timestamp = strtotime($delivery_date . ' ' . $start_time);
                                $end_timestamp = strtotime($actual_end_date . ' ' . $end_time);
                                
                                // If end time is earlier than start time on the same day, it means it's next day
                                if ($end_timestamp < $start_timestamp && $actual_end_date === $delivery_date) {
                                    // Add 24 hours if end is before start on same day
                                    $end_timestamp = strtotime($actual_end_date . ' ' . $end_time . ' +1 day');
                                }
                                
                                if ($start_timestamp && $end_timestamp) {
                                    $livestream_hours = ($end_timestamp - $start_timestamp) / 3600;
                                    $formatted_hours = number_format($livestream_hours, 1);
                                }
                            }
                            
                            $formatted_sales = $total_sales ? '¥' . number_format($total_sales) : '¥0';
                            
                            $detail_url = add_query_arg(array('view' => 'detail', 'id' => $post_id, 'readonly' => '1'), strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?'));
                            ?>
                            <div class="stc-history-item">
                                <div class="stc-history-start">
                                    <div class="stc-history-date"><?php echo esc_html($formatted_start_date); ?></div>
                                    <div class="stc-history-time"><?php echo esc_html($start_time); ?></div>
                                </div>
                                <div>~</div>
                                <div class="stc-history-end">
                                    <div class="stc-history-date"><?php echo esc_html($formatted_end_date); ?></div>
                                    <div class="stc-history-time"><?php echo esc_html($end_time); ?></div>
                                </div>
                                <div class="stc-history-hours"><?php echo esc_html($formatted_hours); ?>時間</div>
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
                    data-type="profile"
                    data-filter-year="<?php echo esc_attr($selected_year); ?>"
                    data-filter-month="<?php echo esc_attr($selected_month); ?>">
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
