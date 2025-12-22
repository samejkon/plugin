<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [stc_detail]
 * Hiển thị chi tiết delivery record
 */
if (!function_exists('stc_detail_shortcode')) {
    function stc_detail_shortcode()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['id'])) {
            $redirect_url = add_query_arg('view', 'mypage');
            wp_safe_redirect($redirect_url);
            exit;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $delivery_id = intval($_GET['id']);
        $post = get_post($delivery_id);

        if (!$post || $post->post_type !== 'stc_delivery') {
            $redirect_url = add_query_arg('view', 'mypage');
            wp_safe_redirect($redirect_url);
            exit;
        }

        // Check if this is read-only mode (viewing other user's profile)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $is_readonly = isset($_GET['readonly']) && $_GET['readonly'] == '1';
        
        $current_user = stc_get_current_user();
        $post_user_id = get_post_meta($delivery_id, 'user_id', true);

        // If not readonly, check ownership
        if (!$is_readonly && (!$current_user || $current_user['id'] != $post_user_id)) {
            $redirect_url = add_query_arg('view', 'mypage');
            wp_safe_redirect($redirect_url);
            exit;
        }

        $delivery_date = get_post_meta($delivery_id, 'delivery_date', true);
        $end_date = get_post_meta($delivery_id, 'end_date', true);
        $start_time = get_post_meta($delivery_id, 'start_time', true);
        $end_time = get_post_meta($delivery_id, 'end_time', true);
        $total_sales = get_post_meta($delivery_id, 'total_sales', true);
        $before_screenshot = get_post_meta($delivery_id, 'before_screenshot', true);
        $after_screenshot = get_post_meta($delivery_id, 'after_screenshot', true);
        $memo = get_post_meta($delivery_id, 'memo', true);

        if (function_exists('get_permalink') && get_the_ID()) {
            $current_url = get_permalink();
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $current_url = home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
        } else {
            $current_url = home_url('/');
        }
        
        // If readonly, back to profile page; otherwise back to mypage
        if ($is_readonly) {
            $back_url = add_query_arg(array('view' => 'profile', 'user_id' => $post_user_id), strtok($current_url, '?'));
        } else {
            $back_url = add_query_arg('view', 'mypage', $current_url);
        }
        
        $update_url = add_query_arg(array('view' => 'update', 'id' => $delivery_id), $current_url);

    ob_start();
?>
    <div class="stc-record-page">
        <div class="stc-record-card">

            <div class="stc-confirm-content">
                <div class="stc-confirm-row">
                    <span class="stc-confirm-label"><?php echo esc_html__('配信期間', 'sale-time-checker'); ?></span>
                    <span class="stc-confirm-value">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <div><?php echo esc_html__('開始', 'sale-time-checker'); ?>　　<?php 
                                $formatted_start_date = $delivery_date ? stc_format_date_with_day($delivery_date) : '';
                                echo esc_html($formatted_start_date . ' ' . $start_time);
                            ?></div>
                            <div><?php echo esc_html__('終了', 'sale-time-checker'); ?>　　<?php 
                                $actual_end_date = $end_date ? $end_date : $delivery_date;
                                $formatted_end_date = $actual_end_date ? stc_format_date_with_day($actual_end_date) : '';
                                echo esc_html($formatted_end_date . ' ' . $end_time);
                            ?></div>
                        </div>
                    </span>
                </div>

                <div class="stc-confirm-row">
                    <span class="stc-confirm-label"><?php echo esc_html__('売上合計', 'sale-time-checker'); ?></span>
                    <span class="stc-confirm-value"><?php echo esc_html(number_format($total_sales)); ?></span>
                </div>

                <div class="stc-confirm-row">
                    <span class="stc-confirm-label"><?php echo esc_html__('メモ', 'sale-time-checker'); ?></span>
                    <span class="stc-confirm-value"><?php echo esc_html($memo); ?></span>
                </div>

                <?php if (!empty($before_screenshot)) : ?>
                <div class="stc-confirm-row stc-confirm-row--image">
                    <span class="stc-confirm-label"><?php echo esc_html__('配信前スクリーンショット', 'sale-time-checker'); ?></span>
                    <img src="<?php echo esc_url($before_screenshot); ?>" alt="Before Screenshot" class="stc-confirm-image">
                </div>
                <?php endif; ?>

                <?php if (!empty($after_screenshot)) : ?>
                <div class="stc-confirm-row stc-confirm-row--image">
                    <span class="stc-confirm-label"><?php echo esc_html__('配信後スクリーンショット', 'sale-time-checker'); ?></span>
                    <img src="<?php echo esc_url($after_screenshot); ?>" alt="After Screenshot" class="stc-confirm-image">
                </div>
                <?php endif; ?>
            </div>

            <div class="stc-confirm-actions">
                <a href="<?php echo esc_url($back_url); ?>" class="stc-confirm-btn stc-confirm-btn--edit">
                    <?php echo esc_html__('戻る', 'sale-time-checker'); ?>
                </a>

                <?php if (!$is_readonly) : ?>
                <a href="<?php echo esc_url($update_url); ?>" class="stc-confirm-btn stc-confirm-btn--save">
                    <?php echo esc_html__('編集', 'sale-time-checker'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php

    return ob_get_clean();
    }
    add_shortcode('stc_detail', 'stc_detail_shortcode');
}
