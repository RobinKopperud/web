"""Exercise the actual JS/CSS and extracted PHP markup with synthetic orders.

Run: python3 crypto-tracker/tests/browser_smoke.py
This is frontend fixture verification, not a live database/PHP integration test.
"""
import html
import json
import re
import time
from pathlib import Path
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parents[1]
source = (root / 'index.php').read_text()
portfolio = source[source.index('    <section class="card portfolio-header'):source.index('    <section class="card view-section is-hidden" id="addOrderSection"')]
template = source[source.index('                    <article class="order-card'):source.index('                    </article>') + len('                    </article>')]
template = re.sub(r'<\?php.*?\?>', '', template, flags=re.S)
now = int(time.time() * 1000)
year = 365.25 * 86400000
orders = [
    dict(id=1, asset='BTC', currency='USD', cost=100, quantity=10, status='OPEN', purchasedAt=now-2*year),
    dict(id=2, asset='ETH', currency='EUR', cost=100, quantity=10, status='OPEN', purchasedAt=now-10*86400000),
    dict(id=3, asset='SOL', currency='USD', cost=100, quantity=10, status='CLOSED', purchasedAt=now-2*year, soldAt=now-year, realizedProfit=-20),
]
cards = []
for order in orders:
    attributes = {
        'performance': json.dumps(order), 'entry-price': '10', 'quantity': '10',
        'remaining': str(order['quantity'] if order['status']=='OPEN' else 0), 'asset': order['asset'].lower(),
        'asset-symbol': order['asset'], 'currency': order['currency'],
        'status': order['status'].lower(),
        'total-cost': '100', 'realized-profit': str(order.get('realizedProfit',0)),
        'search': order['asset'].lower(),
    }
    opening = '<article class="order-card" ' + ' '.join(f'data-{key}="{html.escape(value, quote=True)}"' for key, value in attributes.items()) + '>'
    cards.append(re.sub(r'<article.*?>', opening, template, count=1, flags=re.S))
