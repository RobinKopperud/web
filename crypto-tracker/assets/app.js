document.addEventListener('DOMContentLoaded', () => {
    const priceInput = document.getElementById('currentPrice');
    const updateButton = document.getElementById('updatePrice');
    const refreshButton = document.getElementById('refreshPrices');
    const livePulse = document.getElementById('livePulse');
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
    const summaryCard = document.getElementById('portfolioSummary');
    const unrealizedTotalEl = document.getElementById('unrealizedValue');
    const realizedEl = document.getElementById('realizedValue');
    const portfolioValueEl = document.getElementById('portfolioValue');
    const roiEl = document.getElementById('roiValue');
    const totalInvestedEl = document.getElementById('totalInvestedValue');
    const liveStatusEl = document.getElementById('liveStatus');

    const baseTotals = {
        invested: Number.parseFloat(summaryCard?.dataset.totalInvested || '0') || 0,
        openCost: Number.parseFloat(summaryCard?.dataset.openCostBasis || '0') || 0,
        realized: Number.parseFloat(summaryCard?.dataset.realized || '0') || 0,
    };

    let livePrices = {};

    function formatNumber(value) {
        return Number.parseFloat(value).toFixed(8);
    }

    function parsePositiveNumber(value) {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    }

    function setLiveStatus(text, isError = false) {
        if (liveStatusEl) {
            liveStatusEl.textContent = text;
            liveStatusEl.classList.toggle('error', isError);
        }
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

    function applyFilters() {
        const assetValue = assetFilterSelect?.value.trim().toLowerCase() || '';
        const statusValue = Array.from(statusFilterRadios).find(radio => radio.checked)?.value || 'open';

        document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
            const matchesAsset = !assetValue || row.dataset.asset?.toLowerCase() === assetValue;
            const matchesStatus = statusValue === 'all' || row.dataset.status === statusValue;
            row.classList.toggle('is-hidden', !(matchesAsset && matchesStatus));
        });

        updateUnrealized(livePrices);
    }

    function updateUnrealized(priceMap = livePrices) {
        const rows = document.querySelectorAll('#ordersTable tbody tr');
        let unrealizedTotal = 0;
        let openMarketValue = 0;

        rows.forEach(row => {
            const entryPrice = Number.parseFloat(row.dataset.entryPrice);
            const remaining = Number.parseFloat(row.dataset.remaining);
            const assetSymbol = row.dataset.assetSymbol;
            const currency = row.dataset.currency;
            const cell = row.querySelector('.unrealized');

            const currentPrice = priceMap?.[assetSymbol];

            if (Number.isFinite(currentPrice) && !Number.isNaN(entryPrice) && !Number.isNaN(remaining) && remaining > 0) {
                const profit = remaining * (currentPrice - entryPrice);
                unrealizedTotal += profit;
                openMarketValue += remaining * currentPrice;

                if (cell) {
                    cell.textContent = `${formatNumber(profit)} ${currency}`;
                    cell.classList.remove('positive', 'negative');
                    if (profit > 0) {
                        cell.classList.add('positive');
                    } else if (profit < 0) {
                        cell.classList.add('negative');
                    }
                }
            } else if (cell) {
                cell.textContent = '-';
                cell.classList.remove('positive', 'negative');
            }

            if (remaining <= 0 && cell) {
                cell.textContent = 'Closed';
            }
        });

        const portfolioValue = openMarketValue + baseTotals.realized;
        if (unrealizedTotalEl) {
            unrealizedTotalEl.textContent = `${formatNumber(unrealizedTotal)} USD`;
            unrealizedTotalEl.classList.toggle('positive', unrealizedTotal > 0);
            unrealizedTotalEl.classList.toggle('negative', unrealizedTotal < 0);
        }
        if (portfolioValueEl) {
            portfolioValueEl.textContent = `${formatNumber(portfolioValue)} USD`;
        }
        if (roiEl) {
            const denominator = baseTotals.invested > 0 ? baseTotals.invested : baseTotals.openCost;
            const roi = denominator > 0 ? ((baseTotals.realized + unrealizedTotal) / denominator) * 100 : 0;
            roiEl.textContent = `${roi.toFixed(2)}%`;
            roiEl.classList.toggle('positive', roi > 0);
            roiEl.classList.toggle('negative', roi < 0);
        }
        if (totalInvestedEl) {
            totalInvestedEl.textContent = `${formatNumber(baseTotals.invested)} USD`;
        }
    }

    async function fetchLivePrices() {
        const rows = Array.from(document.querySelectorAll('#ordersTable tbody tr'));
        const symbols = Array.from(new Set(rows
            .map(row => row.dataset.assetSymbol)
            .filter(Boolean)));

        if (!symbols.length) {
            setLiveStatus('No open assets to price.');
            return;
        }

        setLiveStatus('Updating from price feed…');
        livePulse?.classList.add('active');
        refreshButton?.setAttribute('disabled', 'disabled');

        try {
            const response = await fetch(`prices.php?assets=${symbols.join(',')}`);
            if (!response.ok) {
                throw new Error(`Feed error (${response.status})`);
            }
            const data = await response.json();
            livePrices = data.prices || {};
            setLiveStatus('Live prices refreshed.');
            updateUnrealized(livePrices);
        } catch (error) {
            setLiveStatus('Could not load live prices.', true);
            console.error(error);
        } finally {
            refreshButton?.removeAttribute('disabled');
            setTimeout(() => livePulse?.classList.remove('active'), 800);
        }
    }

    function applyManualOverride() {
        const manualPrice = parsePositiveNumber(priceInput?.value || '');
        const selectedAsset = assetFilterSelect?.value.trim().toUpperCase();

        if (!manualPrice || !selectedAsset) {
            setLiveStatus('Select an asset and enter a price to override.', true);
            return;
        }

        const overridePrices = { ...livePrices, [selectedAsset]: manualPrice };
        setLiveStatus(`Manual override applied to ${selectedAsset}.`);
        updateUnrealized(overridePrices);
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

    if (updateButton) {
        updateButton.addEventListener('click', applyManualOverride);
        priceInput?.addEventListener('keyup', (event) => {
            if (event.key === 'Enter') {
                applyManualOverride();
            }
        });
    }

    refreshButton?.addEventListener('click', fetchLivePrices);

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
    fetchLivePrices();
    setInterval(fetchLivePrices, 60000);
});
