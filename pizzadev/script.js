document.addEventListener('DOMContentLoaded', () => {
    // Check local storage for liked dishes
    const likedDishes = JSON.parse(localStorage.getItem('likedDishes')) || [];

    // Update the UI to reflect liked dishes
    likedDishes.forEach(dishId => {
        const button = document.querySelector(`.heart-button[data-id="${dishId}"]`);
        if (button) {
            button.classList.add('liked');
        }
    });

    document.querySelectorAll('.heart-button').forEach(button => {
        button.addEventListener('click', () => {
            const dishId = button.getAttribute('data-id');
            const isLiked = button.classList.toggle('liked');
            const action = isLiked ? 'like' : 'unlike';

            fetch('update_hearts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id=${dishId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const heartCountSpan = button.querySelector('.heart-count');
                    heartCountSpan.textContent = data.hearts;

                    // Update local storage
                    if (isLiked) {
                        likedDishes.push(dishId);
                    } else {
                        const index = likedDishes.indexOf(dishId);
                        if (index > -1) {
                            likedDishes.splice(index, 1);
                        }
                    }
                    localStorage.setItem('likedDishes', JSON.stringify(likedDishes));
                } else {
                    alert('Failed to update hearts: ' + data.message);
                    button.classList.toggle('liked'); // Revert the UI change
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update hearts due to an error.');
                button.classList.toggle('liked'); // Revert the UI change
            });
        });
    });
});



function scrollToSection(sectionId) {
    document.getElementById(sectionId).scrollIntoView({ behavior: 'smooth' });
}
