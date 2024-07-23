document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('register-form').addEventListener('submit', function(e) {
        e.preventDefault();
        registerUser();
    });

    document.getElementById('login-form').addEventListener('submit', function(e) {
        e.preventDefault();
        loginUser();
    });

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

function registerUser() {
    const username = document.getElementById('reg-username').value;
    const password = document.getElementById('reg-password').value;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'register.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        alert(this.responseText);
    };
    xhr.send('username=' + username + '&password=' + password);
}

function loginUser() {
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'login.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.responseText === "Login successful") {
            window.location.href = 'employees.php';
        } else {
            alert(this.responseText);
        }
    };
    xhr.send('username=' + username + '&password=' + password);
}
