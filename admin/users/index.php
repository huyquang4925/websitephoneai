<?php
require_once __DIR__ . '/../../config/config.php';
require_admin();

$users = $conn->query('SELECT u.*, COUNT(o.id) AS order_count FROM users u LEFT JOIN orders o ON o.user_id = u.id GROUP BY u.id ORDER BY u.id DESC')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Người dùng';
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Người dùng</h1>
        <p>Danh sách tài khoản hệ thống.</p>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Điện thoại</th>
                <th>Vai trò</th>
                <th>Đơn</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><strong><?= e($user['full_name']) ?></strong></td>
                <td><?= e($user['email']) ?></td>
                <td><?= e($user['phone']) ?></td>
                <td><span class="badge role-<?= e($user['role']) ?>"><?= role_label($user['role']) ?></span></td>
                <td><?= $user['order_count'] ?></td>
                <td>
                    <?php if ($user['role'] === 'admin'): ?>
                        <span class="badge" style="background:#eef2f6;color:#667085;">None</span>
                    <?php else: ?>
                        <button type="button" class="btn-danger btn-small btn-open-delete" data-id="<?= $user['id'] ?>" data-name="<?= e($user['full_name']) ?>">Xóa</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Popup nhập mã PIN xóa tài khoản -->
<div id="deleteModal" class="modal-backdrop" hidden>
    <div class="modal-content">
        <h3>Xác nhận xóa tài khoản</h3>
        <p>Bạn đang yêu cầu xóa người dùng <strong id="deleteUserName"></strong>.<br>Vui lòng nhập mã PIN bảo mật để xác nhận:</p>
        <form id="deleteForm" action="delete.php" method="post">
            <input type="hidden" name="id" id="deleteUserId" value="">
            <div class="form-group">
                <label for="modalPin">Mã PIN xác nhận (123)</label>
                <input class="modal-pin-input" id="modalPin" type="password" name="pin" inputmode="numeric" maxlength="3" placeholder="Nhập PIN" required autocomplete="off">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline btn-small" id="btnCancelDelete">Hủy</button>
                <button type="submit" class="btn-danger btn-small">Xác nhận xóa</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('deleteModal');
    const deleteUserId = document.getElementById('deleteUserId');
    const deleteUserName = document.getElementById('deleteUserName');
    const modalPin = document.getElementById('modalPin');
    const btnCancel = document.getElementById('btnCancelDelete');

    document.querySelectorAll('.btn-open-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            deleteUserId.value = this.dataset.id;
            deleteUserName.textContent = this.dataset.name;
            modalPin.value = '';
            modal.hidden = false;
            modalPin.focus();
        });
    });

    function closeModal() {
        modal.hidden = true;
        modalPin.value = '';
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
