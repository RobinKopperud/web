// Enkel klientvalidering for MVP-formen.
(function () {
    const form = document.getElementById('upload-form');
    const message = document.getElementById('validation-message');

    if (!form) return;

    form.addEventListener('submit', function (event) {
        const store1 = document.getElementById('store_1').value;
        const store2 = document.getElementById('store_2').value;

        if (store1 && store2 && store1 === store2) {
            event.preventDefault();
            message.textContent = 'Du må velge to ulike butikker.';
            return;
        }

        message.textContent = '';
    });
})();
