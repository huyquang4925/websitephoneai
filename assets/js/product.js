document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.variant-card').forEach(function (card) {
        card.addEventListener('click', function (event) {
            if (event.target.matches('input, button')) return;
            document.querySelectorAll('.variant-card').forEach(function (item) {
                item.classList.remove('selected');
            });
            card.classList.add('selected');
            var quantity = card.querySelector('input[type="number"]');
            if (quantity) quantity.focus();
        });
    });
});
