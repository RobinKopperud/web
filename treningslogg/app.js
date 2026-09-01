document.addEventListener('DOMContentLoaded', () => {
  const source = document.getElementById('chartData');
  const svg = document.getElementById('progressChart');
  if (!source || !svg) return;
  const series = JSON.parse(source.textContent || '[]');
  let selected = series[0]?.id;
  let days = 90;
  const ns = 'http://www.w3.org/2000/svg';
  const line = svg.querySelector('.overview-line');
  const area = svg.querySelector('.overview-area');
  const pointsGroup = svg.querySelector('.overview-points');
  const empty = svg.querySelector('.chart-empty');
  const grid = svg.querySelector('.overview-grid');
  [50,100,150,200].forEach(y => { const l=document.createElementNS(ns,'line'); l.setAttribute('x1','36');l.setAttribute('x2','620');l.setAttribute('y1',y);l.setAttribute('y2',y);grid.appendChild(l); });
  function render() {
    const current = series.find(item => item.id === selected);
    const cutoff = days ? Date.now() - days * 86400000 : 0;
    const entries = (current?.entries || []).filter(e => new Date(`${e.entry_date}T00:00:00`).getTime() >= cutoff);
    pointsGroup.innerHTML = '';
    if (!entries.length) { line.setAttribute('d',''); area.setAttribute('d',''); empty.style.display='block'; document.getElementById('chartLatest').textContent='–'; return; }
    empty.style.display='none';
    const values=entries.map(e=>Number(e.value)), min=Math.min(...values), max=Math.max(...values), spread=max-min||1;
    const pts=entries.map((e,i)=>({x:36+(i/Math.max(entries.length-1,1))*584,y:220-((Number(e.value)-min)/spread)*170,value:Number(e.value)}));
    const d=pts.map((p,i)=>`${i?'L':'M'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' ');
    line.setAttribute('d',d); area.setAttribute('d',`${d} L ${pts.at(-1).x} 220 L ${pts[0].x} 220 Z`);
    pts.forEach(p=>{const c=document.createElementNS(ns,'circle');c.setAttribute('cx',p.x);c.setAttribute('cy',p.y);c.setAttribute('r','4');pointsGroup.appendChild(c);});
    const change=values.at(-1)-values[0];
    document.getElementById('chartLatest').textContent=`${values.at(-1).toLocaleString('nb-NO',{maximumFractionDigits:1})} cm`;
    document.getElementById('chartChange').textContent=entries.length>1?`${change>=0?'+':''}${change.toLocaleString('nb-NO',{maximumFractionDigits:1})} cm i perioden`:'Én registrering i perioden';
  }
  document.querySelectorAll('.metric-tab').forEach(b=>b.addEventListener('click',()=>{selected=Number(b.dataset.series);document.querySelectorAll('.metric-tab').forEach(x=>x.classList.toggle('active',x===b));render();}));
  document.querySelectorAll('.period-tabs button').forEach(b=>b.addEventListener('click',()=>{days=Number(b.dataset.days);document.querySelectorAll('.period-tabs button').forEach(x=>x.classList.toggle('active',x===b));render();}));
  render();
});
