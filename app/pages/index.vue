<template>
  <div
    class="canvas-container w-full h-full relative"
    ref="containerRef"
  >
    <canvas
      ref="canvasRef"
      class="w-full h-full block"
    ></canvas>
  </div>
  <div
    class="ui-overlay pointer-events-none flex flex-col justify-center items-center absolute inset-0 z-10 text-center px-4"
  >
    <h1
      class="text-7xl md:text-9xl font-display font-normal text-transparent bg-clip-text bg-gradient-to-br from-white via-glow-cyan to-blue-600 drop-shadow-[0_0_20px_rgba(0,255,255,0.6)] tracking-wider pb-2"
    >
      GlowitheFlow
    </h1>
    <p
      class="text-sm md:text-base font-sans tracking-[0.2em] text-white mt-6 md:mt-8 drop-shadow-[0_0_10px_rgba(255,255,255,0.4)]">
      Presented By Hall of the Gods, Inc.
    </p>

    <div
      class="absolute bottom-6 md:bottom-8 left-0 right-0 text-center text-[10px] md:text-xs font-mono text-glow-text/40 tracking-[0.2em] uppercase flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4 drop-shadow"
    >
      <span>v{{ version }}</span>
      <span class="hidden sm:inline opacity-50">|</span>
      <span>&copy; 2006-2026 <a href="https://hallofthegods.com/" target="_blank" class="hover:text-white transition-colors pointer-events-auto">Hall of the Gods, Inc.</a> All rights reserved.</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { version } from '../../package.json'

useHead({
  title: 'GlowitheFlow | Hall of the Gods',
  meta: [
    { name: 'description', content: 'The third emanation of the sacred realm. A canvas of infinite possibilities.' }
  ],
  link: [
    { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' }
  ]
})

const canvasRef = ref<HTMLCanvasElement | null>(null)
const containerRef = ref<HTMLElement | null>(null)

let animationFrameId: number

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

onMounted(() => {
  const canvas = canvasRef.value
  const container = containerRef.value
  if (!canvas || !container) return
  
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  
  let w: number = 0
  let h: number = 0
  
  const resize = () => {
    w = canvas.width = window.innerWidth
    h = canvas.height = window.innerHeight
  }
  
  window.addEventListener('resize', resize)
  resize()
  
  const ripples: Ripple[] = []
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
    ripples.push({
      x: e.clientX,
      y: e.clientY,
      radius: 0,
      maxRadius: 350,
      speed: 8,
      strength: 20
    })
  }

  window.addEventListener('mousemove', handleMouseMove)
  document.addEventListener('mouseleave', handleMouseLeave)
  window.addEventListener('click', handleClick)
  window.addEventListener('touchstart', (e: TouchEvent) => {
    if (e.touches[0]) {
      handleClick(e.touches[0] as unknown as MouseEvent)
    }
  }, { passive: true })

  const particles: Particle[] = []
  const maxParticles = window.innerWidth < 768 ? 60 : 150
  
  for (let i = 0; i < maxParticles; i++) {
    const vx = (Math.random() - 0.5) * 1.5
    const vy = (Math.random() - 0.5) * 1.5
    const size = Math.random() * 2 + 0.5
    const hue = Math.random() * 40 + 170 // Cyan to Deep Blue
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
    // Fade out previous frame (creates trail)
    ctx.fillStyle = 'rgba(5, 5, 8, 0.2)'
    ctx.fillRect(0, 0, w, h)
    
    // Render and update ripples
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
      
      // Push effect away from mouse
      if (mdist < 150) {
        const force = (150 - mdist) / 150
        const angle = Math.atan2(mdy, mdx)
        p.vx += Math.cos(angle) * force * 0.5
        p.vy += Math.sin(angle) * force * 0.5
        p.size = Math.max(p.size, p.baseSize + force * 1.5)
        p.hue = Math.min(220, p.hue + force * 20)
        pAlpha = 1
      }
      
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
      
      // Update pos
      p.x += p.vx
      p.y += p.vy
      
      // Bounce
      if (p.x < 0 || p.x > w) { p.vx *= -1; p.baseVx *= -1; p.x = Math.max(0, Math.min(w, p.x)) }
      if (p.y < 0 || p.y > h) { p.vy *= -1; p.baseVy *= -1; p.y = Math.max(0, Math.min(h, p.y)) }
      
      // Draw particle
      ctx.beginPath()
      ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2)
      ctx.fillStyle = `hsla(${p.hue}, 100%, 60%, ${pAlpha})`
      ctx.shadowBlur = p.size > p.baseSize ? 15 : 10
      ctx.shadowColor = `hsla(${p.hue}, 100%, 60%, 1)`
      ctx.fill()
      
      // Draw connections
      for (let j = i + 1; j < particles.length; j++) {
        const p2 = particles[j]!
        const dx = p.x - p2.x
        const dy = p.y - p2.y
        const dist = Math.sqrt(dx * dx + dy * dy)
        
        if (dist < 120) {
          ctx.beginPath()
          ctx.moveTo(p.x, p.y)
          ctx.lineTo(p2.x, p2.y)
          ctx.strokeStyle = `hsla(${p.hue}, 100%, 60%, ${0.6 - dist / 200})`
          ctx.lineWidth = 0.5
          ctx.stroke()
        }
      }
    }
    
    animationFrameId = requestAnimationFrame(draw)
  }
  
  draw()
  
  onUnmounted(() => {
    window.removeEventListener('resize', resize)
    window.removeEventListener('mousemove', handleMouseMove)
    document.removeEventListener('mouseleave', handleMouseLeave)
    window.removeEventListener('click', handleClick)
    cancelAnimationFrame(animationFrameId)
  })
})
</script>
