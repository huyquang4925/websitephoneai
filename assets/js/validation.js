document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var firstInvalid = null;
            form.querySelectorAll('[required], input[type="number"]').forEach(function (field) {
                var invalid = !field.checkValidity();
                if (field.type === 'number' && field.value !== '' && Number(field.value) < Number(field.min || 0)) invalid = true;
                field.classList.toggle('invalid', invalid);
                if (invalid && !firstInvalid) firstInvalid = field;
            });
            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
                firstInvalid.reportValidity();
            }
        });
    });
});
