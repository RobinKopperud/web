document.addEventListener('DOMContentLoaded', () => {
    const priceInput = document.getElementById('currentPrice');
    const updateButton = document.getElementById('updatePrice');

    function formatNumber(value) {
        return Number.parseFloat(value).toFixed(8);
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
