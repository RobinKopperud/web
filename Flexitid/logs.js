document.getElementById('login-btn').addEventListener('click', function() {
    logTime('inn');
});

document.getElementById('logout-btn').addEventListener('click', function() {
    logTime('ut');
});

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
            const response = JSON.parse(this.responseText);
            const logs = response.logs;
            const lastLogType = response.lastLogType;
            let totalMinutes = 0;
            let lastLogTime = '';

            logs.forEach(function(log) {
                if (lastLogTime) {
                    const diff = (new Date(log.log_time) - new Date(lastLogTime)) / 1000 / 60;
                    totalMinutes += (log.log_type === 'inn' ? diff : -diff);
                }
                lastLogTime = log.log_time;
            });

            document.querySelector('.container p').textContent = `Total tid i pluss/minus: ${totalMinutes} minutter`;

            // Enable or disable buttons based on the last log type
            document.getElementById('login-btn').disabled = (lastLogType === 'inn');
            document.getElementById('logout-btn').disabled = (lastLogType !== 'inn');
        }
    };
    xhr.send();
}

document.addEventListener('DOMContentLoaded', fetchLogs);
