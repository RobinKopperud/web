# Performance display contract

This contract covers the added read-only performance surfaces. Purchase entry accepts any two of quantity, unit price and total, deriving the third both in the browser and on the server. Explicitly edited fields take priority over derived values. Sale always closes the full order; a transaction and row lock prevent duplicate execution.

| Capability | Canonical owner | Source of truth | Allowed variants | Verification |
|---|---|---|---|---|
| Date | performance_data.php and performance-ui.js | schema.sql purchased_at and order_closures.created_at; UTC | purchase, sale, holding period | calculation tests and browser fixture |

The server scopes history to the logged-in user, following auth.php and existing order ownership checks. performance_data.php supplies the same performance data to the list and detail.

Whole orders use purchase cost and current full-order value or closed-order proceeds. Annualized order returns use compound growth only after one calendar year of holding. Portfolio XIRR includes only orders with at least one year of holding; aggregate result includes all orders. Closed orders stop at their sale date. Partial-sale controls and allocation logic are removed; inconsistent legacy quantities are unavailable and cannot be sold until corrected. No yearly market-value return is shown without historical valuations. Calendar-year charts show realized sale results only. NOK figures use current exchange rates, disclosed beside the totals.

The shared toolbar integrates filters, search and price refresh across views. All user-owned orders are loaded for local filtering; there is no separate filter page. Price errors/timeouts give a visible retry message, and versioned assets prevent stale scripts after publishing. Missing prices, FX, invalid dates, inconsistent stored quantity or indeterminate XIRR show a dash and explanation; unavailable positions never silently become zero returns. Actual zero results remain numerical zero.

Charts expose series names, signs and values as native text lists; graphics are decorative. The portfolio status is a live status region. Values use nb-NO formatting, and dates/year grouping use UTC. New code introduces no mutations or interactive controls.
