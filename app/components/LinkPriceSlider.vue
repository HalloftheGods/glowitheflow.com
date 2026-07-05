<template>
  <div class="price-slider-card flex flex-col items-end group relative transition-all duration-300">
    
    <!-- Floating Panel (Hovering above the button) -->
    <div class="absolute bottom-full right-0 pb-4 w-full min-w-[260px] transition-all duration-300 opacity-0 translate-y-4 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto z-50">
      <div class="bg-[rgba(10,15,30,0.85)] border border-[rgba(0,255,255,0.3)] rounded-2xl backdrop-blur-xl p-5 flex flex-col gap-4 shadow-[0_10px_40px_rgba(0,0,0,0.5)]">
        <div class="flex justify-between items-center w-full px-1">
        <span class="text-cyan-300/60 text-xs font-mono uppercase tracking-widest">My Vote</span>
        <span class="text-cyan-200 text-sm font-mono font-bold flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-cyan-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 2c-1.716 3.197-5 7.14-5 10a5 5 0 1010 0c0-2.86-3.284-6.803-5-10z" clip-rule="evenodd" />
          </svg>
          {{ formattedVote }} Dropplets
        </span>
      </div>
        <input
          type="range"
          min="1"
          max="100"
          step="1"
          v-model.number="localVote"
          @input="emitVote"
          class="styled-slider w-full"
        />
        <p class="text-xs text-cyan-300/70 text-center font-sans leading-relaxed mt-2 px-2">
        Vote on the Dropplet cost to stream a link. The community balances the pool.
      </p>
      </div>
    </div>

    <!-- Toggle Button (Bottom Right) -->
    <button class="flex items-center justify-center gap-2 bg-[rgba(10,15,30,0.6)] border border-[rgba(0,255,255,0.3)] rounded-full px-6 py-3 backdrop-blur-md shadow-[0_0_15px_rgba(0,255,255,0.15)] group-hover:border-[rgba(0,255,255,0.6)] group-hover:shadow-[0_0_25px_rgba(0,255,255,0.3)] transition-all cursor-pointer pointer-events-auto">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-cyan-400" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 2c-1.716 3.197-5 7.14-5 10a5 5 0 1010 0c0-2.86-3.284-6.803-5-10z" clip-rule="evenodd" />
      </svg>
      <span class="text-cyan-100 font-mono text-base tracking-wide">{{ formattedPrice }} Dropplets</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const props = defineProps<{
  currentPrice: number;
}>();

const emit = defineEmits(['update:vote']);

// Local state for the user's vote
const localVote = ref(props.currentPrice);

const formattedPrice = computed(() => Math.round(props.currentPrice));
const formattedVote = computed(() => Math.round(localVote.value));

let debounceTimer: ReturnType<typeof setTimeout>;

const emitVote = () => {
  // Debounce the vote emission so we don't spam the parent/API while dragging
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    emit('update:vote', localVote.value);
  }, 300);
};
</script>

<style scoped>
.styled-slider {
  -webkit-appearance: none;
  background: transparent;
  width: 100%;
}

.styled-slider:focus {
  outline: none;
}

/* Track */
.styled-slider::-webkit-slider-runnable-track {
  width: 100%;
  height: 4px;
  cursor: pointer;
  background: rgba(0, 255, 255, 0.2);
  border-radius: 4px;
  transition: all 0.2s ease;
}
.styled-slider:hover::-webkit-slider-runnable-track {
  background: rgba(0, 255, 255, 0.4);
  box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
}

/* Thumb */
.styled-slider::-webkit-slider-thumb {
  height: 20px;
  width: 20px;
  background: transparent;
  background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'><path fill='%2322d3ee' fill-rule='evenodd' d='M10 2c-1.716 3.197-5 7.14-5 10a5 5 0 1010 0c0-2.86-3.284-6.803-5-10z' clip-rule='evenodd'/></svg>");
  background-repeat: no-repeat;
  background-position: center;
  background-size: contain;
  cursor: pointer;
  -webkit-appearance: none;
  margin-top: -8px;
  filter: drop-shadow(0 0 6px rgba(34, 211, 238, 0.8));
  border: none;
  transition: all 0.2s ease;
}

.styled-slider::-moz-range-thumb {
  height: 20px;
  width: 20px;
  background: transparent;
  background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'><path fill='%2322d3ee' fill-rule='evenodd' d='M10 2c-1.716 3.197-5 7.14-5 10a5 5 0 1010 0c0-2.86-3.284-6.803-5-10z' clip-rule='evenodd'/></svg>");
  background-repeat: no-repeat;
  background-position: center;
  background-size: contain;
  cursor: pointer;
  filter: drop-shadow(0 0 6px rgba(34, 211, 238, 0.8));
  border: none;
  transition: all 0.2s ease;
}

.styled-slider:hover::-webkit-slider-thumb {
  transform: scale(1.2);
  filter: drop-shadow(0 0 10px rgba(34, 211, 238, 1));
}

.styled-slider:hover::-moz-range-thumb {
  transform: scale(1.2);
  filter: drop-shadow(0 0 10px rgba(34, 211, 238, 1));
}
</style>
