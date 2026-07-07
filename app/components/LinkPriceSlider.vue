<template>
  <div class="price-slider-card flex flex-col w-full relative transition-all duration-300 bg-cyan-950/40 p-5 rounded-2xl border border-cyan-500/15">
    <div class="flex justify-between items-center w-full mb-4">
      <span class="text-cyan-300/60 text-xs font-mono uppercase tracking-widest">Community Link Price</span>
      <span class="text-cyan-100 text-lg font-mono font-bold flex items-center gap-1.5 drop-shadow-[0_0_8px_rgba(0,255,255,0.4)]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-cyan-400" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 2c-1.716 3.197-5 7.14-5 10a5 5 0 1010 0c0-2.86-3.284-6.803-5-10z" clip-rule="evenodd" />
        </svg>
        {{ formattedPrice }} Droplets
      </span>
    </div>
    
    <input
      type="range"
      min="1"
      max="100"
      step="1"
      v-model.number="localVote"
      @input="emitVote"
      class="styled-slider w-full mb-3"
    />
    
    <div class="flex justify-between items-center w-full mt-1">
      <span class="text-xs text-cyan-300/50 font-mono uppercase tracking-wider">Your Vote: {{ formattedVote }}</span>
      <span class="text-xs text-cyan-300/40 font-sans">Adjust to shift network price</span>
    </div>
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
