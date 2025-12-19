<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle avatar upload
 */
if (!function_exists('stc_handle_avatar_upload')) {
    function stc_handle_avatar_upload()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['stc_upload_avatar']) || !isset($_FILES['avatar'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['stc_avatar_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stc_avatar_nonce'])), 'stc_avatar_upload')) {
            return;
        }

        if (!stc_is_user_logged_in()) {
            return;
        }

        $current_user = stc_get_current_user();
        if (!$current_user) {
            return;
        }

        $user_id = $current_user['id'];

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload = wp_handle_upload($_FILES['avatar'], array('test_form' => false));
            if (isset($upload['url'])) {
                update_post_meta($user_id, 'user_avatar', $upload['url']);
            }
        }

        if (function_exists('get_permalink') && get_the_ID()) {
            $base_url = get_permalink();
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $base_url = strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?');
        } else {
            $base_url = home_url('/');
        }
        $redirect_url = add_query_arg('view', 'mypage', $base_url);
        wp_safe_redirect($redirect_url);
        exit;
    }
}
add_action('init', 'stc_handle_avatar_upload');

/**
 * Shortcode: [stc_my_page]
 *
 */
if (!function_exists('stc_my_page_shortcode')) {
    function stc_my_page_shortcode()
    {
        stc_handle_avatar_upload();
        
        $current_user = stc_get_current_user();
        $user_name = $current_user ? $current_user['name'] : 'Guest';
        
        // Get user avatar
        $user_avatar = '';
        if ($current_user) {
            $user_avatar = get_post_meta($current_user['id'], 'user_avatar', true);
        }
        $avatar_url = $user_avatar ? $user_avatar : plugin_dir_url(dirname(__FILE__)) . 'assets/img/default.jpg';

        $total_sales = 0;
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
                    
                    $delivery_date = get_post_meta($post_id, 'delivery_date', true);
                    $end_date = get_post_meta($post_id, 'end_date', true);
                    $start_time = get_post_meta($post_id, 'start_time', true);
                    $end_time = get_post_meta($post_id, 'end_time', true);
                    $sales = get_post_meta($post_id, 'total_sales', true);
                    
                    $total_sales += intval($sales);
                    
                    if ($start_time && $end_time && $delivery_date) {
                        // Use end_date if available, otherwise use delivery_date
                        $actual_end_date = $end_date ? $end_date : $delivery_date;
                        
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
                wp_reset_postdata();
            }
        }

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
                    src="<?php echo esc_url($avatar_url); ?>"
                    alt="<?php esc_attr_e('my-page Image', 'sale-time-checker'); ?>"
                    class="stc-my-page-image"
                    id="stc-avatar-image">
                <?php if ($current_user) : ?>
                <form method="post" enctype="multipart/form-data" class="stc-avatar-upload-form">
                    <?php wp_nonce_field('stc_avatar_upload', 'stc_avatar_nonce'); ?>
                    <input type="file" name="avatar" id="stc-avatar-input" accept="image/*" style="display: none;">
                    <label for="stc-avatar-input" class="stc-avatar-camera-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                            <path d="M12 9c-1.626 0-3 1.374-3 3s1.374 3 3 3 3-1.374 3-3-1.374-3-3-3zm0 4c-.551 0-1-.449-1-1s.449-1 1-1 1 .449 1 1-.449 1-1 1z"/>
                            <path d="M20 5h-2.586l-2.707-2.707A.996.996 0 0 0 14 2H10a.996.996 0 0 0-.707.293L6.586 5H4c-1.103 0-2 .897-2 2v11c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V7c0-1.103-.897-2-2-2zM4 18V7h3c.266 0 .52-.105.707-.293L10.414 4h3.172l2.707 2.707A.996.996 0 0 0 17 7h3v11H4z"/>
                        </svg>
                    </label>
                    <input type="hidden" name="stc_upload_avatar" value="1">
                </form>
                <?php endif; ?>
            </div>

            <p class="stc-my-page-name"><?php echo esc_html($user_name); ?></p>

            <p class="stc-monthly-stats-title"><?php echo esc_html__('月間実績', 'sale-time-checker'); ?></p>

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
                    <div class="stc-history-col"><?php echo esc_html__('開始', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"><?php echo esc_html__('終了', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"><?php echo esc_html__('売上合計', 'sale-time-checker'); ?></div>
                    <div class="stc-history-col"></div>
                </div>

                <div class="stc-history-body">
                    <?php
                    $current_user = stc_get_current_user();
                    if ($current_user) {
                        $user_id = $current_user['id'];
                        
                        // Get total count first
                        $count_args = array(
                            'post_type' => 'stc_delivery',
                            'posts_per_page' => -1,
                            'fields' => 'ids',
                            'meta_query' => array(
                                array(
                                    'key' => 'user_id',
                                    'value' => $user_id,
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
                                $end_date = get_post_meta($post_id, 'end_date', true);
                                $start_time = get_post_meta($post_id, 'start_time', true);
                                $end_time = get_post_meta($post_id, 'end_time', true);
                                $total_sales = get_post_meta($post_id, 'total_sales', true);
                                
                                $formatted_start_date = $delivery_date ? date('Y/m/d', strtotime($delivery_date)) : '';
                                $actual_end_date = $end_date ? $end_date : $delivery_date;
                                $formatted_end_date = $actual_end_date ? date('Y/m/d', strtotime($actual_end_date)) : '';
                                
                                $start_datetime = $formatted_start_date && $start_time ? $formatted_start_date . ' ' . $start_time : '';
                                $end_datetime = $formatted_end_date && $end_time ? $formatted_end_date . ' ' . $end_time : '';
                                
                                $formatted_sales = $total_sales ? '¥' . number_format($total_sales) : '¥0';
                                
                                $detail_url = add_query_arg(array('view' => 'detail', 'id' => $post_id));
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
                    }
                    ?>
                </div>
            </div>
            
            <?php if (isset($total_count) && $total_count > 10) : ?>
            <div class="stc-view-more-container">
                <button class="stc-view-more-btn" 
                    data-page="1"
                    data-per-page="10"
                    data-user-id="<?php echo esc_attr($user_id); ?>"
                    data-total="<?php echo esc_attr($total_count); ?>"
                    data-type="mypage">
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
