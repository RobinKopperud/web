function logTime(type) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/handle_log.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.withCredentials = true; // Ensure cookies are sent with the request
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    document.getElementById('confirmationMessage').textContent = response.message;
                    document.getElementById('confirmationModal').style.display = 'block';
                    document.getElementById('logType').value = type;
                } else {
                    alert('En feil oppstod: ' + response.error);
                }
            } catch (e) {
                console.error('Error parsing JSON response:', e);
                console.error('Response text:', xhr.responseText);
                alert('En feil oppstod ved parsing av serverens respons.');
            }
        }
    };
    xhr.send('logType=' + type);
}

function confirmLog() {
    const logType = document.getElementById('logType').value;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/confirm_log.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.withCredentials = true; // Ensure cookies are sent with the request
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    alert(response.message);
                    location.reload(); // Refresh the page to update the displayed times
                } else {
                    alert('En feil oppstod: ' + response.error);
                }
            } catch (e) {
                console.error('Error parsing JSON response:', e);
                console.error('Response text:', xhr.responseText);
                alert('En feil oppstod ved parsing av serverens respons.');
            }
        }
    };
    xhr.send('logType=' + logType);
}

function denyLog() {
    document.getElementById('confirmationModal').style.display = 'none';
    alert('Vennligst legg inn timer manuelt.');
}
