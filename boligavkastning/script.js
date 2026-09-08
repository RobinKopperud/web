const $ = (id) => document.getElementById(id);
const fmtNok = (n) => new Intl.NumberFormat('nb-NO',{style:'currency',currency:'NOK',maximumFractionDigits:0}).format(Number.isFinite(n)?n:0);
const fmtNumber = (n) => new Intl.NumberFormat('nb-NO',{maximumFractionDigits:0}).format(n);
const fmtPct = (n) => `${(Number.isFinite(n)?n:0).toLocaleString('nb-NO',{minimumFractionDigits:1,maximumFractionDigits:1})} %`;
const val = (id) => Number($(id).value || 0);
let mode = 'simple';

function yearsBetween(){
  if(!$('purchaseDate').value || !$('saleDate').value) return 0;
  const [py,pm]=$('purchaseDate').value.split('-').map(Number);
  const [sy,sm]=$('saleDate').value.split('-').map(Number);
  return ((sy-py)*12+(sm-pm))/12;
}
function annuityPayment(principal,rate,years){
  if(principal<=0||years<=0)return 0;
  const r=rate/100/12,n=years*12;
  return r===0?principal/n:principal*(r/(1-Math.pow(1+r,-n)));
}
function loanAfter(principal,rate,years,months){
  const payment=annuityPayment(principal,rate,years),r=rate/100/12;
  let balance=principal;
  for(let i=0;i<Math.min(months,years*12);i+=1) balance=Math.max(0,balance-(payment-balance*r));
  return balance;
}
function calculate(){
  const purchase=val('purchasePrice'),sale=val('salePrice'),years=yearsBetween();
  const valid=years>0;
  $('dateError').textContent=valid?'':'Salgsdato må være etter kjøpsdato.';
  const appreciation=sale-purchase;
  const totalPct=purchase>0?appreciation/purchase*100:0;
  const annual=purchase>0&&sale>0&&valid?(Math.pow(sale/purchase,1/years)-1)*100:0;
  let profit=appreciation,profitPct=totalPct;
  let details=[['Kjøpspris',fmtNok(purchase)],['Salgspris',fmtNok(sale)],['Verdiøkning',fmtNok(appreciation)],['Total prisendring',fmtPct(totalPct)]];
  if(mode==='advanced'){
    const equity=val('equity'),buyCosts=purchase*val('buyCostPct')/100,loan=Math.max(0,purchase-equity);
    const payment=annuityPayment(loan,val('interestRate'),val('loanYears'));
    const months=Math.max(0,Math.round(years*12));
    const balance=loanAfter(loan,val('interestRate'),val('loanYears'),months);
    let ownerCosts=0;
    for(let m=0;m<months;m+=1){const factor=Math.pow(1+val('costGrowthPct')/100,m/12);ownerCosts+=(val('monthlyCommonCosts')+val('otherCostsYear')/12)*factor+payment;}
    const saleCosts=sale*val('sellCostPct')/100;
    const buyInvestment=equity+buyCosts;
    const fromSale=sale-saleCosts-balance;
    profit=fromSale-buyInvestment-ownerCosts;
    profitPct=buyInvestment>0?profit/buyInvestment*100:0;
    $('equityFromSale').textContent=fmtNok(fromSale);
    $('loanBalance').textContent=`Restlån: ${fmtNok(balance)}`;
    $('totalCosts').textContent=fmtNok(ownerCosts+buyCosts+saleCosts);
    $('monthlyPayment').textContent=`Terminbeløp: ${fmtNok(payment)} / md.`;
    const equityGain=fromSale-equity;
    const equityReturn=equity>0?equityGain/equity*100:0;
    $('equityReturn').textContent=equity>0?fmtPct(equityReturn):'–';
    $('equityReturnNote').textContent=equity>0?`${fmtNok(equityGain)} verdiøkning etter salg og restlån`:'Legg inn egenkapital for å se avkastningen';
    details=[...details,['Lån ved kjøp',fmtNok(loan)],['Kjøpskostnader',fmtNok(buyCosts)],['Salgskostnader',fmtNok(saleCosts)],['Løpende kostnader og terminbeløp',fmtNok(ownerCosts)],['Restlån ved salg',fmtNok(balance)],['Netto resultat på egenkapital',fmtNok(profit)]];
  }
  $('profitLabel').textContent=mode==='simple'?'Verdiøkning':'Netto resultat på egenkapital';
  $('mainProfit').textContent=valid?fmtNok(profit):'–';
  $('mainProfitPct').textContent=valid?`${fmtPct(profitPct)} totalt`:'Kontroller datoene';
  $('annualReturn').textContent=valid?fmtPct(mode==='simple'?annual:(profitPct>-100?((Math.pow(1+profitPct/100,1/years)-1)*100):0)):'–';
  $('holdingPeriod').textContent=valid?`${years.toLocaleString('nb-NO',{maximumFractionDigits:1})} år`:'–';
  const shownAnnual=mode==='simple'?annual:(profitPct>-100?((Math.pow(1+profitPct/100,1/years)-1)*100):0);
  $('returnInsight').textContent=!valid?'Kontroller datoene før resultatet kan vurderes.':shownAnnual<0?'Scenarioet gir negativ beregnet avkastning. Test pris, rente og kostnader.':shownAnnual<3?'Lav beregnet årsavkastning. Sammenlign gjerne med et mer forsiktig og et mer optimistisk scenario.':shownAnnual<6?'Moderat beregnet årsavkastning. Kostnader og salgstidspunkt har stor betydning.':'Sterk beregnet årsavkastning. Kontroller at salgspris og kostnader fortsatt er realistiske.';
  $('resultSalePrice').textContent=fmtNok(sale);
  $('saleDifference').textContent=`${appreciation>=0?'+':''}${fmtNok(appreciation)} fra kjøp`;
  $('details').innerHTML=details.map(([key,value])=>`<div><span>${key}</span><strong>${value}</strong></div>`).join('');
  $('salePriceOutput').textContent=`${fmtNumber(sale)} kr`;
}
function setMode(next){
  mode=next;
  document.querySelectorAll('.mode').forEach(btn=>{const active=btn.dataset.mode===mode;btn.classList.toggle('active',active);btn.setAttribute('aria-pressed',active);});
  $('advancedInputs').hidden=mode!=='advanced';
  document.querySelectorAll('.advanced-result').forEach(el=>el.hidden=mode!=='advanced');
  $('modeHelp').textContent=mode==='simple'?'Alt du trenger for et raskt overslag.':'Start med boligen, og finjuster kostnadene under.';
  calculate();
}
document.querySelectorAll('.mode').forEach(btn=>btn.addEventListener('click',()=>setMode(btn.dataset.mode)));
['purchasePrice','salePrice','purchaseDate','saleDate','equity','buyCostPct','interestRate','loanYears','monthlyCommonCosts','otherCostsYear','costGrowthPct','sellCostPct'].forEach(id=>$(id).addEventListener('input',calculate));
$('salePriceRange').addEventListener('input',()=>{$('salePrice').value=$('salePriceRange').value;calculate();});
$('salePrice').addEventListener('input',()=>{$('salePriceRange').value=Math.min(15000000,Math.max(500000,val('salePrice')));});
[['interestRate','interestRateOutput',' %'],['loanYears','loanYearsOutput',' år'],['costGrowthPct','costGrowthPctOutput',' %'],['sellCostPct','sellCostPctOutput',' %']].forEach(([input,output,suffix])=>$(input).addEventListener('input',()=>{$(output).textContent=`${val(input).toLocaleString('nb-NO')}${suffix}`;}));
calculate();

