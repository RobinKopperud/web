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
    details=[...details,['Lån ved kjøp',fmtNok(loan)],['Kjøpskostnader',fmtNok(buyCosts)],['Salgskostnader',fmtNok(saleCosts)],['Løpende kostnader og terminbeløp',fmtNok(ownerCosts)],['Restlån ved salg',fmtNok(balance)],['Netto resultat på egenkapital',fmtNok(profit)]];
  }
  $('profitLabel').textContent=mode==='simple'?'Verdiøkning':'Netto resultat på egenkapital';
  $('mainProfit').textContent=valid?fmtNok(profit):'–';
  $('mainProfitPct').textContent=valid?`${fmtPct(profitPct)} totalt`:'Kontroller datoene';
  $('annualReturn').textContent=valid?fmtPct(mode==='simple'?annual:(profitPct>-100?((Math.pow(1+profitPct/100,1/years)-1)*100):0)):'–';
  $('holdingPeriod').textContent=valid?`${years.toLocaleString('nb-NO',{maximumFractionDigits:1})} år`:'–';
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
