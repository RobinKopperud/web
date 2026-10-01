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
test('XIRR handles losses, missing flows and ambiguous roots', () => {
  assert.ok(Math.abs(p.xirr([{date:start,amount:-100},{date:start+year,amount:80}])+20)<0.00001);
  assert.equal(p.xirr([]), null);
  assert.equal(p.xirr([{date:start,amount:-100},{date:start,amount:110}]), null);
  assert.equal(p.xirr([{date:start,amount:-100},{date:start+year,amount:230},{date:start+2*year,amount:-132}]), null);
});
test('open orders use the entire quantity', () => {
 const result=p.orderPerformance({cost:100,quantity:10,status:'OPEN',purchasedAt:start},11,start+2*year);
 assert.ok(Math.abs(result.totalReturn-10)<0.000001);
 assert.ok(Math.abs(result.annualReturn-4.8808848)<0.00001);
});
test('closed orders stop at the whole-order sale date', () => {
 const result=p.orderPerformance({cost:100,quantity:10,status:'CLOSED',purchasedAt:start,soldAt:start+year,realizedProfit:10},null,start+4*year);
 assert.equal(result.days,365.25);
 assert.ok(Math.abs(result.annualReturn-10)<0.00001);
});
test('young orders show total return without annual extrapolation', () => {
 const result=p.orderPerformance({cost:100,quantity:10,status:'OPEN',purchasedAt:start},11,start+10*86400000);
 assert.equal(result.annualReturn,null);
 assert.equal(result.annualEligible,false);
 assert.ok(Math.abs(result.totalReturn-10)<0.000001);
});
test('missing live prices preserve holding period but no fabricated return', () => {
 const result=p.orderPerformance({cost:100,quantity:10,status:'OPEN',purchasedAt:start},null,start+year);
 assert.equal(result.totalReturn,null);
 assert.equal(result.days,365.25);
});
test('closely spaced XIRR roots remain ambiguous', () => {
 assert.equal(p.xirr([-100,350.1,-407.24,157.443].map((amount,i)=>({date:start+i*year,amount}))),null);
});
test('portfolio cash flows respect actual purchase dates', () => {
 assert.ok(Math.abs(p.xirr([{date:start,amount:-100},{date:start+year,amount:-100},{date:start+2*year,amount:231}])-10)<0.00001);
});

test('inconsistent stored quantities never produce performance values', () => {
 assert.equal(p.orderPerformance({cost:100,quantity:10,status:'OPEN',validQuantity:false,purchasedAt:start},11,start+year).totalReturn,null);
});
