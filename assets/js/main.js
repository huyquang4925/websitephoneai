document.addEventListener('DOMContentLoaded', function () {
    var menuButton = document.querySelector('.menu-toggle');
    var mainNav = document.querySelector('.main-nav');

    if (menuButton && mainNav) {
        menuButton.addEventListener('click', function () {
            var isOpen = mainNav.classList.toggle('open');
            menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    var accountMenu = document.querySelector('.account-menu');
    var accountToggle = document.querySelector('.account-menu-toggle');
    var accountDropdown = document.getElementById('accountDropdown');

    function closeAccountMenu() {
        if (!accountToggle || !accountDropdown) return;
        accountToggle.setAttribute('aria-expanded', 'false');
        accountDropdown.hidden = true;
    }

    if (accountToggle && accountDropdown) {
        accountToggle.addEventListener('click', function () {
            var isOpen = accountToggle.getAttribute('aria-expanded') === 'true';
            accountToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            accountDropdown.hidden = isOpen;
        });

        document.addEventListener('click', function (event) {
            if (accountMenu && !accountMenu.contains(event.target)) {
                closeAccountMenu();
            }
        });
    }

    document.querySelectorAll('.image-picker').forEach(function (picker) {
        var input = picker.querySelector('.image-input');
        var name = picker.querySelector('.image-name');
        var preview = picker.querySelector('.image-preview');
        var previewUrl = '';

        input.addEventListener('change', function () {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            if (!input.files.length) return;

            name.textContent = input.files[0].name;
            previewUrl = URL.createObjectURL(input.files[0]);
            preview.src = previewUrl;
            preview.hidden = false;
        });
    });

    var message = document.querySelector('.message');
    if (message) {
        window.setTimeout(function () {
            message.hidden = true;
        }, 4000);
    }

    var isFormDirty = false;
    var isFormSubmitting = false;

    // --- Delete Confirmation Modal Handler ---
    var generalDeleteModal = document.getElementById('generalDeleteModal');
    var generalDeleteTitle = document.getElementById('generalDeleteTitle');
    var generalDeleteMessage = document.getElementById('generalDeleteMessage');
    var btnCancelGeneralDelete = document.getElementById('btnCancelGeneralDelete');
    var btnConfirmGeneralDelete = document.getElementById('btnConfirmGeneralDelete');
    var pendingDeleteForm = null;

    function showDeleteModal(form, message, title) {
        pendingDeleteForm = form;
        if (generalDeleteTitle) generalDeleteTitle.textContent = title || 'Xác nhận xóa';
        if (generalDeleteMessage) generalDeleteMessage.textContent = message || 'Bạn có chắc chắn muốn xóa mục này không? Hành động này không thể hoàn tác.';
        if (generalDeleteModal) {
            generalDeleteModal.hidden = false;
        }
    }

    function hideDeleteModal() {
        if (generalDeleteModal) {
            generalDeleteModal.hidden = true;
        }
        pendingDeleteForm = null;
    }

    if (btnCancelGeneralDelete) {
        btnCancelGeneralDelete.addEventListener('click', hideDeleteModal);
    }

    if (btnConfirmGeneralDelete) {
        btnConfirmGeneralDelete.addEventListener('click', function () {
            if (pendingDeleteForm) {
                var formToSubmit = pendingDeleteForm;
                isFormSubmitting = true;
                isFormDirty = false;
                hideDeleteModal();
                formToSubmit.submit();
            }
        });
    }

    if (generalDeleteModal) {
        generalDeleteModal.addEventListener('click', function (e) {
            if (e.target === generalDeleteModal) {
                hideDeleteModal();
            }
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (generalDeleteModal) {
                event.preventDefault();
                var msg = form.dataset.confirm || 'Bạn có chắc chắn muốn xóa không?';
                var ttl = form.dataset.confirmTitle || 'Xác nhận xóa';
                showDeleteModal(form, msg, ttl);
            } else if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('form[method="post"]:not(#deleteForm):not(#generalDeleteForm)').forEach(function (form) {
        form.addEventListener('input', function (e) {
            if (e.target.id === 'modalPin') return;
            isFormDirty = true;
        });
        form.addEventListener('change', function (e) {
            if (e.target.id === 'modalPin') return;
            isFormDirty = true;
        });
        form.addEventListener('submit', function () {
            isFormSubmitting = true;
            isFormDirty = false;
        });
    });

    var unsavedModal = document.getElementById('unsavedChangesModal');
    var btnStay = document.getElementById('btnStayOnPage');
    var btnLeave = document.getElementById('btnLeavePage');
    var navBackBtn = document.getElementById('navBackBtn');
    var pendingNavigation = null;

    function showUnsavedModal(action) {
        pendingNavigation = action;
        if (unsavedModal) {
            unsavedModal.hidden = false;
        }
    }

    function hideUnsavedModal() {
        if (unsavedModal) {
            unsavedModal.hidden = true;
        }
        pendingNavigation = null;
    }

    if (btnStay) {
        btnStay.addEventListener('click', hideUnsavedModal);
    }

    if (btnLeave) {
        btnLeave.addEventListener('click', function () {
            isFormDirty = false;
            hideUnsavedModal();
            if (typeof pendingNavigation === 'function') {
                pendingNavigation();
            } else if (typeof pendingNavigation === 'string') {
                window.location.href = pendingNavigation;
            } else {
                if (window.history.length > 1) {
                    window.history.back();
                } else if (navBackBtn && navBackBtn.dataset.home) {
                    window.location.href = navBackBtn.dataset.home;
                }
            }
        });
    }

    if (unsavedModal) {
        unsavedModal.addEventListener('click', function (e) {
            if (e.target === unsavedModal) {
                hideUnsavedModal();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;

        if (accountDropdown && !accountDropdown.hidden) {
            closeAccountMenu();
            if (accountToggle) accountToggle.focus();
        }
        if (generalDeleteModal && !generalDeleteModal.hidden) {
            hideDeleteModal();
        }
        if (unsavedModal && !unsavedModal.hidden) {
            hideUnsavedModal();
        }
    });

    if (navBackBtn) {
        navBackBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (isFormDirty) {
                showUnsavedModal(function () {
                    if (window.history.length > 1) {
                        window.history.back();
                    } else if (navBackBtn.dataset.home) {
                        window.location.href = navBackBtn.dataset.home;
                    }
                });
            } else {
                if (window.history.length > 1) {
                    window.history.back();
                } else if (navBackBtn.dataset.home) {
                    window.location.href = navBackBtn.dataset.home;
                }
            }
        });
    }

    document.querySelectorAll('.btn-cancel-edit').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (isFormDirty) {
                e.preventDefault();
                var targetHref = this.getAttribute('href');
                showUnsavedModal(function () {
                    window.location.href = targetHref;
                });
            }
        });
    });

    window.addEventListener('beforeunload', function (e) {
        if (isFormDirty && !isFormSubmitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

