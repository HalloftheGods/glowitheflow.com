# GlowitheFlow

The third emanation of the sacred realm. A canvas of infinite possibilities where your attention generates value.

## Overview

GlowitheFlow is an interactive, gamified platform where users generate simulated ad revenue (LTV) by engaging with floating text nodes. Click on thoughts flowing through the stream to earn **Driplets**, which can be converted into **Droplets** and used to cast your own links into the network. 

The community dynamically votes on the cost to drop a link, simulating a real-time attention economy.

## Terminology
* Driplet: Users earn dripplets through their time and interaction on glowitheflow
  * The longer the stream, the more dripplets
  * The more clicks the more driplets. 
* Droplet: 
  * 100 driplets = 1 Droplet
  * Spend droplets to drop links into the network
  * The community votes on the costs of a drop. 
  * The amount of droplets you spend determines the drip rate.
## Tech Stack
* **Frontend:** Nuxt 3, Vue 3, Canvas API, Tailwind CSS
* **Backend:** WordPress Plugin REST API
* **Testing:** Vitest (Frontend) / PHPUnit & Brain Monkey (Backend)

## Setup

Make sure to install dependencies:

```bash
pnpm install
```

## Development Server

Start the development server on `http://localhost:3000`:

```bash
pnpm dev
```

## Backend Setup (WordPress)
1. Navigate to `wordpress-plugin/`
2. Run `composer install` for PHP dependencies
3. Follow the `TEST_INFRA.md` guidelines for test suites.

## Testing

Run frontend Vitest suite:
```bash
pnpm test
```

Run backend PHPUnit suite:
```bash
cd wordpress-plugin
./vendor/bin/phpunit
```
