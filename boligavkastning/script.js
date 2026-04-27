const ids = [
  'purchasePrice', 'equity', 'buyCostPct', 'interestRate', 'loanYears', 'holdYears',
  'monthlyCommonCosts', 'otherCostsYear', 'costGrowthPct', 'salePrice', 'sellCostPct'
];

const fmtNok = (n) => new Intl.NumberFormat('nb-NO', { style: 'currency', currency: 'NOK', maximumFractionDigits: 0 }).format(n);
const fmtPct = (n) => `${n.toFixed(1)} %`;

function val(id) {
  return Number(document.getElementById(id).value || 0);
}

function annuityPayment(principal, yearlyRate, years) {
  if (principal <= 0 || years <= 0) return 0;
  const monthlyRate = (yearlyRate / 100) / 12;
  const periods = years * 12;
  if (monthlyRate === 0) return principal / periods;
  return principal * (monthlyRate / (1 - Math.pow(1 + monthlyRate, -periods)));
}

function remainingLoan(principal, yearlyRate, years, monthsPaid) {
  const payment = annuityPayment(principal, yearlyRate, years);
  let bal = principal;
  const r = (yearlyRate / 100) / 12;

  for (let i = 0; i < monthsPaid; i += 1) {
    const interestPart = bal * r;
    const principalPart = payment - interestPart;
    bal = Math.max(0, bal - principalPart);
  }
  return bal;
}

function calculate() {
  const purchasePrice = val('purchasePrice');
  const equity = val('equity');
  const buyCostPct = val('buyCostPct');
  const interestRate = val('interestRate');
  const loanYears = val('loanYears');
  const holdYears = val('holdYears');
  const monthlyCommonCosts = val('monthlyCommonCosts');
  const otherCostsYear = val('otherCostsYear');
  const costGrowthPct = val('costGrowthPct');
  const salePrice = val('salePrice');
  const sellCostPct = val('sellCostPct');

  const buyCosts = purchasePrice * (buyCostPct / 100);
  const investedCapital = equity + buyCosts;
  const loanAmount = Math.max(0, purchasePrice - equity);
  const monthlyDebtService = annuityPayment(loanAmount, interestRate, loanYears);
  const yearlyDebtService = monthlyDebtService * 12;

  let totalOwnerCosts = 0;
  for (let year = 0; year < holdYears; year += 1) {
    const costFactor = Math.pow(1 + costGrowthPct / 100, year);
    const commonCostsYear = monthlyCommonCosts * 12 * costFactor;
    const otherYear = otherCostsYear * costFactor;
    totalOwnerCosts += commonCostsYear + otherYear + yearlyDebtService;
  }

  const monthsHeld = holdYears * 12;
  const loanBalanceAtSale = remainingLoan(loanAmount, interestRate, loanYears, monthsHeld);
  const saleCosts = salePrice * (sellCostPct / 100);
  const equityFromSale = salePrice - saleCosts - loanBalanceAtSale;

  const totalPropertyProfit = (salePrice - saleCosts) - (purchasePrice + buyCosts) - totalOwnerCosts;
  const propertyTotalReturnPct = (purchasePrice + buyCosts) > 0
    ? (totalPropertyProfit / (purchasePrice + buyCosts)) * 100
    : 0;

  const totalEquityProfit = equityFromSale - investedCapital - totalOwnerCosts;
  const equityTotalReturnPct = investedCapital > 0 ? (totalEquityProfit / investedCapital) * 100 : 0;

  const propertyCagr = holdYears > 0 ? (Math.pow((1 + propertyTotalReturnPct / 100), (1 / holdYears)) - 1) * 100 : 0;
  const equityCagr = holdYears > 0 ? (Math.pow((1 + equityTotalReturnPct / 100), (1 / holdYears)) - 1) * 100 : 0;
  const avgAnnualCost = holdYears > 0 ? totalOwnerCosts / holdYears : 0;

  document.getElementById('propertyTotal').textContent = fmtPct(propertyTotalReturnPct);
  document.getElementById('propertyCagr').textContent = `Årlig snitt (CAGR): ${fmtPct(propertyCagr)}`;

  document.getElementById('equityTotal').textContent = fmtPct(equityTotalReturnPct);
  document.getElementById('equityCagr').textContent = `Årlig snitt (CAGR): ${fmtPct(equityCagr)}`;

  document.getElementById('avgAnnualCost').textContent = fmtNok(avgAnnualCost);
  document.getElementById('loanBalance').textContent = fmtNok(loanBalanceAtSale);
  document.getElementById('equityFromSale').textContent = `Egenkapital frigjort ved salg: ${fmtNok(equityFromSale)}`;

  const leverage = investedCapital > 0 ? purchasePrice / investedCapital : 0;
  const detailRows = [
    ['Lånebeløp ved kjøp', fmtNok(loanAmount)],
    ['Månedlig terminbeløp', fmtNok(monthlyDebtService)],
    ['Totale eierkostnader i perioden', fmtNok(totalOwnerCosts)],
    ['Samlet fortjeneste på bolig', fmtNok(totalPropertyProfit)],
    ['Samlet fortjeneste på egenkapital', fmtNok(totalEquityProfit)],
    ['Belåningsmultiplikator', `${leverage.toFixed(2)}x`],
    ['Netto salg etter kostnader', fmtNok(salePrice - saleCosts)],
    ['Total investert kapital', fmtNok(investedCapital)]
  ];

  const detailRoot = document.getElementById('details');
  detailRoot.innerHTML = detailRows.map(([k, v]) => `<div><strong>${k}:</strong> ${v}</div>`).join('');
}

for (const id of ids) {
  document.getElementById(id).addEventListener('input', calculate);
}
document.getElementById('calculateBtn').addEventListener('click', calculate);

calculate();
