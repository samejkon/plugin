<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Xử lý thêm delivery record
 */
if (!function_exists('stc_handle_create_delivery')) {
    function stc_handle_create_delivery()
    {
        $errors = array();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_POST['stc_create_submit'])) {
            return $errors;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_POST['stc_create_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stc_create_nonce'])), 'stc_create_action')) {
            return $errors;
        }

        if (!stc_is_user_logged_in()) {
            $errors['general'] = esc_html__('ログインが必要です。', 'sale-time-checker');
            return $errors;
        }

        $current_user = stc_get_current_user();
        $user_id = $current_user['id'];

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $delivery_date = isset($_POST['delivery_date']) ? sanitize_text_field(wp_unslash($_POST['delivery_date'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $end_time = isset($_POST['end_time']) ? sanitize_text_field(wp_unslash($_POST['end_time'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $total_sales = isset($_POST['total_sales']) ? intval($_POST['total_sales']) : 0;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $memo = isset($_POST['memo']) ? sanitize_textarea_field(wp_unslash($_POST['memo'])) : '';

        // Validate
        if (empty($delivery_date)) {
            $errors['delivery_date'] = esc_html__('配信日を入力してください。', 'sale-time-checker');
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

        $before_screenshot = '';
        $after_screenshot = '';

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
            'mode' => 'create',
            'delivery_date' => $delivery_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'total_sales' => $total_sales,
            'before_screenshot' => $before_screenshot,
            'after_screenshot' => $after_screenshot,
            'memo' => $memo,
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
add_action('init', 'stc_handle_create_delivery');

/**
 * Shortcode: [stc_create]
 *
 */
if (!function_exists('stc_create_shortcode')) {
    function stc_create_shortcode()
    {
        $errors = stc_handle_create_delivery();

        $session_data = isset($_SESSION['stc_confirm_data']) ? $_SESSION['stc_confirm_data'] : array();
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
            <div class="stc-record-card__head">
                <p class="stc-record-modal__title">
                    <?php echo esc_html__('配信内容の記録', 'sale-time-checker'); ?>
                </p>
                <a class="stc-record-back" href="<?php echo esc_url($my_page_url); ?>">
                    ← <?php echo esc_html__('戻ってくる', 'sale-time-checker'); ?>
                </a>
            </div>

            <form class="stc-record-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('stc_create_action', 'stc_create_nonce'); ?>

                <label class="stc-form-label" for="stc-create-date">
                    <?php echo esc_html__('配信日', 'sale-time-checker'); ?>
                </label>
                <input 
                    type="date" 
                    id="stc-create-date" 
                    name="delivery_date"
                    class="stc-form-input"
                    value="<?php echo isset($_POST['delivery_date']) ? esc_attr(sanitize_text_field(wp_unslash($_POST['delivery_date']))) : (isset($session_data['delivery_date']) ? esc_attr($session_data['delivery_date']) : ''); ?>"
                    required>
                <?php if (isset($errors['delivery_date'])) : ?>
                    <span class="stc-error-message"><?php echo esc_html($errors['delivery_date']); ?></span>
                <?php endif; ?>

                <div class="stc-form-row">
                    <div class="stc-form-group">
                        <label class="stc-form-label" for="stc-create-start">
                            <?php echo esc_html__('開始', 'sale-time-checker'); ?>
                        </label>
                        <input 
                            type="time" 
                            id="stc-create-start" 
                            name="start_time"
                            class="stc-form-input"
                            value="<?php echo isset($_POST['start_time']) ? esc_attr(sanitize_text_field(wp_unslash($_POST['start_time']))) : (isset($session_data['start_time']) ? esc_attr($session_data['start_time']) : ''); ?>"
                            required>
                        <?php if (isset($errors['start_time'])) : ?>
                            <span class="stc-error-message"><?php echo esc_html($errors['start_time']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="stc-form-group">
                        <label class="stc-form-label" for="stc-create-end">
                            <?php echo esc_html__('終了', 'sale-time-checker'); ?>
                        </label>
                        <input 
                            type="time" 
                            id="stc-create-end" 
                            name="end_time"
                            class="stc-form-input"
                            value="<?php echo isset($_POST['end_time']) ? esc_attr(sanitize_text_field(wp_unslash($_POST['end_time']))) : (isset($session_data['end_time']) ? esc_attr($session_data['end_time']) : ''); ?>"
                            required>
                        <?php if (isset($errors['end_time'])) : ?>
                            <span class="stc-error-message"><?php echo esc_html($errors['end_time']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <label class="stc-form-label" for="stc-create-amount">
                    <?php echo esc_html__('売上合計を記入', 'sale-time-checker'); ?>
                </label>
                <div class="stc-form-row stc-form-row--amount">
                    <input
                        type="number"
                        id="stc-create-amount"
                        name="total_sales"
                        class="stc-form-input"
                        value="<?php echo isset($_POST['total_sales']) ? esc_attr(intval($_POST['total_sales'])) : (isset($session_data['total_sales']) ? esc_attr($session_data['total_sales']) : ''); ?>"
                        placeholder="0"
                        required>
                    <span class="stc-form-suffix">円</span>
                </div>
                <?php if (isset($errors['total_sales'])) : ?>
                    <span class="stc-error-message"><?php echo esc_html($errors['total_sales']); ?></span>
                <?php endif; ?>

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
                        <div class="stc-upload-preview" data-preview="before_screenshot" style="display: none;">
                            <img src="" alt="Preview" class="stc-upload-preview__image">
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
                        <div class="stc-upload-preview" data-preview="after_screenshot" style="display: none;">
                            <img src="" alt="Preview" class="stc-upload-preview__image">
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
                    <label class="stc-form-label" for="stc-create-note">
                        <?php echo esc_html__('メモ', 'sale-time-checker'); ?>
                    </label>
                    <textarea
                        id="stc-create-note"
                        name="memo"
                        class="stc-form-textarea"
                        rows="4"
                        placeholder="<?php echo esc_attr__('メモを入力', 'sale-time-checker'); ?>"><?php echo isset($_POST['memo']) ? esc_textarea(sanitize_textarea_field(wp_unslash($_POST['memo']))) : (isset($session_data['memo']) ? esc_textarea($session_data['memo']) : ''); ?></textarea>
                </div>

                <button type="submit" name="stc_create_submit" class="stc-record-submit">
                    <?php echo esc_html__('確認', 'sale-time-checker'); ?>
                </button>
            </form>
        </div>
    </div>
<?php

    return ob_get_clean();
    }
    add_shortcode('stc_create', 'stc_create_shortcode');
}
