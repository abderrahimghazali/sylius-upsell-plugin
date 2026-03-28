# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-03-28

### Added

#### Phase 1 — Frequently Bought Together
- `UpsellRelation` entity for manual product-to-product upsell links with position and optional discount
- `UpsellConfiguration` entity (singleton) for global FBT settings
- `FrequentlyBoughtTogetherResolver` with manual-first, algorithmic fallback (co-purchase query on `sylius_order_item`)
- Cached algorithmic results per product + channel (1h TTL)
- FBT section injected on product page via Twig hooks
- Stimulus `fbt-controller` with checkbox toggling and one-click add-all-to-cart
- Admin: Configuration > Upsell Settings page (enable/disable, threshold, max products, section title, fallback strategy)

#### Phase 2 — Checkout Upsell Modal
- `UpsellOffer` entity with trigger/offer products, discount %, scheduling, priority, and customizable copy
- `PostPurchaseOfferResolver` matching offers by order products or catch-all, respecting date range and excluding already-purchased products
- Upsell modal on checkout complete page — intercepts "Place order" click
- "Yes, add it!" adds product to current cart at discounted price, redirects to cart for review
- "No thanks" submits the order normally
- Admin CRUD: Marketing > Upsell Offers with grid and full create/update forms
- Stimulus `post-purchase-controller` for modal rendering, accept/decline handling
- Unit tests for `FrequentlyBoughtTogetherResolver` and `PostPurchaseOfferResolver`

#### Infrastructure
- Doctrine XML mappings for all entities
- Sylius resource + grid integration for UpsellOffer
- Twig hook injection (no template overrides)
- EN/FR translations
- PHPStan level 5 configuration
- GitHub Actions CI (PHP 8.2/8.3, PHPStan, PHPUnit)
