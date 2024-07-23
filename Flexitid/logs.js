document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('login-btn').addEventListener('click', function() {
        logTime('inn');
    });

    document.getElementById('logout-btn').addEventListener('click', function() {
        logTime('ut');
    });

    fetchLogs();
});

function logTime(type) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'log.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.success) {
                fetchLogs();
                if (type === 'inn') {
                    alert('Du logget inn på: ' + response.logTime);
                } else if (type === 'ut') {
                    alert('Du logget ut på: ' + response.logTime);
                }
            } else {
                alert('En feil oppstod: ' + response.error);
            }
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
            let totalMinutesToday = 0;
            let totalMinutesWeek = 0;
            let lastLogTimeToday = '';
            let lastLogTimeWeek = '';

            const todayStart = new Date().setHours(0, 0, 0, 0);
            const todayEnd = new Date().setHours(23, 59, 59, 999);
            const weekStart = new Date();
            weekStart.setDate(weekStart.getDate() - weekStart.getDay() + 1);
            weekStart.setHours(0, 0, 0, 0);
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);
            weekEnd.setHours(23, 59, 59, 999);

            logs.forEach(function(log) {
                const logTime = new Date(log.log_time);
                if (logTime >= todayStart && logTime <= todayEnd) {
                    if (lastLogTimeToday) {
                        const diff = (logTime - new Date(lastLogTimeToday)) / 1000 / 60;
                        if (lastLogType === 'inn' && log.log_type === 'ut') {
                            totalMinutesToday += diff; // Tid brukt på jobb i dag
                        }
                    }
                    lastLogTimeToday = log.log_time;
                }

                if (logTime >= weekStart && logTime <= weekEnd) {
                    if (lastLogTimeWeek) {
                        const diff = (logTime - new Date(lastLogTimeWeek)) / 1000 / 60;
                        if (lastLogType === 'inn' && log.log_type === 'ut') {
                            totalMinutesWeek += diff; // Tid brukt på jobb denne uken
                        }
                    }
                    lastLogTimeWeek = log.log_time;
                }

                lastLogType = log.log_type;
            });

            const standardWorkDayMinutes = 480; // 8 timer * 60 minutter
            const flexitimeBalance = totalMinutesToday - standardWorkDayMinutes;

            document.getElementById('today-time').textContent = `Tid brukt i dag: ${totalMinutesToday} minutter`;
            document.querySelector('.container p').textContent = `Total tid denne uken: ${totalMinutesWeek} minutter`;
            document.getElementById('flexitime-balance').textContent = `Fleksitid balanse: ${flexitimeBalance} minutter`;

            // Aktiver eller deaktiver knapper basert på siste loggtype
            document.getElementById('login-btn').disabled = (lastLogType === 'inn');
            document.getElementById('logout-btn').disabled = (lastLogType !== 'inn');
        }
    };
    xhr.send();
}