// Separate affordability calculator
function loanFromPayment(monthly, annualRate, years) {
  if (monthly <= 0 || years <= 0) return 0;
  const r = annualRate / 100 / 12;
  const n = years * 12;
  return r === 0 ? monthly * n : monthly * (1 - Math.pow(1 + r, -n)) / r;
}
function calculateLoan() {
  const income = val('annualIncome'), otherDebt = val('otherDebt'), budget = val('housingBudget');
  const rate = val('loanCalcRate'), years = val('loanCalcYears');
  const stressedRate = rate + ($('stressTest').checked ? 3 : 0);
  const sharedDebt = val('sharedDebt'), sharedMonthly = val('sharedDebtMonthly'), commonCost = val('commonCost');
  const fixedExpenses = val('fixedExpenses'), childAllowance = val('children') * 3500;
  const purchaseCosts = val('purchaseCosts'), equity = val('loanEquity');
  const debtLimit = Math.max(0, income * 5 - otherDebt - sharedDebt);
  const availableForPrivateLoan = Math.max(0, budget - sharedMonthly - commonCost - fixedExpenses - childAllowance);
  const paymentLimit = loanFromPayment(availableForPrivateLoan, stressedRate, years);
  const maxLoan = Math.max(0, Math.min(debtLimit, paymentLimit));
  const askingPrice = val('askingPrice'), totalPrice = askingPrice + sharedDebt;
  const requiredPrivateLoan = Math.max(0, askingPrice + purchaseCosts - equity);
  const requiredEquity = totalPrice * val('equityRequirement') / 100 + purchaseCosts;
  const stressedPrivatePayment = annuityPayment(requiredPrivateLoan, stressedRate, years);
  const actualPrivatePayment = annuityPayment(requiredPrivateLoan, rate, years);
  const allMonthly = actualPrivatePayment + sharedMonthly + commonCost;
  const stressedMargin = budget - fixedExpenses - childAllowance - sharedMonthly - commonCost - stressedPrivatePayment;
  const debtOk = requiredPrivateLoan <= debtLimit + 1;
  const paymentOk = requiredPrivateLoan <= paymentLimit + 1;
  const equityOk = equity >= requiredEquity;
  const caseOk = debtOk && paymentOk && equityOk;
  const maxTotalPrice = maxLoan + equity + sharedDebt - purchaseCosts;

  $('maxLoan').textContent = fmtNok(maxLoan);
  $('purchasePower').textContent = fmtNok(Math.max(0, maxTotalPrice));
  $('calcPayment').textContent = `${fmtNok(annuityPayment(maxLoan, rate, years))} / md.`;
  $('stressRate').textContent = fmtPct(stressedRate);
  const limits = [{name:'Samlet gjeldsgrad',value:debtLimit},{name:'Månedsbudsjettet',value:paymentLimit}].sort((a,b)=>a.value-b.value);
  $('loanLimitText').textContent = `${limits[0].name} setter grensen. Fellesgjelden er regnet som gjeld.`;
  $('caseTotalPrice').textContent = fmtNok(totalPrice);
  $('caseMonthlyCost').textContent = `${fmtNok(allMonthly)} / md.`;
  $('caseMargin').textContent = `${stressedMargin >= 0 ? '+' : '−'}${fmtNok(Math.abs(stressedMargin))}`;
  $('caseMargin').classList.toggle('negative', stressedMargin < 0);
  const verdict = $('caseVerdict');
  verdict.classList.toggle('not-ok', !caseOk);
  verdict.querySelector('span').textContent = caseOk ? '✓' : '!';
  verdict.querySelector('strong').textContent = caseOk ? 'Boligen er innenfor estimert låneramme' : 'Boligen er utenfor estimert låneramme';
  const reasons=[];
  if(!debtOk) reasons.push(`gjeldsrammen mangler ${fmtNok(requiredPrivateLoan-debtLimit)}`);
  if(!paymentOk) reasons.push(`betjeningsevnen mangler ${fmtNok(Math.abs(stressedMargin))} per måned`);
  if(!equityOk) reasons.push(`egenkapitalen mangler ${fmtNok(requiredEquity-equity)}`);
  verdict.querySelector('p').textContent = caseOk ? `Beregnet privat lån er ${fmtNok(requiredPrivateLoan)}. Du har ${fmtNok(stressedMargin)} i månedlig margin etter stresstesten.` : `Årsak: ${reasons.join(', ')}.`;
  const debtImpact = Math.min(sharedDebt, Math.max(0, income * 5 - otherDebt));
  $('loanInsight').querySelector('p').innerHTML = `Fellesgjelden på <strong>${fmtNok(sharedDebt)}</strong> reduserer gjeldsrammen med opptil <strong>${fmtNok(debtImpact)}</strong>. I tillegg bruker renter og avdrag <strong>${fmtNok(sharedMonthly)}</strong> av månedsbudsjettet, mens <strong>${fmtNok(commonCost)}</strong> er ren drift.`;
}
function setView(view) {
  $('returnView').hidden = view !== 'return'; $('loanView').hidden = view !== 'loan';
  document.querySelectorAll('.app-tab').forEach((button) => { const active = button.dataset.view === view; button.classList.toggle('active', active); button.setAttribute('aria-pressed', active); });
  if (view === 'loan') calculateLoan();
}
document.querySelectorAll('.app-tab').forEach((button) => button.addEventListener('click', () => setView(button.dataset.view)));
['annualIncome','otherDebt','housingBudget','loanEquity','loanCalcRate','loanCalcYears','commonCost','stressTest','children','fixedExpenses','purchaseCosts','equityRequirement','askingPrice','sharedDebt','sharedDebtMonthly'].forEach((id) => $(id).addEventListener('input', calculateLoan));
$('loanCalcRate').addEventListener('input', () => { $('loanCalcRateOutput').textContent = `${val('loanCalcRate').toLocaleString('nb-NO')} %`; });
$('loanCalcYears').addEventListener('input', () => { $('loanCalcYearsOutput').textContent = `${val('loanCalcYears')} år`; });
calculateLoan();

