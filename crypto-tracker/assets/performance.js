/* Shared calculations for portfolio and order details. Dates are UTC milliseconds. */
globalThis.CryptoPerformance = (() => {
    const yearMs = 365.25 * 86400000;
    function annualized(totalReturn, days) {
        if (!Number.isFinite(totalReturn) || !Number.isFinite(days) || days <= 0 || totalReturn < -100) return null;
        const value = (Math.pow(1 + totalReturn / 100, 365.25 / days) - 1) * 100;
        return Number.isFinite(value) ? value : null;
    }
    function xirr(flows) {
        if (!flows.length || flows.some(f => !Number.isFinite(f.date) || !Number.isFinite(f.amount))) return null;
        const grouped = new Map();
        flows.forEach(f => grouped.set(f.date, (grouped.get(f.date) || 0) + f.amount));
        const entries = [...grouped].filter(([, amount]) => amount !== 0).sort((a,b) => a[0]-b[0]);
        if (entries.length < 2 || !entries.some(([,a]) => a < 0) || !entries.some(([,a]) => a > 0)) return null;
        const first = entries[0][0];
        const terms = entries.map(([date,amount])=>({time:(date-first)/yearMs,amount}));
        // Isolate all roots by splitting at derivative roots. A fixed scan can miss
        // closely spaced solutions in portfolios with alternating purchases/sales.
        function rootsOf(input) {
            const origin=input[0].time;
            const maxAmount=Math.max(...input.map(t=>Math.abs(t.amount)));
            const terms=input.map(t=>({time:t.time-origin,amount:t.amount/maxAmount}));
            const signs=terms.map(t=>Math.sign(t.amount));
            const changes=signs.slice(1).filter((sign,i)=>sign!==signs[i]).length;
            if (!changes) return [];
            if (terms.length>500 && changes>1) return null;
            const last=terms[terms.length-1];
            const before=terms[terms.length-2];
            const total=terms.reduce((sum,t)=>sum+Math.abs(t.amount),0);
            const low=-Math.max(1,Math.log((total-Math.abs(last.amount))/Math.abs(last.amount))/(last.time-before.time)+1);
            const high=Math.max(1,Math.log((total-Math.abs(terms[0].amount))/Math.abs(terms[0].amount))/terms[1].time+1);
            // Scaling the exponential terms prevents overflow at extreme annual rates.
            const value = rate => {
                const shift=rate<0 ? -last.time*rate : 0;
                let sum=0, absolute=0;
                terms.forEach(t=>{
                    const v=t.amount*Math.exp(-t.time*rate-shift);
                    sum+=v; absolute+=Math.abs(v);
                });
                return sum/absolute;
            };
            const derivative=changes===1 ? [] : rootsOf(terms.slice(1).map(t=>({time:t.time,amount:-t.time*t.amount})));
            if (derivative===null) return null;
            const points=[low,...derivative.filter(r=>r>low && r<high),high];
            const roots=[];
            for (let i=0;i<points.length;i++) {
                const current=value(points[i]);
                if (Math.abs(current)<1e-13) roots.push(points[i]);
                if (!i) continue;
                const previous=value(points[i-1]);
                if (Math.abs(current)<1e-13 || Math.abs(previous)<1e-13 || Math.sign(current)===Math.sign(previous)) continue;
                let lo=points[i-1],hi=points[i],lowValue=previous;
                for (let step=0;step<100;step++) {
                    const mid=(lo+hi)/2,midValue=value(mid);
                    if (Math.sign(midValue)===Math.sign(lowValue)) {lo=mid;lowValue=midValue;} else hi=mid;
                }
                roots.push((lo+hi)/2);
            }
            return roots.sort((a,b)=>a-b).filter((r,i,all)=>i===0 || Math.abs(r-all[i-1])>1e-8);
        }
        const roots=rootsOf(terms);
        if (roots===null || roots.length!==1) return null;
        const rate=Math.expm1(roots[0])*100;
        return Number.isFinite(rate) ? rate : null;
    }

    function orderPerformance(order, price, now = Date.now()) {
        const unavailable = {totalReturn:null, annualReturn:null, days:null, flows:[], marketValue:null};
        const {cost, quantity, remaining, purchasedAt, closures} = order;
        if (order.historyAvailable === false || !(cost>0) || !(quantity>0) || remaining<0 || remaining>quantity || !Number.isFinite(purchasedAt) || purchasedAt>now || !Array.isArray(closures)) return unavailable;
        if (closures.some(c => !Number.isFinite(c.date) || c.date<purchasedAt || c.date>now || !Number.isFinite(c.amount) || c.amount<0 || !Number.isFinite(c.quantity) || c.quantity<=0)) return unavailable;
        const soldQuantity = closures.reduce((sum,c)=>sum+c.quantity,0);
        if (Math.abs(soldQuantity-(quantity-remaining)) > Math.max(1e-8,quantity*1e-10)) return unavailable;
        const end = remaining>0 ? now : Math.max(purchasedAt, ...closures.map(c=>c.date));
        const days = (end-purchasedAt)/86400000;
        if (remaining>0 && (!Number.isFinite(price) || price<0) || remaining===0 && !closures.length) return {...unavailable, days};
        const marketValue = remaining>0 ? remaining*price : 0;
        const proceeds = closures.reduce((sum,c)=>sum+c.amount,0);
        const totalReturn = ((proceeds+marketValue-cost)/cost)*100;
        const flows = [{date:purchasedAt,amount:-cost}, ...closures.map(c=>({date:c.date,amount:c.amount}))];
        if (remaining>0) flows.push({date:now,amount:marketValue});
        const annualReturn = closures.length ? xirr(flows) : annualized(totalReturn,days);
        return {totalReturn, annualReturn, days, flows, marketValue};
    }
    return {annualized, xirr, orderPerformance};
})();
