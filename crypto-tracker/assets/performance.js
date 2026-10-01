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
        const unavailable = {totalReturn:null, annualReturn:null, annualEligible:false, days:null, flows:[], marketValue:null};
        const {cost, quantity, purchasedAt, status, soldAt, realizedProfit} = order;
        if (order.validQuantity === false || !(cost>0) || !(quantity>0) || !Number.isFinite(purchasedAt) || purchasedAt>now || !['OPEN','CLOSED'].includes(status)) return unavailable;
        const closed = status === 'CLOSED';
        const end = closed ? soldAt : now;
        if (!Number.isFinite(end) || end<purchasedAt || end>now) return unavailable;
        const days = (end-purchasedAt)/86400000;
        const anniversary = new Date(purchasedAt);
        anniversary.setUTCFullYear(anniversary.getUTCFullYear()+1);
        const annualEligible = end >= anniversary.getTime();
        if (closed ? !Number.isFinite(realizedProfit) : !Number.isFinite(price) || price<0) return {...unavailable,days,annualEligible};
        const marketValue = closed ? 0 : quantity*price;
        const proceeds = closed ? cost+realizedProfit : marketValue;
        if (proceeds<0) return {...unavailable,days,annualEligible};
        const totalReturn = (proceeds-cost)/cost*100;
        const annualReturn = annualEligible ? annualized(totalReturn,days) : null;
        const flows = [{date:purchasedAt,amount:-cost},{date:end,amount:proceeds}];
        return {totalReturn,annualReturn,annualEligible,days,flows,marketValue};
    }
    return {annualized,xirr,orderPerformance};
})();
