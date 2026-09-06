</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-col">
            <strong class="footer-title">
                <span class="footer-title-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                </span>
                MCHAphone Store
            </strong>
            <p class="footer-desc">Điện thoại chính hãng, lựa chọn dễ dàng.</p>
            <ul class="footer-feature-list">
                <li>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <span>Chính sách bảo hành uy tín</span>
                </li>
                <li>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Hoạt động 24/7</span>
                </li>
            </ul>
        </div>
        <div class="footer-col">
            <strong class="footer-title">
                <span class="footer-title-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
                </span>
                Hỗ trợ
            </strong>
            <ul class="footer-links">
                <li><span>Tư vấn mua hàng</span></li>
                <li><span>Bảo hành sản phẩm</span></li>
            </ul>
        </div>
        <div class="footer-col">
            <strong class="footer-title">
                <span class="footer-title-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </span>
                Liên hệ
            </strong>
            <ul class="footer-contact-list">
                <li>
                    <strong>Hotline:</strong> <span>1900 0000</span>
                </li>
                <li>
                    <strong>Email:</strong> <a href="mailto:mchaphonestore@mail.com">mchaphonestore@mail.com</a>
                </li>
            </ul>
        </div>
    </div>
    <p class="copyright">© 2026 MCHAphone Store</p>
</footer>

<!-- Modal Popup cảnh báo thay đổi chưa lưu -->
<div id="unsavedChangesModal" class="modal-backdrop" hidden>
    <div class="modal-content">
        <h3>Bạn có muốn lưu thay đổi?</h3>
        <p>Các thay đổi bạn vừa nhập chưa được lưu lại. Bạn có chắc chắn muốn rời khỏi trang này không?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline btn-small" id="btnStayOnPage">Hủy</button>
            <button type="button" class="btn-danger btn-small" id="btnLeavePage">OK</button>
        </div>
    </div>
</div>

<!-- Modal Popup xác nhận xóa chung -->
<div id="generalDeleteModal" class="modal-backdrop" hidden>
    <div class="modal-content">
        <h3 id="generalDeleteTitle">Xác nhận xóa</h3>
        <p id="generalDeleteMessage">Bạn có chắc chắn muốn xóa mục này không? Hành động này không thể hoàn tác.</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline btn-small" id="btnCancelGeneralDelete">Hủy</button>
            <button type="button" class="btn-danger btn-small" id="btnConfirmGeneralDelete">Xác nhận xóa</button>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js?v=<?= file_exists(__DIR__ . '/../assets/js/main.js') ? filemtime(__DIR__ . '/../assets/js/main.js') : '1' ?>"></script>
<script src="<?= BASE_URL ?>/assets/js/validation.js?v=<?= file_exists(__DIR__ . '/../assets/js/validation.js') ? filemtime(__DIR__ . '/../assets/js/validation.js') : '1' ?>"></script>
<?php foreach (($pageJs ?? []) as $js): ?>
    <script src="<?= BASE_URL ?>/assets/js/<?= e($js) ?>.js?v=<?= file_exists(__DIR__ . '/../assets/js/' . $js . '.js') ? filemtime(__DIR__ . '/../assets/js/' . $js . '.js') : '1' ?>"></script>
<?php endforeach; ?>
</body>
</html>
