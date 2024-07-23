function fetchTopUsers() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'fetch_top_users.php', true);
    xhr.onload = function() {
        if (this.status === 200) {
            const topUsers = JSON.parse(this.responseText);
            const topList = document.getElementById('top-list');
            topList.innerHTML = '';
            topUsers.forEach(function(user) {
                const userItem = document.createElement('li');
                userItem.textContent = `${user.username}: ${user.flex_time} minutter`;
                topList.appendChild(userItem);
            });
        }
    };
    xhr.send();
}

document.addEventListener('DOMContentLoaded', function() {
    fetchTopUsers();
});
