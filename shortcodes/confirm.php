<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('stc_handle_confirm_save')) {
    function stc_handle_confirm_save()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_POST['stc_confirm_save'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_POST['stc_confirm_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stc_confirm_nonce'])), 'stc_confirm_action')) {
            return;
        }

        if (!stc_is_user_logged_in()) {
            return;
        }

        $current_user = stc_get_current_user();
        $user_id = $current_user['id'];

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : 'create';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $delivery_id = isset($_POST['delivery_id']) ? intval($_POST['delivery_id']) : 0;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $delivery_date = isset($_POST['delivery_date']) ? sanitize_text_field(wp_unslash($_POST['delivery_date'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $end_date = isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $end_time = isset($_POST['end_time']) ? sanitize_text_field(wp_unslash($_POST['end_time'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $total_sales = isset($_POST['total_sales']) ? intval($_POST['total_sales']) : 0;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $before_screenshot = isset($_POST['before_screenshot']) ? sanitize_text_field(wp_unslash($_POST['before_screenshot'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $after_screenshot = isset($_POST['after_screenshot']) ? sanitize_text_field(wp_unslash($_POST['after_screenshot'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $memo = isset($_POST['memo']) ? sanitize_textarea_field(wp_unslash($_POST['memo'])) : '';
        // stream_brand is expected to be the Brand post ID (CPT: brands)
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $stream_brand = isset($_POST['stream_brand']) ? (string) absint(wp_unslash($_POST['stream_brand'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $stream_result = isset($_POST['stream_result']) ? sanitize_text_field(wp_unslash($_POST['stream_result'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $stream_factor = isset($_POST['stream_factor']) ? sanitize_text_field(wp_unslash($_POST['stream_factor'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $stream_reason = isset($_POST['stream_reason']) ? sanitize_textarea_field(wp_unslash($_POST['stream_reason'])) : '';

        // Preserve history context (month/year) so redirect can return to the same selected month.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $history_year = isset($_POST['history_year']) ? intval(wp_unslash($_POST['history_year'])) : 0;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $history_month = isset($_POST['history_month']) ? intval(wp_unslash($_POST['history_month'])) : 0;

        if ($mode === 'update' && $delivery_id) {
            // Update existing post
            wp_update_post([
                'ID' => $delivery_id,
                'post_title' => $delivery_date . ' - ' . $start_time . '~' . $end_time,
            ]);

            update_post_meta($delivery_id, 'delivery_date', $delivery_date);
            update_post_meta($delivery_id, 'end_date', $end_date);
            update_post_meta($delivery_id, 'start_time', $start_time);
            update_post_meta($delivery_id, 'end_time', $end_time);
            update_post_meta($delivery_id, 'total_sales', $total_sales);
            update_post_meta($delivery_id, 'before_screenshot', $before_screenshot);
            update_post_meta($delivery_id, 'after_screenshot', $after_screenshot);
            update_post_meta($delivery_id, 'memo', $memo);
            update_post_meta($delivery_id, 'stream_brand', $stream_brand);
            update_post_meta($delivery_id, 'stream_result', $stream_result);
            update_post_meta($delivery_id, 'stream_factor', $stream_factor);
            update_post_meta($delivery_id, 'stream_reason', $stream_reason);
            update_post_meta($delivery_id, 'updated_date', current_time('mysql'));

            unset($_SESSION['stc_confirm_data']);

            if (function_exists('get_permalink') && get_the_ID()) {
                $base_url = get_permalink();
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $base_url = strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?');
            } else {
                $base_url = home_url('/');
            }
            $redirect_args = array('view' => 'mypage');
            if ($history_year > 0 && $history_month > 0) {
                $redirect_args['history_year'] = $history_year;
                $redirect_args['history_month'] = $history_month;
            }
            $redirect_url = add_query_arg($redirect_args, $base_url);
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            // Create new post
            $new_delivery_id = wp_insert_post([
                'post_type'   => 'stc_delivery',
                'post_title'  => $delivery_date . ' - ' . $start_time . '~' . $end_time,
                'post_status' => 'publish',
            ]);

            if ($new_delivery_id) {
                update_post_meta($new_delivery_id, 'user_id', $user_id);
                update_post_meta($new_delivery_id, 'delivery_date', $delivery_date);
                update_post_meta($new_delivery_id, 'end_date', $end_date);
                update_post_meta($new_delivery_id, 'start_time', $start_time);
                update_post_meta($new_delivery_id, 'end_time', $end_time);
                update_post_meta($new_delivery_id, 'total_sales', $total_sales);
                update_post_meta($new_delivery_id, 'before_screenshot', $before_screenshot);
                update_post_meta($new_delivery_id, 'after_screenshot', $after_screenshot);
                update_post_meta($new_delivery_id, 'memo', $memo);
                update_post_meta($new_delivery_id, 'stream_brand', $stream_brand);
                update_post_meta($new_delivery_id, 'stream_result', $stream_result);
                update_post_meta($new_delivery_id, 'stream_factor', $stream_factor);
                update_post_meta($new_delivery_id, 'stream_reason', $stream_reason);
                update_post_meta($new_delivery_id, 'created_date', current_time('mysql'));

                unset($_SESSION['stc_confirm_data']);

                if (function_exists('get_permalink') && get_the_ID()) {
                    $base_url = get_permalink();
                } elseif (isset($_SERVER['REQUEST_URI'])) {
                    $base_url = strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?');
                } else {
                    $base_url = home_url('/');
                }
                $redirect_args = array('view' => 'mypage');
                if ($history_year > 0 && $history_month > 0) {
                    $redirect_args['history_year'] = $history_year;
                    $redirect_args['history_month'] = $history_month;
                }
                $redirect_url = add_query_arg($redirect_args, $base_url);
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
    }
}
add_action('init', 'stc_handle_confirm_save');

/**
 * Shortcode: [stc_confirm]
 */
if (!function_exists('stc_confirm_shortcode')) {
    function stc_confirm_shortcode()
    {
        stc_handle_confirm_save();

        $data = isset($_SESSION['stc_confirm_data']) ? $_SESSION['stc_confirm_data'] : array();
        
        $mode = isset($data['mode']) ? $data['mode'] : 'create';
        $delivery_id = isset($data['delivery_id']) ? intval($data['delivery_id']) : 0;
        $delivery_date = isset($data['delivery_date']) ? $data['delivery_date'] : '';
        $end_date = isset($data['end_date']) ? $data['end_date'] : '';
        $start_time = isset($data['start_time']) ? $data['start_time'] : '';
        $end_time = isset($data['end_time']) ? $data['end_time'] : '';
        $total_sales = isset($data['total_sales']) ? intval($data['total_sales']) : 0;
        $before_screenshot = isset($data['before_screenshot']) ? $data['before_screenshot'] : '';
        $after_screenshot = isset($data['after_screenshot']) ? $data['after_screenshot'] : '';
        $memo = isset($data['memo']) ? $data['memo'] : '';
        $stream_brand_id = isset($data['stream_brand']) ? $data['stream_brand'] : '';
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
        $stream_result = isset($data['stream_result']) ? $data['stream_result'] : '';
        $stream_factor = isset($data['stream_factor']) ? $data['stream_factor'] : '';
        $stream_reason = isset($data['stream_reason']) ? $data['stream_reason'] : '';
        $history_year = isset($data['history_year']) ? intval($data['history_year']) : 0;
        $history_month = isset($data['history_month']) ? intval($data['history_month']) : 0;

        if (empty($data)) {
            $redirect_url = add_query_arg('view', 'create');
            wp_safe_redirect($redirect_url);
            exit;
        }

        if (function_exists('get_permalink') && get_the_ID()) {
            $current_url = get_permalink();
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $current_url = home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
        } else {
            $current_url = home_url('/');
        }
        
        if ($mode === 'update' && $delivery_id) {
            $edit_args = array('view' => 'update', 'id' => $delivery_id);
            if ($history_year > 0 && $history_month > 0) {
                $edit_args['history_year'] = $history_year;
                $edit_args['history_month'] = $history_month;
            }
            $edit_url = add_query_arg($edit_args, $current_url);
        } else {
            $edit_url = add_query_arg('view', 'create', $current_url);
        }

    ob_start();
?>
    <div class="stc-record-page">
        <div class="stc-record-card">
            <div class="stc-record-card__head">
                <p class="stc-record-modal__title">
                    <?php echo esc_html__('記録の確認', 'sale-time-checker'); ?>
                </p>
            </div>

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

                <div class="stc-confirm-row">
                    <span class="stc-confirm-label"><?php echo esc_html__('理由', 'sale-time-checker'); ?></span>
                    <span class="stc-confirm-value"><?php echo esc_html($stream_reason); ?></span>
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
                <a href="<?php echo esc_url($edit_url); ?>" class="stc-confirm-btn stc-confirm-btn--edit">
                    <?php echo esc_html__('修正', 'sale-time-checker'); ?>
                </a>

                <form method="post">
                    <?php wp_nonce_field('stc_confirm_action', 'stc_confirm_nonce'); ?>
                    <input type="hidden" name="mode" value="<?php echo esc_attr($mode); ?>">
                    <input type="hidden" name="history_year" value="<?php echo esc_attr($history_year); ?>">
                    <input type="hidden" name="history_month" value="<?php echo esc_attr($history_month); ?>">
                    <?php if ($mode === 'update' && $delivery_id) : ?>
                        <input type="hidden" name="delivery_id" value="<?php echo esc_attr($delivery_id); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="delivery_date" value="<?php echo esc_attr($delivery_date); ?>">
                    <input type="hidden" name="end_date" value="<?php echo esc_attr($end_date ? $end_date : $delivery_date); ?>">
                    <input type="hidden" name="start_time" value="<?php echo esc_attr($start_time); ?>">
                    <input type="hidden" name="end_time" value="<?php echo esc_attr($end_time); ?>">
                    <input type="hidden" name="total_sales" value="<?php echo esc_attr($total_sales); ?>">
                    <input type="hidden" name="before_screenshot" value="<?php echo esc_attr($before_screenshot); ?>">
                    <input type="hidden" name="after_screenshot" value="<?php echo esc_attr($after_screenshot); ?>">
                    <input type="hidden" name="memo" value="<?php echo esc_attr($memo); ?>">
                    <input type="hidden" name="stream_brand" value="<?php echo esc_attr($stream_brand_id); ?>">
                    <input type="hidden" name="stream_result" value="<?php echo esc_attr($stream_result); ?>">
                    <input type="hidden" name="stream_factor" value="<?php echo esc_attr($stream_factor); ?>">
                    <input type="hidden" name="stream_reason" value="<?php echo esc_attr($stream_reason); ?>">
                    <button type="submit" name="stc_confirm_save" class="stc-confirm-btn stc-confirm-btn--save">
                        <?php echo esc_html__('記録', 'sale-time-checker'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php

    return ob_get_clean();
    }
    add_shortcode('stc_confirm', 'stc_confirm_shortcode');
}
