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
        $my_page_url = add_query_arg('view', 'mypage', $current_url);

        ob_start();
?>
        <div class="stc-record-page">
            <div class="stc-record-card">
                <div class="stc-rankings-title">
                    <p class="stc-record-modal__title">
                        <?php echo esc_html__('ライバーリスト', 'sale-time-checker'); ?>
                    </p>
                </div>

                <!--  月間売上ランキング-->
                <div class="stc-rankings-list">
                    <label class="stc-rankings-label">月間売上ランキング</label>

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
                            $max_hours = 0;
                            $total_hours = 0;

                            $deliveries = new WP_Query(array(
                                'post_type' => 'stc_delivery',
                                'posts_per_page' => 5,
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
                            $avatar_url = plugin_dir_url(dirname(__FILE__)) . 'assets/img/default.jpg';
                            $profile_url = add_query_arg(array('view' => 'profile', 'user_id' => $user_stat['user_id']), strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?'));
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
                                        <p class="stc-stat-value"><?php echo esc_html(number_format($user_stat['max_hours'], 1)); ?></p>
                                        <span class="stc-stat-label">配信時間</span>
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

                <!--  配信時間ランキング-->
                <div class="stc-rankings-list">
                    <label class="stc-rankings-label">配信時間ランキング</label>

                    <?php
                    $max_hours_stats = $users_stats;
                    usort($max_hours_stats, function ($a, $b) {
                        return $b['max_hours'] - $a['max_hours'];
                    });
                    ?>

                    <div class="stc-rankings-item">
                        <?php
                        $rank = 1;
                        foreach ($max_hours_stats as $user_stat) {
                            $item_class = $rank > 5 ? 'rankings_item rankings_item-hidden-monthly' : 'rankings_item';
                            $avatar_url = plugin_dir_url(dirname(__FILE__)) . 'assets/img/default.jpg';
                            $profile_url = add_query_arg(array('view' => 'profile', 'user_id' => $user_stat['user_id']), strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?'));
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
                                        <p class="stc-stat-value"><?php echo esc_html(number_format($user_stat['max_hours'], 1)); ?></p>
                                        <span class="stc-stat-label">配信時間</span>
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

                        if (empty($max_hours_stats)) {
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
                                    data-hidden-class="rankings_item-hidden-monthly"
                                    data-container-class="rankings-view-more-container">
                                    VIEW MORE
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!--  累計配信時間ランキング-->
                <div class="stc-rankings-list">
                    <label class="stc-rankings-label">累計配信時間ランキング</label>

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
                            $avatar_url = plugin_dir_url(dirname(__FILE__)) . 'assets/img/default.jpg';
                            $profile_url = add_query_arg(array('view' => 'profile', 'user_id' => $user_stat['user_id']), strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?'));
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
                                        <p class="stc-stat-value"><?php echo esc_html(number_format($user_stat['max_hours'], 1)); ?></p>
                                        <span class="stc-stat-label">配信時間</span>
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
            <a href="<?php echo esc_url($my_page_url); ?>" class="stc-btn-back-to-mypage">
                <?php echo esc_html__('戻る', 'sale-time-checker'); ?>
            </a>
        </div>
<?php

        return ob_get_clean();
    }
    add_shortcode('stc_rankings', 'stc_rankings_shortcode');
}
