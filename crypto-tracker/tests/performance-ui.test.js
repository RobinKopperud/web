const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
function fixture(orders) {
    const nodes = new Map();
    const node = () => ({textContent:'', className:'',children:[],dataset:{},style:{},classList:{contains:()=>false,remove(){},add(){}},append(...items){this.children.push(...items)},replaceChildren(...items){this.children=items},setAttribute(){}});
    const cards = orders.map(order => ({...node(),dataset:{performance:JSON.stringify(order)},querySelector:()=>null}));
    const document = {querySelectorAll:()=>cards,getElementById(id){if(!nodes.has(id))nodes.set(id,node());return nodes.get(id)},createElement:node,addEventListener(){}};
    const context={document,globalThis:{},Date,Map,Number};
    for(const file of ['performance.js','performance-ui.js']) {
        const filename=path.join(__dirname,'../assets',file);
        if(fs.existsSync(filename))vm.runInNewContext(fs.readFileSync(filename,'utf8'),context);
    }
    return {nodes,update:context.globalThis.CryptoPerformanceUI?.update};
}
const purchasedAt = Date.now()-365.25*86400000;
const order={id:1,asset:'BTC',currency:'USD',cost:100,quantity:10,remaining:10,purchasedAt,closures:[]};
test('portfolio annual return and total return use all cash flows',()=>{
    const {nodes,update}=fixture([order]);
    assert.ok(update, 'performance UI missing');
    update({BTC:{USD:11}}, {}, {USD:10});
    assert.match(nodes.get('portfolioAnnualReturn').textContent,/10,00/);
    assert.match(nodes.get('lifetimeRoi').textContent,/10,00/);
    assert.match(nodes.get('portfolioTotalProfit').textContent,/100,00/);
});
test('unavailable prices never turn into a zero return',()=>{
    const {nodes,update}=fixture([order]);
    assert.ok(update,'performance UI missing');
    update({}, {}, {USD:10});
    assert.equal(nodes.get('portfolioAnnualReturn').textContent,'–');
    assert.equal(nodes.get('lifetimeRoi').textContent,'–');
    assert.match(nodes.get('performanceStatus').textContent,/Mangler/);
});
test('missing FX never yields a partial portfolio return',()=>{
    const {nodes,update}=fixture([order,{...order,id:2,currency:'EUR'}]);
    assert.ok(update,'performance UI missing');
    update({BTC:{USD:11,EUR:12}}, {}, {USD:10});
    assert.equal(nodes.get('portfolioAnnualReturn').textContent,'–');
    assert.equal(nodes.get('totalInvestedNok').textContent,'–');
});
