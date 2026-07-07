# Click Valuation & Dropplet Gamification System

This documentation describes the click valuation model, simulated ad-metric algorithms, and interactive feedback systems built to gamify user interaction inside GlowitheFlow.

## Core Concepts

### 1. Click Value (CPC) & Lifetime Value (LTV)
Rather than simple click tracking, the application calculates a simulated **Cost Per Click (CPC)** and tracks the user's generated **Lifetime Value (LTV)** in fake ad network currency. This serves as an immediate visual metric showing the user how "valuable" their clicks are to the ecosystem.

- **Base CPC:** Initially set at `$0.10`.
- **Award Ratio:** Each click converts `$0.01` of generated value to `1 Dropplet (💧)`. For example, a `$0.25` click awards `25 Dropplets`.
- **Initial Balance:** New visitors start with `50 Dropplets` so they can experiment with submitting links immediately.

### 2. CPC Valuation Factors
The real-time click value fluctuates dynamically based on:
1. **Click Velocity (Traffic Quality):** Spacing between clicks. If clicks occur faster than `300ms`, a bot/fraud detector flags the traffic. Quality decays exponentially down to `0%`, which sets the click value to a minimum of `$0.01`. Spaced, rhythmic clicks allow quality to recover back to `100%`.
2. **Mouse Path Complexity (Engagement):** Tracks the cumulative mouse travel distance (pixels). Moving the mouse deliberately before clicking earns up to a `1.5x` multiplier.
3. **Market Price (Link Price):** Higher slider-voted link cost represents higher network demand, scaling the base CPC multiplier.
4. **Session Depth:** Click count tracks session depth. Value peaks around clicks 20–50, then slowly decays to simulate ad fatigue.

---

## Technical Details

### State Variables
The state is managed in [index.vue](file:///home/xopher/www/x/glowitheflow.com/app/pages/index.vue) and synchronized with `localStorage`:
- `droppletBalance`: Current spendable dropplets (`glow_dropplet_balance`).
- `lifetimeValue`: Total accumulated ad-revenue generated (`glow_lifetime_value`).
- `trafficQuality`: Genuine interaction score (`0 - 100`).
- `cpc`: Value of the next click.
- `clickCount`: Lifetime session clicks (`glow_click_count`).
- `mouseTravelDistance`: Accumulated travel pixels.

### Spending Loop
Submitting a link consumes `currentLinkPrice` Dropplets.
- Gated in [FlowComposer.vue](file:///home/xopher/www/x/glowitheflow.com/app/components/FlowComposer.vue).
- Deducted in [index.vue](file:///home/xopher/www/x/glowitheflow.com/app/pages/index.vue) inside `handleFlowSubmit`.

---

## Visual Presentation

### 1. User HUD Dashboard
A glassmorphic floating panel rendered in the top-right corner of the canvas:
- **Network Tier:** Dynamically calculated based on LTV:
  - `Bronze Trafficker` (< $5.00)
  - `Silver Streamer` (< $20.00)
  - `Gold Thinker` (< $50.00)
  - `Platinum Emitter` (< $150.00)
  - `Ultimate Whale` (>= $150.00)
- **Traffic Quality Gauge:** Interactive progress bar that turns red and flashes a warning during rapid spam-clicking.
- **Dropplet Balance:** Current spendable balance with a custom neon drop icon.

### 2. Canvas Text Particles
Every background click spawns a floating canvas text element:
- Renders at click coordinates using a neon shadow glow.
- Displays the value in format: `+$0.25 (+25💧)`.
- If traffic quality drops below 40%, colors turn red and display `[FRAUD DETECTED]` to warn the user.
