# GlowitheFlow E2E Testing Infrastructure

This document details the architecture, design, and execution guidelines for the End-to-End (E2E) testing suite across both the Nuxt frontend and the WordPress plugin backend.

---

## 1. Architecture Overview

GlowitheFlow's opaque-box testing infrastructure is split into two independent tracks:
1. **Frontend Testing Track**: Driven by **Vitest** to test Nuxt client-side features, user interface components (such as HUD, composer, link pricing slider), and client-side calculations (CPC decay, spam detection).
2. **Backend Testing Track**: Driven by **PHPUnit** combined with **Brain Monkey** and **Mockery** to test WordPress REST API endpoints (user, feed, submissions, posts boosting) and custom database integrations using isolated mocks without requiring an active database or running Docksal containers.

---

## 2. Frontend Testing Track (Vitest)

### Setup & Configuration
- **Test Runner**: Vitest (`^3.0.0`)
- **Environment**: Happy DOM (`happy-dom`) configured via `@nuxt/test-utils` to simulate a browser environment.
- **Config File**: `vitest.config.ts` at the project root.
- **Source Location**: `/app/tests/e2e.spec.ts`

### Running Frontend Tests
Run all frontend test suites using the following command at the root directory:
```bash
pnpm test
```

For watch mode during active development:
```bash
pnpm test:watch
```

---

## 3. Backend Testing Track (PHPUnit & Brain Monkey)

### Setup & Configuration
- **Test Runner**: PHPUnit (`^9.5`)
- **Mocks & Isolation**: Brain Monkey (`^2.6.1`) for mocking WordPress hooks/functions and Mockery (`^1.5`) for general PHP objects.
- **Config File**: `wordpress-plugin/phpunit.xml.dist`
- **Bootstrap File**: `wordpress-plugin/tests/bootstrap.php` (initializes Composer's autoloader, sets up crucial constants like `ABSPATH`, and loads database classes).
- **Source Location**: `/wordpress-plugin/tests/test-glow-api-e2e.php`

### Running Backend Tests
Execute the PHPUnit tests using the vendor binary within the `wordpress-plugin` folder:
```bash
cd wordpress-plugin
./vendor/bin/phpunit
```

---

## 4. The 4-Tier Testing Strategy

To ensure comprehensive validation across all layers, the test suites are structured using a four-tier testing hierarchy:

### Tier 1: Feature Coverage
Validates the fundamental functional requirements and positive paths for individual features.
- **Frontend (Vitest)**: HUD network tier calculations, balance string formatting, base CPC multiplier calculations, Flow Composer link detection regex, and Link Price Slider moving averages.
- **Backend (PHPUnit)**: REST API responses, feed JSON payloads, thought submission outcomes, boost upvote additions, and Stripe session generation.

### Tier 2: Boundary & Corner Cases
Tests the robustness under extreme inputs, error states, and edge conditions.
- **Frontend (Vitest)**: Floating point zero-padding formatting, click-velocity quality degradation, CPC decay limits ($0.01 threshold), balance gating in Composer, and slider value rounding/clamping.
- **Backend (PHPUnit)**: Zero user ID errors, negative page numbers in feed, empty thought content rejections, 404s for invalid post IDs, and rejected Stripe webhook signatures.

### Tier 3: Cross-Feature Combinations
Tests pairwise interactions where actions or configuration changes in one feature affect the behavior of another.
- **Frontend (Vitest)**: Composer balance gating dynamically reacting to slider price votes, and click velocity spam detection operating independently from other views.
- **Backend (PHPUnit)**: Submit link transaction updating user balances while simultaneously registering posts, and boost reward transactions distributing driplets and log updates concurrently.

### Tier 4: Real-World Application Scenarios
Simulates realistic, multi-step user workflows to replicate real-world application utilization.
- **Frontend (Vitest)**: Visitor onboarding (landing, initial clicks, earning driplets, and writing thoughts) and spam click prevention (rapid clicking fraud states followed by recovery cooldowns).
- **Backend (PHPUnit)**: Multi-step user onboarding & API validation flows, and checkout fulfillment webhook simulations (verifying user balances and lifetime value updates).
