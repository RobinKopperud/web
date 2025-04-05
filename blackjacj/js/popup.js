document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const gruppekode = params.get('gruppekode');
    const spiller_id = params.get('spiller_id');

    // Du må hente gruppe_id basert på gruppekode
    // For å gjøre det enkelt, legg gruppe_id i en JS-variabel fra PHP:
    const gruppe_id = window.gruppe_id;

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

    setInterval(checkForProposal, 5000);
});
