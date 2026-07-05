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
            @submit="handleFlowSubmit"
          />
        </div>
      </div>

      <!-- Link Price Slider -->
      <div
        class="absolute bottom-6 right-6 transition-all duration-1000 ease-in-out z-50"
        :class="[
          uiState === 'flow' ? 'opacity-100 translate-y-0' : 'opacity-0 pointer-events-none translate-y-10'
        ]"
      >
        <LinkPriceSlider
          :current-price="currentLinkPrice"
          @update:vote="handleLinkPriceVote"
        />
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { version } from '../../package.json'
import FlowComposer from '../components/FlowComposer.vue'
import LinkPriceSlider from '../components/LinkPriceSlider.vue'
import { WaterEngine } from '../utils/WaterEngine'

const AUTOMATED_TEXT = "Jump aboard the train of thought and let your thoughts glow...";

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

type UIState = 'landing' | 'transitioning' | 'flow'
const uiState = ref<UIState>('landing')

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
}

const thoughts: ThoughtNode[] = []
const ripples: Ripple[] = []
const particles: Particle[] = []

const knownThoughtsList = ref<{text: string, count: number}[]>([])

// Link Pricing State
const currentLinkPrice = ref(10);

const handleLinkPriceVote = (vote: number) => {
  // Simulate community average adjusting slightly towards the user's vote
  currentLinkPrice.value = Math.round((currentLinkPrice.value * 9 + vote) / 10);
}

const handleFlowSubmit = (content: string) => {
  const query = content.trim();
  if (!query) return;

  const existingIndex = knownThoughtsList.value.findIndex(t => t.text.toLowerCase() === query.toLowerCase());
  let voteCount = 1;
  if (existingIndex >= 0) {
    knownThoughtsList.value[existingIndex].count += 1;
    voteCount = knownThoughtsList.value[existingIndex].count;
  } else {
    knownThoughtsList.value.push({ text: query, count: 1 });
  }

  localStorage.setItem('glow_known_thoughts', JSON.stringify(knownThoughtsList.value));

  const isHighVolume = voteCount > 10;
  const scale = isHighVolume
    ? Math.min(2.5, 1 + Math.log10(voteCount) * 0.5)
    : 1 + (voteCount - 1) * 0.3;

  const speedScale = Math.max(0.05, 1 / (1 + Math.log10(Math.max(1, voteCount)) * 0.8));

  thoughts.push({
    x: Math.random() * (w - 400) + 200,
    y: h + 50,
    text: query,
    alpha: 0,
    vy: (-0.5 - Math.random() * 0.5) * speedScale,
    font: FONTS[Math.floor(Math.random() * FONTS.length)] || 'Inter',
    fontSize: (24 + Math.random() * 24) * scale,
    passengers: voteCount
  })
}

const dropAutomatedThought = () => {
  const autoIndex = knownThoughtsList.value.findIndex(t => t.text.toLowerCase() === AUTOMATED_TEXT.toLowerCase());
  const voteCount = autoIndex >= 0 ? knownThoughtsList.value[autoIndex].count : 0;

  const scale = 1.0;
  const speedScale = Math.max(0.05, 1 / (1 + Math.log10(Math.max(1, voteCount)) * 0.8));

  thoughts.push({
    x: Math.random() * (w - 400) + 200,
    y: h + 50,
    text: AUTOMATED_TEXT,
    alpha: 0,
    vy: (-0.5 - Math.random() * 0.5) * speedScale,
    font: FONTS[Math.floor(Math.random() * FONTS.length)] || 'Inter',
    fontSize: (24 + Math.random() * 24) * scale,
    passengers: voteCount
  });
}

