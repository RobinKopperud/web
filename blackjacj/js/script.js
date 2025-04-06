document.addEventListener('DOMContentLoaded', () => {
    const adminForm = document.getElementById('admin-login');
    const outputDiv = document.getElementById('admin-output');

    adminForm?.addEventListener('submit', e => {
        e.preventDefault();
        const password = document.getElementById('admin_password').value;

        fetch('php/admin_fetch.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `password=${encodeURIComponent(password)}`
        })
        .then(res => res.json())
        .then(data => {
            if (!Array.isArray(data)) return outputDiv.innerHTML = "Ingen grupper funnet.";

            let html = "<table border='1' style='width:100%; background:white; border-collapse:collapse;'><tr><th>Kode</th><th>Opprettet</th><th>Oppretter</th><th>Slett</th></tr>";
            data.forEach(gruppe => {
                html += `<tr>
                    <td>${gruppe.gruppekode}</td>
                    <td>${gruppe.opprettet_tidspunkt}</td>
                    <td>${gruppe.oppretter}</td>
                    <td><button onclick="slettGruppe(${gruppe.gruppe_id}, '${password}')">🗑️ Slett</button></td>
                </tr>`;
            });
            html += "</table>";
            outputDiv.innerHTML = html;
        });
    });
});

function slettGruppe(gruppe_id, password) {
    if (!confirm("Er du sikker på at du vil slette denne gruppen og alt tilhørende?")) return;

    fetch('php/admin_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `gruppe_id=${gruppe_id}&password=${encodeURIComponent(password)}`
    })
    .then(res => res.text())
    .then(msg => {
        alert(msg);
        location.reload();
    });
}
