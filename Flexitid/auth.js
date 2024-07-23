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

document.getElementById('logout-btn-system').addEventListener('click', function() {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'logout.php', true);
    xhr.onload = function() {
        window.location.href = 'index.php';
    };
    xhr.send();
});
