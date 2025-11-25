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

    function formatNumber(value) {
        return Number.parseFloat(value).toFixed(8);
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

        document.querySelectorAll('.order-card').forEach(card => {
            const matchesAsset = !assetValue || card.dataset.asset?.toLowerCase() === assetValue;
            const matchesStatus = statusValue === 'all' || card.dataset.status === statusValue;
            card.classList.toggle('is-hidden', !(matchesAsset && matchesStatus));
        });

        updateOrderCards(livePrices);
    }

    function updateOrderCards(priceMap = livePrices) {
        const cards = document.querySelectorAll('.order-card');

        cards.forEach(card => {
            if (card.classList.contains('is-hidden')) return;

            const entryPrice = Number.parseFloat(card.dataset.entryPrice);
            const remaining = Number.parseFloat(card.dataset.remaining);
            const assetSymbol = card.dataset.assetSymbol;
            const currency = card.dataset.currency || 'USD';
            const profitEl = card.querySelector('.unrealized');
            const liveEl = card.querySelector('.order-live-price');

            const { price: feedPrice } = resolveLivePrice(assetSymbol, currency, priceMap);
            const currentPrice = Number.isFinite(feedPrice) ? feedPrice : null;

            if (liveEl) {
                liveEl.textContent = Number.isFinite(currentPrice) ? formatWithCurrency(currentPrice, currency) : '-';
            }

            if (remaining <= 0 && profitEl) {
                profitEl.textContent = 'Closed';
                profitEl.classList.remove('positive', 'negative');
                return;
            }

            if (Number.isFinite(currentPrice) && !Number.isNaN(entryPrice) && !Number.isNaN(remaining) && remaining > 0) {
                const profitNative = remaining * (currentPrice - entryPrice);

                if (profitEl) {
                    profitEl.textContent = formatWithCurrency(profitNative, currency);
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
            const binanceRequests = (data.binance_requests || []).map(url => `GET ${url}`);
            const binanceDisplay = binanceRequests.length ? binanceRequests.join(' · ') : 'Ingen Binance-spørringer registrert.';
            setApiDebugMessage('prices', `GET ${path} | Binance: ${binanceDisplay}`);
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
