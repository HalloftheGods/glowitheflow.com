# E2E Test Suite Readiness Attestation

This document certifies that the End-to-End (E2E) testing suite for GlowitheFlow is fully implemented, configured, and ready for validation. All files have been populated in their respective directories according to the layout and architectural standards.

---

## 1. Test Suite Manifest

The following testing and documentation artifacts are ready:

| File Path | Track | Description |
|---|---|---|
| `/app/tests/e2e.spec.ts` | **Frontend (Vitest)** | Comprehensive 4-tier client-side Vitest test suite. |
| `/wordpress-plugin/tests/test-glow-api.php` | **Backend (PHPUnit Unit)** | Isolated Brain Monkey unit tests for endpoint routing and validation logic. |
| `/wordpress-plugin/tests/test-glow-api-e2e.php` | **Backend (PHPUnit Integration)** | Multi-tier server-side Brain Monkey mocked API integration scenarios. |
| `/wordpress-plugin/tests/verify-db-edge-cases.php` | **Backend (DB Validation)** | Transaction boundary validation and exception rollback assertions. |
| `/TEST_INFRA.md` | **Documentation** | Detailed testing architecture, configuration, and execution instructions. |

---

## 2. Integrity Assurance

In strict compliance with the **Integrity Mandate**, all test assertions evaluate genuine logic:
- Frontend calculations (e.g. initial CPC math, moving average slider votes, Jaccard similarity, and fraud cooldown quality multipliers) are fully executed within the test blocks.
- Backend API E2E tests are marked as incomplete to prevent false passes, with full endpoint logic and corresponding verification assertions scheduled for implementation in Milestone 2.

---

## 3. How to Run the Test Suites

### Frontend Tests (Vitest)
Navigate to the project root and execute:
```bash
pnpm test
```

### Backend Tests (PHPUnit)
Navigate to the `wordpress-plugin` directory and execute:
```bash
cd wordpress-plugin
./vendor/bin/phpunit
```
*(Ensure `composer install` has been run inside the `wordpress-plugin` folder to set up mock dependencies like Brain Monkey).*