// Scenarier, hurtigvalg og delbart sammendrag
const scenarioFields = [
  'purchasePrice','salePrice','purchaseDate','saleDate','equity','buyCostPct','interestRate','loanYears','monthlyCommonCosts','otherCostsYear','costGrowthPct','sellCostPct',
  'annualIncome','otherDebt','housingBudget','loanEquity','loanCalcRate','loanCalcYears','commonCost','children','fixedExpenses','purchaseCosts','equityRequirement','askingPrice','sharedDebt','sharedDebtMonthly'
];
const scenarioStorageKey = 'boligkalkulator-scenarios-v1';
let activeView = 'return';
const captureScenario = () => ({
  fields: Object.fromEntries(scenarioFields.map(id => [id, $(id).value])),
  stressTest: $('stressTest').checked,
  mode,
  view: activeView
});
const initialScenario = captureScenario();
const readScenarios = () => { try { return JSON.parse(localStorage.getItem(scenarioStorageKey) || '{}'); } catch { return {}; } };
const writeScenarios = (scenarios) => localStorage.setItem(scenarioStorageKey, JSON.stringify(scenarios));
const setScenarioStatus = (message) => { $('scenarioStatus').textContent = message; window.clearTimeout(setScenarioStatus.timer); setScenarioStatus.timer = window.setTimeout(() => $('scenarioStatus').textContent = '', 3500); };
const applyScenario = (scenario) => {
  if (!scenario?.fields) return;
  scenarioFields.forEach(id => { if (scenario.fields[id] !== undefined) $(id).value = scenario.fields[id]; });
  $('stressTest').checked = scenario.stressTest !== false;
  $('salePriceRange').value = Math.min(15000000, Math.max(500000, val('salePrice')));
  setMode(scenario.mode === 'advanced' ? 'advanced' : 'simple');
  setView(scenario.view === 'loan' ? 'loan' : 'return');
  activeView = scenario.view === 'loan' ? 'loan' : 'return';
  document.querySelectorAll('input').forEach(input => input.dispatchEvent(new Event('input', {bubbles:true})));
};
const refreshScenarioOptions = (selected = '') => {
  const scenarios = readScenarios();
  $('savedScenarios').replaceChildren(new Option('Velg scenario', ''));
  Object.keys(scenarios).sort((a,b)=>a.localeCompare(b,'nb')).forEach(name => $('savedScenarios').add(new Option(name, name)));
  $('savedScenarios').value = selected;
  $('deleteScenario').disabled = !selected;
};
document.querySelectorAll('.app-tab').forEach(button => button.addEventListener('click', () => { activeView = button.dataset.view; }));
document.querySelectorAll('[data-preset]').forEach(button => button.addEventListener('click', () => {
  const preset = button.dataset.preset;
  const years = Math.max(1, yearsBetween());
  const annualGrowth = preset === 'cautious' ? 0.02 : preset === 'optimistic' ? 0.06 : 0.04;
  $('salePrice').value = Math.round(val('purchasePrice') * Math.pow(1 + annualGrowth, years) / 50000) * 50000;
  $('interestRate').value = preset === 'cautious' ? 7 : preset === 'optimistic' ? 4.5 : 5.5;
  $('costGrowthPct').value = preset === 'cautious' ? 4 : preset === 'optimistic' ? 2 : 3;
  $('salePriceRange').value = Math.min(15000000, Math.max(500000, val('salePrice')));
  ['salePrice','interestRate','costGrowthPct'].forEach(id => $(id).dispatchEvent(new Event('input', {bubbles:true})));
  setScenarioStatus(`${button.textContent}-scenario er lagt inn.`);
}));
$('saveScenario').addEventListener('click', () => {
  const name = $('scenarioName').value.trim();
  if (!name) { $('scenarioName').focus(); setScenarioStatus('Gi scenarioet et navn først.'); return; }
  const scenarios = readScenarios(); scenarios[name] = captureScenario(); writeScenarios(scenarios); refreshScenarioOptions(name); setScenarioStatus(`«${name}» er lagret i denne nettleseren.`);
});
$('savedScenarios').addEventListener('change', () => {
  const name = $('savedScenarios').value; $('deleteScenario').disabled = !name;
  if (name) { $('scenarioName').value = name; applyScenario(readScenarios()[name]); setScenarioStatus(`«${name}» er lastet.`); }
});
$('deleteScenario').addEventListener('click', () => {
  const name = $('savedScenarios').value; if (!name) return;
  const scenarios = readScenarios(); delete scenarios[name]; writeScenarios(scenarios); refreshScenarioOptions(); $('scenarioName').value=''; setScenarioStatus(`«${name}» er slettet.`);
});
$('resetCalculator').addEventListener('click', () => { applyScenario(initialScenario); $('scenarioName').value=''; $('savedScenarios').value=''; $('deleteScenario').disabled=true; setScenarioStatus('Kalkulatoren er nullstilt.'); });
$('copySummary').addEventListener('click', async () => {
  const summary = activeView === 'loan'
    ? `Boligscenario: makslån ${$('maxLoan').textContent}, totalpris ${$('caseTotalPrice').textContent}, samlet boligkostnad ${$('caseMonthlyCost').textContent}, margin ${$('caseMargin').textContent}.`
    : `Boligscenario: kjøp ${fmtNok(val('purchasePrice'))}, salg ${fmtNok(val('salePrice'))}, resultat ${$('mainProfit').textContent}, gjennomsnitt ${$('annualReturn').textContent} per år.`;
  try { await navigator.clipboard.writeText(summary); setScenarioStatus('Sammendraget er kopiert.'); } catch { setScenarioStatus('Nettleseren tillot ikke kopiering.'); }
});
refreshScenarioOptions();