const resetToLanding = () => {
  uiState.value = 'landing'
  // Trigger a subtle center ripple
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
  const savedThoughts = localStorage.getItem('glow_known_thoughts');
  if (savedThoughts) {
    try {
      knownThoughtsList.value = JSON.parse(savedThoughts);
    } catch (e) {
      console.error('Failed to parse known thoughts:', e);
    }
  }

  let currentVisitorCount = Number(localStorage.getItem('glow_visitor_count') || '0');
  const hasVisited = localStorage.getItem('glow_visited') === 'true';
  if (!hasVisited) {
    currentVisitorCount += 1;
    localStorage.setItem('glow_visitor_count', String(currentVisitorCount));
    localStorage.setItem('glow_visited', 'true');
  }

  const autoIndex = knownThoughtsList.value.findIndex(t => t.text.toLowerCase() === AUTOMATED_TEXT.toLowerCase());
  if (autoIndex >= 0) {
    knownThoughtsList.value[autoIndex].count = currentVisitorCount;
  } else {
    knownThoughtsList.value.push({ text: AUTOMATED_TEXT, count: currentVisitorCount });
  }

  localStorage.setItem('glow_known_thoughts', JSON.stringify(knownThoughtsList.value));

  // Initialize 3D Water Background
  if (waterBgRef.value) {
    waterEngine = new WaterEngine(waterBgRef.value)
    waterEngine.init()
  }

  const canvas = canvasRef.value
  const container = containerRef.value
  if (!canvas || !container) return
  
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  
  const resize = () => {
    w = canvas.width = window.innerWidth
    h = canvas.height = window.innerHeight
  }
  
  window.addEventListener('resize', resize)
  resize()
  
  let mouseX = -1000
  let mouseY = -1000

  const handleMouseMove = (e: MouseEvent) => {
    mouseX = e.clientX
    mouseY = e.clientY
  }

  const handleMouseLeave = () => {
    mouseX = -1000
    mouseY = -1000
  }

  const handleClick = (e: MouseEvent) => {
    if (uiState.value === 'landing') {
      // Trigger massive ripple
      ripples.push({
        x: e.clientX,
        y: e.clientY,
        radius: 0,
        maxRadius: Math.max(w, h) * 1.5,
        speed: 20,
        strength: 50 // Stronger ripple to clear particles
      })
      
      uiState.value = 'transitioning'
      
      setTimeout(() => {
        uiState.value = 'flow'
        dropAutomatedThought()
      }, 800)
    } else if (uiState.value === 'flow') {
      // Subtle ripples in flow mode
      ripples.push({
        x: e.clientX,
        y: e.clientY,
        radius: 0,
        maxRadius: 200,
        speed: 5,
        strength: 10
      })
    }
  }

  window.addEventListener('mousemove', handleMouseMove)
  document.addEventListener('mouseleave', handleMouseLeave)
  window.addEventListener('click', handleClick)
  window.addEventListener('touchstart', (e: TouchEvent) => {
    if (e.touches[0]) {
      handleClick(e.touches[0] as unknown as MouseEvent)
    }
  }, { passive: true })

  const maxParticles = window.innerWidth < 768 ? 35 : 80
  
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
    // Clear canvas entirely to reveal 3D water underneath
    ctx.clearRect(0, 0, w, h)
    
    // Draw ripples
    for (let i = ripples.length - 1; i >= 0; i--) {
      const r = ripples[i]!
      r.radius += r.speed
      
      const alpha = 1 - (r.radius / r.maxRadius)
      if (alpha <= 0) {
        ripples.splice(i, 1)
        continue
      }
      
      ctx.beginPath()
      ctx.arc(r.x, r.y, r.radius, 0, Math.PI * 2)
      ctx.strokeStyle = `hsla(190, 100%, 60%, ${alpha * 0.3})`
      ctx.lineWidth = 1.5 + (r.strength / 10)
      ctx.stroke()
    }
    
    // Draw particles
    for (let i = 0; i < particles.length; i++) {
      const p = particles[i]!
      
      // Default natural motion
      p.vx += (p.baseVx - p.vx) * 0.05
      p.vy += (p.baseVy - p.vy) * 0.05
      p.size += (p.baseSize - p.size) * 0.1
      p.hue += (p.baseHue - p.hue) * 0.1
      
      let pAlpha = 0.8
      
      // Mouse interaction
      const mdx = p.x - mouseX
      const mdy = p.y - mouseY
      const mdist = Math.sqrt(mdx * mdx + mdy * mdy)
      
      if (mdist < 150) {
        const force = (150 - mdist) / 150
        const angle = Math.atan2(mdy, mdx)
        p.vx += Math.cos(angle) * force * 0.5
        p.vy += Math.sin(angle) * force * 0.5
        p.size = Math.max(p.size, p.baseSize + force * 1.5)
        p.hue = Math.min(220, p.hue + force * 20)
        pAlpha = 1
      }

      // Center repulsion around the Flow Composer input
      if (uiState.value === 'flow') {
        const cx = w / 2;
        const cy = h / 2;
        const cdx = p.x - cx;
        const cdy = (p.y - cy) * 3; // Compress Y heavily to make a wide horizontal oval bounding box
        const cdistSq = cdx * cdx + cdy * cdy;
        
        if (cdistSq < 160000) { // 400 radius squared
          const cdist = Math.sqrt(cdistSq);
          const force = (400 - cdist) / 400;
          const angle = Math.atan2(cdy / 3, cdx); // Decompress angle for vector
          
          p.vx += Math.cos(angle) * force * 2.0;
          p.vy += Math.sin(angle) * force * 2.0;
          p.hue = Math.min(220, p.hue + force * 15);
        }
      }

      // Repel from Thoughts (Words passing by)
      for (const t of thoughts) {
        const tdx = p.x - t.x;
        // Text is wide, so we make the repulsion radius an oval (compress Y distance)
        const tdy = (p.y - (t.y - t.fontSize / 3)) * 2; 
        const tDistSq = tdx * tdx + tdy * tdy;
        
        // Approximate width influence area
        if (tDistSq < 40000) { // 200 radius squared
          const tDist = Math.sqrt(tDistSq);
          const force = (200 - tDist) / 200;
          const angle = Math.atan2(tdy / 2, tdx);
          
          p.vx += Math.cos(angle) * force * 1.5;
          p.vy += Math.sin(angle) * force * 1.5;
          // Light up particle when it interacts with a thought
          p.hue = Math.min(250, p.hue + force * 30);
          pAlpha = Math.min(1, pAlpha + force);
        }
      }
      
      // Ripple interaction
      for (const r of ripples) {
        const dx = p.x - r.x
        const dy = p.y - r.y
        const dist = Math.sqrt(dx * dx + dy * dy)
        const distToRing = Math.abs(dist - r.radius)
        
        if (distToRing < 40) {
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
      
      // Bounce
      if (p.x < 0 || p.x > w) { p.vx *= -1; p.baseVx *= -1; p.x = Math.max(0, Math.min(w, p.x)) }
      if (p.y < 0 || p.y > h) { p.vy *= -1; p.baseVy *= -1; p.y = Math.max(0, Math.min(h, p.y)) }
      
      // Base particle
      ctx.beginPath()
      ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2)
      ctx.fillStyle = `hsla(${p.hue}, 100%, 60%, ${pAlpha})`
      ctx.fill()
      
      // Faux glow (much faster than shadowBlur)
      if (p.size > p.baseSize) {
        ctx.beginPath()
        ctx.arc(p.x, p.y, p.size * 2.5, 0, Math.PI * 2)
        ctx.fillStyle = `hsla(${p.hue}, 100%, 60%, ${pAlpha * 0.2})`
        ctx.fill()
      }
      
      // Draw connections
      for (let j = i + 1; j < particles.length; j++) {
        const p2 = particles[j]!
        const dx = p.x - p2.x
        const dy = p.y - p2.y
        const distSq = dx * dx + dy * dy
        
        if (distSq < 14400) {
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
      
      if (t.passengers > 1) {
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
      
      if (t.passengers > 1) {
        ctx.font = `600 ${Math.max(10, t.fontSize * 0.4)}px ${t.font}, sans-serif`
        ctx.textAlign = 'left'
        ctx.fillStyle = `rgba(255, 255, 255, ${t.alpha * 0.9})`
        const lastCharWaveOffset = Math.sin(animTime * 1.5 + (t.text.length - 1) * 0.25) * 4
        ctx.fillText(`${t.passengers}`, t.x + (textWidth / 2) + 4, t.y - (t.fontSize * 0.4) + lastCharWaveOffset)
        ctx.textAlign = 'center'
      }
      
      ctx.shadowBlur = 0
      
      if (t.y < -50) {
        thoughts.splice(i, 1)
      }
    }
    
    if (waterEngine) {
      waterEngine.updateThoughts(
        thoughts.map(t => ({ x: t.x, y: t.y, passengers: t.passengers }))
      )
      waterEngine.updateRipples(
        ripples.map(r => ({ x: r.x, y: r.y, radius: r.radius, maxRadius: r.maxRadius, strength: r.strength }))
      )
    }
    
    animationFrameId = requestAnimationFrame(draw)
  }
  
  draw()
  
  onUnmounted(() => {
    if (waterEngine) {
      waterEngine.dispose()
    }
    window.removeEventListener('resize', resize)
    window.removeEventListener('mousemove', handleMouseMove)
    document.removeEventListener('mouseleave', handleMouseLeave)
    window.removeEventListener('click', handleClick)
    cancelAnimationFrame(animationFrameId)
  })
})
</script>

<style scoped>
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
</style>
