document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.compare-form');
    if (!form) return;

    form.addEventListener('submit', function (event) {
        var input = form.querySelector('input[name="ids"]');
        var error = form.querySelector('.compare-error');
        var ids = input.value.split(',').map(function (id) { return id.trim(); }).filter(Boolean);

        if (ids.length < 2 || ids.length > 4 || ids.some(function (id) { return !/^\d+$/.test(id); })) {
            event.preventDefault();
            error.textContent = 'Nhập từ 2 đến 4 ID số, cách nhau bằng dấu phẩy.';
            return;
        }
        input.value = ids.join(',');
        error.textContent = '';
    });
});
