function copyPromoCode(code, el) {
    navigator.clipboard.writeText(code).then(function () {
        const originalText = el.innerText;
        el.innerText = 'Copied!';
        el.classList.add('copied');

        setTimeout(function () {
            el.innerText = originalText;
            el.classList.remove('copied');
        }, 1500);
    });
}