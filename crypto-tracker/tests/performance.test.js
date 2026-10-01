const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const context = { globalThis: {} };
const path = require('node:path').join(__dirname, '../assets/performance.js');
if (fs.existsSync(path)) vm.runInNewContext(fs.readFileSync(path, 'utf8'), context);
const p = context.globalThis.CryptoPerformance || {};
const start = Date.UTC(2022, 0, 1);
const year = 365.25 * 86400000;
test('10 percent over two years is 4.88 percent annually', () => {
  assert.ok(typeof p.annualized === 'function', 'annualized calculation is missing');
  assert.ok(Math.abs(p.annualized(10, 730.5) - 4.8808848) < 0.00001);
});
test('invalid holding periods and unavailable returns stay unavailable', () => {
  assert.equal(p.annualized(10, 0), null);
  assert.equal(p.annualized(null, 20), null);
  assert.equal(p.annualized(10, -1), null);
  assert.equal(p.annualized(-100, 365.25), -100);
});
test('XIRR respects dated investments and partial sales', () => {
  const flows = [{date:start, amount:-100}, {date:start+year, amount:55}, {date:start+2*year, amount:60.5}];
  assert.ok(Math.abs(p.xirr(flows) - 10) < 0.00001);
});
test('XIRR handles losses, missing flows and ambiguous roots', () => {
  assert.ok(Math.abs(p.xirr([{date:start,amount:-100},{date:start+year,amount:80}])+20)<0.00001);
  assert.equal(p.xirr([]), null);
  assert.equal(p.xirr([{date:start,amount:-100},{date:start,amount:110}]), null);
  assert.equal(p.xirr([{date:start,amount:-100},{date:start+year,amount:230},{date:start+2*year,amount:-132}]), null);
});
test('order performance combines realized proceeds with remaining value', () => {
  const order = {cost:100, quantity:10, remaining:5, purchasedAt:start, closures:[{date:start+year, amount:55, quantity:5}]};
  const result = p.orderPerformance(order, 12.1, start+2*year);
  assert.equal(result.totalReturn, 15.5);
  assert.ok(Math.abs(result.annualReturn-10)<0.00001);
  assert.equal(result.days, 730.5);
});
test('closed orders stop at last sale and do not need live prices', () => {
  const result = p.orderPerformance({cost:100,quantity:10,remaining:0,purchasedAt:start,closures:[{date:start+year,amount:110, quantity:10}]}, null, start+4*year);
  assert.equal(result.days,365.25);
  assert.ok(Math.abs(result.annualReturn-10)<0.00001);
});
test('missing prices or inconsistent sale history cannot produce a return', () => {
  assert.equal(p.orderPerformance({cost:100,quantity:10,remaining:10,purchasedAt:start,closures:[]},null,start+year).totalReturn,null);
  assert.equal(p.orderPerformance({cost:100,quantity:10,remaining:0,purchasedAt:start,closures:[]},null,start+year).annualReturn,null);
});

test('partial positions need complete sale history', () => {
  assert.equal(p.orderPerformance({cost:100,quantity:10,remaining:5,purchasedAt:start,closures:[]},11,start+year).totalReturn,null);
});
test('closely spaced XIRR roots must remain ambiguous', () => {
  assert.equal(p.xirr([-100,350.1,-407.24,157.443].map((amount,i)=>({date:start+i*year,amount}))),null);
});
test('one-day sold returns agree with compound annualization', () => {
  const expected=p.annualized(10,1);
  const actual=p.xirr([{date:start,amount:-100},{date:start+86400000,amount:110}]);
  assert.ok(Number.isFinite(actual));
  assert.ok(Math.abs(actual/expected-1)<1e-10);
});
test('multiple purchases use actual investment dates', () => {
  const result=p.xirr([{date:start,amount:-100},{date:start+year,amount:-100},{date:start+2*year,amount:231}]);
  assert.ok(Math.abs(result-10)<0.00001);
});
test('unavailable history and future purchase dates stay unavailable', () => {
  const order={cost:100,quantity:10,remaining:10,purchasedAt:start,closures:[]};
  assert.equal(p.orderPerformance({...order,historyAvailable:false},11,start+year).totalReturn,null);
  assert.equal(p.orderPerformance({...order,purchasedAt:start+2*year},11,start+year).annualReturn,null);
});
