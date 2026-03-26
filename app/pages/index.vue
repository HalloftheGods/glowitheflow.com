<template>
  <div class="canvas-container w-full h-full relative" ref="containerRef">
    <canvas ref="canvasRef" class="w-full h-full block"></canvas>
  </div>
  <div class="ui-overlay pointer-events-none flex flex-col justify-center items-center absolute inset-0 z-10 text-center px-4">
    <p class="text-xs md:text-sm font-sans tracking-widest text-glow-gold/80 mb-5 md:mb-8 drop-shadow-[0_0_8px_rgba(197,160,89,0.5)]">
      Hall of the Gods, Inc. Presents...
    </p>
    <h1 class="text-7xl md:text-9xl font-display font-light text-glow-cyan drop-shadow-[0_0_20px_rgba(0,255,255,0.7)] tracking-wider">
      GlowitheFlow
    </h1>
    <p class="text-sm md:text-base font-sans tracking-[0.2em] text-glow-cyan/60 italic mt-6 md:mt-8 drop-shadow-[0_0_10px_rgba(0,255,255,0.4)]">
      Coming Soon
    </p>
    
    <div class="absolute bottom-6 md:bottom-8 left-0 right-0 text-center text-[10px] md:text-xs font-mono text-glow-text/40 tracking-[0.2em] uppercase flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4 drop-shadow">
      <span>v0.1.0</span>
      <span class="hidden sm:inline opacity-50">|</span>
      <span>&copy; 2026 Hall of the Gods, Inc.</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'

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
  size: number
  hue: number
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
  
  const particles: Particle[] = []
  const maxParticles = window.innerWidth < 768 ? 60 : 150
  
  for (let i = 0; i < maxParticles; i++) {
    particles.push({
      x: Math.random() * w,
      y: Math.random() * h,
      vx: (Math.random() - 0.5) * 1.5,
      vy: (Math.random() - 0.5) * 1.5,
      size: Math.random() * 2 + 0.5,
      hue: Math.random() * 40 + 170 // Cyan to Deep Blue
    })
  }
  
  const draw = () => {
    // Fade out previous frame (creates trail)
    ctx.fillStyle = 'rgba(5, 5, 8, 0.2)'
    ctx.fillRect(0, 0, w, h)
    
    for (let i = 0; i < particles.length; i++) {
      const p = particles[i]!
      
      // Update pos
      p.x += p.vx
      p.y += p.vy
      
      // Bounce
      if (p.x < 0 || p.x > w) p.vx *= -1
      if (p.y < 0 || p.y > h) p.vy *= -1
      
      // Draw particle
      ctx.beginPath()
      ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2)
      ctx.fillStyle = `hsla(${p.hue}, 100%, 60%, 0.8)`
      ctx.shadowBlur = 10
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
    cancelAnimationFrame(animationFrameId)
  })
})
</script>
