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
    const roiEl = document.getElementById('roiValue');
    const totalInvestedEl = document.getElementById('totalInvestedValue');
    const liveStatusEl = document.getElementById('liveStatus');
    const apiDebugList = document.getElementById('apiDebugList');
    const ordersTable = document.getElementById('ordersTable');

    const baseTotals = {
        invested: Number.parseFloat(summaryCard?.dataset.totalInvested || '0') || 0,
        openCost: Number.parseFloat(summaryCard?.dataset.openCostBasis || '0') || 0,
        realized: Number.parseFloat(summaryCard?.dataset.realized || '0') || 0,
        realizedCurrency: summaryCard?.dataset.realizedCurrency || 'USD',
    };

    let livePrices = {};
    let symbolPrices = {};
    const selectedDisplayCurrency = (summaryCard?.dataset.realizedCurrency || 'USD').toUpperCase();

    const apiDebugMessages = {
        prices: 'Ingen spørring utført ennå.',
    };

    function renderApiDebug() {
        if (!apiDebugList) return;
        apiDebugList.innerHTML = '';

        Object.entries(apiDebugMessages).forEach(([type, message]) => {
            const item = document.createElement('li');
            item.innerHTML = `<span class="debug-label">Live-priser:</span> <code>${message}</code>`;
            apiDebugList.appendChild(item);
        });
    }

    function setApiDebugMessage(type, message) {
        apiDebugMessages[type] = `${new Date().toLocaleTimeString()} – ${message}`;
        renderApiDebug();
    }

    renderApiDebug();

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

    function formatWithCurrency(amount, currency) {
        if (!Number.isFinite(amount)) return '-';
        return `${formatNumber(amount)} ${currency}`;
    }

    function resolveLivePrice(assetSymbol, preferredCurrency = 'USD', priceMap = livePrices, symbolMap = symbolPrices) {
        const available = priceMap?.[assetSymbol];

        if (available?.[preferredCurrency]) {
            return { price: available[preferredCurrency], currency: preferredCurrency };
        }

        const flatSymbol = `${assetSymbol}${preferredCurrency}`;
        if (symbolMap?.[flatSymbol]) {
            return { price: symbolMap[flatSymbol], currency: preferredCurrency };
        }

        if (available) {
            const [firstQuote] = Object.keys(available);
            if (firstQuote) {
                return { price: available[firstQuote], currency: firstQuote };
            }
        }

        const fallback = Object.entries(symbolMap || {}).find(([key]) => key.startsWith(assetSymbol));
        if (fallback) {
            const [symbol, price] = fallback;
            const quote = symbol.replace(assetSymbol, '') || null;
            return { price, currency: quote };
        }

        return { price: null, currency: null };
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

            investedTotal += Number.isFinite(totalCost) ? totalCost : 0;
            openCostTotal += Number.isFinite(openCost) ? openCost : 0;

            const { price: feedPrice, currency: feedCurrency } = resolveLivePrice(assetSymbol, currency, priceMap);
            const currentPrice = Number.isFinite(feedPrice) ? feedPrice : null;

            if (Number.isFinite(currentPrice) && !Number.isNaN(entryPrice) && !Number.isNaN(remaining) && remaining > 0) {
                const profitNative = remaining * (currentPrice - entryPrice);
                unrealizedTotal += profitNative;

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

        const realizedDisplay = Number.isFinite(baseTotals.realized) ? baseTotals.realized : 0;
        if (unrealizedTotalEl) {
            unrealizedTotalEl.textContent = formatWithCurrency(unrealizedTotal, selectedDisplayCurrency);
            unrealizedTotalEl.classList.toggle('positive', unrealizedTotal > 0);
            unrealizedTotalEl.classList.toggle('negative', unrealizedTotal < 0);
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
        const statusValue = Array.from(statusFilterRadios).find(radio => radio.checked)?.value || 'open';
        const params = new URLSearchParams();
        const selectedAsset = assetFilterSelect?.value.trim();

        if (selectedAsset) {
            params.set('asset', selectedAsset);
        }

        if (statusValue) {
            params.set('status', statusValue);
        }

        setLiveStatus('Updating from price feed…');
        livePulse?.classList.add('active');
        refreshButton?.setAttribute('disabled', 'disabled');

        try {
            const queryString = params.toString();
            const path = queryString ? `prices.php?${queryString}` : 'prices.php';
            setApiDebugMessage('prices', `GET ${path}`);
            const response = await fetch(path);
            if (!response.ok) {
                throw new Error(`Feed error (${response.status})`);
            }
            const data = await response.json();
            livePrices = data.prices || {};
            symbolPrices = data.symbol_prices || {};
            const binanceRequests = (data.binance_requests || []).map(url => `GET ${url}`);
            const binanceDisplay = binanceRequests.length ? binanceRequests.join(' · ') : 'Ingen Binance-spørringer registrert.';
            setApiDebugMessage('prices', `GET ${path} | Binance: ${binanceDisplay}`);
            if (!Object.keys(livePrices).length) {
                setLiveStatus('No live prices returned. Asset might not be available on Binance.', true);
            } else {
                setLiveStatus('Live prices refreshed.');
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
        const overrideSymbolPrices = {
            ...symbolPrices,
            [`${selectedAsset}${assetCurrency}`]: manualPrice,
        };
        setLiveStatus(`Manual override applied to ${selectedAsset}/${assetCurrency}.`);
        livePrices = overridePrices;
        symbolPrices = overrideSymbolPrices;
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
        element?.addEventListener('change', () => {
            applyFilters();
            fetchLivePrices();
        });
    });

    filterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
        fetchLivePrices();
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
