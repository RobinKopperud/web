document.addEventListener('DOMContentLoaded', () => {
    const gruppekode = window.gruppekode;
    const spiller_id = window.spiller_id;

    function oppdaterSpillere() {
        fetch(`/Web/blackjacj/php/get_players.php?gruppekode=${gruppekode}`)
            .then(res => res.json())
            .then(data => {
                const bord = document.getElementById('bord');
                bord.innerHTML = "";

                data.forEach((spiller, index) => {
                    const div = document.createElement('div');
                    div.className = `spiller pos${index + 1}`;
                    div.innerHTML = `
                        ${spiller.navn}<br>
                        <small>Saldo: ${Number(spiller.saldo).toLocaleString("no-NO", {minimumFractionDigits:2})} kr</small>
                        ${spiller.spiller_id == spiller_id ? '<p style="color: orange; margin-top:5px;">(deg)</p>' : ''}
                    `;
                    bord.appendChild(div);
                });
            });
    }

    setInterval(oppdaterSpillere, 7000); // Hver 7. sekund
});
