function logTime(type) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'includes/handle_log.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                document.getElementById('confirmationMessage').textContent = response.message;
                document.getElementById('confirmationModal').style.display = 'block';
                document.getElementById('logType').value = type;
            } else {
                alert('En feil oppstod: ' + response.error);
            }
        }
    };
    xhr.send('logType=' + type);
}

function confirmLog() {
    document.getElementById('logForm').submit();
}

function denyLog() {
    document.getElementById('confirmationModal').style.display = 'none';
    alert('Vennligst legg inn timer manuelt.');
}
