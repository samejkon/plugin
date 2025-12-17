<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle register
 */
if (!function_exists('stc_handle_register')) {
    function stc_handle_register()
    {
        $errors = array();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_POST['stc_register_submit'])) {
            return $errors;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_POST['stc_register_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stc_register_nonce'])), 'stc_register_action')) {
            return $errors;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $name = isset($_POST['stc_name']) ? sanitize_text_field(wp_unslash($_POST['stc_name'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $email = isset($_POST['stc_email']) ? sanitize_email(wp_unslash($_POST['stc_email'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $password = isset($_POST['stc_password']) ? wp_unslash($_POST['stc_password']) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $confirm_password = isset($_POST['stc_confirm_password']) ? wp_unslash($_POST['stc_confirm_password']) : '';

        if (empty($name)) {
            $errors['name'] = esc_html__('名前を入力してください。', 'sale-time-checker');
        }

        if (empty($email)) {
            $errors['email'] = esc_html__('メールアドレスを入力してください。', 'sale-time-checker');
        } else {
            $existing_user = new WP_Query([
                'post_type'      => 'stc_user',
                'meta_key'       => 'user_email',
                'meta_value'     => $email,
                'posts_per_page' => 1,
            ]);

            if ($existing_user->have_posts()) {
                $errors['email'] = esc_html__('このメールアドレスは既に登録されています。', 'sale-time-checker');
            }
        }

        if (empty($password)) {
            $errors['password'] = esc_html__('パスワードを入力してください。', 'sale-time-checker');
        }

        if (empty($confirm_password)) {
            $errors['confirm_password'] = esc_html__('パスワード確認を入力してください。', 'sale-time-checker');
        } elseif ($password !== $confirm_password) {
            $errors['confirm_password'] = esc_html__('パスワードが一致しません。', 'sale-time-checker');
        }

        if (!empty($errors)) {
            return $errors;
        }

        $new_user_id = wp_insert_post([
            'post_type'   => 'stc_user',
            'post_title'  => $name,
            'post_status' => 'publish',
        ]);

        if ($new_user_id) {
            update_post_meta($new_user_id, 'user_name', $name);
            update_post_meta($new_user_id, 'user_email', $email);
            update_post_meta($new_user_id, 'user_password', wp_hash_password($password));
            update_post_meta($new_user_id, 'user_registered_date', current_time('mysql'));

            $_SESSION['stc_user_id'] = $new_user_id;

            $redirect_url = add_query_arg('view', 'mypage');
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            $errors['general'] = esc_html__('ユーザー登録中にエラーが発生しました。', 'sale-time-checker');
        }

        return $errors;
    }
}
add_action('init', 'stc_handle_register');

/**
 * Handle login
 */
if (!function_exists('stc_handle_login')) {
    function stc_handle_login()
    {
        $errors = array();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_POST['stc_login_submit'])) {
            return $errors;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_POST['stc_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stc_login_nonce'])), 'stc_login_action')) {
            return $errors;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $email = isset($_POST['stc_email']) ? sanitize_email(wp_unslash($_POST['stc_email'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $password = isset($_POST['stc_password']) ? wp_unslash($_POST['stc_password']) : '';

        if (empty($email)) {
            $errors['email'] = esc_html__('メールアドレスを入力してください。', 'sale-time-checker');
        }

        if (empty($password)) {
            $errors['password'] = esc_html__('パスワードを入力してください。', 'sale-time-checker');
        }

        if (!empty($errors)) {
            return $errors;
        }

        $user_query = new WP_Query([
            'post_type'      => 'stc_user',
            'meta_key'       => 'user_email',
            'meta_value'     => $email,
            'posts_per_page' => 1,
        ]);

        if (!$user_query->have_posts()) {
            $errors['email'] = esc_html__('メールアドレスまたはパスワードが正しくありません。', 'sale-time-checker');
            return $errors;
        }

        $user = $user_query->posts[0];
        $stored_password = get_post_meta($user->ID, 'user_password', true);

        if (!wp_check_password($password, $stored_password)) {
            $errors['password'] = esc_html__('メールアドレスまたはパスワードが正しくありません。', 'sale-time-checker');
            return $errors;
        }

        $_SESSION['stc_user_id'] = $user->ID;

        $redirect_url = add_query_arg('view', 'mypage');
        wp_safe_redirect($redirect_url);
        exit;
    }
}
add_action('init', 'stc_handle_login');

/**
 * Shortcode: [stc_login]
 *
 */
if (!function_exists('stc_login_shortcode')) {
    function stc_login_shortcode()
    {
        if (stc_is_user_logged_in()) {
            return '<p class="stc-logged">ログインしています。</p>';
        }

        $register_errors = stc_handle_register();
        $login_errors = stc_handle_login();

        $mode = isset($_GET['mode']) ? sanitize_text_field(wp_unslash($_GET['mode'])) : 'login';
        
        $register_url = add_query_arg(['view' => 'login', 'mode' => 'register']);
        $login_link_url = add_query_arg(['view' => 'login', 'mode' => 'login']);

    ob_start();
    ?>
    <div class="stc-login-wrapper">
        <?php if ($mode === 'register') : ?>
            <p class="title"><?php echo esc_html__('アカウント登録', 'sale-time-checker'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('stc_register_action', 'stc_register_nonce'); ?>

                <div class="login-field">
                    <label for="stc-register-name"><?php echo esc_html__('名前', 'sale-time-checker'); ?></label>
                    <input
                        id="stc-register-name"
                        type="text"
                        name="stc_name"
                        value="<?php echo isset($_POST['stc_name']) ? esc_attr(sanitize_text_field(wp_unslash($_POST['stc_name']))) : ''; ?>"
                        required
                    >
                    <?php if (isset($register_errors['name'])) : ?>
                        <span class="stc-error-message"><?php echo esc_html($register_errors['name']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="login-field">
                    <label for="stc-register-email"><?php echo esc_html__('メールアドレス', 'sale-time-checker'); ?></label>
                    <input
                        id="stc-register-email"
                        type="email"
                        name="stc_email"
                        value="<?php echo isset($_POST['stc_email']) ? esc_attr(sanitize_email(wp_unslash($_POST['stc_email']))) : ''; ?>"
                        required
                    >
                    <?php if (isset($register_errors['email'])) : ?>
                        <span class="stc-error-message"><?php echo esc_html($register_errors['email']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="login-field">
                    <label for="stc-register-password"><?php echo esc_html__('パスワード', 'sale-time-checker'); ?></label>
                    <input
                        id="stc-register-password"
                        type="password"
                        name="stc_password"
                        required
                    >
                    <?php if (isset($register_errors['password'])) : ?>
                        <span class="stc-error-message"><?php echo esc_html($register_errors['password']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="login-field">
                    <label for="stc-register-confirm-password"><?php echo esc_html__('パスワード確認', 'sale-time-checker'); ?></label>
                    <input
                        id="stc-register-confirm-password"
                        type="password"
                        name="stc_confirm_password"
                        required
                    >
                    <?php if (isset($register_errors['confirm_password'])) : ?>
                        <span class="stc-error-message"><?php echo esc_html($register_errors['confirm_password']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="register-link">
                    <a href="<?php echo esc_url($login_link_url); ?>">
                        <?php echo esc_html__('既にアカウントをお持ちですか？ログイン', 'sale-time-checker'); ?>
                    </a>
                </div>

                <button type="submit" name="stc_register_submit" class="login-btn">
                    <?php echo esc_html__('登録', 'sale-time-checker'); ?>
                </button>
            </form>

        <?php else : ?>
            <p class="title"><?php echo esc_html__('ログイン', 'sale-time-checker'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('stc_login_action', 'stc_login_nonce'); ?>

                <div class="login-field">
                    <label for="stc-login-email"><?php echo esc_html__('メールアドレス', 'sale-time-checker'); ?></label>
                    <input
                        id="stc-login-email"
                        type="email"
                        name="stc_email"
                        value="<?php echo isset($_POST['stc_email']) ? esc_attr(sanitize_email(wp_unslash($_POST['stc_email']))) : ''; ?>"
                        required
                    >
                    <?php if (isset($login_errors['email'])) : ?>
                        <span class="stc-error-message"><?php echo esc_html($login_errors['email']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="login-field">
                    <label for="stc-login-password"><?php echo esc_html__('パスワード', 'sale-time-checker'); ?></label>
                    <input
                        id="stc-login-password"
                        type="password"
                        name="stc_password"
                        required
                    >
                    <?php if (isset($login_errors['password'])) : ?>
                        <span class="stc-error-message"><?php echo esc_html($login_errors['password']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="register-link">
                    <a href="<?php echo esc_url($register_url); ?>">
                        <?php echo esc_html__('アカウントをお持ちでないですか？登録', 'sale-time-checker'); ?>
                    </a>
                </div>

                <button type="submit" name="stc_login_submit" class="login-btn">
                    <?php echo esc_html__('ログイン', 'sale-time-checker'); ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
    }
    add_shortcode('stc_login', 'stc_login_shortcode');
}
