<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Xử lý update delivery record
 */
if (!function_exists('stc_handle_update_delivery')) {
    function stc_handle_update_delivery()
    {
        $errors = array();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_POST['stc_update_submit'])) {
            return $errors;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_POST['stc_update_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stc_update_nonce'])), 'stc_update_action')) {
            return $errors;
        }

        if (!stc_is_user_logged_in()) {
            $errors['general'] = esc_html__('ログインが必要です。', 'sale-time-checker');
            return $errors;
        }

        $current_user = stc_get_current_user();
        $user_id = $current_user['id'];

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $delivery_id = isset($_POST['delivery_id']) ? intval($_POST['delivery_id']) : 0;
        
        if (!$delivery_id) {
            $errors['general'] = esc_html__('配信IDが無効です。', 'sale-time-checker');
            return $errors;
        }

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
        $memo = isset($_POST['memo']) ? sanitize_textarea_field(wp_unslash($_POST['memo'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $stream_brand = isset($_POST['stream_brand']) ? sanitize_text_field(wp_unslash($_POST['stream_brand'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $stream_result = isset($_POST['stream_result']) ? sanitize_text_field(wp_unslash($_POST['stream_result'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $stream_factor = isset($_POST['stream_factor']) ? sanitize_text_field(wp_unslash($_POST['stream_factor'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $stream_reason = isset($_POST['stream_reason']) ? sanitize_textarea_field(wp_unslash($_POST['stream_reason'])) : '';

        // Validate
        if (empty($delivery_date)) {
            $errors['delivery_date'] = esc_html__('配信日を入力してください。', 'sale-time-checker');
        }

        if (empty($end_date)) {
            $errors['end_date'] = esc_html__('終了日を入力してください。', 'sale-time-checker');
        }

        if (empty($start_time)) {
            $errors['start_time'] = esc_html__('開始時間を入力してください。', 'sale-time-checker');
        }

        if (empty($end_time)) {
            $errors['end_time'] = esc_html__('終了時間を入力してください。', 'sale-time-checker');
        }

        if ($total_sales <= 0) {
            $errors['total_sales'] = esc_html__('売上合計を入力してください。', 'sale-time-checker');
        }

        if (empty($stream_brand)) {
            $errors['stream_brand'] = esc_html__('配信したブランドを選択してください。', 'sale-time-checker');
        }

        if (empty($stream_result)) {
            $errors['stream_result'] = esc_html__('配信結果を選択してください。', 'sale-time-checker');
        }

        if (empty($stream_factor)) {
            $errors['stream_factor'] = esc_html__('影響した要因を選択してください。', 'sale-time-checker');
        }

        if (empty($stream_reason)) {
            $errors['stream_reason'] = esc_html__('理由を入力してください。', 'sale-time-checker');
        }

        $before_screenshot = get_post_meta($delivery_id, 'before_screenshot', true);
        $after_screenshot = get_post_meta($delivery_id, 'after_screenshot', true);

        $remove_before = isset($_POST['remove_before_screenshot']) ? sanitize_text_field(wp_unslash($_POST['remove_before_screenshot'])) : '';
        $remove_after = isset($_POST['remove_after_screenshot']) ? sanitize_text_field(wp_unslash($_POST['remove_after_screenshot'])) : '';

        if ($remove_before === '1') {
            $before_screenshot = '';
        }

        if ($remove_after === '1') {
            $after_screenshot = '';
        }

        if (isset($_FILES['before_screenshot']) && $_FILES['before_screenshot']['error'] === UPLOAD_ERR_OK) {
            $upload = wp_handle_upload($_FILES['before_screenshot'], array('test_form' => false));
            if (isset($upload['url'])) {
                $before_screenshot = $upload['url'];
            }
        }

        if (isset($_FILES['after_screenshot']) && $_FILES['after_screenshot']['error'] === UPLOAD_ERR_OK) {
            $upload = wp_handle_upload($_FILES['after_screenshot'], array('test_form' => false));
            if (isset($upload['url'])) {
                $after_screenshot = $upload['url'];
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        $_SESSION['stc_confirm_data'] = array(
            'mode' => 'update',
            'delivery_id' => $delivery_id,
            'delivery_date' => $delivery_date,
            'end_date' => $end_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'total_sales' => $total_sales,
            'before_screenshot' => $before_screenshot,
            'after_screenshot' => $after_screenshot,
            'memo' => $memo,
            'stream_brand' => $stream_brand,
            'stream_result' => $stream_result,
            'stream_factor' => $stream_factor,
            'stream_reason' => $stream_reason,
        );

        if (function_exists('get_permalink') && get_the_ID()) {
            $base_url = get_permalink();
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $base_url = strtok(home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))), '?');
        } else {
            $base_url = home_url('/');
        }
        $confirm_url = add_query_arg('view', 'confirm', $base_url);
        wp_safe_redirect($confirm_url);
        exit;
    }
}
add_action('init', 'stc_handle_update_delivery');

/**
 * Shortcode: [stc_update]
 *
 */
if (!function_exists('stc_update_shortcode')) {
    function stc_update_shortcode()
    {
        if (!isset($_GET['id'])) {
            $redirect_url = add_query_arg('view', 'mypage');
            wp_safe_redirect($redirect_url);
            exit;
        }

        $delivery_id = intval($_GET['id']);
        $post = get_post($delivery_id);

        if (!$post || $post->post_type !== 'stc_delivery') {
            $redirect_url = add_query_arg('view', 'mypage');
            wp_safe_redirect($redirect_url);
            exit;
        }

        $current_user = stc_get_current_user();
        $post_user_id = get_post_meta($delivery_id, 'user_id', true);

        if (!$current_user || $current_user['id'] != $post_user_id) {
            $redirect_url = add_query_arg('view', 'mypage');
            wp_safe_redirect($redirect_url);
            exit;
        }

        $errors = stc_handle_update_delivery();

        $session_data = isset($_SESSION['stc_confirm_data']) ? $_SESSION['stc_confirm_data'] : array();
        
        if (!empty($session_data) && isset($session_data['mode']) && $session_data['mode'] === 'update' && isset($session_data['delivery_id']) && $session_data['delivery_id'] == $delivery_id) {
            $delivery_date = $session_data['delivery_date'];
            $end_date = isset($session_data['end_date']) ? $session_data['end_date'] : '';
            $start_time = $session_data['start_time'];
            $end_time = $session_data['end_time'];
            $total_sales = $session_data['total_sales'];
            $before_screenshot = $session_data['before_screenshot'];
            $after_screenshot = $session_data['after_screenshot'];
            $memo = $session_data['memo'];
            $stream_brand = isset($session_data['stream_brand']) ? $session_data['stream_brand'] : '';
            $stream_result = isset($session_data['stream_result']) ? $session_data['stream_result'] : '';
            $stream_factor = isset($session_data['stream_factor']) ? $session_data['stream_factor'] : '';
            $stream_reason = isset($session_data['stream_reason']) ? $session_data['stream_reason'] : '';
            
            unset($_SESSION['stc_confirm_data']);
        } else {
            $delivery_date = get_post_meta($delivery_id, 'delivery_date', true);
            $end_date = get_post_meta($delivery_id, 'end_date', true);
            $start_time = get_post_meta($delivery_id, 'start_time', true);
            $end_time = get_post_meta($delivery_id, 'end_time', true);
            $total_sales = get_post_meta($delivery_id, 'total_sales', true);
            $before_screenshot = get_post_meta($delivery_id, 'before_screenshot', true);
            $after_screenshot = get_post_meta($delivery_id, 'after_screenshot', true);
            $memo = get_post_meta($delivery_id, 'memo', true);
            $stream_brand = get_post_meta($delivery_id, 'stream_brand', true);
            $stream_result = get_post_meta($delivery_id, 'stream_result', true);
            $stream_factor = get_post_meta($delivery_id, 'stream_factor', true);
            $stream_reason = get_post_meta($delivery_id, 'stream_reason', true);
        }

        if (function_exists('get_permalink') && get_the_ID()) {
            $current_url = get_permalink();
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $current_url = home_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
        } else {
            $current_url = home_url('/');
        }
        $detail_url = add_query_arg(array('view' => 'detail', 'id' => $delivery_id), $current_url);

    ob_start();
?>
    <div class="stc-record-page">
        <div class="stc-record-card">
            <div class="stc-record-card__head">
                <p class="stc-record-modal__title">
                    <?php echo esc_html__('配信内容の編集', 'sale-time-checker'); ?>
                </p>
                <a class="stc-record-back" href="<?php echo esc_url($detail_url); ?>">
                    ← <?php echo esc_html__('詳細へ戻る', 'sale-time-checker'); ?>
                </a>
            </div>

            <form class="stc-record-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('stc_update_action', 'stc_update_nonce'); ?>
                <input type="hidden" name="delivery_id" value="<?php echo esc_attr($delivery_id); ?>">
                <input type="hidden" name="current_before_screenshot" value="<?php echo esc_attr($before_screenshot); ?>">
                <input type="hidden" name="current_after_screenshot" value="<?php echo esc_attr($after_screenshot); ?>">
                <input type="hidden" name="remove_before_screenshot" id="remove-before-screenshot" value="">
                <input type="hidden" name="remove_after_screenshot" id="remove-after-screenshot" value="">

                <div class="stc-form-row">
                    <div class="stc-form-group">
                        <label class="stc-form-label" for="stc-update-date">
                            <?php echo esc_html__('配信日', 'sale-time-checker'); ?>
                        </label>
                        <input 
                            type="date" 
                            id="stc-update-date" 
                            name="delivery_date"
                            class="stc-form-input"
                            value="<?php echo esc_attr($delivery_date); ?>"
                            required>
                        <?php if (isset($errors['delivery_date'])) : ?>
                            <span class="stc-error-message"><?php echo esc_html($errors['delivery_date']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="stc-form-group">
                        <label class="stc-form-label" for="stc-update-start">
                            <?php echo esc_html__('開始', 'sale-time-checker'); ?>
                        </label>
                        <input 
                            type="time" 
                            id="stc-update-start" 
                            name="start_time"
                            class="stc-form-input"
                            value="<?php echo esc_attr($start_time); ?>"
                            required>
                        <?php if (isset($errors['start_time'])) : ?>
                            <span class="stc-error-message"><?php echo esc_html($errors['start_time']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="stc-form-row">
                    <div class="stc-form-group">
                        <label class="stc-form-label" for="stc-update-end-date">
                            <?php echo esc_html__('終了日', 'sale-time-checker'); ?>
                        </label>
                        <input 
                            type="date" 
                            id="stc-update-end-date" 
                            name="end_date"
                            class="stc-form-input"
                            value="<?php echo esc_attr($end_date ? $end_date : $delivery_date); ?>"
                            required>
                        <?php if (isset($errors['end_date'])) : ?>
                            <span class="stc-error-message"><?php echo esc_html($errors['end_date']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="stc-form-group">
                        <label class="stc-form-label" for="stc-update-end">
                            <?php echo esc_html__('終了', 'sale-time-checker'); ?>
                        </label>
                        <input 
                            type="time" 
                            id="stc-update-end" 
                            name="end_time"
                            class="stc-form-input"
                            value="<?php echo esc_attr($end_time); ?>"
                            required>
                        <?php if (isset($errors['end_time'])) : ?>
                            <span class="stc-error-message"><?php echo esc_html($errors['end_time']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <label class="stc-form-label" for="stc-update-amount">
                    <?php echo esc_html__('売上合計を記入', 'sale-time-checker'); ?>
                </label>
                <div class="stc-form-row stc-form-row--amount">
                    <input
                        type="number"
                        id="stc-update-amount"
                        name="total_sales"
                        class="stc-form-input"
                        value="<?php echo esc_attr($total_sales); ?>"
                        placeholder="0"
                        required>
                    <span class="stc-form-suffix">円</span>
                </div>
                <?php if (isset($errors['total_sales'])) : ?>
                    <span class="stc-error-message"><?php echo esc_html($errors['total_sales']); ?></span>
                <?php endif; ?>

                <div class="stc-form-group">
                    <label class="stc-form-label" for="stc-update-stream-brand">
                        <?php echo esc_html__('配信したブランド', 'sale-time-checker'); ?>
                    </label>
                    <select 
                        id="stc-update-stream-brand"
                        name="stream_brand"
                        class="stc-form-select"
                        required>
                        <option value=""><?php echo esc_html__('選択してください', 'sale-time-checker'); ?></option>
                        <?php
                        // Prefer posted value (when validation fails), otherwise use value loaded from session/DB.
                        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                        $selected_stream_brand = isset($_POST['stream_brand'])
                            ? sanitize_text_field(wp_unslash($_POST['stream_brand']))
                            : $stream_brand;

                        $brands = get_posts([
                            'post_type' => 'brands',
                            'numberposts' => -1,
                            'orderby' => 'ID',
                            'order' => 'DESC',
                            'post_status' => 'publish'
                        ]);
                        if ($brands) :
                            foreach ($brands as $brand) :
                                $brand_id = $brand->ID;
                                $brand_name = $brand->post_title;
                                // Backward compatible: old records may have stored brand name instead of brand ID.
                                $selected = ((string) $selected_stream_brand === (string) $brand_id) ||
                                    ((string) $selected_stream_brand === (string) $brand_name);
                        ?>
                        <option value="<?php echo esc_attr($brand_id); ?>" <?php echo $selected ? 'selected' : ''; ?>>
                            <?php echo esc_html($brand_name); ?>
                        </option>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </select>
                    <?php if (isset($errors['stream_brand'])) : ?>
                        <span class="stc-error-message"><?php echo esc_html($errors['stream_brand']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="stc-questions-section">
                    <label class="stc-form-label">
                        <?php echo esc_html__('今日の配信結果は？', 'sale-time-checker'); ?>
                    </label>
                    <div class="stc-radio-options">
                        <label class="stc-radio-option">
                            <input type="radio" name="stream_result" value="success" 
                                   <?php echo ($stream_result === 'success') ? 'checked' : ''; ?> 
                                   required>
                            <span class="stc-radio-label">⭕ <?php echo esc_html__('成功', 'sale-time-checker'); ?></span>
                        </label>
                        <label class="stc-radio-option">
                            <input type="radio" name="stream_result" value="failure" 
                                   <?php echo ($stream_result === 'failure') ? 'checked' : ''; ?> 
                                   required>
                            <span class="stc-radio-label">❌ <?php echo esc_html__('失敗', 'sale-time-checker'); ?></span>
                        </label>
                    </div>
                    <?php if (isset($errors['stream_result'])) : ?>
                        <span class="stc-error-message"><?php echo esc_html($errors['stream_result']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="stc-questions-section">
                    <label class="stc-form-label">
                        <?php echo esc_html__('一番影響した要因は？', 'sale-time-checker'); ?>
                    </label>
                    <div class="stc-radio-options">
                        <label class="stc-radio-option">
                            <input type="radio" name="stream_factor" value="product" 
                                   <?php echo ($stream_factor === 'product') ? 'checked' : ''; ?> 
                                   required>
                            <span class="stc-radio-label">📦 <?php echo esc_html__('商品', 'sale-time-checker'); ?></span>
                        </label>
                        <label class="stc-radio-option">
                            <input type="radio" name="stream_factor" value="price" 
                                   <?php echo ($stream_factor === 'price') ? 'checked' : ''; ?> 
                                   required>
                            <span class="stc-radio-label">💰 <?php echo esc_html__('価格', 'sale-time-checker'); ?></span>
                        </label>
                        <label class="stc-radio-option">
                            <input type="radio" name="stream_factor" value="speech" 
                                   <?php echo ($stream_factor === 'speech') ? 'checked' : ''; ?> 
                                   required>
                            <span class="stc-radio-label">🗣 <?php echo esc_html__('話し方', 'sale-time-checker'); ?></span>
                        </label>
                        <label class="stc-radio-option">
                            <input type="radio" name="stream_factor" value="structure" 
                                   <?php echo ($stream_factor === 'structure') ? 'checked' : ''; ?> 
                                   required>
                            <span class="stc-radio-label">🧩 <?php echo esc_html__('構成', 'sale-time-checker'); ?></span>
                        </label>
                        <label class="stc-radio-option">
                            <input type="radio" name="stream_factor" value="condition" 
                                   <?php echo ($stream_factor === 'condition') ? 'checked' : ''; ?> 
                                   required>
                            <span class="stc-radio-label">😐 <?php echo esc_html__('コンディション', 'sale-time-checker'); ?></span>
                        </label>
                        <label class="stc-radio-option">
                            <input type="radio" name="stream_factor" value="impression" 
                                   <?php echo ($stream_factor === 'impression') ? 'checked' : ''; ?> 
                                   required>
                            <span class="stc-radio-label">📈 <?php echo esc_html__('インプレッション', 'sale-time-checker'); ?></span>
                        </label>
                    </div>
                    <?php if (isset($errors['stream_factor'])) : ?>
                        <span class="stc-error-message"><?php echo esc_html($errors['stream_factor']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="stc-form-group">
                    <label class="stc-form-label" for="stc-update-reason">
                        <?php echo esc_html__('ひとこと理由', 'sale-time-checker'); ?>
                    </label>
                    <textarea
                        id="stc-update-reason"
                        name="stream_reason"
                        class="stc-form-textarea"
                        rows="3"
                        placeholder="<?php echo esc_attr__('例：価格出すのが遅れた', 'sale-time-checker'); ?>"
                        required><?php echo esc_textarea($stream_reason); ?></textarea>
                    <?php if (isset($errors['stream_reason'])) : ?>
                        <span class="stc-error-message"><?php echo esc_html($errors['stream_reason']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="stc-form-group">
                    <label class="stc-form-label">
                        <?php echo esc_html__('配信前スクリーンショット', 'sale-time-checker'); ?>
                    </label>
                    <div class="stc-upload-wrapper">
                        <label class="stc-upload" data-upload-target="before_screenshot">
                            <input type="file" name="before_screenshot" class="stc-upload__input" accept="image/*" data-upload-input="before_screenshot">
                            <span class="stc-upload__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M352 173.3L352 384C352 401.7 337.7 416 320 416C302.3 416 288 401.7 288 384L288 173.3L246.6 214.7C234.1 227.2 213.8 227.2 201.3 214.7C188.8 202.2 188.8 181.9 201.3 169.4L297.3 73.4C309.8 60.9 330.1 60.9 342.6 73.4L438.6 169.4C451.1 181.9 451.1 202.2 438.6 214.7C426.1 227.2 405.8 227.2 393.3 214.7L352 173.3zM320 464C364.2 464 400 428.2 400 384L480 384C515.3 384 544 412.7 544 448L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 448C96 412.7 124.7 384 160 384L240 384C240 428.2 275.8 464 320 464zM464 488C477.3 488 488 477.3 488 464C488 450.7 477.3 440 464 440C450.7 440 440 450.7 440 464C440 477.3 450.7 488 464 488z"/></svg></span>
                            <span class="stc-upload__text">
                                <?php echo esc_html__('アップロード', 'sale-time-checker'); ?>
                            </span>
                        </label>
                        <div class="stc-upload-preview" data-preview="before_screenshot" <?php echo !empty($before_screenshot) ? 'style="display: block;"' : 'style="display: none;"'; ?>>
                            <img src="<?php echo !empty($before_screenshot) ? esc_url($before_screenshot) : ''; ?>" alt="Preview" class="stc-upload-preview__image">
                            <button type="button" class="stc-upload-preview__remove" data-remove="before_screenshot">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="stc-form-group">
                    <label class="stc-form-label">
                        <?php echo esc_html__('配信後スクリーンショット', 'sale-time-checker'); ?>
                    </label>
                    <div class="stc-upload-wrapper">
                        <label class="stc-upload" data-upload-target="after_screenshot">
                            <input type="file" name="after_screenshot" class="stc-upload__input" accept="image/*" data-upload-input="after_screenshot">
                            <span class="stc-upload__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M352 173.3L352 384C352 401.7 337.7 416 320 416C302.3 416 288 401.7 288 384L288 173.3L246.6 214.7C234.1 227.2 213.8 227.2 201.3 214.7C188.8 202.2 188.8 181.9 201.3 169.4L297.3 73.4C309.8 60.9 330.1 60.9 342.6 73.4L438.6 169.4C451.1 181.9 451.1 202.2 438.6 214.7C426.1 227.2 405.8 227.2 393.3 214.7L352 173.3zM320 464C364.2 464 400 428.2 400 384L480 384C515.3 384 544 412.7 544 448L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 448C96 412.7 124.7 384 160 384L240 384C240 428.2 275.8 464 320 464zM464 488C477.3 488 488 477.3 488 464C488 450.7 477.3 440 464 440C450.7 440 440 450.7 440 464C440 477.3 450.7 488 464 488z"/></svg></span>
                            <span class="stc-upload__text">
                                <?php echo esc_html__('アップロード', 'sale-time-checker'); ?>
                            </span>
                        </label>
                        <div class="stc-upload-preview" data-preview="after_screenshot" <?php echo !empty($after_screenshot) ? 'style="display: block;"' : 'style="display: none;"'; ?>>
                            <img src="<?php echo !empty($after_screenshot) ? esc_url($after_screenshot) : ''; ?>" alt="Preview" class="stc-upload-preview__image">
                            <button type="button" class="stc-upload-preview__remove" data-remove="after_screenshot">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="stc-form-group">
                    <label class="stc-form-label" for="stc-update-note">
                        <?php echo esc_html__('メモ', 'sale-time-checker'); ?>
                    </label>
                    <textarea
                        id="stc-update-note"
                        name="memo"
                        class="stc-form-textarea"
                        rows="4"
                        placeholder="<?php echo esc_attr__('メモを入力', 'sale-time-checker'); ?>"><?php echo esc_textarea($memo); ?></textarea>
                </div>

                <button type="submit" name="stc_update_submit" class="stc-record-submit">
                    <?php echo esc_html__('確認', 'sale-time-checker'); ?>
                </button>
            </form>
        </div>
    </div>
<?php

    return ob_get_clean();
    }
    add_shortcode('stc_update', 'stc_update_shortcode');
}
