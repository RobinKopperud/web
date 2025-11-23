document.addEventListener('DOMContentLoaded', () => {
    const priceInput = document.getElementById('currentPrice');
    const updateButton = document.getElementById('updatePrice');
    const quantityInput = document.getElementById('quantity');
    const entryPriceInput = document.getElementById('entry_price');
    const totalCostInput = document.getElementById('total_cost');

    function formatNumber(value) {
        return Number.parseFloat(value).toFixed(8);
    }

    function parsePositiveNumber(value) {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    }

    function updateMissingOrderField(changedField) {
        if (!quantityInput || !entryPriceInput || !totalCostInput) return;

        const quantity = parsePositiveNumber(quantityInput.value);
        const entryPrice = parsePositiveNumber(entryPriceInput.value);
        const totalCost = parsePositiveNumber(totalCostInput.value);

        if (changedField !== 'quantity' && entryPrice !== null && totalCost !== null) {
            const computedQuantity = totalCost / entryPrice;
            quantityInput.value = Number.isFinite(computedQuantity) ? formatNumber(computedQuantity) : '';
            return;
        }

        if (changedField !== 'entry_price' && quantity !== null && totalCost !== null) {
            const computedEntry = totalCost / quantity;
            entryPriceInput.value = Number.isFinite(computedEntry) ? formatNumber(computedEntry) : '';
            return;
        }

        if (changedField !== 'total_cost' && quantity !== null && entryPrice !== null) {
            const computedTotal = quantity * entryPrice;
            totalCostInput.value = Number.isFinite(computedTotal) ? formatNumber(computedTotal) : '';
        }
    }

    function updateUnrealized() {
        const currentPrice = Number.parseFloat(priceInput.value);
        const rows = document.querySelectorAll('#ordersTable tbody tr');

        rows.forEach(row => {
            const entryPrice = Number.parseFloat(row.dataset.entryPrice);
            const remaining = Number.parseFloat(row.dataset.remaining);
            const cell = row.querySelector('.unrealized');

            if (Number.isFinite(currentPrice) && !Number.isNaN(entryPrice) && !Number.isNaN(remaining) && remaining > 0) {
                const profit = remaining * (currentPrice - entryPrice);
                cell.textContent = formatNumber(profit);
                cell.classList.remove('positive', 'negative');
                if (profit > 0) {
                    cell.classList.add('positive');
                } else if (profit < 0) {
                    cell.classList.add('negative');
                }
            } else {
                cell.textContent = '-';
                cell.classList.remove('positive', 'negative');
            }
        });
    }

    if (updateButton) {
        updateButton.addEventListener('click', updateUnrealized);
        priceInput?.addEventListener('keyup', (event) => {
            if (event.key === 'Enter') {
                updateUnrealized();
            }
        });
    }

    [
        { element: quantityInput, name: 'quantity' },
        { element: entryPriceInput, name: 'entry_price' },
        { element: totalCostInput, name: 'total_cost' },
    ].forEach(({ element, name }) => {
        element?.addEventListener('input', () => updateMissingOrderField(name));
    });

    document.querySelectorAll('.toggle-close').forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.dataset.target;
            const form = document.getElementById(targetId);
            if (form) {
                form.style.display = form.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
});
