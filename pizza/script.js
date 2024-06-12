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
          <h3>${price}</h3>
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




// Function to handle the add card form submission
function handleAddCard() {
  const section = document.getElementById('section').value;
  const title = document.getElementById('title').value;
  const price = document.getElementById('price').value;
  const description = document.getElementById('description').value;

  addCard(section, title, price, description);
}

// Function to handle the remove card form submission
function handleRemoveCard() {
  const number = parseInt(document.getElementById('removeNumber').value);

  removeCard(number);
}

document.addEventListener('DOMContentLoaded', (event) => {
  // Example usage:
  addCard('pizza', 'Margherita', '235/150,-', 'med tomat, basilikum og hvitløk');
  addCard('pizza', 'Vesuvio', '245/160,-', 'med skinke, ost og tomatsaus');
  addCard('pizza', 'Capricciosa', '255/170,-', 'med skinke, sopp, artisjokker og oliven');
});
