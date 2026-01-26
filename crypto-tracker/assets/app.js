document.addEventListener('DOMContentLoaded', () => {
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
    const apiDebugList = document.getElementById('apiDebugList');

    let livePrices = {};
    let symbolPrices = {};
    let fxRates = {};

    const apiDebugMessages = {
        prices: 'Ingen spørring utført ennå.',
        resolution: 'Ingen oppdatering ennå.',
    };

    const apiDebugLabels = {
        prices: 'Live-priser',
        resolution: 'Pris-resolusjon',
    };

    function renderApiDebug() {
        if (!apiDebugList) return;
        apiDebugList.innerHTML = '';

        Object.entries(apiDebugMessages).forEach(([type, message]) => {
            const item = document.createElement('li');
            const label = apiDebugLabels[type] || type;
            item.innerHTML = `<span class="debug-label">${label}:</span> <code>${message}</code>`;
            apiDebugList.appendChild(item);
        });
    }

    function setApiDebugMessage(type, message) {
        apiDebugMessages[type] = `${new Date().toLocaleTimeString()} – ${message}`;
        renderApiDebug();
    }

    renderApiDebug();

    function formatNumber(value) {
        return Number.parseFloat(value).toFixed(8);
    }

    function formatNok(value) {
        if (!Number.isFinite(value)) return '-';
        return `${value.toLocaleString('nb-NO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} NOK`;
    }

    function formatPercent(value) {
        if (!Number.isFinite(value)) return '-';
        return `${value.toFixed(2)}%`;
    }

    function parsePositiveNumber(value) {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    }

    function formatWithCurrency(amount, currency) {
        if (!Number.isFinite(amount)) return '-';
        return `${formatNumber(amount)} ${currency}`;
    }

    function resolveLivePrice(assetSymbol, preferredCurrency = 'USD', priceMap = livePrices, symbolMap = symbolPrices) {
        const available = priceMap?.[assetSymbol];

        if (available?.[preferredCurrency]) {
            return { price: available[preferredCurrency], currency: preferredCurrency, source: 'direkte match (pris-matrise)' };
        }

        const flatSymbol = `${assetSymbol}${preferredCurrency}`;
        if (symbolMap?.[flatSymbol]) {
            return { price: symbolMap[flatSymbol], currency: preferredCurrency, source: 'direkte match (symbolkart)' };
        }

        if (available) {
            const [firstQuote] = Object.keys(available);
            if (firstQuote) {
                return { price: available[firstQuote], currency: firstQuote, source: 'fallback (første notert valuta)' };
            }
        }

        const fallback = Object.entries(symbolMap || {}).find(([key]) => key.startsWith(assetSymbol));
        if (fallback) {
            const [symbol, price] = fallback;
            const quote = symbol.replace(assetSymbol, '') || null;
            return { price, currency: quote, source: 'fallback (symbolkart)' };
        }

        return { price: null, currency: null, source: 'ingen pris tilgjengelig' };
    }

    function convertToNok(amount, currency) {
        const rate = fxRates?.[currency];
        if (!Number.isFinite(amount) || !Number.isFinite(rate)) return null;
        return amount * rate;
    }

    function updatePortfolioSummary(priceMap = livePrices) {
        const totalInvestedEl = document.getElementById('totalInvestedNok');
        const realizedEl = document.getElementById('realizedNok');
        const unrealizedEl = document.getElementById('unrealizedNok');
        const roiEl = document.getElementById('lifetimeRoi');

        if (!totalInvestedEl || !realizedEl || !unrealizedEl || !roiEl) return;

        let totalInvestedNok = 0;
        let realizedNok = 0;
        let unrealizedNok = 0;

        document.querySelectorAll('.order-card').forEach(card => {
            if (card.classList.contains('is-hidden')) return;

            const currency = (card.dataset.currency || 'USD').toUpperCase();
            const totalCost = Number.parseFloat(card.dataset.totalCost);
            const realizedProfit = Number.parseFloat(card.dataset.realizedProfit || '0');
            const entryPrice = Number.parseFloat(card.dataset.entryPrice);
            const remaining = Number.parseFloat(card.dataset.remaining);
            const assetSymbol = card.dataset.assetSymbol;

            const investedNok = convertToNok(totalCost, currency);
            if (Number.isFinite(investedNok)) {
                totalInvestedNok += investedNok;
            }

            const realizedNokValue = convertToNok(realizedProfit, currency);
            if (Number.isFinite(realizedNokValue)) {
                realizedNok += realizedNokValue;
            }

            if (remaining > 0 && Number.isFinite(entryPrice)) {
                const { price: livePrice, currency: liveCurrency } = resolveLivePrice(assetSymbol, currency, priceMap, symbolPrices);
                const priceCurrency = (liveCurrency || currency || '').toUpperCase();
                if (Number.isFinite(livePrice) && fxRates[priceCurrency]) {
                    const unrealizedNative = remaining * (livePrice - entryPrice);
                    const unrealizedNokValue = convertToNok(unrealizedNative, priceCurrency);
                    if (Number.isFinite(unrealizedNokValue)) {
                        unrealizedNok += unrealizedNokValue;
                    }
                }
            }
        });

        const lifetimeRoi = totalInvestedNok > 0 ? ((realizedNok + unrealizedNok) / totalInvestedNok) * 100 : null;

        totalInvestedEl.textContent = formatNok(totalInvestedNok);
        realizedEl.textContent = formatNok(realizedNok);
        unrealizedEl.textContent = formatNok(unrealizedNok);
        roiEl.textContent = formatPercent(lifetimeRoi);
    }

    function updateAssetAverages() {
        const averagesContainer = document.getElementById('assetAverages');
        if (!averagesContainer) return;

        const assets = {};

        document.querySelectorAll('.order-card').forEach(card => {
            if (card.classList.contains('is-hidden')) return;

            const remaining = Number.parseFloat(card.dataset.remaining);
            const entryPrice = Number.parseFloat(card.dataset.entryPrice);
            const assetSymbol = (card.dataset.assetSymbol || card.dataset.asset || '').toUpperCase();
            const currency = (card.dataset.currency || 'USD').toUpperCase();

            if (!assetSymbol || !Number.isFinite(entryPrice) || !Number.isFinite(remaining) || remaining <= 0) {
                return;
            }

            const key = `${assetSymbol}-${currency}`;
            if (!assets[key]) {
                assets[key] = { asset: assetSymbol, currency, totalQty: 0, totalCost: 0 };
            }

            assets[key].totalQty += remaining;
            assets[key].totalCost += remaining * entryPrice;
        });

        const entries = Object.values(assets).sort((a, b) => a.asset.localeCompare(b.asset));
        averagesContainer.innerHTML = '';

        if (!entries.length) {
            averagesContainer.innerHTML = '<p class="muted">Ingen åpne posisjoner i filteret.</p>';
            return;
        }

        entries.forEach(entry => {
            const averagePrice = entry.totalQty > 0 ? entry.totalCost / entry.totalQty : null;
            const card = document.createElement('div');
            card.className = 'asset-average-card';
            card.innerHTML = `
                <div>
                    <p class="eyebrow">Asset</p>
                    <p class="mono">${entry.asset}</p>
                </div>
                <div>
                    <p class="eyebrow">Snittpris</p>
                    <p class="mono">${formatWithCurrency(averagePrice, entry.currency)}</p>
                </div>
                <div>
                    <p class="eyebrow">Åpen mengde</p>
                    <p class="mono">${formatNumber(entry.totalQty)}</p>
                </div>
            `;
            averagesContainer.appendChild(card);
        });
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
            entryPriceInput.value = Number.isFinite(computedEntry) ? computedEntry.toFixed(4) : '';
            return;
        }

        if (changedField !== 'total_cost' && quantity !== null && entryPrice !== null) {
            const computedTotal = quantity * entryPrice;
            totalCostInput.value = Number.isFinite(computedTotal) ? computedTotal.toFixed(4) : '';
        }
    }

    function applyFilters() {
        const assetValue = assetFilterSelect?.value.trim().toLowerCase() || '';
        const statusValue = Array.from(statusFilterRadios).find(radio => radio.checked)?.value || 'all';

        document.querySelectorAll('.order-card').forEach(card => {
            const matchesAsset = !assetValue || card.dataset.asset?.toLowerCase() === assetValue;
            const matchesStatus = statusValue === 'all' || card.dataset.status === statusValue;
            card.classList.toggle('is-hidden', !(matchesAsset && matchesStatus));
        });

        updateOrderCards(livePrices);
    }

    function updateOrderCards(priceMap = livePrices) {
        const cards = document.querySelectorAll('.order-card');
        const resolutionLogs = [];

        cards.forEach(card => {
            if (card.classList.contains('is-hidden')) return;

            const entryPrice = Number.parseFloat(card.dataset.entryPrice);
            const remaining = Number.parseFloat(card.dataset.remaining);
            const assetSymbol = card.dataset.assetSymbol;
            const currency = (card.dataset.currency || 'USD').trim().toUpperCase();
            const profitEl = card.querySelector('.unrealized');
            const liveEl = card.querySelector('.order-live-price');
            const liveCurrencyChip = card.querySelector('.order-card__live .chip');

            const { price: feedPrice, currency: feedCurrency, source: resolutionSource } = resolveLivePrice(assetSymbol, currency, priceMap);
            const currentPrice = Number.isFinite(feedPrice) ? feedPrice : null;
            const displayCurrency = (feedCurrency || currency || '').toUpperCase();

            if (!Number.isFinite(currentPrice)) {
                resolutionLogs.push(`${assetSymbol}/${currency}: ingen pris (${resolutionSource})`);
                console.debug('[live-price] Ingen pris tilgjengelig', {
                    assetSymbol,
                    requestedCurrency: currency,
                    resolutionSource,
                    priceMapSnapshot: priceMap?.[assetSymbol],
                    symbolMapHit: symbolPrices?.[`${assetSymbol}${currency}`],
                });
            } else {
                resolutionLogs.push(`${assetSymbol}/${displayCurrency}: ${formatNumber(currentPrice)} (${resolutionSource})`);
            }

            if (liveEl) {
                liveEl.textContent = Number.isFinite(currentPrice) ? formatWithCurrency(currentPrice, displayCurrency) : '-';
            }
            if (liveCurrencyChip && displayCurrency) {
                liveCurrencyChip.textContent = displayCurrency;
            }

            if (remaining <= 0 && profitEl) {
                profitEl.textContent = 'Closed';
                profitEl.classList.remove('positive', 'negative');
                return;
            }

            if (Number.isFinite(currentPrice) && !Number.isNaN(entryPrice) && !Number.isNaN(remaining) && remaining > 0) {
                const profitNative = remaining * (currentPrice - entryPrice);

                if (profitEl) {
                    profitEl.textContent = formatWithCurrency(profitNative, displayCurrency);
                    profitEl.classList.remove('positive', 'negative');
                    if (profitNative > 0) {
                        profitEl.classList.add('positive');
                    } else if (profitNative < 0) {
                        profitEl.classList.add('negative');
                    }
                }
            } else if (profitEl) {
                profitEl.textContent = '-';
                profitEl.classList.remove('positive', 'negative');
            }
        });

        if (resolutionLogs.length) {
            setApiDebugMessage('resolution', resolutionLogs.join(' · '));
        }

        updatePortfolioSummary(priceMap);
        updateAssetAverages();
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
            fxRates = data.fx_rates || {};
            const binanceRequests = (data.binance_requests || []).map(url => `GET ${url}`);
            const fxRequests = (data.fx_requests || []).map(url => url ? `GET ${url}` : 'Derived USDC=USD');
            const binanceDisplay = binanceRequests.length ? binanceRequests.join(' · ') : 'Ingen Binance-spørringer registrert.';
            const fxDisplay = fxRequests.length ? fxRequests.join(' · ') : 'Ingen FX-spørringer registrert.';
            setApiDebugMessage('prices', `GET ${path} | Binance: ${binanceDisplay} | FX: ${fxDisplay}`);
            updateOrderCards(livePrices);
        } catch (error) {
            console.error(error);
        } finally {
            refreshButton?.removeAttribute('disabled');
            setTimeout(() => livePulse?.classList.remove('active'), 800);
        }
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
