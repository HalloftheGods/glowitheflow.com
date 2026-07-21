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
        @click="handleLandingClick"
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

      <!-- Footer (Fades out) -->
      <div
        class="absolute bottom-6 md:bottom-8 left-0 right-0 text-center text-[10px] md:text-xs font-mono text-cyan-200/40 tracking-[0.2em] uppercase flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4 drop-shadow transition-opacity duration-500"
        :class="uiState === 'landing' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
      >
        <span>v{{ version }}</span>
        <span class="hidden sm:inline opacity-50">|</span>
        <span>&copy; 2006-2026 <a href="https://hallofthegods.com/" target="_blank" class="hover:text-white transition-colors pointer-events-auto">Hall of the Gods, Inc.</a> All rights reserved.</span>
        <span class="hidden sm:inline opacity-50">|</span>
        <NuxtLink to="/about" class="hover:text-white transition-colors pointer-events-auto font-bold text-cyan-300">About</NuxtLink>
        <span class="hidden sm:inline opacity-50">|</span>
        <NuxtLink to="/privacy" class="hover:text-white transition-colors pointer-events-auto">Privacy Policy</NuxtLink>
        <span class="hidden sm:inline opacity-50">|</span>
        <NuxtLink to="/terms" class="hover:text-white transition-colors pointer-events-auto">Terms of Service</NuxtLink>
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
            @submit="handleFlowSubmit"
          />
        </div>
      </div>

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
          @click="toggleDashboard"
          class="flex items-center justify-center gap-3 bg-cyan-950/60 backdrop-blur-md border border-cyan-500/30 rounded-full px-8 py-3 shadow-[0_0_20px_rgba(0,255,255,0.15)] hover:bg-cyan-900/70 hover:border-cyan-400 hover:shadow-[0_0_30px_rgba(0,255,255,0.4)] hover:scale-105 transition-all duration-300 active:scale-95 cursor-pointer"
        >
          <span class="text-2xl font-bold font-mono text-cyan-100 drop-shadow-[0_0_5px_rgba(0,255,255,0.5)]">
            {{ userStore.dropletBalance }}.<span class="text-lg text-cyan-300/80">{{ userStore.dripletBalance.toString().padStart(2, '0') }}</span>
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
              @click="setActiveDashTab(tab.id)"
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
            <LearnTab v-if="activeDashTab === 'learn'" />
            <StatsTab
              v-else-if="activeDashTab === 'stats'"
              :network-tier="networkTier"
              :lifetime-value="userStore.lifetimeValue"
              :cpc="cpc"
              :traffic-quality="trafficQuality"
            />
            <div
              v-else-if="activeDashTab === 'price'"
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
            <DropletShop v-else-if="activeDashTab === 'shop'" />
          </div>
        </div>

        <!-- Return Button — floating at bottom -->
        <div class="absolute bottom-20 left-1/2 -translate-x-1/2">
          <button
            @click="returnToFlow"
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
import LearnTab from '../components/LearnTab.vue'
import StatsTab from '../components/StatsTab.vue'
import DropletShop from '../components/DropletShop.vue'
import { CanvasEngine, AUTOMATED_TEXT, FONTS } from '../utils/CanvasEngine'
import { useUserStore } from '../stores/user'
import { useGlowApi } from '../composables/useGlowApi'
import { getNetworkTier } from '../utils/tiers'
import { calculateAdjustedPrice } from '../utils/price'

