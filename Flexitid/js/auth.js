document.addEventListener('DOMContentLoaded', function() {
    const logoutBtnSystem = document.getElementById('logout-btn-system');
    if (logoutBtnSystem) {
        logoutBtnSystem.addEventListener('click', function() {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'logout.php', true);
            xhr.onload = function() {
                window.location.href = 'index.php';
            };
            xhr.send();
        });
    }
});
