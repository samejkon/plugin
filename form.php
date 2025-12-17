<?php
function register_report_post_type() {
    $labels = array(
        'name'               => 'レポート',
        'singular_name'      => 'レポート',
        'menu_name'          => 'レポート',
        'name_admin_bar'     => 'レポート',
        'add_new'            => '新規追加',
        'add_new_item'       => '新しいレポートを追加',
        'edit_item'          => 'レポートを編集',
        'new_item'           => '新しいレポート',
        'all_items'          => 'レポート一覧',
        'view_item'          => 'レポートを表示',
        'search_items'       => 'レポートを検索',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'menu_icon'          => 'dashicons-chart-line',
        'supports'           => array('title', 'editor'),
        'capability_type'    => 'post',
        'has_archive'        => false,
    );

    register_post_type('report', $args);
}
add_action('init', 'register_report_post_type');

add_action('admin_menu', function() {
    add_menu_page(
        'レポート管理',
        'レポート',
        'manage_options',
        'manage-report',
        'render_report_admin_page',
        'dashicons-chart-line',
        8
    );
});

function render_report_admin_page() {
    if (isset($_POST['save_report']) && check_admin_referer('add_report_action', 'add_report_nonce')) {
        $report_id      = intval($_POST['report_id']);
        $staff_id       = intval($_POST['report_staff']);
        $report_date    = sanitize_text_field($_POST['report_date']);
        $report_content = sanitize_textarea_field($_POST['report_content']);
        $report_insight = sanitize_textarea_field($_POST['report_insight']);
        $report_remarks = sanitize_textarea_field($_POST['report_remarks']);

        if ($report_id > 0) {
            wp_update_post([
                'ID'           => $report_id,
                'post_title'   => get_the_title($staff_id) . ' - ' . $report_date,
                'post_content' => $report_content,
            ]);

            update_post_meta($report_id, 'report_staff', $staff_id);
            update_post_meta($report_id, 'report_date', $report_date);
            update_post_meta($report_id, 'report_insight', $report_insight);
            update_post_meta($report_id, 'report_remarks', $report_remarks);

            echo '<div class="updated" style="background:#d4edda; color:#155724; padding:10px; border-left:4px solid #28a745; margin-bottom:10px;">
                    <p>レポートを更新しました！</p>
                  </div>';
        } else {
            $new_id = wp_insert_post([
                'post_type'   => 'report',
                'post_title'  => get_the_title($staff_id) . ' - ' . $report_date,
                'post_status' => 'publish',
                'post_content'=> $report_content,
            ]);

            if ($new_id) {
                update_post_meta($new_id, 'report_staff', $staff_id);
                update_post_meta($new_id, 'report_date', $report_date);
                update_post_meta($new_id, 'report_insight', $report_insight);
                update_post_meta($new_id, 'report_remarks', $report_remarks);
                echo '<div class="updated" style="background:#d4edda; color:#155724; padding:10px; border-left:4px solid #28a745; margin-bottom:10px;">
                        <p>新しいレポートを追加しました！</p>
                      </div>';
            }
        }
    }

    if (isset($_POST['delete_report']) && check_admin_referer('delete_report_action', 'delete_report_nonce')) {
        $report_id = intval($_POST['report_id']);
        if (wp_delete_post($report_id, true)) {
            echo '<div class="updated" style="background:#d4edda; color:#155724; border-left:4px solid #dc3545; padding:10px; margin-bottom:10px;">
                    <p> レポートを削除しました！</p>
                  </div>';
        } else {
            echo '<div class="error"><p>レポートの削除中にエラーが発生しました！</p></div>';
        }
    }

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $staffs = get_posts([
        'manage-report',
        'post_type'      => 'staff',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $selected_staff = isset($_GET['filter_staff']) ? intval($_GET['filter_staff']) : '';
    $selected_date  = isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : '';
    $args = [
        'post_type'      => 'report',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'paged'          => $paged,
    ];
    
    $meta_query = [];
    
    if ($selected_staff) {
        $meta_query[] = [
            'key'     => 'report_staff',
            'value'   => $selected_staff,
            'compare' => '=',
        ];
    }
    
    if ($selected_date) {
        $meta_query[] = [
            'key'     => 'report_date',
            'value'   => $selected_date,
            'compare' => '=',
        ];
    }
    
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
    
    $query = new WP_Query($args);
    $reports = $query->posts;
    ?>

    <div class="wrap">
        <h2>日報記入数</h2>
        <?php 
        $prev_month = date('m', strtotime('first day of last month'));
        $prev_year  = date('Y', strtotime('first day of last month'));
        $prev_days  = cal_days_in_month(CAL_GREGORIAN, $prev_month, $prev_year);
        
        $this_month = date('m');
        $this_year  = date('Y');
        $this_days  = cal_days_in_month(CAL_GREGORIAN, $this_month, $this_year);
        ?>
        <table class="table text-center">
            <thead>
                <tr>
                    <?php foreach ($staffs as $staff): ?>
                        <th class="fw-bold" style="color: #437ec4;"><?php echo esc_html($staff->post_title); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php foreach ($staffs as $staff): ?>
                        <?php
                        $count_reports = new WP_Query([
                            'post_type'      => 'report',
                            'meta_key'       => 'report_staff',
                            'meta_value'     => $staff->ID,
                            'posts_per_page' => -1
                        ]);
                        ?>
                        <td><?php echo intval($count_reports->found_posts); ?></td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <?php foreach ($staffs as $staff): 
                        $prev_reports = new WP_Query([
                            'post_type' => 'report',
                            'meta_key' => 'report_staff',
                            'meta_value' => $staff->ID,
                            'posts_per_page' => -1,
                            'date_query' => [
                                [
                                    'year'  => $prev_year,
                                    'month' => $prev_month,
                                ],
                            ],
                        ]);
                        $this_reports = new WP_Query([
                            'post_type' => 'report',
                            'meta_key' => 'report_staff',
                            'meta_value' => $staff->ID,
                            'posts_per_page' => -1,
                            'date_query' => [
                                [
                                    'year'  => $this_year,
                                    'month' => $this_month,
                                ],
                            ],
                        ]);
                    ?>
                        <td>
                            前月: <?php echo intval($prev_reports->found_posts); ?>件 (<?php echo $prev_days; ?>日中)<br>
                            今月: <?php echo intval($this_reports->found_posts); ?>件 (<?php echo $this_days; ?>日中)
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        </table>
        <style>
            .table td, .table th {
                border-top: 1px solid #dee2e6 !important;
                border-bottom: 1px solid #dee2e6 !important;
                border-left: none !important;
                border-right: none !important;
            }
        </style>
        <div class="d-flex flex-row">
            <button type="button" 
                    class="btn btn-primary ms-auto" 
                    data-bs-toggle="modal" 
                    data-bs-target="#exampleModal">
                新規レポートを作成
            </button>
        </div>
        <h2>レポート一覧</h2>
        <form method="GET" class="row g-3 align-items-end mb-3">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? ''); ?>">
            <div class="col-auto">
                <label for="filter_staff" class="form-label mb-1">スタッフ:</label>
                <select name="filter_staff" id="filter_staff" class="form-select" style="width: 400px;">
                    <option value=""></option>
                    <?php foreach ($staffs as $staff): ?>
                        <option value="<?php echo esc_attr($staff->ID); ?>" <?php selected($selected_staff, $staff->ID); ?>>
                            <?php echo esc_html($staff->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        
            <div class="col-auto">
                <label for="filter_date" class="form-label mb-1">日付:</label>
                <input type="date" name="filter_date" id="filter_date" class="form-control" style="width: 300px;" value="<?php echo esc_attr($selected_date); ?>">
            </div>
        
            <div class="w-100"></div>
        
            <div class="col-auto">
                <button type="submit" class="btn btn-success">検索</button>
                <?php if (isset($query)): ?>
                    <span class="ms-2 fw-bold">検索結果：<?php echo intval($query->found_posts); ?> 件が該当しました</span>
                <?php endif; ?>
            </div>
        </form>
        <table class="table table-bordered mt-3">
            <thead class="table-light">
                <tr>
                    <th></th>
                    <th>スタッフ</th>
                    <th>日付</th>
                    <th>業務内容</th>
                    <th>気付き・問題・理由</th>
                    <th>備考</th>
                    <th>更新日時</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="report-table-body">
                <?php if (!empty($reports)): ?>
                    <?php foreach ($reports as $report): 
                        $staff_id   = get_post_meta($report->ID, 'report_staff', true);
                        $staff_name = $staff_id ? get_the_title($staff_id) : '—';
                        $date       = get_post_meta($report->ID, 'report_date', true);
                        $insight    = get_post_meta($report->ID, 'report_insight', true);
                        $remarks    = get_post_meta($report->ID, 'report_remarks', true);
                        $modified   = $report->post_modified;
                    ?>
                    <tr>
                        <td>
                            <button
                            class="btn btn-primary edit-btn"
                            data-id="<?php echo $report->ID; ?>"
                            data-staff="<?php echo esc_attr($staff_id); ?>"
                            data-date="<?php echo esc_attr(date('Y-m-d', strtotime($date))); ?>"
                            data-content="<?php echo esc_attr($report->post_content); ?>"
                            data-insight="<?php echo esc_attr($insight); ?>"
                            data-remarks="<?php echo esc_attr($remarks); ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#exampleModal">
                                編集
                            </button>
                        </td>
                        <td><?php echo esc_html($staff_name); ?></td>
                        <td><?php echo esc_html($date); ?></td>
                        <td><?php echo nl2br(esc_html($report->post_content)); ?></td>
                        <td><?php echo nl2br(esc_html($insight)); ?></td>
                        <td><?php echo nl2br(esc_html($remarks)); ?></td>
                        <td><?php echo esc_html(date('Y-m-d H:i', strtotime($modified))); ?></td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <?php wp_nonce_field('delete_report_action', 'delete_report_nonce'); ?>
                                <input type="hidden" name="report_id" value="<?php echo esc_attr($report->ID); ?>">
                                <button type="submit" name="delete_report" class="btn btn-sm" onclick="return confirm('このレポートを削除してもよろしいですか？');">
                                     <i class="fa fa-close fa-lg text-secondary"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">レポートがまだありません。</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        $current_page_slug = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $base_url = menu_page_url($current_page_slug, false);
        $base_with_paged = add_query_arg(array_merge($_GET, ['paged' => '%#%']), $base_url);
        
        $links = paginate_links([
            'base'      => $base_with_paged,
            'format'    => '',
            'total'     => $query->max_num_pages,
            'current'   => $paged,
            'prev_text' => '« 前へ',
            'next_text' => '次へ »',
            'type'      => 'array',
        ]);
        
        if (!empty($links)) {
            echo '<nav aria-label="ページネーション" class="mt-3">';
            echo '<ul class="pagination justify-content-center">';
            foreach ($links as $link) {
                $active = strpos($link, 'current') !== false ? ' active' : '';
                $disabled = strpos($link, 'dots') !== false ? ' disabled' : '';
        
                $link = str_replace('page-numbers', 'page-link', $link);
                echo '<li class="page-item' . $active . $disabled . '">' . $link . '</li>';
            }
            echo '</ul>';
            echo '</nav>';
        }
        
        wp_reset_postdata();
        ?>
    </div>

    <!-- MODAL -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
          <div class="modal-header"> 
            <h1 class="modal-title fs-5" id="exampleModalLabel">新規レポート作成</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
          </div>

          <form method="POST" action="">
            <?php wp_nonce_field('add_report_action', 'add_report_nonce'); ?>
            <div class="modal-body">
            
              <div class="row mb-3">
                <label for="report_staff" class="col-sm-3 col-form-label">スタッフ:</label>
                <div class="col-sm-9">
                    <select name="report_staff" id="report_staff" class="form-select" required>
                        <option value="">-- スタッフを選択 --</option>
                        <?php foreach ($staffs as $staff): ?>
                            <option value="<?php echo esc_attr($staff->ID); ?>">
                                <?php echo esc_html($staff->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
              </div>

              <div class="row mb-3">
                <label for="report_date" class="col-sm-3 col-form-label">日付:</label>
                <div class="col-sm-9">
                    <input type="date" 
                           name="report_date" 
                           id="report_date"
                           class="form-control"
                           min="<?php echo esc_attr($yesterday); ?>" 
                           max="<?php echo esc_attr($today); ?>"
                           value="<?php echo esc_attr(date('Y-m-d', strtotime($today))); ?>"
                           required>
                </div>
              </div>

              <div class="row mb-3">
                <label for="report_content" class="col-sm-3 col-form-label">業務内容:</label>
                <div class="col-sm-9">
                    <textarea name="report_content" class="form-control" id="report_content" rows="4" required></textarea>
                </div>
              </div>

              <div class="row mb-3">
                <label for="report_insight" class="col-sm-3 col-form-label">気付き・問題・理由:</label>
                <div class="col-sm-9">
                    <textarea name="report_insight" class="form-control" id="report_insight" rows="4"></textarea>
                </div>
              </div>

              <div class="row mb-3">
                <label for="report_remarks" class="col-sm-3 col-form-label">備考:</label>
                <div class="col-sm-9">
                    <textarea name="report_remarks" class="form-control" id="report_remarks" rows="4"></textarea>
                </div>
              </div>

            </div>
            <div class="modal-footer">
              <input type="hidden" name="report_id" id="report_id" value="">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
              <button type="submit" name="save_report" class="btn btn-primary">保存</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('exampleModal');
            const title = modal.querySelector('.modal-title');
            const idField = modal.querySelector('#report_id');
            const staffSelect = modal.querySelector('#report_staff');
            const dateInput = modal.querySelector('#report_date');
            const contentTextarea = modal.querySelector('#report_content');
            const insightTextarea = modal.querySelector('#report_insight');
            const remarksTextarea = modal.querySelector('#report_remarks');
            const table = document.querySelector('#report-table-body');

            table.addEventListener('click', (e) => {
                const btn = e.target.closest('.edit-btn');
                if (!btn) return; 
                
                title.textContent = 'レポートを編集';
                idField.value = btn.dataset.id;
                staffSelect.value = btn.dataset.staff;
                
                const dateStr = btn.dataset.date;
                if (dateStr) {
                    const d = new Date(dateStr);
                    dateInput.value = !isNaN(d) ? d.toISOString().split('T')[0] : '';
                } else {
                    dateInput.value = '';
                }
                
                contentTextarea.value = btn.dataset.content || '';
                insightTextarea.value = btn.dataset.insight || '';
                remarksTextarea.value = btn.dataset.remarks || '';
                });
            
            const addBtn = document.querySelector('[data-bs-target="#exampleModal"]:not(.edit-btn)');
            if (addBtn) {
                addBtn.addEventListener('click', () => {
                title.textContent = '新しいレポートを作成';
                idField.value = '';
                staffSelect.value = '';
                dateInput.value = new Date().toISOString().split('T')[0];
                contentTextarea.value = '';
                insightTextarea.value = '';
                remarksTextarea.value = '';
                });
            }
        });
    </script>
    <?php
    }
?>