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
                            totalMinutesToday += diff; // Time spent working today
                        }
                    }
                    lastLogTimeToday = log.log_time;
                }

                if (logTime >= weekStart && logTime <= weekEnd) {
                    if (lastLogTimeWeek) {
                        const diff = (logTime - new Date(lastLogTimeWeek)) / 1000 / 60;
                        if (lastLogType === 'inn' && log.log_type === 'ut') {
                            totalMinutesWeek += diff; // Time spent working this week
                        }
                    }
                    lastLogTimeWeek = log.log_time;
                }

                lastLogType = log.log_type;
            });

            const standardWorkDayMinutes = 480; // 8 hours * 60 minutes
            const flexitimeBalance = totalMinutesToday - standardWorkDayMinutes;

            document.getElementById('today-time').textContent = `Time spent today: ${totalMinutesToday} minutes`;
            document.querySelector('.container p').textContent = `Total time this week: ${totalMinutesWeek} minutes`;
            document.getElementById('flexitime-balance').textContent = `Flexitime balance: ${flexitimeBalance} minutes`;

            // Enable or disable buttons based on the last log type
            document.getElementById('login-btn').disabled = (lastLogType === 'inn');
            document.getElementById('logout-btn').disabled = (lastLogType !== 'inn');
        }
    };
    xhr.send();
}
