document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.quantity-control').forEach(function (control) {
        var input = control.querySelector('input[type="number"]');
        if (!input) return;

        control.querySelectorAll('[data-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                var nextValue = Number(input.value) + Number(button.dataset.step);
                var minimum = Number(input.min || 0);
                var maximum = Number(input.max || 999);
                input.value = Math.min(maximum, Math.max(minimum, nextValue));
            });
        });
    });
});
