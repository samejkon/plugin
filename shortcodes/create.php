<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [stc_create]
 *
 */
if (!function_exists('stc_create_shortcode')) {
    function stc_create_shortcode()
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
            <div class="stc-record-card__head">
                <p class="stc-record-modal__title">
                    <?php echo esc_html__('配信内容の記録', 'sale-time-checker'); ?>
                </p>
                <a class="stc-record-back" href="<?php echo esc_url($my_page_url); ?>">
                    ← <?php echo esc_html__('マイページへ戻る', 'sale-time-checker'); ?>
                </a>
            </div>

            <form class="stc-record-form">
                <label class="stc-form-label" for="stc-create-date">
                    <?php echo esc_html__('配信日', 'sale-time-checker'); ?>
                </label>
                <input type="date" id="stc-create-date" class="stc-form-input">

                <div class="stc-form-row">
                    <div class="stc-form-group">
                        <label class="stc-form-label" for="stc-create-start">
                            <?php echo esc_html__('開始', 'sale-time-checker'); ?>
                        </label>
                        <input type="time" id="stc-create-start" class="stc-form-input">
                    </div>
                    <div class="stc-form-group">
                        <label class="stc-form-label" for="stc-create-end">
                            <?php echo esc_html__('終了', 'sale-time-checker'); ?>
                        </label>
                        <input type="time" id="stc-create-end" class="stc-form-input">
                    </div>
                </div>

                <label class="stc-form-label" for="stc-create-amount">
                    <?php echo esc_html__('売上合計を記入', 'sale-time-checker'); ?>
                </label>
                <div class="stc-form-row stc-form-row--amount">
                    <input
                        type="number"
                        id="stc-create-amount"
                        class="stc-form-input"
                        placeholder="0">
                    <span class="stc-form-suffix">円</span>
                </div>

                <div class="stc-form-group">
                    <label class="stc-form-label">
                        <?php echo esc_html__('配信前スクリーンショット', 'sale-time-checker'); ?>
                    </label>
                    <label class="stc-upload">
                        <input type="file" class="stc-upload__input" accept="image/*">
                        <span class="stc-upload__icon">↑</span>
                        <span class="stc-upload__text">
                            <?php echo esc_html__('アップロード', 'sale-time-checker'); ?>
                        </span>
                    </label>
                </div>

                <div class="stc-form-group">
                    <label class="stc-form-label">
                        <?php echo esc_html__('配信後スクリーンショット', 'sale-time-checker'); ?>
                    </label>
                    <label class="stc-upload">
                        <input type="file" class="stc-upload__input" accept="image/*">
                        <span class="stc-upload__icon">↑</span>
                        <span class="stc-upload__text">
                            <?php echo esc_html__('アップロード', 'sale-time-checker'); ?>
                        </span>
                    </label>
                </div>

                <div class="stc-form-group">
                    <label class="stc-form-label" for="stc-create-note">
                        <?php echo esc_html__('メモ', 'sale-time-checker'); ?>
                    </label>
                    <textarea
                        id="stc-create-note"
                        class="stc-form-textarea"
                        rows="4"
                        placeholder="<?php echo esc_attr__('メモを入力', 'sale-time-checker'); ?>"></textarea>
                </div>

                <button type="submit" class="stc-record-submit">
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
