---
version: alpha
colors:
  background: "#0f172a"
  surface: "#111827"
  accent: "#22d3ee"
  text: "#e5e7eb"
  muted: "#9ca3af"
  negative: "#ef4444"
  positive: "#22c55e"
typography:
  body:
    fontFamily: 'system-ui, -apple-system, "Segoe UI", sans-serif'
omitted:
  - section: spacing
    reason: Existing stylesheet owns spacing.
  - section: rounded
    reason: Existing stylesheet owns shape values.
  - section: components
    reason: Plain PHP and shared CSS own components.
---

## Overview

Personal Norwegian crypto investment dashboard. Preserve the existing dark trading-ledger appearance; prioritize comparable returns and readable dates over decoration.

## Colors

Runtime tokens in assets/style.css are canonical. The values above mirror --bg, --card, --accent, --text, --muted, --danger and --success respectively. New chart styles consume these variables directly. Cyan identifies annualized performance. A shared centre line identifies zero; numerical labels and series names carry meaning independently of color.

## Typography

Use the existing system font and .mono tabular numbers, Norwegian number formatting, and UTC dates. Labels explain whether a number is total, per year, or realized in a calendar year.

## Layout

Reuse .card, .stat and .summary-grid. Annual return and aggregate result precede the two charts. Charts stack below 700px. Order performance uses the same three metrics in list and detail. Long values wrap instead of truncating.

## Elevation & Depth

Keep existing card surfaces and borders. Charts are flat data regions inside the portfolio card.

## Shapes

Reuse existing stat corners. Signed chart bars have a fixed zero baseline and no animation.

## Components

assets/performance.js owns calculation rules. assets/performance-ui.js owns repeated formatting, signed bars, missing-data states and order summaries. PHP provides escaped user-owned data. order-entry.js and order_input.php resolve purchase pairs. Existing navigation, forms and journal remain in their current owners.

## Do's and Don'ts

Show explicit values beside every chart bar. Show “Under 1 år” for short holding periods without extrapolation. Show unavailable results as a dash with a reason. Keep annualized return distinct from realized calendar-year result. Do not infer historical valuations, add new fee information, or rebrand the app for this feature.
