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

        if ($mode === 'update' && $delivery_id) {
            // Update existing post
            wp_update_post([
                'ID' => $delivery_id,
                'post_title' => $delivery_date . ' - ' . $start_time . '~' . $end_time,
            ]);

            update_post_meta($delivery_id, 'delivery_date', $delivery_date);
            update_post_meta($delivery_id, 'start_time', $start_time);
            update_post_meta($delivery_id, 'end_time', $end_time);
            update_post_meta($delivery_id, 'total_sales', $total_sales);
            update_post_meta($delivery_id, 'before_screenshot', $before_screenshot);
            update_post_meta($delivery_id, 'after_screenshot', $after_screenshot);
            update_post_meta($delivery_id, 'memo', $memo);
            update_post_meta($delivery_id, 'updated_date', current_time('mysql'));

            unset($_SESSION['stc_confirm_data']);

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
                update_post_meta($new_delivery_id, 'start_time', $start_time);
                update_post_meta($new_delivery_id, 'end_time', $end_time);
                update_post_meta($new_delivery_id, 'total_sales', $total_sales);
                update_post_meta($new_delivery_id, 'before_screenshot', $before_screenshot);
                update_post_meta($new_delivery_id, 'after_screenshot', $after_screenshot);
                update_post_meta($new_delivery_id, 'memo', $memo);
                update_post_meta($new_delivery_id, 'created_date', current_time('mysql'));

                unset($_SESSION['stc_confirm_data']);

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
        $start_time = isset($data['start_time']) ? $data['start_time'] : '';
        $end_time = isset($data['end_time']) ? $data['end_time'] : '';
        $total_sales = isset($data['total_sales']) ? intval($data['total_sales']) : 0;
        $before_screenshot = isset($data['before_screenshot']) ? $data['before_screenshot'] : '';
        $after_screenshot = isset($data['after_screenshot']) ? $data['after_screenshot'] : '';
        $memo = isset($data['memo']) ? $data['memo'] : '';

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
        
        // URL to go back to edit
        if ($mode === 'update' && $delivery_id) {
            $edit_url = add_query_arg(array('view' => 'update', 'id' => $delivery_id), $current_url);
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
                    <span class="stc-confirm-label"><?php echo esc_html__('配信日', 'sale-time-checker'); ?></span>
                    <span class="stc-confirm-value"><?php echo esc_html($delivery_date); ?></span>
                </div>

                <div class="stc-confirm-row">
                    <span class="stc-confirm-label"><?php echo esc_html__('時間', 'sale-time-checker'); ?></span>
                    <span class="stc-confirm-value"><?php echo esc_html($start_time . '~' . $end_time); ?></span>
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
                <a href="<?php echo esc_url($edit_url); ?>" class="stc-confirm-btn stc-confirm-btn--edit">
                    <?php echo esc_html__('修正', 'sale-time-checker'); ?>
                </a>

                <form method="post">
                    <?php wp_nonce_field('stc_confirm_action', 'stc_confirm_nonce'); ?>
                    <input type="hidden" name="mode" value="<?php echo esc_attr($mode); ?>">
                    <?php if ($mode === 'update' && $delivery_id) : ?>
                        <input type="hidden" name="delivery_id" value="<?php echo esc_attr($delivery_id); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="delivery_date" value="<?php echo esc_attr($delivery_date); ?>">
                    <input type="hidden" name="start_time" value="<?php echo esc_attr($start_time); ?>">
                    <input type="hidden" name="end_time" value="<?php echo esc_attr($end_time); ?>">
                    <input type="hidden" name="total_sales" value="<?php echo esc_attr($total_sales); ?>">
                    <input type="hidden" name="before_screenshot" value="<?php echo esc_attr($before_screenshot); ?>">
                    <input type="hidden" name="after_screenshot" value="<?php echo esc_attr($after_screenshot); ?>">
                    <input type="hidden" name="memo" value="<?php echo esc_attr($memo); ?>">
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
