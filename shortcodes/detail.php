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

        // Preserve selected month/year context when navigating back to mypage
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $history_year = isset($_GET['history_year']) ? intval($_GET['history_year']) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $history_month = isset($_GET['history_month']) ? intval($_GET['history_month']) : 0;
        
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
        $stream_brand_id = get_post_meta($delivery_id, 'stream_brand', true);
        // Get brand name from brand ID
        $stream_brand = '';
        if (!empty($stream_brand_id)) {
            $brand_post = get_post($stream_brand_id);
            if ($brand_post && $brand_post->post_type === 'brands') {
                $stream_brand = $brand_post->post_title;
            } else {
                // Fallback to ID if brand not found
                $stream_brand = $stream_brand_id;
            }
        }
        $stream_result = get_post_meta($delivery_id, 'stream_result', true);
        $stream_factor = get_post_meta($delivery_id, 'stream_factor', true);
        $stream_reason = get_post_meta($delivery_id, 'stream_reason', true);

        if (function_exists('get_permalink') && get_the_ID()) {
            $current_url = get_permalink();
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $current_url = home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
        } else {
            $current_url = home_url('/');
        }
        
        // Prefer browser referer for "戻る" button to truly return to previous page (keeps selected month context).
        $back_url = '';
        if (function_exists('wp_get_referer')) {
            $referer = wp_get_referer();
            if ($referer) {
                $home_host = function_exists('wp_parse_url') ? wp_parse_url(home_url(), PHP_URL_HOST) : '';
                $ref_host = function_exists('wp_parse_url') ? wp_parse_url($referer, PHP_URL_HOST) : '';
                if (!$home_host || !$ref_host || $home_host === $ref_host) {
                    $back_url = $referer;
                }
            }
        }

        // Fallback: If readonly, back to profile page; otherwise back to mypage
        if (empty($back_url)) {
            if ($is_readonly) {
                $back_args = array('view' => 'profile', 'user_id' => $post_user_id);
                if ($history_year > 0 && $history_month > 0) {
                    $back_args['history_year'] = $history_year;
                    $back_args['history_month'] = $history_month;
                }
                $back_url = add_query_arg($back_args, strtok($current_url, '?'));
            } else {
                $back_args = array('view' => 'mypage');
                if ($history_year > 0 && $history_month > 0) {
                    $back_args['history_year'] = $history_year;
                    $back_args['history_month'] = $history_month;
                }
                $back_url = add_query_arg($back_args, strtok($current_url, '?'));
            }
        }
        
        $update_args = array('view' => 'update', 'id' => $delivery_id);
        if ($history_year > 0 && $history_month > 0) {
            $update_args['history_year'] = $history_year;
            $update_args['history_month'] = $history_month;
        }
        $update_url = add_query_arg($update_args, strtok($current_url, '?'));

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

                <?php if (!empty($stream_brand)) : ?>
                <div class="stc-confirm-row">
                    <span class="stc-confirm-label"><?php echo esc_html__('配信したブランド', 'sale-time-checker'); ?></span>
                    <span class="stc-confirm-value"><?php echo esc_html($stream_brand); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($stream_result)) : ?>
                <div class="stc-confirm-row">
                    <span class="stc-confirm-label"><?php echo esc_html__('配信結果', 'sale-time-checker'); ?></span>
                    <span class="stc-confirm-value">
                        <?php 
                        if ($stream_result === 'success') {
                            echo '<span class="stc-result-badge stc-result-badge--success">⭕ ' . esc_html__('成功', 'sale-time-checker') . '</span>';
                        } elseif ($stream_result === 'failure') {
                            echo '<span class="stc-result-badge stc-result-badge--failure">❌ ' . esc_html__('失敗', 'sale-time-checker') . '</span>';
                        }
                        ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if (!empty($stream_factor)) : ?>
                <div class="stc-confirm-row">
                    <span class="stc-confirm-label"><?php echo esc_html__('影響した要因', 'sale-time-checker'); ?></span>
                    <span class="stc-confirm-value">
                        <?php
                        $factor_labels = array(
                            'product' => '📦 ' . esc_html__('商品', 'sale-time-checker'),
                            'price' => '💰 ' . esc_html__('価格', 'sale-time-checker'),
                            'speech' => '🗣 ' . esc_html__('話し方', 'sale-time-checker'),
                            'structure' => '🧩 ' . esc_html__('構成', 'sale-time-checker'),
                            'condition' => '😐 ' . esc_html__('コンディション', 'sale-time-checker'),
                            'impression' => '📈 ' . esc_html__('インプレッション', 'sale-time-checker'),
                        );
                        echo isset($factor_labels[$stream_factor]) ? esc_html($factor_labels[$stream_factor]) : esc_html($stream_factor);
                        ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if (!empty($stream_reason)) : ?>
                <div class="stc-confirm-row">
                    <span class="stc-confirm-label"><?php echo esc_html__('理由', 'sale-time-checker'); ?></span>
                    <span class="stc-confirm-value"><?php echo esc_html($stream_reason); ?></span>
                </div>
                <?php endif; ?>

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
