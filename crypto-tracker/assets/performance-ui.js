globalThis.CryptoPerformanceUI = (() => {
    const {orderPerformance, xirr} = globalThis.CryptoPerformance;
    const number = value => value.toLocaleString('nb-NO', {maximumFractionDigits:2, minimumFractionDigits:2});
    const percent = value => Number.isFinite(value) ? `${number(value)} %` : '–';
    const nok = value => Number.isFinite(value) ? `${number(value)} NOK` : '–';
    const dateLabel = date => new Date(date).toLocaleDateString('nb-NO', {timeZone:'UTC'});
    function text(id, value, signedValue) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = value;
        el.classList.remove('positive','negative');
        if (Number.isFinite(signedValue)) el.classList.add(signedValue<0 ? 'negative' : 'positive');
    }
    function readOrder(card) {
        try { return JSON.parse(card.dataset.performance); } catch { return null; }
    }
    function priceFor(order, prices, symbols) {
        const price = prices?.[order.asset]?.[order.currency] ?? symbols?.[order.asset+order.currency];
        return Number.isFinite(price) ? price : null;
    }
    function cardSummary(card, order, result) {
        const values = [
            ['.unrealized',Number.isFinite(result.marketValue) ? (order.status==='OPEN' ? `${number(result.marketValue-order.cost)} ${order.currency}` : 'Lukket') : '–',order.status==='OPEN' ? result.marketValue-order.cost : null],
            ['.order-total-return',percent(result.totalReturn),result.totalReturn],
            ['.order-annual-return',Number.isFinite(result.days) && !result.annualEligible ? 'Under 1 år' : percent(result.annualReturn),result.annualReturn],
            ['.order-holding-period',Number.isFinite(result.days) ? `${Math.floor(result.days).toLocaleString('nb-NO')} dager` : '–',null],
        ];
        values.forEach(([selector,value,signed]) => {
            const el = card.querySelector(selector);
            if (!el) return;
            el.textContent=value;
            el.classList.remove('positive','negative');
            if (Number.isFinite(signed)) el.classList.add(signed<0?'negative':'positive');
        });
        const note = card.querySelector('.order-performance-note');
        if (note) {
            const bought = Number.isFinite(order.purchasedAt) ? `Kjøpt ${dateLabel(order.purchasedAt)}.` : 'Kjøpsdato mangler.';
            const end = order.status==='CLOSED' && Number.isFinite(order.soldAt) ? ` Solgt ${dateLabel(order.soldAt)}.` : ' Eiertid frem til i dag.';
            const unavailable = !Number.isFinite(result.totalReturn) ? ' Mangler gyldig kjøps-/salgsdato eller livepris.' : '';
            const short = Number.isFinite(result.days) && !result.annualEligible ? ' Avkastning per år vises etter ett års eiertid.' : '';
            note.textContent = bought+end+unavailable+short;
        }
    }
    function element(tag, className, content) {
        const el=document.createElement(tag);
        el.className=className;
        if (content!==undefined) el.textContent=content;
        return el;
    }
    // A shared signed bar chart: the centre line is always zero; numbers remain available as text.
    function chart(id, rows, labels, format) {
        const host=document.getElementById(id);
        if (!host) return;
        host.replaceChildren();
        if (!rows.length) {
            host.append(element('p','muted','Ingen data i dette utvalget.'));
            return;
        }
        const scale=Math.max(1,...rows.flatMap(row=>row.values.filter(Number.isFinite).map(Math.abs)));
        const legend=element('p','chart-legend',`${labels.join(' / ')} · midtlinjen er 0`);
        host.append(legend);
        const list=element('ul','chart-list');
        rows.forEach(row=>{
            const item=element('li','chart-item');
            item.append(element('p','chart-label',row.label));
            row.values.forEach((value,index)=>{
                const line=element('div','chart-line');
                line.append(element('span','chart-series-label',labels[index]));
                const track=element('span','chart-track');
                track.setAttribute('aria-hidden','true');
                if (Number.isFinite(value)) {
                    const bar=element('i',`chart-bar series-${index}${value<0?' is-negative':''}`);
                    const width=Math.min(50,Math.abs(value)/scale*50);
                    bar.style.width=`${width}%`;
                    bar.style.left=`${value<0?50-width:50}%`;
                    track.append(bar);
                }
                line.append(track,element('span','mono chart-value',format(value)));
                item.append(line);
            });
            list.append(item);
        });
        host.append(list);
    }
    function update(prices={}, symbols={}, fx={}) {
        const cards=[...document.querySelectorAll('.order-card')].filter(c=>!c.classList.contains('is-hidden'));
        const now=Date.now();
        const entries=cards.map(card=>{
            const order=readOrder(card);
            if (!order) return null;
            const result=orderPerformance(order,priceFor(order,prices,symbols),now);
            cardSummary(card,order,result);
            return {order,result};
        }).filter(Boolean);
        let invested=0,realized=0,market=0,openCost=0;
        let complete=entries.length===cards.length,fxComplete=complete,annualComplete=true;
        const flows=[],years=new Map();
        let annualCount=0;
        entries.forEach(({order,result})=>{
            if (!Number.isFinite(result.days)) annualComplete=false;
            const rate=fx[order.currency];
            if (!Number.isFinite(rate) || rate<=0) {complete=false;fxComplete=false;if(result.annualEligible)annualComplete=false;return;}
            invested+=order.cost*rate;
            if (order.status==='OPEN') openCost+=order.cost*rate;
            else if (Number.isFinite(order.realizedProfit) && Number.isFinite(order.soldAt)) {
                realized+=order.realizedProfit*rate;
                const year=new Date(order.soldAt).getUTCFullYear();
                years.set(year,(years.get(year)||0)+order.realizedProfit*rate);
            } else complete=false;
            if (!Number.isFinite(result.totalReturn)) complete=false;
            else market+=result.marketValue*rate;
            if (result.annualEligible) {
                annualCount++;
                if (!Number.isFinite(result.totalReturn)) annualComplete=false;
                else result.flows.forEach(flow=>flows.push({date:flow.date,amount:flow.amount*rate}));
            }
        });
        const hasOrders=entries.length>0;
        const unrealized=complete && hasOrders ? market-openCost : null;
        const totalProfit=Number.isFinite(unrealized) ? realized+unrealized : null;
        const totalReturn=Number.isFinite(totalProfit) && invested>0 ? totalProfit/invested*100 : null;
        const annualReturn=annualComplete && annualCount>0 ? xirr(flows) : null;
        text('totalInvestedNok',fxComplete && hasOrders ? nok(invested) : '–');
        text('realizedNok',complete && hasOrders ? nok(realized) : '–',realized);
        text('unrealizedNok',nok(unrealized),unrealized);
        text('lifetimeRoi',percent(totalReturn),totalReturn);
        text('portfolioAnnualReturn',annualCount===0 && complete && hasOrders ? 'Under 1 år' : percent(annualReturn),annualReturn);
        text('portfolioTotalProfit',nok(totalProfit),totalProfit);
        text('performanceStatus',!hasOrders ? 'Ingen ordrer i dette utvalget.' : !complete ? 'Mangler livepris, valutakurs eller gyldig ordrehistorikk. Oppdater prisene for å prøve igjen.' : `${entries.length} ordrer i utvalget · ${annualCount} med minst ett års eiertid.`);
        chart('orderReturnChart',entries.map(({order,result})=>({label:`${order.asset} #${order.id} · ${Number.isFinite(result.days)?Math.floor(result.days).toLocaleString('nb-NO'):'–'} dager`,values:[result.totalReturn,result.annualReturn]})),['Totalt','Per år'],percent);
        const yearRows=[...years].sort((a,b)=>a[0]-b[0]).map(([year,profit])=>({label:String(year),values:[profit]}));
        chart('yearProfitChart',fxComplete ? yearRows : [],['Realisert'],nok);
        if (!fxComplete) text('yearProfitChart','Mangler valutakurs eller salgshistorikk. Årsresultatet vises når alle nødvendige data er tilgjengelige.');
    }
    document.addEventListener('DOMContentLoaded',async()=>{
        update();
        const detail=document.getElementById('orderPerformanceDetail');
        if (!detail?.dataset.performance) return;
        const order=readOrder(detail);
        if (!order) return;
        cardSummary(detail,order,orderPerformance(order,null));
        if (order.status==='CLOSED') return;
        try {
            const params=new URLSearchParams({asset:order.asset,status:'all'});
            const controller=new AbortController();
            const timeout=setTimeout(()=>controller.abort(),45000);
            let response;
            try {response=await fetch(`prices.php?${params}`,{signal:controller.signal,cache:'no-store'});} finally {clearTimeout(timeout);}
            if (!response.ok) throw new Error('price request failed');
            const data=await response.json();
            cardSummary(detail,order,orderPerformance(order,priceFor(order,data.prices,data.symbol_prices)));
        } catch {
            const note=detail.querySelector('.order-performance-note');
            if (note) note.textContent+=' Kunne ikke hente priser. Last siden på nytt for å prøve igjen.';
        }
    });
    return {update};
})();
