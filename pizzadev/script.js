document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.heart-button').forEach(button => {
        button.addEventListener('click', () => {
            const dishId = button.getAttribute('data-id');

            fetch('update_hearts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id=${dishId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const heartCountSpan = button.querySelector('.heart-count');
                    heartCountSpan.textContent = data.hearts;
                } else {
                    alert('Failed to update hearts: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update hearts due to an error.');
            });
        });
    });
});


function scrollToSection(sectionId) {
    document.getElementById(sectionId).scrollIntoView({ behavior: 'smooth' });
}
