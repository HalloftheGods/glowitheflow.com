<template>
  <div class="space-y-5">
    <!-- Tier Badge — large, prominent on the water -->
    <div class="water-panel p-8 text-center">
      <span class="text-sm font-mono uppercase tracking-[0.25em] text-cyan-300/60 block mb-3">Network Tier</span>
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
        <span class="text-sm md:text-base text-cyan-50/90 block mt-3 md:mt-4">Total revenue from your clicks</span>
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
</template>

<script setup lang="ts">
defineProps<{
  networkTier: string
  lifetimeValue: number
  cpc: number
  trafficQuality: number
}>()
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
</style>
