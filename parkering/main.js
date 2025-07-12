// Init state
let rolle = null;
let data = [];

window.addEventListener('load', () => {
  fetch('api/session.php') // Sjekk om bruker er logget inn
    .then(r => r.json())
    .then(res => {
      if (res.loggedIn) {
        rolle = res.rolle;
        visApp();
      } else {
        document.getElementById('loginView').style.display = 'block';
      }
    });
});

// Login form handler
document.getElementById('loginForm').addEventListener('submit', e => {
  e.preventDefault();
  const formData = new FormData(e.target);
  fetch('api/login.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        rolle = res.rolle;
        visApp();
      } else {
        document.getElementById('loginError').style.display = 'block';
      }
    });
});

function visApp() {
  document.getElementById('loginView').style.display = 'none';
  document.getElementById('appView').style.display = 'block';
  initMap();
}

document.getElementById('loggUt').addEventListener('click', () => {
  fetch('api/logout.php').then(() => location.reload());
});

// Map and features
function initMap() {
  const map = L.map('map').setView([59.912, 10.75], 17);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

  fetch('api/hent.php')
    .then(r => r.json())
    .then(json => {
      data = json;
      data.forEach(anlegg => {
        const marker = L.marker(anlegg.posisjon).addTo(map);
        marker.bindPopup(anlegg.navn);
        marker.on('click', () => visOversikt(anlegg));
      });
    });
}

function visOversikt(anlegg) {
  skjulAlt();
  document.getElementById('tilbake').style.display = 'block';
  const oversikt = document.getElementById('oversikt');
  oversikt.innerHTML = `<h2>${anlegg.navn}</h2><div class="grid"></div>`;
  const grid = oversikt.querySelector('.grid');
  anlegg.plasser.forEach(p => {
    const div = document.createElement('div');
    div.className = `plass ${p.status} ${p.elbillader ? 'elbil' : ''}`;
    div.innerText = `Plass ${p.nummer}`;
    div.onclick = () => visPopup(p);
    grid.appendChild(div);
  });
  oversikt.style.display = 'block';
}

function visPopup(plass) {
  document.getElementById('popup').style.display = 'block';
  document.getElementById('popup-tittel').innerText = `Plass ${plass.nummer}`;
  document.getElementById('popup-eier').innerText = `Eier: ${plass.eier || 'Ikke tildelt'}`;
  document.getElementById('popup-pris').innerText = `Pris: ${plass.pris_per_mnd} kr/mnd`;

  const kontraktDiv = document.getElementById('popup-lenke');
  if (rolle === 'admin' && plass.kontrakt) {
    kontraktDiv.innerHTML = `<a href="${plass.kontrakt}" target="_blank">Vis kontrakt</a>`;
  } else {
    kontraktDiv.innerHTML = '';
  }
}

function lukkPopup() {
  document.getElementById('popup').style.display = 'none';
}

function skjulAlt() {
  document.getElementById('map').style.display = 'none';
  document.getElementById('oversikt').style.display = 'none';
  document.getElementById('rapport').style.display = 'none';
}

document.getElementById('tilbake').addEventListener('click', () => {
  skjulAlt();
  document.getElementById('map').style.display = 'block';
  document.getElementById('tilbake').style.display = 'none';
});

document.getElementById('vis-rapport').addEventListener('click', () => {
  skjulAlt();
  document.getElementById('rapport').style.display = 'block';
  genererRapport(data);
});

function genererRapport(data) {
  const tbody = document.getElementById('rapport-innhold');
  tbody.innerHTML = "";
  data.forEach(anlegg => {
    const rad = document.createElement("tr");
    rad.innerHTML = `
      <td>${anlegg.navn}</td>
      <td>${anlegg.plasser.length}</td>
      <td>${anlegg.plasser.filter(p => p.status === "ledig").length}</td>
      <td>${anlegg.plasser.filter(p => p.status === "opptatt").length}</td>
      <td>${anlegg.plasser.filter(p => p.elbillader).length}</td>
      <td>${anlegg.plasser.filter(p => p.kontrakt && p.kontrakt.length > 0).length}</td>
    `;
    tbody.appendChild(rad);
  });
}

document.getElementById('registerForm').addEventListener('submit', e => {
  e.preventDefault();
  const formData = new FormData(e.target);
  fetch('api/register.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        document.getElementById('registerMsg').style.display = 'block';
      } else {
        alert(res.message || 'Registrering feilet');
      }
    });
});

