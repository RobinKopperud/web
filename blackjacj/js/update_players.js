document.addEventListener('DOMContentLoaded', () => {
    const gruppekode = window.gruppekode;
    const spiller_id = window.spiller_id;

    let forrigeAntall = null;

    function oppdaterSpillere() {
        fetch(`/Web/blackjacj/php/get_players.php?gruppekode=${window.gruppekode}`)
            .then(res => res.json())
            .then(data => {
                if (forrigeAntall === null) {
                    forrigeAntall = data.length;
                } else if (data.length > forrigeAntall) {
                    // Noen nye har kommet inn → last siden på nytt
                    location.reload();
                }
            });
    }


    setInterval(oppdaterSpillere, 7000); // Hver 7. sekund
});
