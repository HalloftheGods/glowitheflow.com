<template>
  <div class="space-y-5">
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
</template>

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
