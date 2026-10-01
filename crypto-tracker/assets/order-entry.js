globalThis.CryptoOrderEntry = (() => {
    const keys = ['quantity','entry_price','total_cost'];
    function solve(values, entered) {
        const valid = key => Number.isFinite(values[key]) && values[key] > 0;
        const pair = [...new Set(entered)].filter(valid).slice(-2);
        if (pair.length < 2) return values;
        const target = keys.find(key => !pair.includes(key));
        const result = {...values};
        if (target === 'quantity') result.quantity = values.total_cost / values.entry_price;
        if (target === 'entry_price') result.entry_price = values.total_cost / values.quantity;
        if (target === 'total_cost') result.total_cost = values.quantity * values.entry_price;
        return result;
    }
    return {solve};
})();
