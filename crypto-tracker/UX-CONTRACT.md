# Performance display contract

This contract covers the added read-only performance surfaces. Existing mutation flows are outside this change.

| Capability | Canonical owner | Source of truth | Allowed variants | Verification |
|---|---|---|---|---|
| Date | performance_data.php and performance-ui.js | schema.sql purchased_at and order_closures.created_at; UTC | purchase, sale, holding period | calculation tests and browser fixture |

The server scopes history to the logged-in user, following auth.php and existing order ownership checks. performance_data.php supplies the same performance data to the list and detail.

Annualized return is based on purchase date, dated proceeds and remaining value. Unsold orders use compound growth; orders with sales and portfolio totals use XIRR. Closed orders stop at their final sale. No yearly market-value return is shown without historical valuations. Calendar-year charts show realized sale results only. NOK figures use current exchange rates, disclosed beside the totals.

Portfolio aggregates and charts follow the existing visible-order filters and local search. Missing prices, FX, invalid dates, incomplete sale history or indeterminate XIRR show a dash and explanation; unavailable positions never silently become zero returns. Actual zero results remain numerical zero.

Charts expose series names, signs and values as native text lists; graphics are decorative. The portfolio status is a live status region. Values use nb-NO formatting, and dates/year grouping use UTC. New code introduces no mutations or interactive controls.
