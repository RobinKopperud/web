document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const spiller_id = params.get('spiller_id');
    const gruppe_id = window.gruppe_id; // Satt fra PHP

    function checkForProposal() {
        fetch(`/Web/blackjacj/php/check_proposals.php?gruppe_id=${gruppe_id}&spiller_id=${spiller_id}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.transaksjon_id) {
                    document.getElementById("proposal-text").innerText =
                        `${data.navn} foreslår ny saldo: ${data.belop} kr`;

                    document.getElementById("transaksjon_id_accept").value = data.transaksjon_id;
                    document.getElementById("transaksjon_id_reject").value = data.transaksjon_id;
                    document.getElementById("proposal-popup").style.display = "block";
                }
            });
    }

    // Sjekk hvert 5. sekund
    setInterval(checkForProposal, 5000);
});
