<template>
  <div
    class="canvas-container w-full h-full relative overflow-hidden"
    ref="containerRef"
  >
    <div
      ref="waterBgRef"
      class="absolute inset-0 z-0 pointer-events-none"
    ></div>
    <canvas
      ref="canvasRef"
      class="w-full h-full block transition-colors duration-1000 z-10 relative pointer-events-none"
      :class="uiState === 'landing' ? 'pointer-events-auto cursor-pointer' : 'pointer-events-auto'"
    ></canvas>

    <!-- Overlay UI -->
    <div class="ui-overlay pointer-events-none absolute inset-0 z-10 transition-all duration-1000 ease-in-out">

      <!-- Dynamic Title & Subtext -->
      <div
        class="absolute transition-all duration-1000 ease-in-out text-center flex flex-col items-center justify-center w-full"
        :class="[
          uiState === 'landing' 
            ? 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2' 
            : uiState === 'dashboard'
              ? 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-[180px] opacity-0 pointer-events-none'
              : 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-[180px] pointer-events-auto cursor-pointer'
        ]"
        @click="uiState === 'flow' && resetToLanding()"
      >
        <h1
          class="font-display font-normal text-transparent bg-clip-text bg-gradient-to-br from-white via-cyan-400 to-blue-600 tracking-wider inline-block transition-all duration-1000"
          :class="[
            uiState === 'landing' 
              ? 'text-7xl md:text-9xl drop-shadow-[0_0_20px_rgba(0,255,255,0.6)]' 
              : 'text-4xl md:text-6xl drop-shadow-[0_0_15px_rgba(0,255,255,0.5)]'
          ]"
        >
          <span
            v-for="(char, i) in 'GlowitheFlow'.split('')"
            :key="i"
            class="inline-block wavy-char"
            :style="{ animationDelay: `${i * 0.1}s` }"
          >{{ char }}</span>
        </h1>
        <p
          class="font-sans text-cyan-200/90 tracking-widest transition-all duration-1000 mt-4 md:mt-6"
          :class="[
            uiState === 'landing' ? 'text-lg md:text-2xl opacity-100 translate-y-0 drop-shadow-[0_0_10px_rgba(0,255,255,0.4)]' : 'text-base md:text-lg opacity-90 translate-y-0 drop-shadow-[0_0_5px_rgba(0,255,255,0.3)]'
          ]"
        >
          Free your thoughts, let your links drop.
        </p>
        <div
          class="mt-12 transition-all duration-1000 ease-in-out cursor-pointer pointer-events-auto"
          :class="[
            uiState === 'landing' ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-10 scale-95 pointer-events-none'
          ]"
        >
          <span
            class="text-sm font-sans tracking-[0.25em] text-cyan-100/90 uppercase px-8 py-3.5 rounded-full border border-cyan-500/30 bg-cyan-950/40 backdrop-blur-md shadow-[0_0_20px_rgba(0,255,255,0.15)] hover:border-cyan-300 hover:text-white hover:shadow-[0_0_30px_rgba(0,255,255,0.4)] hover:scale-105 transition-all duration-300 ease-out select-none active:scale-95"
          >
            Click to Think
          </span>
        </div>
      </div>

      <!-- Landing Content (Fades out) -->
      <!-- <div 
        class="absolute top-1/2 left-1/2 -translate-x-1/2 translate-y-[80px] text-center transition-opacity duration-500 w-full"
        :class="uiState === 'landing' ? 'opacity-100' : 'opacity-0'"
      >
        <p class="text-sm md:text-base font-sans tracking-[0.2em] text-white drop-shadow-[0_0_10px_rgba(255,255,255,0.4)]">
          Presented By Hall of the Gods, Inc.
        </p>
      </div> -->

      <!-- Footer (Fades out) -->
      <div
        class="absolute bottom-6 md:bottom-8 left-0 right-0 text-center text-[10px] md:text-xs font-mono text-cyan-200/40 tracking-[0.2em] uppercase flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4 drop-shadow transition-opacity duration-500"
        :class="uiState === 'landing' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
      >
        <span>v{{ version }}</span>
        <span class="hidden sm:inline opacity-50">|</span>
        <span>&copy; 2006-2026 <a
            href="https://hallofthegods.com/"
            target="_blank"
            class="hover:text-white transition-colors pointer-events-auto"
          >Hall of the Gods, Inc.</a> All rights reserved.</span>
        <span class="hidden sm:inline opacity-50">|</span>
        <NuxtLink
          to="/privacy"
          class="hover:text-white transition-colors pointer-events-auto"
        >Privacy Policy</NuxtLink>
        <span class="hidden sm:inline opacity-50">|</span>
        <NuxtLink
          to="/terms"
          class="hover:text-white transition-colors pointer-events-auto"
        >Terms of Service</NuxtLink>
      </div>

      <!-- Flow Interface (Fades in) -->
      <div
        class="absolute inset-0 flex items-center justify-center transition-all duration-1000 ease-in-out"
        :class="[
          uiState === 'flow' ? 'opacity-100 translate-y-0 pointer-events-auto' : 'opacity-0 translate-y-10 pointer-events-none'
        ]"
      >
        <div class="w-full max-w-2xl px-4">
          <FlowComposer
            :knownThoughts="knownThoughtsList"
            :currentLinkPrice="currentLinkPrice"
            :dripletBalance="dripletBalance"
            @submit="handleFlowSubmit"
          />
        </div>
      </div>

      <!-- (LinkPriceSlider moved to dashboard) -->

      <!-- Discord Invite Icon (Floating Top Right) -->
      <a
        href="https://discord.gg/2QDwKKxEqb"
        target="_blank"
        rel="noopener noreferrer"
        class="absolute top-6 right-6 md:top-8 md:right-8 z-50 pointer-events-auto flex items-center justify-center w-12 h-12 rounded-full bg-cyan-950/40 backdrop-blur-md border border-cyan-500/30 shadow-[0_0_15px_rgba(0,255,255,0.15)] hover:bg-cyan-900/60 hover:border-cyan-300 hover:shadow-[0_0_25px_rgba(0,255,255,0.5)] hover:scale-110 transition-all duration-300 group"
      >
        <svg class="w-6 h-6 text-cyan-200/80 group-hover:text-white transition-colors drop-shadow-[0_0_5px_rgba(0,255,255,0.5)]" viewBox="0 0 24 24" fill="currentColor">
          <path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189Z"/>
        </svg>
      </a>

      <!-- Droplet Counter (Bottom) -->
      <div
        class="absolute bottom-8 left-1/2 -translate-x-1/2 pointer-events-auto transition-all duration-1000 ease-in-out z-40"
        :class="[
          uiState === 'flow' || uiState === 'dashboard' ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-10 scale-95 pointer-events-none'
        ]"
      >
        <button
          @click="uiState = uiState === 'dashboard' ? 'flow' : 'dashboard'"
          class="flex items-center justify-center gap-3 bg-cyan-950/60 backdrop-blur-md border border-cyan-500/30 rounded-full px-8 py-3 shadow-[0_0_20px_rgba(0,255,255,0.15)] hover:bg-cyan-900/70 hover:border-cyan-400 hover:shadow-[0_0_30px_rgba(0,255,255,0.4)] hover:scale-105 transition-all duration-300 active:scale-95 cursor-pointer"
        >
          <span class="text-2xl font-bold font-mono text-cyan-100 drop-shadow-[0_0_5px_rgba(0,255,255,0.5)]">
            {{ Math.floor(dripletBalance / 100) }}.<span class="text-lg text-cyan-300/80">{{ (dripletBalance %
              100).toString().padStart(2, '0') }}</span>
          </span>
          <span class="text-2xl drop-shadow-[0_0_5px_rgba(0,255,255,0.5)]">💧</span>
        </button>
      </div>

      <!-- User Value Dashboard — Full Viewport on Water -->
      <div
        class="absolute inset-0 pointer-events-auto transition-all duration-1000 ease-in-out z-40 flex flex-col"
        :class="[
          uiState === 'dashboard' ? 'opacity-100 scale-100' : 'opacity-0 scale-95 pointer-events-none'
        ]"
      >
        <!-- Header — floats at the top -->
        <div class="text-center pt-10 pb-4 px-4">
          <h2
            class="text-5xl md:text-7xl font-display text-transparent bg-clip-text bg-gradient-to-r from-cyan-300 to-blue-500 tracking-wider mb-3 drop-shadow-[0_0_30px_rgba(0,255,255,0.4)]"
          >The Flow Economy</h2>
          <p
            class="text-cyan-100/70 text-base md:text-lg max-w-xl mx-auto leading-relaxed drop-shadow-[0_0_8px_rgba(0,0,0,0.6)]">
            Free your thoughts, let your links drop.
          </p>
        </div>

        <!-- Tab Bar — floating pill on the water -->
        <div class="flex justify-center px-4 pb-4">
          <div
            class="flex bg-[rgba(6,10,24,0.5)] backdrop-blur-xl rounded-full p-1.5 border border-cyan-500/20 shadow-[0_0_30px_rgba(0,255,255,0.1)]"
          >
            <button
              v-for="tab in dashboardTabs"
              :key="tab.id"
              @click="activeDashTab = tab.id"
              class="py-2.5 px-5 md:px-8 rounded-full text-sm font-mono uppercase tracking-widest transition-all duration-300 flex items-center justify-center gap-2"
              :class="[
                activeDashTab === tab.id
                  ? 'bg-gradient-to-r from-cyan-800/80 to-blue-900/60 text-cyan-50 shadow-[0_0_25px_rgba(0,255,255,0.3)] border border-cyan-400/40'
                  : 'text-cyan-400/50 hover:text-cyan-300/80 hover:bg-cyan-900/20 border border-transparent'
              ]"
            >
              <span class="text-lg">{{ tab.icon }}</span>
              <span class="hidden sm:inline">{{ tab.label }}</span>
            </button>
          </div>
        </div>

        <!-- Content Area — glassmorphic panels floating on the water -->
        <div class="flex-1 overflow-y-auto px-4 md:px-8 pb-28 dash-scroll">
          <div class="max-w-4xl mx-auto">

            <!-- Tab: How It Works -->
            <div
              v-if="activeDashTab === 'learn'"
              class="space-y-5"
            >
              <div class="water-panel p-6 md:p-8">
                <p class="text-cyan-100/80 text-base md:text-lg leading-relaxed">
                  Your attention generates value. Earn <strong class="text-cyan-300">Driplets</strong> by clicking links
                  in the stream. Spend <strong class="text-cyan-300">Droplets</strong> to share your own links. <span
                    class="text-cyan-400/50 font-mono text-sm"
                  >(100 Driplets = 1 Droplet)</span>
                </p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="water-panel p-6 flex flex-col items-center text-center gap-3">
                  <span class="text-4xl">🖱️</span>
                  <strong class="text-cyan-100 text-lg">Click Floating Links</strong>
                  <span class="text-sm text-cyan-200/60 leading-relaxed">Click the links flowing through the water to
                    earn simulated ad revenue. The more you explore, the higher your click value multiplier.</span>
                </div>
                <div class="water-panel p-6 flex flex-col items-center text-center gap-3">
                  <span class="text-4xl">💧</span>
                  <strong class="text-cyan-100 text-lg">Earn Driplets</strong>
                  <span class="text-sm text-cyan-200/60 leading-relaxed">Your generated revenue converts directly into
                    Driplets. 1¢ = 1 Driplet. Save up 100 Driplets to form a full Droplet.</span>
                </div>
                <div class="water-panel p-6 flex flex-col items-center text-center gap-3">
                  <span class="text-4xl">🔗</span>
                  <strong class="text-cyan-100 text-lg">Drop Your Links</strong>
                  <span class="text-sm text-cyan-200/60 leading-relaxed">Spend Droplets to share links in the global
                    stream. Thoughts are free — but links cost Droplets. The community sets the price.</span>
                </div>
              </div>
            </div>

            <!-- Tab: Your Stats -->
            <div
              v-if="activeDashTab === 'stats'"
              class="space-y-5"
            >
              <!-- Tier Badge — large, prominent on the water -->
              <div class="water-panel p-8 text-center">
                <span class="text-sm font-mono uppercase tracking-[0.25em] text-cyan-300/60 block mb-3">Network
                  Tier</span>
                <span
                  class="text-4xl md:text-6xl font-bold tracking-widest uppercase text-cyan-50 drop-shadow-[0_0_20px_rgba(0,255,255,0.6)]"
                >
                  {{ networkTier }}
                </span>
              </div>

              <!-- Stat Cards — distributed across the water -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
                <div class="water-panel p-6 md:p-8 text-center flex flex-col justify-center">
                  <span
                    class="text-base md:text-lg font-mono uppercase tracking-widest text-cyan-100 block mb-3 md:mb-4"
                  >Lifetime Value</span>
                  <span
                    class="text-5xl md:text-7xl font-bold font-mono text-cyan-50 drop-shadow-[0_0_15px_rgba(0,255,255,0.4)]"
                  >${{ lifetimeValue.toFixed(2) }}</span>
                  <span class="text-sm md:text-base text-cyan-50/90 block mt-3 md:mt-4">Total revenue from your
                    clicks</span>
                </div>
                <div class="water-panel p-6 md:p-8 text-center flex flex-col justify-center">
                  <span
                    class="text-base md:text-lg font-mono uppercase tracking-widest text-cyan-100 block mb-3 md:mb-4"
                  >Current CPC</span>
                  <span
                    class="text-5xl md:text-7xl font-bold font-mono text-cyan-100 drop-shadow-[0_0_15px_rgba(0,255,255,0.4)]"
                  >${{ cpc.toFixed(2) }}</span>
                  <span class="text-sm md:text-base text-cyan-50/90 block mt-3 md:mt-4">Value per click right now</span>
                </div>
                <div class="water-panel p-6 md:p-8 flex flex-col justify-center">
                  <div class="flex flex-col items-center mb-4 md:mb-5">
                    <span
                      class="text-base md:text-lg font-mono uppercase tracking-widest text-cyan-100 block mb-3 md:mb-4"
                    >Traffic Quality</span>
                    <span
                      class="text-5xl md:text-6xl font-mono font-bold"
                      :class="[trafficQuality < 45 ? 'text-red-400 animate-pulse drop-shadow-[0_0_15px_rgba(239,68,68,0.5)]' : trafficQuality < 80 ? 'text-yellow-400 drop-shadow-[0_0_15px_rgba(250,204,21,0.5)]' : 'text-green-400 drop-shadow-[0_0_15px_rgba(74,222,128,0.5)]']"
                    >
                      {{ trafficQuality }}%
                    </span>
                  </div>
                  <div
                    class="w-full h-4 bg-cyan-950/80 rounded-full overflow-hidden border border-cyan-500/20 mb-4 md:mb-5"
                  >
                    <div
                      class="h-full transition-all duration-300 rounded-full"
                      :class="[trafficQuality < 45 ? 'bg-red-500 shadow-[0_0_15px_rgba(239,68,68,0.8)]' : trafficQuality < 80 ? 'bg-yellow-400 shadow-[0_0_15px_rgba(250,204,21,0.8)]' : 'bg-cyan-400 shadow-[0_0_15px_rgba(34,211,238,0.8)]']"
                      :style="{ width: `${trafficQuality}%` }"
                    ></div>
                  </div>
                  <p class="text-sm md:text-base text-cyan-50/90 leading-relaxed text-center">
                    Deliberate interactions are rewarded. Spam tanks your CPC.
                  </p>
                </div>
              </div>
            </div>

            <!-- Tab: Link Price -->
            <div
              v-if="activeDashTab === 'price'"
              class="space-y-5"
            >
              <div class="water-panel p-6 md:p-8">
                <p class="text-cyan-100/70 text-base leading-relaxed mb-4">
                  The community votes on the cost to drop a link. Use the slider to cast your vote and shift the network
                  price.
                </p>
                <LinkPriceSlider
                  :current-price="currentLinkPrice"
                  @update:vote="handleLinkPriceVote"
                />
              </div>
            </div>

          </div>
        </div>

        <!-- Return Button — floating at bottom -->
        <div class="absolute bottom-20 left-1/2 -translate-x-1/2">
          <button
            @click="uiState = 'flow'"
            class="px-10 py-3.5 rounded-full border border-cyan-400/30 bg-[rgba(6,10,24,0.5)] backdrop-blur-xl text-cyan-100 text-sm hover:text-white hover:bg-[rgba(6,10,24,0.7)] hover:border-cyan-300 hover:shadow-[0_0_30px_rgba(0,255,255,0.4)] transition-all tracking-[0.2em] uppercase font-mono font-bold shadow-[0_0_20px_rgba(0,255,255,0.1)]"
          >
            Return to Stream
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { version } from '../../package.json'
import FlowComposer from '../components/FlowComposer.vue'
import LinkPriceSlider from '../components/LinkPriceSlider.vue'
import { WaterEngine } from '../utils/WaterEngine'

