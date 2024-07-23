document.getElementById('register-form').addEventListener('submit', function(e) {
    e.preventDefault();
    registerUser();
});

document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    loginUser();
});

document.getElementById('login-btn').addEventListener('click', function() {
    logTime('inn');
});

document.getElementById('logout-btn').addEventListener('click', function() {
    logTime('ut');
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
            document.getElementById('auth-section').style.display = 'none';
            document.getElementById('log-section').style.display = 'block';
            fetchLogs();
        } else {
            alert(this.responseText);
        }
    };
    xhr.send('username=' + username + '&password=' + password);
}

function logTime(type) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'log.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            fetchLogs();
        }
    };
    xhr.send('logType=' + type);
}

function fetchLogs() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'fetch_logs.php', true);
    xhr.onload = function() {
        if (this.status === 200) {
            const logs = JSON.parse(this.responseText);
            const logList = document.getElementById('log-list');
            logList.innerHTML = '';
            let totalMinutes = 0;
            let lastLogType = '';
            let lastLogTime = '';

            logs.forEach(function(log) {
                const logItem = document.createElement('li');
                logItem.textContent = `Logg ${log.log_type === 'inn' ? 'inn' : 'ut'}: ${new Date(log.log_time).toLocaleString('no-NO')}`;
                logList.appendChild(logItem);

                if (lastLogType === 'inn' && log.log_type === 'ut') {
                    const diff = (new Date(log.log_time) - new Date(lastLogTime)) / 1000 / 60;
                    totalMinutes += diff;
                } else if (lastLogType === 'ut' && log.log_type === 'inn') {
                    const diff = (new Date(log.log_time) - new Date(lastLogTime)) / 1000 / 60;
                    totalMinutes -= diff;
                }

                lastLogType = log.log_type;
                lastLogTime = log.log_time;
            });

            document.getElementById('time-status').textContent = `Total tid i pluss/minus: ${totalMinutes} minutter`;
        }
    };
    xhr.send();
}

document.addEventListener('DOMContentLoaded', fetchLogs);
