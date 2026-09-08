(() => {
  const search = document.getElementById('stationSearch');
  const cards = Array.from(document.querySelectorAll('.station-card'));
  const pins = Array.from(document.querySelectorAll('.map-pin'));
  const count = document.getElementById('stationCount');
  const empty = document.getElementById('stationEmpty');
  let filter = 'all';

  const update = () => {
    const term = (search?.value || '').trim().toLocaleLowerCase('nb-NO');
    let visibleCount = 0;
    cards.forEach(card => {
      const matchesText = !term || card.dataset.name.includes(term);
      const matchesType = filter === 'all' || card.dataset.types.split(' ').includes(filter);
      const visible = matchesText && matchesType;
      card.hidden = !visible;
      document.querySelector(`[data-station-target="${card.id}"]`)?.toggleAttribute('hidden', !visible);
      if (visible) visibleCount += 1;
    });
    if (count) count.textContent = `${visibleCount} ${visibleCount === 1 ? 'stasjon' : 'stasjoner'}`;
    if (empty) empty.hidden = visibleCount !== 0;
  };

  search?.addEventListener('input', update);
  document.querySelectorAll('.filter-chip').forEach(button => button.addEventListener('click', () => {
    filter = button.dataset.filter;
    document.querySelectorAll('.filter-chip').forEach(item => {
      const active = item === button;
      item.classList.toggle('active', active);
      item.setAttribute('aria-pressed', String(active));
    });
    update();
  }));
  pins.forEach(pin => pin.addEventListener('click', () => {
    const card = document.getElementById(pin.dataset.stationTarget);
    if (!card || card.hidden) return;
    cards.forEach(item => item.classList.remove('highlight'));
    card.classList.add('highlight');
    card.scrollIntoView({behavior:'smooth', block:'center'});
    window.setTimeout(() => card.classList.remove('highlight'), 2500);
  }));
  update();
})();
