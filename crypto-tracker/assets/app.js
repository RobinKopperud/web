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
    const displayCurrencySelect = document.getElementById('displayCurrency');

    const baseTotals = {
        invested: Number.parseFloat(summaryCard?.dataset.totalInvested || '0') || 0,
        openCost: Number.parseFloat(summaryCard?.dataset.openCostBasis || '0') || 0,
        realized: Number.parseFloat(summaryCard?.dataset.realized || '0') || 0,
        realizedCurrency: summaryCard?.dataset.realizedCurrency || 'USD',
    };

    let livePrices = {};
    let fxRates = { USD: 1 };
    let fxBase = 'USD';
    let selectedDisplayCurrency = displayCurrencySelect?.value || 'USD';

    function findCurrencyForAsset(assetSymbol) {
        const rows = Array.from(document.querySelectorAll('#ordersTable tbody tr'));
        const match = rows.find(row => row.dataset.assetSymbol === assetSymbol);
        return match?.dataset.currency || null;
    }

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

    function collectCurrencies() {
        const rows = Array.from(document.querySelectorAll('#ordersTable tbody tr'));
        const currencies = new Set(rows.map(row => (row.dataset.currency || 'USD').toUpperCase()));
        currencies.add((selectedDisplayCurrency || 'USD').toUpperCase());
        currencies.add('USD');
        currencies.add((baseTotals.realizedCurrency || 'USD').toUpperCase());
        return Array.from(currencies);
    }

    function convertAmount(amount, fromCurrency, toCurrency) {
        if (!Number.isFinite(amount)) return null;
        const from = fromCurrency || fxBase;
        const to = toCurrency || fxBase;

        if (from === to) return amount;

        const fromRate = fxRates[from];
        const toRate = fxRates[to];

        if (from === fxBase) {
            if (toRate) return amount * toRate;
            return null;
        }

        if (!fromRate) return null;

        const baseAmount = amount / fromRate;
        if (to === fxBase) return baseAmount;
        if (!toRate) return null;
        return baseAmount * toRate;
    }

    function formatWithCurrency(amount, currency) {
        if (!Number.isFinite(amount)) return '-';
        return `${formatNumber(amount)} ${currency}`;
    }

    function resolveLivePrice(assetSymbol, preferredCurrency = 'USD', priceMap = livePrices) {
        const available = priceMap?.[assetSymbol];
        if (!available) return { price: null, currency: null };

        if (available[preferredCurrency]) {
            return { price: available[preferredCurrency], currency: preferredCurrency };
        }

        if (available.USD) {
            return { price: available.USD, currency: 'USD' };
        }

        if (available.USDT) {
            return { price: available.USDT, currency: 'USDT' };
        }

        const [firstQuote] = Object.keys(available);
        return { price: available[firstQuote], currency: firstQuote };
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

    async function fetchFxRates() {
        const currencies = collectCurrencies()
            .filter(code => /^[A-Z]{3}$/.test(code));
        const params = new URLSearchParams({
            base: 'USD',
            symbols: currencies.join(','),
        });

        try {
            const response = await fetch(`rates.php?${params.toString()}`);
            if (!response.ok) throw new Error(`FX error (${response.status})`);
            const data = await response.json();

            if (data?.rates) {
                fxRates = { ...data.rates };
                fxRates.USDT = fxRates.USD ?? 1;
                fxBase = data.base || 'USD';
            }
        } catch (error) {
            console.error('Currency feed failed', error);
            fxRates = { [selectedDisplayCurrency]: 1, USD: 1 };
            fxBase = 'USD';
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
        let investedTotal = 0;
        let openCostTotal = 0;

        rows.forEach(row => {
            if (row.classList.contains('is-hidden')) return;

            const entryPrice = Number.parseFloat(row.dataset.entryPrice);
            const remaining = Number.parseFloat(row.dataset.remaining);
            const assetSymbol = row.dataset.assetSymbol;
            const currency = row.dataset.currency || 'USD';
            const totalCost = Number.parseFloat(row.dataset.totalCost);
            const openCost = Number.parseFloat(row.dataset.openCost);
            const cell = row.querySelector('.unrealized');
            const liveCell = row.querySelector('.live-price');

            investedTotal += convertAmount(totalCost, currency, selectedDisplayCurrency) || 0;
            openCostTotal += convertAmount(openCost, currency, selectedDisplayCurrency) || 0;

            const { price: feedPrice, currency: feedCurrency } = resolveLivePrice(assetSymbol, currency, priceMap);
            const currentPrice = convertAmount(feedPrice, feedCurrency, currency);

            if (Number.isFinite(currentPrice) && !Number.isNaN(entryPrice) && !Number.isNaN(remaining) && remaining > 0) {
                const profitNative = remaining * (currentPrice - entryPrice);
                const profitDisplay = convertAmount(profitNative, currency, selectedDisplayCurrency);
                const marketValueDisplay = convertAmount(remaining * currentPrice, currency, selectedDisplayCurrency);

                if (Number.isFinite(profitDisplay)) {
                    unrealizedTotal += profitDisplay;
                }
                if (Number.isFinite(marketValueDisplay)) {
                    openMarketValue += marketValueDisplay;
                }

                if (cell) {
                    cell.textContent = formatWithCurrency(profitNative, currency);
                    cell.classList.remove('positive', 'negative');
                    if (profitNative > 0) {
                        cell.classList.add('positive');
                    } else if (profitNative < 0) {
                        cell.classList.add('negative');
                    }
                }
            } else if (cell) {
                cell.textContent = '-';
                cell.classList.remove('positive', 'negative');
            }

            if (liveCell) {
                if (Number.isFinite(currentPrice)) {
                    liveCell.textContent = formatWithCurrency(currentPrice, currency);
                } else {
                    liveCell.textContent = '-';
                }
            }

            if (remaining <= 0 && cell) {
                cell.textContent = 'Closed';
                cell.classList.remove('positive', 'negative');
            }
        });

        const realizedDisplay = convertAmount(baseTotals.realized, baseTotals.realizedCurrency, selectedDisplayCurrency) ?? baseTotals.realized;
        const portfolioValue = openMarketValue + (Number.isFinite(realizedDisplay) ? realizedDisplay : 0);
        if (unrealizedTotalEl) {
            unrealizedTotalEl.textContent = formatWithCurrency(unrealizedTotal, selectedDisplayCurrency);
            unrealizedTotalEl.classList.toggle('positive', unrealizedTotal > 0);
            unrealizedTotalEl.classList.toggle('negative', unrealizedTotal < 0);
        }
        if (portfolioValueEl) {
            portfolioValueEl.textContent = formatWithCurrency(portfolioValue, selectedDisplayCurrency);
        }
        if (roiEl) {
            const denominator = investedTotal > 0 ? investedTotal : openCostTotal;
            const roi = denominator > 0 ? ((realizedDisplay + unrealizedTotal) / denominator) * 100 : 0;
            roiEl.textContent = `${roi.toFixed(2)}%`;
            roiEl.classList.toggle('positive', roi > 0);
            roiEl.classList.toggle('negative', roi < 0);
        }
        if (totalInvestedEl) {
            totalInvestedEl.textContent = formatWithCurrency(investedTotal, selectedDisplayCurrency);
        }
        if (realizedEl) {
            realizedEl.textContent = formatWithCurrency(realizedDisplay, selectedDisplayCurrency);
        }
    }

    async function fetchLivePrices() {
        const rows = Array.from(document.querySelectorAll('#ordersTable tbody tr'));
        const symbols = Array.from(new Set(rows
            .map(row => row.dataset.assetSymbol)
            .filter(Boolean)));

        const currencies = Array.from(new Set(rows
            .map(row => row.dataset.currency)
            .filter(Boolean)));

        const allowedCurrencies = ['USD', 'USDT', 'EUR', 'GBP'];
        const feedCurrencies = currencies.filter(q => allowedCurrencies.includes(q));

        if (!feedCurrencies.includes('USD')) {
            feedCurrencies.unshift('USD');
        }

        if (!feedCurrencies.length) {
            feedCurrencies.push('USD');
        }

        if (!symbols.length) {
            setLiveStatus('No open assets to price.');
            return;
        }

        setLiveStatus('Updating from price feed…');
        livePulse?.classList.add('active');
        refreshButton?.setAttribute('disabled', 'disabled');

        try {
            const params = new URLSearchParams({
                assets: symbols.join(','),
                currencies: feedCurrencies.join(','),
            });
            const response = await fetch(`prices.php?${params.toString()}`);
            if (!response.ok) {
                throw new Error(`Feed error (${response.status})`);
            }
            const data = await response.json();
            livePrices = data.prices || {};
            if (!Object.keys(livePrices).length) {
                setLiveStatus('No live prices returned. Asset might not be available on Binance.', true);
            } else {
                setLiveStatus(`Live prices refreshed (${feedCurrencies.join('/')}).`);
            }
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

        const assetCurrency = findCurrencyForAsset(selectedAsset) || 'USD';
        const overridePrices = {
            ...livePrices,
            [selectedAsset]: {
                ...(livePrices[selectedAsset] || {}),
                [assetCurrency]: manualPrice,
            },
        };
        setLiveStatus(`Manual override applied to ${selectedAsset}/${assetCurrency}.`);
        livePrices = overridePrices;
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

    displayCurrencySelect?.addEventListener('change', async () => {
        selectedDisplayCurrency = displayCurrencySelect.value || 'USD';
        await fetchFxRates();
        updateUnrealized(livePrices);
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

    async function bootstrapPrices() {
        await fetchFxRates();
        applyFilters();
        await fetchLivePrices();
    }

    bootstrapPrices();
    setInterval(fetchLivePrices, 60000);
});