// Setup SEO headers
const formatFontFamily = (f: string) => f.replace(/"/g, '').replace(/ /g, '+')
const fontFamilies = FONTS.map(formatFontFamily).join('&family=')
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

type DashTab = 'learn' | 'stats' | 'price' | 'shop'
const activeDashTab = ref<DashTab>('learn')
const dashboardTabs = [
  { id: 'learn' as DashTab, label: 'How It Works', icon: '💡' },
  { id: 'stats' as DashTab, label: 'Your Stats', icon: '📊' },
  { id: 'price' as DashTab, label: 'Link Price', icon: '💧' },
  { id: 'shop' as DashTab, label: 'Droplet Shop', icon: '🛒' },
]

const userStore = useUserStore()
const knownThoughtsList = ref<{ text: string; count: number }[]>([])
const currentLinkPrice = ref(10)
const trafficQuality = ref(100)
const cpc = ref(0.10)
const clickCount = ref(0)
const mouseTravelDistance = ref(0)

let canvasEngine: CanvasEngine | null = null

const networkTier = computed(() => {
  return getNetworkTier(userStore.lifetimeValue)
})

const handleLandingClick = () => {
  const isFlow = uiState.value === 'flow'
  if (isFlow) {
    resetToLanding()
  }
}

const toggleDashboard = () => {
  uiState.value = uiState.value === 'dashboard' ? 'flow' : 'dashboard'
}

const setActiveDashTab = (tabId: DashTab) => {
  activeDashTab.value = tabId
}

const returnToFlow = () => {
  uiState.value = 'flow'
}

const handleLinkPriceVote = (vote: number) => {
  currentLinkPrice.value = calculateAdjustedPrice(currentLinkPrice.value, vote)
}

const handleSpawnThought = (post: any) => {
  canvasEngine?.spawnThought(post)
}

const fetchFeed = async () => {
  try {
    const feedPosts = await useGlowApi('/glow/v1/feed')
    if (Array.isArray(feedPosts)) {
      canvasEngine?.clearThoughts()
      feedPosts.forEach(handleSpawnThought)
    }
  } catch (error) {
    console.error('Failed to fetch feed:', error)
  }
}

const parseSavedThoughts = (saved: string) => {
  try {
    const parsed = JSON.parse(saved)
    const isValidArray = Array.isArray(parsed)
    if (isValidArray) {
      const mapRawThought = (t: any) => {
        const isString = typeof t === 'string'
        if (isString) {
          return { text: t, count: 1 }
        }
        const hasText = t && typeof t.text === 'string'
        const hasCount = t && typeof t.count === 'number' && !isNaN(t.count)
        return {
          text: hasText ? t.text : '',
          count: hasCount ? t.count : 1
        }
      }

      const filterNonEmptyThought = (t: any) => {
        return t.text.trim().length > 0
      }

      return parsed.map(mapRawThought).filter(filterNonEmptyThought)
    }
  } catch (error) {
    console.error('Failed to parse known thoughts:', error)
  }
  return []
}

const handleFlowSubmit = (payload: any) => {
  canvasEngine?.handleFlowSubmit(payload)
}

const resetToLanding = () => {
  canvasEngine?.resetToLanding()
}

onMounted(async () => {
  const handleFetchUserError = (err: any) => console.error('Failed to fetch user:', err)
  userStore.fetchUser().catch(handleFetchUserError)

  const isLocalStorageAvailable = typeof window !== 'undefined' && window.localStorage
  if (isLocalStorageAvailable) {
    const savedThoughts = window.localStorage.getItem('glow_known_thoughts')
    if (savedThoughts) {
      knownThoughtsList.value = parseSavedThoughts(savedThoughts)
    }

    const rawVisitorCount = window.localStorage.getItem('glow_visitor_count') || '0'
    const parsedVisitorCount = Number(rawVisitorCount)
    const isInvalidVisitorCount = isNaN(parsedVisitorCount)
    let currentVisitorCount = isInvalidVisitorCount ? 1 : parsedVisitorCount
    const isVisited = window.localStorage.getItem('glow_visited') === 'true'
    if (!isVisited) {
      currentVisitorCount += 1
      window.localStorage.setItem('glow_visitor_count', String(currentVisitorCount))
      window.localStorage.setItem('glow_visited', 'true')
    }

    const normalizedAutoText = AUTOMATED_TEXT.toLowerCase()
    const findMatchingAutoThought = (thought: any) => {
      const isMatch = thought && typeof thought.text === 'string' && thought.text.toLowerCase() === normalizedAutoText
      return isMatch
    }
    const autoIndex = knownThoughtsList.value.findIndex(findMatchingAutoThought)

    const hasAutoIndex = autoIndex >= 0
    if (hasAutoIndex) {
      const existingAuto = knownThoughtsList.value[autoIndex]
      if (existingAuto) {
        existingAuto.count = currentVisitorCount
      }
    } else {
      knownThoughtsList.value.push({ text: AUTOMATED_TEXT, count: currentVisitorCount })
    }

    window.localStorage.setItem('glow_known_thoughts', JSON.stringify(knownThoughtsList.value))
  }

  const canvas = canvasRef.value
  const container = containerRef.value
  const waterBg = waterBgRef.value

  if (canvas && container && waterBg) {
    canvasEngine = new CanvasEngine({
      canvas,
      container,
      waterBg,
      userStore,
      currentLinkPrice,
      knownThoughtsList,
      uiState,
      trafficQuality,
      cpc,
      clickCount,
      mouseTravelDistance
    })
    canvasEngine.init()
  }

  await fetchFeed()
})

onUnmounted(() => {
  if (canvasEngine) {
    canvasEngine.destroy()
    canvasEngine = null
  }
})
</script>


