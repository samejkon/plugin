<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [stc_rankings]
 * Streamer Rankings Page
 */
if (!function_exists('stc_rankings_shortcode')) {
    function stc_rankings_shortcode()
    {
        if (function_exists('get_permalink') && get_the_ID()) {
            $current_url = get_permalink();
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $current_url = home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
        } else {
            $current_url = home_url('/');
        }
        // Selected month/year for rankings (from URL) - defaults to current month/year
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $selected_year = isset($_GET['history_year']) ? intval($_GET['history_year']) : date('Y');
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $selected_month = isset($_GET['history_month']) ? intval($_GET['history_month']) : date('n');
        $selected_month_key = sprintf('%04d-%02d', $selected_year, $selected_month);

        $my_page_url = add_query_arg(
            array(
                'view' => 'mypage',
                'history_year' => $selected_year,
                'history_month' => $selected_month,
            ),
            $current_url
        );

        ob_start();
?>
        <div class="stc-record-page">
            <div class="stc-record-card">
                <div class="stc-rankings-title">
                    <p class="stc-record-modal__title">
                        <?php echo esc_html__('ライバーリスト', 'sale-time-checker'); ?>
                    </p>
                </div>

                <!-- Month selector (calendar) -->
                <div class="stc-history-header">
                    <p class="stc-history-title">
                        <?php echo esc_html($selected_year . '年 ' . $selected_month . '月'); ?>
                    </p>
                    <button type="button" class="stc-history-date-trigger" id="stc-history-date-trigger">
                        <?php
                        $month_names = array(
                            1 => 'Jan', 2 => 'Feb', 3 => 'Mar',
                            4 => 'Apr', 5 => 'May', 6 => 'Jun',
                            7 => 'Jul', 8 => 'Aug', 9 => 'Sep',
                            10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                        );
                        ?>
                        <?php echo esc_html($selected_year . '年 ' . $selected_month . '月' . '▼'); ?>
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
                                <?php foreach ($month_names as $m => $month_name): ?>
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

                <!--  月間売上ランキング-->
                <div class="stc-rankings-list">
                    <label class="stc-rankings-label">月間売上ランキング（<?php echo esc_html($selected_year . '年' . $selected_month . '月'); ?>）</label>

                    <?php
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
                            $total_hours = 0;

                            // Get all deliveries for this user
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

                                    $delivery_date = get_post_meta($delivery_id, 'delivery_date', true);
                                    $end_date = get_post_meta($delivery_id, 'end_date', true);
                                    $start_time = get_post_meta($delivery_id, 'start_time', true);
                                    $end_time = get_post_meta($delivery_id, 'end_time', true);
                                    $sales = get_post_meta($delivery_id, 'total_sales', true);

                                    // Use end_date if available, otherwise use delivery_date
                                    $actual_end_date = $end_date ? $end_date : $delivery_date;
                                    
                                    // Check if delivery belongs to current month (based on end_date)
                                    $end_month = $actual_end_date ? date('Y-m', strtotime($actual_end_date)) : '';
                                    
                                    // Only count deliveries from current month
                                    if ($end_month === $selected_month_key) {
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
                                
                                // Only add user to stats if they have deliveries in current month
                                if ($total_sales > 0 || $total_hours > 0) {
                                    $users_stats[] = array(
                                        'user_id' => $user_id,
                                        'name' => $user_name,
                                        'total_sales' => $total_sales,
                                        'total_hours' => $total_hours,
                                    );
                                }
                            }
                        }
                        wp_reset_postdata();
                    }

                    usort($users_stats, function ($a, $b) {
                        return $b['total_sales'] - $a['total_sales'];
                    });

                    $plugin_url = plugin_dir_url(dirname(__FILE__));
                    $total_users = count($users_stats);
                    ?>

                    <div class="stc-rankings-item">
                        <?php
                        $rank = 1;
                        foreach ($users_stats as $user_stat) {
                            $item_class = $rank > 5 ? 'rankings_item rankings_item-hidden' : 'rankings_item';
                            $user_avatar = get_post_meta($user_stat['user_id'], 'user_avatar', true);
                            $avatar_url = $user_avatar ? $user_avatar : plugin_dir_url(dirname(__FILE__)) . 'assets/img/default.jpg';
                            $profile_url = add_query_arg(
                                array(
                                    'view' => 'profile',
                                    'user_id' => $user_stat['user_id'],
                                    'history_year' => $selected_year,
                                    'history_month' => $selected_month,
                                ),
                                strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?')
                            );
                        ?>
                            <div class="<?php echo esc_attr($item_class); ?>" onclick="window.location.href='<?php echo esc_url($profile_url); ?>'" style="cursor: pointer;">
                                <div class="stc-rankings-grid">
                                    <div class="stc-rankings-grid-item">
                                        <?php if ($rank == 1) : ?>
                                            <img src="<?php echo esc_url($plugin_url . 'assets/img/rank1.svg'); ?>" alt="Rank 1" class="stc-rankings-rank-icon">
                                        <?php elseif ($rank == 2) : ?>
                                            <img src="<?php echo esc_url($plugin_url . 'assets/img/rank2.svg'); ?>" alt="Rank 2" class="stc-rankings-rank-icon">
                                        <?php elseif ($rank == 3) : ?>
                                            <img src="<?php echo esc_url($plugin_url . 'assets/img/rank3.svg'); ?>" alt="Rank 3" class="stc-rankings-rank-icon">
                                        <?php else : ?>
                                            <?php echo $rank; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="stc-rankings-grid-item">
                                        <div class="stc-img-my-page-container">
                                            <img
                                                src="<?php echo esc_url($avatar_url); ?>"
                                                alt="<?php echo esc_attr($user_stat['name']); ?>"
                                                class="stc-rankings-grid-item__image">
                                        </div>
                                    </div>
                                    <div class="stc-rankings-grid-item"><?php echo esc_html($user_stat['name']); ?></div>
                                </div>
                                <div class="stc-stats-grid">
                                    <div class="stc-stat-item">
                                        <p class="stc-stat-value">¥<?php echo esc_html(number_format($user_stat['total_sales'])); ?></p>
                                        <span class="stc-stat-label">売上</span>
                                    </div>
                                    <div class="stc-stat-item">
                                        <p class="stc-stat-value"><?php echo esc_html(number_format($user_stat['total_hours'], 1)); ?></p>
                                        <span class="stc-stat-label">累計配信時間</span>
                                    </div>
                                </div>
                            </div>
                        <?php
                            $rank++;
                        }

                        if (empty($users_stats)) {
                        ?>
                            <div class="stc-rankings-empty">
                                <p>データがありません。</p>
                            </div>
                        <?php
                        }
                        ?>

                        <?php if ($total_users > 5) : ?>
                            <div class="rankings-view-more-container">
                                <button class="rankings-view-more-btn" 
                                    data-items-per-load="5" 
                                    data-hidden-class="rankings_item-hidden"
                                    data-container-class="rankings-view-more-container">
                                    VIEW MORE
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!--  累計配信時間ランキング-->
                <div class="stc-rankings-list">
                    <label class="stc-rankings-label">累計配信時間ランキング（<?php echo esc_html($selected_year . '年' . $selected_month . '月'); ?>）</label>

                    <?php
                    $total_hours_stats = $users_stats;
                    usort($total_hours_stats, function ($a, $b) {
                        return $b['total_hours'] - $a['total_hours'];
                    });
                    ?>

                    <div class="stc-rankings-item">
                        <?php
                        $rank = 1;
                        foreach ($total_hours_stats as $user_stat) {
                            $item_class = $rank > 5 ? 'rankings_item rankings_item-hidden-total' : 'rankings_item';
                            $user_avatar = get_post_meta($user_stat['user_id'], 'user_avatar', true);
                            $avatar_url = $user_avatar ? $user_avatar : plugin_dir_url(dirname(__FILE__)) . 'assets/img/default.jpg';
                            $profile_url = add_query_arg(
                                array(
                                    'view' => 'profile',
                                    'user_id' => $user_stat['user_id'],
                                    'history_year' => $selected_year,
                                    'history_month' => $selected_month,
                                ),
                                strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?')
                            );
                        ?>
                            <div class="<?php echo esc_attr($item_class); ?>" onclick="window.location.href='<?php echo esc_url($profile_url); ?>'" style="cursor: pointer;">
                                <div class="stc-rankings-grid">
                                    <div class="stc-rankings-grid-item">
                                        <?php if ($rank == 1) : ?>
                                            <img src="<?php echo esc_url($plugin_url . 'assets/img/rank1.svg'); ?>" alt="Rank 1" class="stc-rankings-rank-icon">
                                        <?php elseif ($rank == 2) : ?>
                                            <img src="<?php echo esc_url($plugin_url . 'assets/img/rank2.svg'); ?>" alt="Rank 2" class="stc-rankings-rank-icon">
                                        <?php elseif ($rank == 3) : ?>
                                            <img src="<?php echo esc_url($plugin_url . 'assets/img/rank3.svg'); ?>" alt="Rank 3" class="stc-rankings-rank-icon">
                                        <?php else : ?>
                                            <?php echo $rank; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="stc-rankings-grid-item">
                                        <div class="stc-img-my-page-container">
                                            <img
                                                src="<?php echo esc_url($avatar_url); ?>"
                                                alt="<?php echo esc_attr($user_stat['name']); ?>"
                                                class="stc-rankings-grid-item__image">
                                        </div>
                                    </div>
                                    <div class="stc-rankings-grid-item"><?php echo esc_html($user_stat['name']); ?></div>
                                </div>
                                <div class="stc-stats-grid">
                                    <div class="stc-stat-item">
                                        <p class="stc-stat-value">¥<?php echo esc_html(number_format($user_stat['total_sales'])); ?></p>
                                        <span class="stc-stat-label">売上</span>
                                    </div>
                                    <div class="stc-stat-item">
                                        <p class="stc-stat-value"><?php echo esc_html(number_format($user_stat['total_hours'], 1)); ?></p>
                                        <span class="stc-stat-label">累計配信時間</span>
                                    </div>
                                </div>
                            </div>
                        <?php
                            $rank++;
                        }

                        if (empty($total_hours_stats)) {
                        ?>
                            <div class="stc-rankings-empty">
                                <p>データがありません。</p>
                            </div>
                        <?php
                        }
                        ?>

                        <?php if ($total_users > 5) : ?>
                            <div class="rankings-view-more-container">
                                <button class="rankings-view-more-btn" 
                                    data-items-per-load="5" 
                                    data-hidden-class="rankings_item-hidden-total"
                                    data-container-class="rankings-view-more-container">
                                    VIEW MORE
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="stc-confirm-actions">
            <a href="/my-page" class="stc-btn-back-to-mypage">
                <?php echo esc_html__('戻る', 'sale-time-checker'); ?>
            </a>
        </div>
<?php

        return ob_get_clean();
    }
    add_shortcode('stc_rankings', 'stc_rankings_shortcode');
}
