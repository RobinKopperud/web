// Function to add a card
function addCard(sectionId, title, price, description) {
  const section = document.getElementById(sectionId + '-section');
  const menu = section.querySelector('.menu');

  const card = document.createElement('div');
  card.className = 'card';
  card.innerHTML = `
      <div class="number">0</div>
      <div class="card-content">
          <h3>${title}</h3>
          <p>${price}</p>
          <p>${description}</p>
      </div>
  `;
  menu.appendChild(card);
  renumberCards();  // Renumber all cards after adding a new one
}


// Function to get the next available number across all sections
function getNextNumber() {
  const cards = document.querySelectorAll('.card .number');
  const numbers = Array.from(cards).map(card => parseInt(card.textContent));
  return numbers.length > 0 ? Math.max(...numbers) + 1 : 1;
}


// Function to remove a card by its number
function removeCard(number) {
  const cards = document.querySelectorAll('.card');
  for (let card of cards) {
      if (card.querySelector('.number').textContent === number.toString()) {
          card.remove();
          break;  // Stop after removing the first matching card
      }
  }
  renumberCards();  // Renumber remaining cards across all sections
}




// Function to renumber the cards in order across all sections
function renumberCards() {
  const sections = ['pizza', 'kebab', 'grill'];
  let number = 1;
  sections.forEach(sectionId => {
      const section = document.getElementById(sectionId + '-section');
      const menu = section.querySelector('.menu');
      const cards = menu.querySelectorAll('.card');
      cards.forEach(card => {
          card.querySelector('.number').textContent = number++;
      });
  });
}




// Function to handle the add card form submission using Fetch API
// Function to handle the add card form submission using Fetch API
function handleAddCard() {
  const section = document.getElementById('section').value;
  const title = document.getElementById('title').value;
  const price = document.getElementById('price').value;
  const description = document.getElementById('description').value;

  // Data to be sent in the POST request
  const data = new URLSearchParams({
      title: title,
      price: price,
      description: description
  });

  fetch('pizzadev/add_pizza.php', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: data
  })
  .then(response => response.text())
  .then(text => {
      console.log(text);

      // Alert the user based on the response from the server
      if (text.includes("New record created successfully")) {
          alert("Pizza added successfully!");
      } else {
          alert("Failed to add pizza: " + text);
      }
  })
  .catch(error => {
      console.error('Error:', error);
      alert("Failed to add pizza due to an error.");
  });
}





// Function to handle the remove card form submission
function handleRemoveCard() {
  const number = parseInt(document.getElementById('removeNumber').value);

  removeCard(number);
}

document.addEventListener('DOMContentLoaded', (event) => {
});
