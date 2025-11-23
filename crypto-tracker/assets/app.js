document.addEventListener('DOMContentLoaded', () => {
    const priceInput = document.getElementById('currentPrice');
    const updateButton = document.getElementById('updatePrice');
    const quantityInput = document.getElementById('quantity');
    const entryPriceInput = document.getElementById('entry_price');
    const totalCostInput = document.getElementById('total_cost');
    const closeModal = document.getElementById('closeModal');
    const closeModalForm = document.getElementById('closeModalForm');
    const closeOrderIdInput = document.getElementById('closeModalOrderId');
    const closeQuantityInput = document.getElementById('close_quantity_modal');
    const closePriceInput = document.getElementById('close_price_modal');
    const closeFeeInput = document.getElementById('close_fee_modal');
    const closeModalTitle = document.getElementById('closeModalTitle');
    const closeModalAsset = document.getElementById('closeModalAsset');
    const closeRemainingHelper = document.getElementById('closeRemainingHelper');
    const closeCurrencyBadge = document.getElementById('closeCurrencyBadge');
    const closeModalDismiss = document.getElementById('closeModalDismiss');
    const closeModalCancel = document.getElementById('closeModalCancel');
    const filterForm = document.querySelector('.filters form');
    const assetFilterSelect = document.getElementById('filter_asset');
    const statusFilterRadios = document.querySelectorAll('input[name="status"]');

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

    function applyFilters() {
        const assetValue = assetFilterSelect?.value.trim().toLowerCase() || '';
        const statusValue = Array.from(statusFilterRadios).find(radio => radio.checked)?.value || 'open';

        document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
            const matchesAsset = !assetValue || row.dataset.asset?.toLowerCase() === assetValue;
            const matchesStatus = statusValue === 'all' || row.dataset.status === statusValue;
            row.classList.toggle('is-hidden', !(matchesAsset && matchesStatus));
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

    [assetFilterSelect, ...statusFilterRadios].forEach(element => {
        element?.addEventListener('change', applyFilters);
    });

    filterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });

    function hideCloseModal() {
        closeModal?.classList.remove('open');
        document.body.classList.remove('modal-open');
        if (closeModalForm) {
            closeModalForm.reset();
        }
        closeModal?.setAttribute('aria-hidden', 'true');
    }

    function openCloseModal({ id, asset, remaining, currency }) {
        if (!closeModal || !closeModalForm) return;

        closeModal.classList.add('open');
        document.body.classList.add('modal-open');
        closeModal.setAttribute('aria-hidden', 'false');

        closeOrderIdInput.value = id;
        closeQuantityInput.value = remaining;
        closeQuantityInput.max = remaining;
        closePriceInput.value = '';
        if (closeFeeInput) {
            closeFeeInput.value = '';
        }
        closeModalTitle.textContent = `Order #${id}`;
        closeModalAsset.textContent = asset;
        closeRemainingHelper.textContent = `(Remaining: ${remaining})`;
        closeCurrencyBadge.textContent = currency;
        closeQuantityInput.focus();
    }

    document.querySelectorAll('.open-close-modal').forEach(button => {
        button.addEventListener('click', () => {
            const { orderId, asset, remaining, currency } = button.dataset;
            openCloseModal({
                id: orderId,
                asset: asset || 'Order',
                remaining: remaining || '0',
                currency: currency || 'USD',
            });
        });
    });

    closeModalDismiss?.addEventListener('click', hideCloseModal);
    closeModalCancel?.addEventListener('click', hideCloseModal);
    closeModal?.addEventListener('click', (event) => {
        if (event.target === closeModal) {
            hideCloseModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && closeModal?.classList.contains('open')) {
            hideCloseModal();
        }
    });

    applyFilters();
});
