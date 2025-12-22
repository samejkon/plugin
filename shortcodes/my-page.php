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
 * Handle delivery deletion
 */
if (!function_exists('stc_handle_delete_delivery')) {
    function stc_handle_delete_delivery()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['stc_delete_delivery']) || !isset($_POST['delivery_id'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['stc_delete_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stc_delete_nonce'])), 'stc_delete_delivery')) {
            return;
        }

        if (!stc_is_user_logged_in()) {
            return;
        }

        $current_user = stc_get_current_user();
        if (!$current_user) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $delivery_id = isset($_POST['delivery_id']) ? intval($_POST['delivery_id']) : 0;

        if (!$delivery_id) {
            return;
        }

        // Verify the delivery belongs to the current user
        $delivery = get_post($delivery_id);
        if (!$delivery || $delivery->post_type !== 'stc_delivery') {
            return;
        }

        $delivery_user_id = get_post_meta($delivery_id, 'user_id', true);
        if (intval($delivery_user_id) !== intval($current_user['id'])) {
            return;
        }

        // Delete the delivery
        wp_delete_post($delivery_id, true);

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
add_action('init', 'stc_handle_delete_delivery');

/**
 * Handle user name update
 */
if (!function_exists('stc_handle_update_user_name')) {
    function stc_handle_update_user_name()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['stc_update_name']) || !isset($_POST['user_name'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['stc_name_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stc_name_nonce'])), 'stc_update_name')) {
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
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $new_name = isset($_POST['user_name']) ? sanitize_text_field(wp_unslash($_POST['user_name'])) : '';

        if (!empty($new_name)) {
            update_post_meta($user_id, 'user_name', $new_name);
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
add_action('init', 'stc_handle_update_user_name');

/**
 * Shortcode: [stc_my_page]
 *
 */
if (!function_exists('stc_my_page_shortcode')) {
    function stc_my_page_shortcode()
    {
        stc_handle_avatar_upload();
        stc_handle_update_user_name();
        
        $current_user = stc_get_current_user();
        $user_name = $current_user ? $current_user['name'] : 'Guest';
        
        // Get user avatar
        $user_avatar = '';
        if ($current_user) {
            $user_avatar = get_post_meta($current_user['id'], 'user_avatar', true);
        }
        $avatar_url = $user_avatar ? $user_avatar : plugin_dir_url(dirname(__FILE__)) . 'assets/img/default.jpg';

        // Stats for current month and previous month
        $current_month_sales = 0;
        $current_month_hours = 0;
        $previous_month_sales = 0;
        $previous_month_hours = 0;
        
        // Total stats (all time)
        $total_sales = 0;
        $total_hours = 0;

        if ($current_user) {
            $user_id = $current_user['id'];
            
            // Get current month and previous month
            $current_month = date('Y-m');
            $previous_month = date('Y-m', strtotime('first day of last month'));
            
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
                    
                    // Use end_date if available, otherwise use delivery_date
                    $actual_end_date = $end_date ? $end_date : $delivery_date;
                    
                    // Check which month this delivery belongs to (based on end_date)
                    $end_month = $actual_end_date ? date('Y-m', strtotime($actual_end_date)) : '';
                    
                    // Calculate total stats (all time)
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
                        
                        // Count in appropriate month based on end_date
                        if ($end_month === $current_month) {
                            $current_month_sales += intval($sales);
                            $current_month_hours += $hours;
                        } elseif ($end_month === $previous_month) {
                            $previous_month_sales += intval($sales);
                            $previous_month_hours += $hours;
                        }
                    } else {
                        // Even without time, count sales in appropriate month
                        if ($end_month === $current_month) {
                            $current_month_sales += intval($sales);
                        } elseif ($end_month === $previous_month) {
                            $previous_month_sales += intval($sales);
                        }
                    }
                }
                wp_reset_postdata();
            }
        }

        $previous_month_sales_formatted = number_format($previous_month_sales);
        $previous_month_hours_formatted = number_format($previous_month_hours, 1);
        $current_month_sales_formatted = number_format($current_month_sales);
        $current_month_hours_formatted = number_format($current_month_hours, 1);
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

            <div class="stc-name-edit-container">
                <p class="stc-my-page-name" id="stc-user-name-display"><?php echo esc_html($user_name); ?></p>
                <input type="text" 
                       id="stc-user-name-input" 
                       class="stc-user-name-input" 
                       value="<?php echo esc_attr($user_name); ?>" 
                       style="display: none;">
                <?php if ($current_user) : ?>
                <button type="button" class="stc-name-edit-btn" id="stc-name-edit-btn" title="<?php echo esc_attr__('名前を編集', 'sale-time-checker'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="20" height="20">
                        <path d="M505 122.9L517.1 135C526.5 144.4 526.5 159.6 517.1 168.9L488 198.1L441.9 152L471 122.9C480.4 113.5 495.6 113.5 504.9 122.9zM273.8 320.2L408 185.9L454.1 232L319.8 366.2C316.9 369.1 313.3 371.2 309.4 372.3L250.9 389L267.6 330.5C268.7 326.6 270.8 323 273.7 320.1zM437.1 89L239.8 286.2C231.1 294.9 224.8 305.6 221.5 317.3L192.9 417.3C190.5 425.7 192.8 434.7 199 440.9C205.2 447.1 214.2 449.4 222.6 447L322.6 418.4C334.4 415 345.1 408.7 353.7 400.1L551 202.9C579.1 174.8 579.1 129.2 551 101.1L538.9 89C510.8 60.9 465.2 60.9 437.1 89zM152 128C103.4 128 64 167.4 64 216L64 488C64 536.6 103.4 576 152 576L424 576C472.6 576 512 536.6 512 488L512 376C512 362.7 501.3 352 488 352C474.7 352 464 362.7 464 376L464 488C464 510.1 446.1 528 424 528L152 528C129.9 528 112 510.1 112 488L112 216C112 193.9 129.9 176 152 176L264 176C277.3 176 288 165.3 288 152C288 138.7 277.3 128 264 128L152 128z" fill="#ffc107"/>
                    </svg>
                </button>
                <form method="post" id="stc-name-save-form" style="display: none;">
                    <?php wp_nonce_field('stc_update_name', 'stc_name_nonce'); ?>
                    <input type="hidden" name="stc_update_name" value="1">
                    <input type="hidden" name="user_name" id="stc-name-save-input">
                    <button type="submit" class="stc-name-save-btn" id="stc-name-save-btn" title="<?php echo esc_attr__('保存', 'sale-time-checker'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="20" height="20">
                            <path d="M160 144C151.2 144 144 151.2 144 160L144 480C144 488.8 151.2 496 160 496L480 496C488.8 496 496 488.8 496 480L496 237.3C496 233.1 494.3 229 491.3 226L416 150.6L416 240C416 257.7 401.7 272 384 272L224 272C206.3 272 192 257.7 192 240L192 144L160 144zM240 144L240 224L368 224L368 144L240 144zM96 160C96 124.7 124.7 96 160 96L402.7 96C419.7 96 436 102.7 448 114.7L525.3 192C537.3 204 544 220.3 544 237.3L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 160zM256 384C256 348.7 284.7 320 320 320C355.3 320 384 348.7 384 384C384 419.3 355.3 448 320 448C284.7 448 256 419.3 256 384z" fill="#ffc107"/>
                        </svg>
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <p class="stc-monthly-stats-title"><?php echo esc_html__('前月実績', 'sale-time-checker'); ?></p>

            <div class="stc-stats-grid">
                <div class="stc-stat-item">
                    <p class="stc-stat-value">¥<?php echo esc_html($previous_month_sales_formatted); ?></p>
                    <span class="stc-stat-label"><?php echo esc_html__('売上', 'sale-time-checker'); ?></span>
                </div>
                <div class="stc-stat-item">
                    <p class="stc-stat-value"><?php echo esc_html($previous_month_hours_formatted); ?></p>
                    <span class="stc-stat-label"><?php echo esc_html__('累計配信時間', 'sale-time-checker'); ?></span>
                </div>
            </div>
            
            <p class="stc-monthly-stats-title"><?php echo esc_html__('当月実績', 'sale-time-checker'); ?></p>
            
            <div class="stc-stats-grid">
                <div class="stc-stat-item">
                    <p class="stc-stat-value">¥<?php echo esc_html($current_month_sales_formatted); ?></p>
                    <span class="stc-stat-label"><?php echo esc_html__('売上', 'sale-time-checker'); ?></span>
                </div>
                <div class="stc-stat-item">
                    <p class="stc-stat-value"><?php echo esc_html($current_month_hours_formatted); ?></p>
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
                                
                                $formatted_start_date = $delivery_date ? stc_format_date_with_day($delivery_date) : '';
                                $actual_end_date = $end_date ? $end_date : $delivery_date;
                                $formatted_end_date = $actual_end_date ? stc_format_date_with_day($actual_end_date) : '';
                                
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
                                        <form method="post" class="stc-delete-form" style="display: inline;">
                                            <?php wp_nonce_field('stc_delete_delivery', 'stc_delete_nonce'); ?>
                                            <input type="hidden" name="stc_delete_delivery" value="1">
                                            <input type="hidden" name="delivery_id" value="<?php echo esc_attr($post_id); ?>">
                                            <button type="submit" 
                                                    class="stc-delete-button" 
                                                    title="<?php echo esc_attr__('削除', 'sale-time-checker'); ?>">
                                                <?php echo esc_html__('削除', 'sale-time-checker'); ?>
                                            </button>
                                        </form>
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