const AUTOMATED_TEXT = "Jump aboard the train of thought and let your flow glow...";

const FONTS = [
  'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald', 'Raleway', 'PT Sans', 
  'Merriweather', 'Nunito', 'Playfair Display', 'Ubuntu', 'Rubik', 'Lora', 'Work Sans', 
  'Fira Sans', 'Quicksand', 'Anton', 'Inconsolata', 'Pacifico', 'Dancing Script', 'Josefin Sans', 
  'Righteous', 'Lobster', 'Caveat', 'Indie Flower', 'Shadows Into Light', 'Amatic SC', 
  'Comfortaa', 'Kalam', 'Satisfy', 'Courgette', 'Great Vibes', 'Permanent Marker', 
  'Sacramento', 'Cookie', 'Tangerine', 'Parisienne', 'Bangers', '"Press Start 2P"', 
  '"Fredoka One"', '"Abril Fatface"', '"Carter One"', '"Patua One"', '"Alfa Slab One"', 
  '"Special Elite"', 'Monoton', 'Creepster', '"Black Ops One"', 'Syncopate', 'Audiowide', 
  'Orbitron', 'Exo', 'Rajdhani', 'VT323', '"Share Tech Mono"', 'Cinzel', '"Bebas Neue"', 
  '"Fjalla One"'
]

