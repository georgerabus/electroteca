# Reputation System

This document explains how the reputation system works, where to find it in the UI, and how admins can manage discounts.

## What users see
- User menu (avatar) includes "Your Reputation" with a quick rating + discount preview.
- `/reputation` page shows score, rating, milestones, and recent changes.
- Score is a raw value; rating is clamped to 0-100 for display.

## Scoring rules
Reputation score is recalculated automatically from user stats plus manual adjustments:

```
score = (completed_loans * 10)
      + (completed_orders * 5)
      - (items_damaged * 20)
      + reputation_adjustment
```

## Events that change reputation
- Loan status changes to `Returned` or `Defective`:
  - `completed_loans` increments (+10 score per loan)
  - if `damage_fee > 0`, `items_damaged` increments (-20 score per damage)
  - on-time returns are tracked for insights
- Order status changes to `completed`:
  - `completed_orders` increments (+5 score per order)
- Admin manual adjustments:
  - `reputation_adjustment` increments by the given amount
  - creates a history record

Each change creates a history row in `reputations` with a reason and timestamp.

## Discounts and milestones
Discount tiers are stored in `reputation_tiers` and shown on the Reputation page.
The current discount is the highest active tier with `min_score <= user score`.

Default tiers:
- Starter: 0 points, 0%
- Bronze: 20 points, 5%
- Silver: 50 points, 10%
- Gold: 80 points, 15%

## Admin controls
- User reputation adjustments: Admin user dashboard
- Discount milestones: `/admin/reputation-tiers`
  - Create, edit, or delete tiers
  - Set minimum score and discount percent

## Endpoints
- `GET /reputation` - user reputation page (Inertia)
- `GET /reputation/{user}` - JSON reputation data (owner or admin)
- `GET /reputation/{user}/rating` - JSON rating (owner or admin)

## Storage
- `users` columns: reputation_score, completed_loans, completed_orders, items_damaged, returns_on_time, reputation_adjustment
- `reputations`: history of changes
- `reputation_tiers`: milestones and discounts
