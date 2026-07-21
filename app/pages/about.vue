<template>
  <div class="h-screen w-full overflow-y-auto overflow-x-hidden bg-black text-white selection:bg-cyan-500/30 font-sans relative custom-scrollbar">
    <!-- Ambient Background Gradients -->
    <div class="fixed inset-0 z-0 pointer-events-none">
      <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] bg-blue-600/20 rounded-full blur-[120px]"></div>
      <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-cyan-400/20 rounded-full blur-[120px]"></div>
    </div>

    <!-- Navigation -->
    <nav class="relative z-20 max-w-6xl mx-auto px-6 py-8 flex justify-between items-center">
      <NuxtLink to="/" class="group flex items-center gap-2 text-cyan-400 hover:text-cyan-300 transition-colors">
        <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span class="font-medium tracking-wide">Return to Flow</span>
      </NuxtLink>
    </nav>

    <!-- Main Content -->
    <main class="relative z-10 max-w-5xl mx-auto px-6 pt-12 pb-24">
      <!-- Hero Section -->
      <header class="text-center mb-24 animate-fade-in-up">
        <h1 class="text-5xl md:text-7xl font-light tracking-tight mb-6 text-transparent bg-clip-text bg-gradient-to-br from-white via-cyan-200 to-blue-500">
          Your Attention has Value. <br />
          <span class="font-semibold">Take it Back.</span>
        </h1>
        <p class="text-lg md:text-2xl text-cyan-100/70 max-w-2xl mx-auto leading-relaxed">
          GlowitheFlow is the world's first positive-sum news feed where you get paid for your genuine attention, and the community controls what rises to the top.
        </p>
      </header>

      <!-- Feature Grid -->
      <div class="grid md:grid-cols-2 gap-8 mb-24">
        <!-- Card 1 -->
        <div class="group relative bg-white/[0.03] backdrop-blur-xl border border-white/10 p-8 rounded-3xl hover:bg-white/[0.05] transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_0_40px_rgba(34,211,238,0.15)]">
          <div class="w-12 h-12 rounded-full bg-cyan-500/20 flex items-center justify-center mb-6 text-cyan-400 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h3 class="text-2xl font-semibold mb-3 text-white">Earn by Engaging</h3>
          <p class="text-cyan-100/60 leading-relaxed">
            As you explore the feed, read, and interact, you earn <strong>Driplets</strong>. We measure the quality of your attention—no bots allowed. Your time is valuable, and it's time you got rewarded for it.
          </p>
        </div>

        <!-- Card 2 -->
        <div class="group relative bg-white/[0.03] backdrop-blur-xl border border-white/10 p-8 rounded-3xl hover:bg-white/[0.05] transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_0_40px_rgba(59,130,246,0.15)]">
          <div class="w-12 h-12 rounded-full bg-blue-500/20 flex items-center justify-center mb-6 text-blue-400 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <h3 class="text-2xl font-semibold mb-3 text-white">Share Your Voice</h3>
          <p class="text-cyan-100/60 leading-relaxed">
            Drop a quick text <strong>Thought</strong> into the stream completely for free. Watch it ride the current, gather momentum, and spark conversations across the network.
          </p>
        </div>

        <!-- Card 3 -->
        <div class="group relative bg-white/[0.03] backdrop-blur-xl border border-white/10 p-8 rounded-3xl hover:bg-white/[0.05] transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_0_40px_rgba(34,211,238,0.15)]">
          <div class="w-12 h-12 rounded-full bg-cyan-400/20 flex items-center justify-center mb-6 text-cyan-300 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
          </div>
          <h3 class="text-2xl font-semibold mb-3 text-white">Promote What Matters</h3>
          <p class="text-cyan-100/60 leading-relaxed">
            Convert your Driplets into <strong>Droplets</strong> to submit a <strong>Drop</strong> (a link to anywhere on the web). It's like your own personal ad network, driven entirely by community engagement rather than corporate algorithms.
          </p>
        </div>

        <!-- Card 4 -->
        <div class="group relative bg-white/[0.03] backdrop-blur-xl border border-white/10 p-8 rounded-3xl hover:bg-white/[0.05] transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_0_40px_rgba(59,130,246,0.15)]">
          <div class="w-12 h-12 rounded-full bg-blue-400/20 flex items-center justify-center mb-6 text-blue-300 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
          </div>
          <h3 class="text-2xl font-semibold mb-3 text-white">Give it some Glow</h3>
          <p class="text-cyan-100/60 leading-relaxed">
            Found something amazing? Boost it to increase its <strong>Glow</strong>. Items with high Glow surface more frequently for other users, ensuring the best content naturally floats to the top.
          </p>
        </div>
      </div>

      <!-- CTA -->
      <div class="text-center bg-gradient-to-b from-cyan-900/20 to-transparent border border-cyan-500/20 p-12 rounded-[2rem] relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiMyMkQzRUUiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] [mask-image:linear-gradient(to_bottom,white,transparent)] pointer-events-none"></div>
        <h2 class="text-3xl md:text-4xl font-semibold mb-6 text-white relative z-10">Ready to join the flow?</h2>
        <NuxtLink to="/" class="inline-flex items-center justify-center px-8 py-4 bg-cyan-500 hover:bg-cyan-400 text-black font-semibold rounded-full transition-all duration-300 hover:scale-105 hover:shadow-[0_0_30px_rgba(34,211,238,0.4)] relative z-10">
          Jump In Now
        </NuxtLink>
      </div>
    </main>
  </div>
</template>

<style scoped>
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.animate-fade-in-up {
  animation: fadeInUp 1s ease-out forwards;
}
</style>