// Generate the Google Fonts URL for all selected fonts
const fontFamilies = FONTS.map(f => f.replace(/"/g, '').replace(/ /g, '+')).join('&family=')
const googleFontsUrl = `https://fonts.googleapis.com/css2?family=${fontFamilies}&display=swap`

useHead({
  title: 'GlowitheFlow | Hall of the Gods',
  meta: [
    { name: 'description', content: 'The third emanation of the sacred realm. A canvas of infinite possibilities.' }
  ],
  link: [
    { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
    { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
    { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: 'anonymous' },
    { rel: 'stylesheet', href: googleFontsUrl }
  ]
})

const canvasRef = ref<HTMLCanvasElement | null>(null)
const containerRef = ref<HTMLElement | null>(null)
const waterBgRef = ref<HTMLElement | null>(null)

type UIState = 'landing' | 'transitioning' | 'flow' | 'dashboard'
const uiState = ref<UIState>('landing')

type DashTab = 'learn' | 'stats' | 'price'
const activeDashTab = ref<DashTab>('learn')
const dashboardTabs = [
  { id: 'learn' as DashTab, label: 'How It Works', icon: '💡' },
  { id: 'stats' as DashTab, label: 'Your Stats', icon: '📊' },
  { id: 'price' as DashTab, label: 'Link Price', icon: '💧' },
]

let animationFrameId: number
let waterEngine: WaterEngine | null = null
let w: number = 0
let h: number = 0

interface Particle {
  x: number
  y: number
  vx: number
  vy: number
  baseVx: number
  baseVy: number
  size: number
  baseSize: number
  hue: number
  baseHue: number
}

interface Ripple {
  x: number
  y: number
  radius: number
  maxRadius: number
  speed: number
  strength: number
}

interface ThoughtNode {
  x: number
  y: number
  text: string
  alpha: number
  vy: number
  font: string
  fontSize: number
  passengers: number
  width: number
}

const thoughts: ThoughtNode[] = []
const ripples: Ripple[] = []
const particles: Particle[] = []

const knownThoughtsList = ref<{text: string, count: number}[]>([])

// Link Pricing State
const currentLinkPrice = ref(10);

const handleLinkPriceVote = (vote: number) => {
  const adjustedPrice = Math.round((currentLinkPrice.value * 9 + vote) / 10);
  currentLinkPrice.value = adjustedPrice;
}

const dripletBalance = ref(500); // Start with 5 Droplets (500 Driplets)
const lifetimeValue = ref(0.0);
const trafficQuality = ref(100);
const cpc = ref(0.10);
const clickCount = ref(0);
const mouseTravelDistance = ref(0);
let lastClickTime = 0;

interface FloatingVal {
  x: number;
  y: number;
  text: string;
  alpha: number;
  vy: number;
  hue: number;
}

const floatingValues: FloatingVal[] = [];

const networkTier = computed(() => {
  const currentLTV = lifetimeValue.value;
  const isDewDrop = currentLTV < 5.0;
  if (isDewDrop) return 'Dew Drop';
  const isTrickle = currentLTV < 20.0;
  if (isTrickle) return 'Trickle';
  const isStream = currentLTV < 50.0;
  if (isStream) return 'Stream';
  const isRiver = currentLTV < 150.0;
  if (isRiver) return 'River';
  return 'Ocean';
});

const calculateCPC = () => {
  const baseCPC = 0.10;
  const timeSinceLastClick = Date.now() - lastClickTime;
  const isRapidClick = timeSinceLastClick < 300;
  const isFastClick = timeSinceLastClick < 600;
  
  if (isRapidClick) {
    trafficQuality.value = Math.max(0, trafficQuality.value - 25);
  } else if (isFastClick) {
    trafficQuality.value = Math.max(10, trafficQuality.value - 10);
  } else {
    const recoveryAmount = Math.min(20, (timeSinceLastClick - 600) / 100);
    trafficQuality.value = Math.min(100, trafficQuality.value + recoveryAmount);
  }
  
  const qualityMultiplier = trafficQuality.value / 100;
  const travelBonus = Math.min(0.5, mouseTravelDistance.value / 10000);
  const engagementMultiplier = 1.0 + travelBonus;
  const linkPriceMultiplier = 0.8 + (currentLinkPrice.value / 50);
  
  let depthMultiplier = 1.0;
  const isEarlySession = clickCount.value < 20;
  const isLateSession = clickCount.value > 50;
  if (isEarlySession) {
    depthMultiplier = 0.6 + (clickCount.value / 20) * 0.4;
  } else if (isLateSession) {
    depthMultiplier = Math.max(0.3, 1.0 - (clickCount.value - 50) * 0.01);
  }
  
  const finalCPC = baseCPC * qualityMultiplier * engagementMultiplier * linkPriceMultiplier * depthMultiplier;
  cpc.value = Math.max(0.01, Math.min(5.00, finalCPC));
  mouseTravelDistance.value = 0;
};

const handleInteractionClick = (clientX: number, clientY: number) => {
  clickCount.value++;
  calculateCPC();
  
  const earnedValue = cpc.value;
  const earnedDriplets = Math.round(earnedValue * 100);
  
  dripletBalance.value += earnedDriplets;
  lifetimeValue.value += earnedValue;
  
  localStorage.setItem('glow_driplet_balance', String(dripletBalance.value));
  localStorage.setItem('glow_lifetime_value', String(lifetimeValue.value.toFixed(4)));
  localStorage.setItem('glow_click_count', String(clickCount.value));
  
  lastClickTime = Date.now();
  
  const isLowQuality = trafficQuality.value < 40;
  const textHue = isLowQuality ? 0 : 190;
  const displayedValue = earnedValue.toFixed(2);
  const displayedText = isLowQuality 
    ? `+$${displayedValue} (+${earnedDriplets}💧) [FRAUD DETECTED]` 
    : `+$${displayedValue} (+${earnedDriplets}💧)`;

  floatingValues.push({
    x: clientX,
    y: clientY,
    text: displayedText,
    alpha: 1,
    vy: -1.5,
    hue: textHue
  });
};

const findThoughtByText = (queryText: string) => {
  const normalizedQuery = queryText.toLowerCase();
  const matchesQuery = (thought: { text: string; count: number }) => {
    const isMatch = thought && typeof thought.text === 'string' && thought.text.toLowerCase() === normalizedQuery;
    return isMatch;
  };
  return matchesQuery;
};

const parseSavedThoughts = (saved: string) => {
  try {
    const parsed = JSON.parse(saved);
    const isValidArray = Array.isArray(parsed);
    if (isValidArray) {
      const mappedThoughts = parsed.map((t: any) => {
        const isString = typeof t === 'string';
        if (isString) {
          const stringThought = { text: t, count: 1 };
          return stringThought;
        }
        const hasText = t && typeof t.text === 'string';
        const hasCount = t && typeof t.count === 'number' && !isNaN(t.count);
        const parsedThought = {
          text: hasText ? t.text : '',
          count: hasCount ? t.count : 1
        };
        return parsedThought;
      });
      const filteredThoughts = mappedThoughts.filter((t: any) => {
        const isNonEmpty = t.text.trim().length > 0;
        return isNonEmpty;
      });
      return filteredThoughts;
    }
  } catch (error) {
    console.error('Failed to parse known thoughts:', error);
  }
  return [];
};

const mapToWaterThought = (t: ThoughtNode) => {
  const mapped = { x: t.x, y: t.y, width: t.width, passengers: t.passengers };
  return mapped;
};

const mapToWaterRipple = (r: Ripple) => {
  const mapped = { x: r.x, y: r.y, radius: r.radius, maxRadius: r.maxRadius, strength: r.strength };
  return mapped;
};

const handleFlowSubmit = (content: string) => {
  const query = content.trim();
  const isEmpty = !query;
  if (isEmpty) return;

  const urlRegex = /(https?:\/\/[^\s]+)|(www\.[^\s]+)|([a-zA-Z0-9]+(\.[a-zA-Z0-9]+)+([^\s]?)+)/i;
  const hasLink = urlRegex.test(query);
  const cost = Math.round(currentLinkPrice.value) * 100; // Price in Driplets (1 Droplet = 100 Driplets)
  const hasEnoughDroplets = dripletBalance.value >= cost;
  
  if (hasLink) {
    if (!hasEnoughDroplets) return;
    dripletBalance.value -= cost;
    localStorage.setItem('glow_driplet_balance', String(dripletBalance.value));
  }

  const existingIndex = knownThoughtsList.value.findIndex(findThoughtByText(query));
  let voteCount = 1;
  const hasExisting = existingIndex >= 0;
  if (hasExisting) {
    const existing = knownThoughtsList.value[existingIndex];
    if (existing) {
      const currentCount = typeof existing.count === 'number' && !isNaN(existing.count) ? existing.count : 1;
      existing.count = currentCount + 1;
      voteCount = existing.count;
    }
  } else {
    knownThoughtsList.value.push({ text: query, count: 1 });
  }

  localStorage.setItem('glow_known_thoughts', JSON.stringify(knownThoughtsList.value));

  const isHighVolume = voteCount > 10;
  const scale = isHighVolume
    ? Math.min(2.5, 1 + Math.log10(voteCount) * 0.5)
    : 1 + (voteCount - 1) * 0.3;

  const maxVal = Math.max(1, voteCount);
  const logVal = Math.log10(maxVal);
  const denominator = 1 + logVal * 0.8;
  const rawSpeedScale = 1 / denominator;
  const speedScale = Math.max(0.05, rawSpeedScale);

  const randomFont = FONTS[Math.floor(Math.random() * FONTS.length)] || 'Inter';
  const randomSize = (24 + Math.random() * 24) * scale;
  const randomVy = (-0.5 - Math.random() * 0.5) * speedScale;
  const estimatedWidth = query.length * randomSize * 0.5;

  const safeBuffer = 40;
  const halfW = estimatedWidth / 2;
  const minX = halfW + safeBuffer;
  const maxX = w - halfW - safeBuffer;
  const randomX = minX < maxX ? Math.random() * (maxX - minX) + minX : w / 2;

  thoughts.push({
    x: randomX,
    y: h + 50,
    text: query,
    alpha: 0,
    vy: randomVy,
    font: randomFont,
    fontSize: randomSize,
    passengers: voteCount,
    width: estimatedWidth
  })
}

const dropAutomatedThought = () => {
  const autoIndex = knownThoughtsList.value.findIndex(findThoughtByText(AUTOMATED_TEXT));
  const hasAutoIndex = autoIndex >= 0;
  const foundThought = hasAutoIndex ? knownThoughtsList.value[autoIndex] : null;
  const hasValidCount = foundThought && typeof foundThought.count === 'number' && !isNaN(foundThought.count);
  const voteCount = hasValidCount ? foundThought.count : 1;

  const maxVal = Math.max(1, voteCount);
  const logVal = Math.log10(maxVal);
  const denominator = 1 + logVal * 0.8;
  const rawSpeedScale = 1 / denominator;
  const speedScale = Math.max(0.05, rawSpeedScale);

  const randomScale = 1.0;
  const randomFont = FONTS[Math.floor(Math.random() * FONTS.length)] || 'Inter';
  const randomSize = (24 + Math.random() * 24) * randomScale;
  const randomVy = (-0.5 - Math.random() * 0.5) * speedScale;
  const estimatedWidth = AUTOMATED_TEXT.length * randomSize * 0.5;

  const safeBuffer = 40;
  const halfW = estimatedWidth / 2;
  const minX = halfW + safeBuffer;
  const maxX = w - halfW - safeBuffer;
  const randomX = minX < maxX ? Math.random() * (maxX - minX) + minX : w / 2;

  thoughts.push({
    x: randomX,
    y: h + 50,
    text: AUTOMATED_TEXT,
    alpha: 0,
    vy: randomVy,
    font: randomFont,
    fontSize: randomSize,
    passengers: voteCount,
    width: estimatedWidth
  });
}

const resetToLanding = () => {
  uiState.value = 'landing'
  ripples.push({
    x: w / 2,
    y: h / 2,
    radius: 0,
    maxRadius: w,
    speed: 15,
    strength: 10
  })
}

onMounted(() => {
  const storedBalance = localStorage.getItem('glow_driplet_balance');
  if (storedBalance !== null) {
    dripletBalance.value = parseInt(storedBalance, 10);
  }
  const storedLTV = localStorage.getItem('glow_lifetime_value');
  if (storedLTV !== null) {
    lifetimeValue.value = parseFloat(storedLTV);
  }
  const storedClickCount = localStorage.getItem('glow_click_count');
  if (storedClickCount !== null) {
    clickCount.value = parseInt(storedClickCount, 10);
  }

  const savedThoughts = localStorage.getItem('glow_known_thoughts');
  if (savedThoughts) {
    knownThoughtsList.value = parseSavedThoughts(savedThoughts);
  }

  const rawVisitorCount = localStorage.getItem('glow_visitor_count') || '0';
  const parsedVisitorCount = Number(rawVisitorCount);
  const isInvalidVisitorCount = isNaN(parsedVisitorCount);
  let currentVisitorCount = isInvalidVisitorCount ? 1 : parsedVisitorCount;
  const isVisited = localStorage.getItem('glow_visited') === 'true';
  if (!isVisited) {
    currentVisitorCount += 1;
    localStorage.setItem('glow_visitor_count', String(currentVisitorCount));
    localStorage.setItem('glow_visited', 'true');
  }

  const autoIndex = knownThoughtsList.value.findIndex(findThoughtByText(AUTOMATED_TEXT));
  const hasAutoIndex = autoIndex >= 0;
  if (hasAutoIndex) {
    knownThoughtsList.value[autoIndex].count = currentVisitorCount;
  } else {
    knownThoughtsList.value.push({ text: AUTOMATED_TEXT, count: currentVisitorCount });
  }

  localStorage.setItem('glow_known_thoughts', JSON.stringify(knownThoughtsList.value));

  const hasWaterBgRef = !!waterBgRef.value;
  if (hasWaterBgRef) {
    waterEngine = new WaterEngine(waterBgRef.value!)
    waterEngine.init()
  }

  const canvas = canvasRef.value
  const container = containerRef.value
  const hasCanvasAndContainer = canvas && container;
  if (!hasCanvasAndContainer) return
  
  const ctx = canvas.getContext('2d')
  const hasCtx = !!ctx;
  if (!hasCtx) return
  
  const resize = () => {
    w = canvas.width = window.innerWidth
    h = canvas.height = window.innerHeight
  }
  
  window.addEventListener('resize', resize)
  resize()
  
  let mouseX = -1000;
  let mouseY = -1000;
  let lastMouseX = -1;
  let lastMouseY = -1;

  const handleMouseMove = (e: MouseEvent) => {
    const hasValidCoords = e && typeof e.clientX === 'number' && typeof e.clientY === 'number';
    if (hasValidCoords) {
      mouseX = e.clientX;
      mouseY = e.clientY;
      const hasPreviousCoords = lastMouseX !== -1 && lastMouseY !== -1;
      if (hasPreviousCoords) {
        const dx = e.clientX - lastMouseX;
        const dy = e.clientY - lastMouseY;
        mouseTravelDistance.value += Math.sqrt(dx * dx + dy * dy);
      }
      lastMouseX = e.clientX;
      lastMouseY = e.clientY;
    }
  };

  const handleMouseLeave = () => {
    mouseX = -1000;
    mouseY = -1000;
    lastMouseX = -1;
    lastMouseY = -1;
  };

  const handleClick = (e: MouseEvent) => {
    const isLanding = uiState.value === 'landing';
    const isFlow = uiState.value === 'flow' || uiState.value === 'dashboard';
    
    const clientX = e && typeof e.clientX === 'number' && !isNaN(e.clientX) ? e.clientX : w / 2;
    const clientY = e && typeof e.clientY === 'number' && !isNaN(e.clientY) ? e.clientY : h / 2;

    if (e && e.target) {
      const target = e.target as HTMLElement;
      const isInteractiveTarget = target.closest('button, input, textarea, a, .price-slider-card, .flow-composer-card, .water-panel');
      if (isInteractiveTarget) return;
    }

    if (isLanding) {
      const maxDim = Math.max(w, h);
      const targetMaxRadius = maxDim * 1.5;
      
      ripples.push({
        x: clientX,
        y: clientY,
        radius: 0,
        maxRadius: targetMaxRadius,
        speed: 20,
        strength: 50
      });
      
      uiState.value = 'transitioning';
      
      const transitionToFlow = () => {
        uiState.value = 'flow';
        dropAutomatedThought();
      };
      
      setTimeout(transitionToFlow, 800);
    } else if (isFlow) {
      let clickedThought = null;
      for (const t of thoughts) {
        const halfW = t.width / 2;
        const x1 = t.x - halfW;
        const x2 = t.x + halfW;
        const y1 = t.y - t.fontSize;
        const y2 = t.y + t.fontSize * 0.2;
        if (clientX >= x1 && clientX <= x2 && clientY >= y1 && clientY <= y2) {
          clickedThought = t;
          break;
        }
      }

      if (clickedThought) {
        handleInteractionClick(clientX, clientY);
        
        const urlRegex = /^(https?:\/\/[^\s]+)$/i;
        if (urlRegex.test(clickedThought.text)) {
           window.open(clickedThought.text, '_blank');
        }

        ripples.push({
          x: clientX,
          y: clientY,
          radius: 0,
          maxRadius: 300,
          speed: 15,
          strength: 20
        });
      } else {
        ripples.push({
          x: clientX,
          y: clientY,
          radius: 0,
          maxRadius: 200,
          speed: 5,
          strength: 10
        });
      }
    }
  };

  const handleTouchStart = (e: TouchEvent) => {
    const hasTouch = e && e.touches && e.touches[0];
    if (hasTouch) {
      handleClick(e.touches[0] as unknown as MouseEvent);
    }
  };

  window.addEventListener('mousemove', handleMouseMove)
  document.addEventListener('mouseleave', handleMouseLeave)
  window.addEventListener('click', handleClick)
  window.addEventListener('touchstart', handleTouchStart, { passive: true })

  const widthThreshold = window.innerWidth < 768;
  const maxParticles = widthThreshold ? 35 : 80
  
  for (let i = 0; i < maxParticles; i++) {
    const vx = (Math.random() - 0.5) * 1.5
    const vy = (Math.random() - 0.5) * 1.5
    const size = Math.random() * 2 + 0.5
    const hue = Math.random() * 40 + 170 
    particles.push({
      x: Math.random() * w,
      y: Math.random() * h,
      vx,
      vy,
      baseVx: vx,
      baseVy: vy,
      size,
      baseSize: size,
      hue,
      baseHue: hue
    })
  }
  
  const draw = () => {
    ctx.clearRect(0, 0, w, h)

    const isDashboard = uiState.value === 'dashboard';
    if (waterEngine) {
      waterEngine.setDashboardState(isDashboard);
    }
    
    for (let i = ripples.length - 1; i >= 0; i--) {
      const r = ripples[i]!
      r.radius += r.speed
      
      const alpha = 1 - (r.radius / r.maxRadius)
      const isRippleExpired = alpha <= 0;
      if (isRippleExpired) {
        ripples.splice(i, 1)
        continue
      }
      
      ctx.beginPath()
      ctx.arc(r.x, r.y, r.radius, 0, Math.PI * 2)
      ctx.strokeStyle = `hsla(190, 100%, 60%, ${alpha * 0.3})`
      ctx.lineWidth = 1.5 + (r.strength / 10)
      ctx.stroke()
    }
    
    for (let i = 0; i < particles.length; i++) {
      const p = particles[i]!
      
      p.vx += (p.baseVx - p.vx) * 0.05
      p.vy += (p.baseVy - p.vy) * 0.05
      p.size += (p.baseSize - p.size) * 0.1
      p.hue += (p.baseHue - p.hue) * 0.1
      
      let pAlpha = 0.8
      
      const mdx = p.x - mouseX
      const mdy = p.y - mouseY
      const mdist = Math.sqrt(mdx * mdx + mdy * mdy)
      const isCloseToMouse = mdist < 150;
      
      if (isCloseToMouse) {
        const force = (150 - mdist) / 150
        const angle = Math.atan2(mdy, mdx)
        p.vx += Math.cos(angle) * force * 0.5
        p.vy += Math.sin(angle) * force * 0.5
        p.size = Math.max(p.size, p.baseSize + force * 1.5)
        p.hue = Math.min(220, p.hue + force * 20)
        pAlpha = 1
      }

      const isFlowState = uiState.value === 'flow';
      if (isFlowState) {
        const cx = w / 2;
        const cy = h / 2;
        const cdx = p.x - cx;
        const cdy = (p.y - cy) * 3; 
        const cdistSq = cdx * cdx + cdy * cdy;
        const isNearCenter = cdistSq < 160000;
        
        if (isNearCenter) {
          const cdist = Math.sqrt(cdistSq);
          const force = (400 - cdist) / 400;
          const angle = Math.atan2(cdy / 3, cdx); 
          
          p.vx += Math.cos(angle) * force * 2.0;
          p.vy += Math.sin(angle) * force * 2.0;
          p.hue = Math.min(220, p.hue + force * 15);
        }
      } else if (isDashboard) {
        const cx = w / 2;
        const cy = h / 2;
        const cdx = p.x - cx;
        const cdy = p.y - cy; 
        const cdistSq = cdx * cdx + cdy * cdy;
        const isNearDashboard = cdistSq < 120000; // ~346px radius
        
        if (isNearDashboard) {
          const cdist = Math.sqrt(cdistSq);
          const force = (350 - cdist) / 350;
          const angle = Math.atan2(cdy, cdx); 
          
          p.vx += Math.cos(angle) * force * 4.0;
          p.vy += Math.sin(angle) * force * 4.0;
          p.hue = Math.min(260, p.hue + force * 40); // Shift towards purple/magenta
        }
      }

      for (const t of thoughts) {
        const halfW = t.width / 2;
        const x1 = t.x - halfW;
        const x2 = t.x + halfW;
        const targetY = t.y - t.fontSize / 3;
        
        const closestX = Math.max(x1, Math.min(p.x, x2));
        const tdx = p.x - closestX;
        const tdy = (p.y - targetY) * 2; 
        const tDistSq = tdx * tdx + tdy * tdy;
        const isNearThought = tDistSq < 40000;
        
        if (isNearThought) {
          const tDist = Math.sqrt(tDistSq);
          const force = (200 - tDist) / 200;
          const angle = Math.atan2(tdy / 2, tdx);
          
          p.vx += Math.cos(angle) * force * 1.5;
          p.vy += Math.sin(angle) * force * 1.5;
          p.hue = Math.min(250, p.hue + force * 30);
          pAlpha = Math.min(1, pAlpha + force);
        }
      }
      
      for (const r of ripples) {
        const dx = p.x - r.x
        const dy = p.y - r.y
        const dist = Math.sqrt(dx * dx + dy * dy)
        const distToRing = Math.abs(dist - r.radius)
        const isNearRippleRing = distToRing < 40;
        
        if (isNearRippleRing) {
          const force = r.strength * (1 - r.radius / r.maxRadius) * (1 - distToRing / 40)
          const angle = Math.atan2(dy, dx)
          
          p.vx += Math.cos(angle) * force * 0.2
          p.vy += Math.sin(angle) * force * 0.2
          
          p.size = Math.max(p.size, p.baseSize + force * 0.8)
          p.hue = 220
          pAlpha = 1
        }
      }
      
      p.x += p.vx
      p.y += p.vy
      
      const isXOutOfBounds = p.x < 0 || p.x > w;
      if (isXOutOfBounds) { 
        p.vx *= -1; 
        p.baseVx *= -1; 
        p.x = Math.max(0, Math.min(w, p.x)); 
      }
      const isYOutOfBounds = p.y < 0 || p.y > h;
      if (isYOutOfBounds) { 
        p.vy *= -1; 
        p.baseVy *= -1; 
        p.y = Math.max(0, Math.min(h, p.y)); 
      }
      
      ctx.beginPath()
      ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2)
      ctx.fillStyle = `hsla(${p.hue}, 100%, 60%, ${pAlpha})`
      ctx.fill()
      
      const hasGlow = p.size > p.baseSize;
      if (hasGlow) {
        ctx.beginPath()
        ctx.arc(p.x, p.y, p.size * 2.5, 0, Math.PI * 2)
        ctx.fillStyle = `hsla(${p.hue}, 100%, 60%, ${pAlpha * 0.2})`
        ctx.fill()
      }
      
      for (let j = i + 1; j < particles.length; j++) {
        const p2 = particles[j]!
        const dx = p.x - p2.x
        const dy = p.y - p2.y
        const distSq = dx * dx + dy * dy
        const isConnected = distSq < 14400;
        
        if (isConnected) {
          const dist = Math.sqrt(distSq)
          ctx.beginPath()
          ctx.moveTo(p.x, p.y)
          ctx.lineTo(p2.x, p2.y)
          ctx.strokeStyle = `hsla(${p.hue}, 100%, 60%, ${0.6 - dist / 200})`
          ctx.lineWidth = 0.5
          ctx.stroke()
        }
      }
    }

    ctx.textAlign = 'center'
    const animTime = Date.now() * 0.003
    
    for (let i = thoughts.length - 1; i >= 0; i--) {
      const t = thoughts[i]
      t.y += t.vy
      t.alpha = Math.min(1, t.alpha + 0.015)
      
      ctx.font = `300 ${t.fontSize}px ${t.font}, sans-serif`
      const textWidth = ctx.measureText(t.text).width
      t.width = textWidth
      
      const safeBuffer = 40;
      const halfW = textWidth / 2;
      const minX = halfW + safeBuffer;
      const maxX = w - halfW - safeBuffer;
      
      if (minX > maxX) {
        t.x = w / 2;
      } else {
        t.x = Math.max(minX, Math.min(maxX, t.x));
      }
      
      const shift = Math.sin(animTime * 0.3 + i) * (textWidth * 0.5)
      const grad = ctx.createLinearGradient(
        t.x - textWidth / 2 + shift,
        t.y - t.fontSize * 0.3,
        t.x + textWidth / 2 + shift,
        t.y + t.fontSize * 0.3
      )
      
      grad.addColorStop(0, `rgba(255, 255, 255, ${t.alpha})`)
      grad.addColorStop(0.5, `rgba(0, 255, 255, ${t.alpha})`)
      grad.addColorStop(1, `rgba(37, 99, 235, ${t.alpha})`)
      
      ctx.fillStyle = grad
      
      const hasPassengersGlow = t.passengers > 1;
      if (hasPassengersGlow) {
        ctx.shadowColor = 'rgba(0, 255, 255, 0.8)'
        ctx.shadowBlur = Math.min(50, t.passengers * 10)
      }

      let currentX = t.x - textWidth / 2
      for (let j = 0; j < t.text.length; j++) {
        const char = t.text[j]
        const charWidth = ctx.measureText(char).width
        const waveOffset = Math.sin(animTime * 1.5 + j * 0.25) * 4
        ctx.fillText(char, currentX + charWidth / 2, t.y + waveOffset)
        currentX += charWidth
      }
      
      if (hasPassengersGlow) {
        ctx.font = `600 ${Math.max(10, t.fontSize * 0.4)}px ${t.font}, sans-serif`
        ctx.textAlign = 'left'
        ctx.fillStyle = `rgba(255, 255, 255, ${t.alpha * 0.9})`
        const lastCharWaveOffset = Math.sin(animTime * 1.5 + (t.text.length - 1) * 0.25) * 4
        ctx.fillText(`${t.passengers}`, t.x + (textWidth / 2) + 4, t.y - (t.fontSize * 0.4) + lastCharWaveOffset)
        ctx.textAlign = 'center'
      }
      
      ctx.shadowBlur = 0
      
      const isThoughtOffScreen = t.y < -50;
      if (isThoughtOffScreen) {
        thoughts.splice(i, 1)
      }
    }
    
    for (let i = floatingValues.length - 1; i >= 0; i--) {
      const f = floatingValues[i]!;
      f.y += f.vy;
      f.alpha -= 0.015;
      
      const isExpired = f.alpha <= 0;
      if (isExpired) {
        floatingValues.splice(i, 1);
        continue;
      }
      
      ctx.font = 'bold 16px "Fira Code", monospace';
      ctx.textAlign = 'center';
      ctx.shadowColor = `hsla(${f.hue}, 100%, 60%, ${f.alpha})`;
      ctx.shadowBlur = 10;
      ctx.fillStyle = `hsla(${f.hue}, 100%, 60%, ${f.alpha})`;
      ctx.fillText(f.text, f.x, f.y);
    }
    ctx.shadowBlur = 0;

    const hasWaterEngine = !!waterEngine;
    if (hasWaterEngine) {
      waterEngine.updateThoughts(thoughts.map(mapToWaterThought));
      waterEngine.updateRipples(ripples.map(mapToWaterRipple));
    }
    
    animationFrameId = requestAnimationFrame(draw);
  }
  
  draw()
  
  onUnmounted(() => {
    const hasWaterEngineInstance = !!waterEngine;
    if (hasWaterEngineInstance) {
      waterEngine.dispose()
    }
    window.removeEventListener('resize', resize)
    window.removeEventListener('mousemove', handleMouseMove)
    document.removeEventListener('mouseleave', handleMouseLeave)
    window.removeEventListener('click', handleClick)
    window.removeEventListener('touchstart', handleTouchStart)
    cancelAnimationFrame(animationFrameId)
  })
})
</script>

<style scoped>
  .water-panel {
    background: rgba(6, 10, 24, 0.45);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(0, 255, 255, 0.15);
    border-radius: 1.25rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 255, 255, 0.08);
    transition: all 0.3s ease;
  }

  .water-panel:hover {
    border-color: rgba(0, 255, 255, 0.3);
    box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4), 0 0 30px rgba(0, 255, 255, 0.15);
  }

  .wavy-char {
    animation: waveText 4s ease-in-out infinite;
    display: inline-block;
    position: relative;
  }

  @keyframes waveText {

    0%,
    100% {
      top: 0px;
    }

    33% {
      top: -6px;
    }

    66% {
      top: 4px;
    }
  }

  .dash-scroll::-webkit-scrollbar {
    width: 6px;
  }

  .dash-scroll::-webkit-scrollbar-track {
    background: transparent;
    margin: 8px 0;
  }

  .dash-scroll::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, rgba(34, 211, 238, 0.5), rgba(59, 130, 246, 0.3));
    border-radius: 999px;
    border: 1px solid rgba(34, 211, 238, 0.15);
    box-shadow: 0 0 8px rgba(34, 211, 238, 0.3);
  }

  .dash-scroll::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, rgba(34, 211, 238, 0.8), rgba(59, 130, 246, 0.5));
    box-shadow: 0 0 14px rgba(34, 211, 238, 0.6);
  }

  .dash-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(34, 211, 238, 0.4) transparent;
  }
</style>