nav = '<nav class="top-nav card"><button type="button" class="btn nav-btn" data-target="portfolioSection">Portefølje</button><button type="button" class="btn nav-btn" data-target="ordersSection">Ordre</button><button type="button" class="btn nav-btn" data-target="addOrderSection">Legg inn ordre</button></nav>'
filters = '<section><form id="integratedFilters"><button type="button" id="refreshPrices">Oppdater priser</button><label for="filter_asset">Kryptovaluta</label><select id="filter_asset"><option value="">Alle</option><option value="BTC">BTC</option></select><input type="radio" name="status" value="all" checked><input type="search" id="orderSearch" aria-label="Søk"></form></section>'
scripts = ''.join('<script>' + (root / 'assets' / name).read_text() + '</script>' for name in ['performance.js', 'performance-ui.js', 'order-entry.js', 'app.js'])
add_markup = source[source.index('    <section class="card view-section is-hidden" id="addOrderSection"'):source.index('    <section class="card view-section is-hidden" id="ordersSection"')]
add_markup = re.sub(r'<\?php.*?\?>','',add_markup,flags=re.S)
page_html = '<!doctype html><html lang="no"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>' + (root / 'assets/style.css').read_text() + '</style><body><div class="container"><h1>Kryptooversikt</h1>' + nav + filters + portfolio + add_markup + '<section class="card view-section" id="ordersSection"><div class="order-grid" id="ordersTable">' + ''.join(cards) + '</div></section></div>' + scripts + '</body></html>'
prices = dict(prices={'BTC': {'USD': 11}, 'ETH': {'EUR': 12.1}}, symbol_prices={}, fx_rates={'USD':10, 'EUR':11})
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path='/usr/bin/chromium', args=['--no-sandbox'])
    page = browser.new_page(viewport={'width':1280, 'height':1000})
    errors = []
    price_failure = False
    page.on('pageerror', lambda error: errors.append(str(error)))
    def route_handler(route):
        if 'prices.php' in route.request.url:
            if price_failure: route.fulfill(status=502,body='Upstream error')
            else: route.fulfill(json=prices)
        else:
            route.fulfill(content_type='text/html', body=page_html)
    page.route('http://crypto.test/**', route_handler)
    page.goto('http://crypto.test/')
    page.wait_for_function("document.getElementById('portfolioAnnualReturn').textContent !== '–'")
    assert page.locator('.order-card[data-asset="btc"] .order-total-return').inner_text() == '10,00 %'
    assert page.locator('.order-card[data-asset="btc"] .order-annual-return').inner_text() == '4,88 %'
    assert page.locator('.order-card[data-asset="eth"] .order-annual-return').inner_text() == 'Under 1 år'
    assert page.locator('.order-card[data-asset="sol"] .order-annual-return').inner_text() == '−20,00 %'
    assert page.locator('#orderReturnChart .chart-item').count() == 3
    page.locator('#filter_asset').select_option('BTC')
    page.wait_for_function("document.getElementById('performanceStatus').textContent.startsWith('1 ordrer')")
    assert page.locator('#portfolioAnnualReturn').inner_text() == '4,88 %'
    page.locator('#filter_asset').select_option('')
    page.locator('#orderSearch').fill('no results')
    assert page.locator('#performanceStatus').inner_text() == 'Ingen ordrer i dette utvalget.'
    page.locator('#orderSearch').fill('')
    page.evaluate('CryptoPerformanceUI.update({}, {}, {USD:10, EUR:11})')
    assert page.locator('#portfolioAnnualReturn').inner_text() == '–'
    assert 'Mangler' in page.locator('#performanceStatus').inner_text()
    page.evaluate('CryptoPerformanceUI.update({}, {}, {})')
    assert page.locator('#totalInvestedNok').inner_text() == '–'
    page.evaluate('CryptoPerformanceUI.update(' + json.dumps(prices['prices']) + ', {}, ' + json.dumps(prices['fx_rates']) + ')')
    price_failure = True
    page.locator('#refreshPrices').click()
    page.wait_for_function("document.getElementById('priceUpdated').textContent.includes('Kunne ikke hente')")
    assert 'Kunne ikke hente' in page.locator('#performanceStatus').inner_text()
    price_failure = False
    page.locator('#refreshPrices').click()
    page.wait_for_function("document.getElementById('priceUpdated').textContent.startsWith('Oppdatert')")
    page.locator('[data-target="addOrderSection"]').click()
    for first,second,values,target,expected in [
        ('quantity','entry_price',('2','10'),'total_cost','20'),
        ('quantity','total_cost',('2','20'),'entry_price','10'),
        ('entry_price','total_cost',('10','20'),'quantity','2'),
    ]:
        page.evaluate("document.querySelector('#addOrderSection form').reset()")
        for field in ['quantity','entry_price','total_cost']: page.locator('#'+field).fill('')
        page.locator('#'+first).fill(values[0])
        page.locator('#'+second).fill(values[1])
        assert page.locator('#'+target).input_value()==expected
    page.locator('#quantity').fill('3')
    assert page.locator('#total_cost').input_value()=='20'
    assert abs(float(page.locator('#entry_price').input_value())-20/3)<1e-7
    page.evaluate("document.querySelector('#addOrderSection form').reset()")
    for field in ['quantity','entry_price','total_cost']: page.locator('#'+field).fill('')
    page.locator('#quantity').fill('2')
    page.locator('#entry_price').fill('10')
    page.locator('#quantity').fill('3')
    assert page.locator('#entry_price').input_value()=='10'
    assert page.locator('#total_cost').input_value()=='30'
    page.locator('#quantity').fill('3')
    page.locator('#total_cost').fill('100')
    assert abs(float(page.locator('#entry_price').input_value())-100/3)<1e-7
    assert page.locator('#entry_price').evaluate('(el)=>el.checkValidity()'), 'derived price must be submittable'
    page.locator('#quantity').fill('0.001')
    page.locator('#entry_price').fill('0.1234')
    assert page.locator('#total_cost').input_value()=='0.0001234'
    assert page.locator('#total_cost').evaluate('(el)=>el.checkValidity()'), 'derived total must be submittable'
    page.locator('[data-target="portfolioSection"]').click()
    output = Path('/tmp/crypto-performance-preview')
    output.mkdir(exist_ok=True)
    page.screenshot(path=str(output / 'portfolio-desktop.png'), full_page=True)
    for width in [375, 320]:
        page.set_viewport_size({'width':width, 'height':900})
        assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth'), f'overflow at {width}px'
        page.locator('[data-target="ordersSection"]').click()
        assert page.locator('.order-card[data-asset="btc"]').is_visible()
        page.screenshot(path=str(output / f'orders-{width}.png'), full_page=True)
        page.locator('[data-target="portfolioSection"]').click()
        assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth'), f'portfolio overflow at {width}px'
    detail_source = (root / 'order_detail.php').read_text()
    detail_template = detail_source[detail_source.index('    <section class="card" id="orderPerformanceDetail"'):detail_source.index('    <section class="card danger"')]
    detail_template = re.sub(r'<\?php.*?\?>', '', detail_template, flags=re.S)
    for order in [orders[0], orders[2]]:
        detail_markup = detail_template.replace('data-performance=""', 'data-performance="' + html.escape(json.dumps(order), quote=True) + '"')
        detail_page = browser.new_page(viewport={'width':375, 'height':900})
        detail_page.on('pageerror', lambda error: errors.append(str(error)))
        detail_html = '<html lang="no"><meta name="viewport" content="width=device-width, initial-scale=1"><style>' + (root / 'assets/style.css').read_text() + '</style><body><div class="container">' + detail_markup + '</div>' + scripts.replace('<script>' + (root / 'assets/app.js').read_text() + '</script>', '') + '</body></html>'
        detail_page.route('http://detail.test/**', lambda route: route.fulfill(json=prices) if 'prices.php' in route.request.url else route.fulfill(content_type='text/html', body=detail_html))
        detail_page.goto('http://detail.test/')
        detail_page.wait_for_function("document.querySelector('.order-annual-return').textContent !== '–'")
        assert detail_page.locator('.order-annual-return').inner_text() == ('4,88 %' if order['status']=='OPEN' else '−20,00 %')
        assert detail_page.evaluate('document.documentElement.scrollWidth <= window.innerWidth')
        detail_page.close()
    assert not errors, errors
    browser.close()
print('Browser fixture passed: returns, filters, empty/missing data, navigation, open/closed order details, 1280/375/320px, price errors/retry and all purchase input pairs; no JS errors.')
