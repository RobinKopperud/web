let players = [];

function addPlayer() {
  const nameInput = document.getElementById("player-name");
  const name = nameInput.value.trim();
  if (!name) return;

  const player = {
    name,
    amount: null,
    approvals: [],
    isApproved: false,
  };

  players.push(player);
  nameInput.value = "";
  renderPlayers();
}

function submitAmount(index, amount) {
  players[index].amount = parseFloat(amount);
  players[index].approvals = [];
  players[index].isApproved = false;
  renderPlayers();
}

function approveAmount(index, approver) {
  const player = players[index];
  if (!player.approvals.includes(approver)) {
    player.approvals.push(approver);
  }

  if (player.approvals.length >= Math.ceil(players.length / 2)) {
    player.isApproved = true;
  }

  renderPlayers();
}

function renderPlayers() {
  const list = document.getElementById("player-list");
  list.innerHTML = "";

  players.forEach((player, index) => {
    const li = document.createElement("li");
    li.className = "player";

    li.innerHTML = `
      <strong>${player.name}</strong><br/>
      Beløp: ${player.amount !== null ? player.amount + " kr" : "Ingen"}<br/>
      ${player.amount === null
        ? `<div class="amount-form">
             <input type="number" placeholder="Beløp" id="amount-${index}" />
             <button onclick="submitAmount(${index}, document.getElementById('amount-${index}').value)">Send inn</button>
           </div>`
        : `<div class="approvals">
             Godkjenninger: ${player.approvals.length} / ${Math.ceil(players.length / 2)}<br/>
             ${player.isApproved ? "<strong>GODKJENT</strong>" : players.map((p, i) =>
                i !== index && !player.approvals.includes(p.name)
                  ? `<button onclick="approveAmount(${index}, '${p.name}')">Godkjenn som ${p.name}</button>`
                  : ""
              ).join("<br/>")}
           </div>`}
    `;

    list.appendChild(li);
  });
}
