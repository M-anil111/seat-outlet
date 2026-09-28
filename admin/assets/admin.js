document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.admin-toggle-visibility').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            if (!input) return;
            var showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            btn.querySelector('.bi').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    });
});
