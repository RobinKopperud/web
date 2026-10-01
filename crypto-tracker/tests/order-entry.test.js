const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const context = {globalThis:{}};
const filename=path.join(__dirname,'../assets/order-entry.js');
if(fs.existsSync(filename))vm.runInNewContext(fs.readFileSync(filename,'utf8'),context);
const solve=(...args)=>context.globalThis.CryptoOrderEntry?.solve(...args);
test('quantity and price derive total',()=>assert.deepEqual(JSON.parse(JSON.stringify(solve({quantity:2,entry_price:10,total_cost:null},['quantity','entry_price']))),{quantity:2,entry_price:10,total_cost:20}));
test('quantity and total derive price',()=>assert.deepEqual(JSON.parse(JSON.stringify(solve({quantity:2,entry_price:null,total_cost:20},['quantity','total_cost']))),{quantity:2,entry_price:10,total_cost:20}));
test('price and total derive quantity',()=>assert.deepEqual(JSON.parse(JSON.stringify(solve({quantity:null,entry_price:10,total_cost:20},['entry_price','total_cost']))),{quantity:2,entry_price:10,total_cost:20}));
test('changing an explicitly entered quantity keeps explicitly entered price',()=>{
 const result=solve({quantity:3,entry_price:10,total_cost:20},['entry_price','quantity']);
 assert.equal(result?.total_cost,30);
 assert.equal(result?.entry_price,10);
});
test('changing total after quantity and price makes price the computed field',()=>{
 const result=solve({quantity:2,entry_price:10,total_cost:30},['quantity','total_cost']);
 assert.equal(result?.entry_price,15);
});
