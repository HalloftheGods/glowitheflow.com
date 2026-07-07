<template>
  <div class="flow-composer-card p-4 relative">
    <div class="flex items-center gap-2">
      <input
        v-model="content"
        type="text"
        placeholder="&quot;So. what you thinkin'?&quot; ~ Dee"
        class="flow-input flex-grow bg-transparent border-none text-white text-lg outline-none px-4 py-2"
        @keyup.enter="submit"
      />
      <button
        @click="submit"
        :disabled="!content.trim() || (hasLink && !hasEnoughDroplets)"
        class="disabled:opacity-50 transition-all duration-300 px-5 py-2 rounded-full font-sans text-sm whitespace-nowrap"
        :class="hasLink ? 'bg-[rgba(10,15,30,0.6)] border border-cyan-500/50 text-cyan-100 shadow-[0_0_15px_rgba(0,255,255,0.2)] hover:shadow-[0_0_25px_rgba(0,255,255,0.4)] hover:bg-[rgba(10,15,30,0.8)] hover:scale-105 active:scale-95' : 'text-blue-400 hover:text-white'"
      >
        <span v-if="hasLink" class="flex flex-col items-center justify-center px-2">
          <span class="tracking-wide font-medium text-base">
            {{ hasEnoughDroplets ? 'Add Link to Stream' : 'Insufficient Droplets' }}
          </span>
          <span class="text-[10px] text-cyan-400/80 font-mono uppercase tracking-widest mt-1 flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 2c-1.716 3.197-5 7.14-5 10a5 5 0 1010 0c0-2.86-3.284-6.803-5-10z" clip-rule="evenodd" />
            </svg>
            {{ hasEnoughDroplets ? `Consumes ${formattedPrice} Droplets` : `Needs ${formattedPrice} Droplets (Click links to earn)` }}
          </span>
        </span>
        <span v-else>
          Add to the Thought Stream
        </span>
      </button>
    </div>

    <!-- Suggestions Typeahead -->
    <div
      v-if="suggestions.length > 0 && content.trim().length > 0"
      class="absolute top-[calc(100%+0.5rem)] left-0 right-0 bg-[rgba(10,15,30,0.6)] backdrop-blur-xl rounded-2xl border border-[rgba(0,255,255,0.3)] shadow-[0_0_20px_rgba(0,255,255,0.15)] overflow-hidden transition-all z-50"
    >
      <ul class="max-h-60 overflow-y-auto custom-scrollbar">
        <li
          v-for="suggestion in suggestions"
          :key="suggestion.text"
          class="px-4 py-3 text-white/80 hover:text-white hover:bg-[rgba(0,255,255,0.2)] cursor-pointer transition-colors flex items-center justify-between border-b border-[rgba(0,255,255,0.1)] last:border-none"
          @click="selectSuggestion(suggestion.text)"
        >
          <span>{{ suggestion.text }}</span>
          <span class="text-xs text-cyan-300/60 font-mono">{{ suggestion.count }} {{ suggestion.count === 1 ?
            'passenger' : 'passengers' }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { getSimilarity } from '../utils/similarity';

const props = defineProps<{
  knownThoughts: { text: string; count: number }[];
  currentLinkPrice?: number;
  dripletBalance: number;
}>();

const emit = defineEmits(['submit']);
const content = ref('');

const urlRegex = /(https?:\/\/[^\s]+)|(www\.[^\s]+)|([a-zA-Z0-9]+(\.[a-zA-Z0-9]+)+([^\s]?)+)/i;
const hasLink = computed(() => urlRegex.test(content.value));

const formattedPrice = computed(() => Math.round(props.currentLinkPrice || 10));

const hasEnoughDroplets = computed(() => {
  const price = (props.currentLinkPrice || 10) * 100; // Price is in Droplets, balance is in Driplets
  const isSufficient = props.dripletBalance >= price;
  return isSufficient;
});

const suggestions = computed(() => {
  const query = content.value.trim();
  const isQueryEmpty = !query;
  if (isQueryEmpty) return [];

  return props.knownThoughts
    .map(thought => ({
      ...thought,
      score: getSimilarity(query, thought.text)
    }))
    .filter(thought => thought.score > 0.2)
    .sort((a, b) => b.score - a.score)
    .slice(0, 5);
});

const selectSuggestion = (text: string) => {
  emit('submit', text);
  content.value = '';
};

const submit = () => {
  const queryText = content.value.trim();
  const isQueryNotEmpty = !!queryText;
  if (isQueryNotEmpty) {
    emit('submit', queryText);
    content.value = '';
  }
};
</script>

<style scoped>
  .flow-composer-card {
    backdrop-filter: blur(20px);
    border-radius: 100px;
    border: 1px solid rgba(0, 255, 255, 0.3);
    background: rgba(10, 15, 30, 0.4);
    animation: flowBreathe 4s ease-in-out infinite;
    transition: all 0.3s ease;
  }

  .flow-composer-card:focus-within {
    animation: flowBreatheActive 2s ease-in-out infinite;
    background: rgba(10, 15, 30, 0.6);
  }

  @keyframes flowBreathe {

    0%,
    100% {
      box-shadow: 0 0 20px rgba(0, 255, 255, 0.15), inset 0 0 10px rgba(0, 255, 255, 0.05);
      border-color: rgba(0, 255, 255, 0.2);
    }

    50% {
      box-shadow: 0 0 35px rgba(0, 255, 255, 0.3), inset 0 0 15px rgba(0, 255, 255, 0.1);
      border-color: rgba(0, 255, 255, 0.5);
    }
  }

  @keyframes flowBreatheActive {

    0%,
    100% {
      box-shadow: 0 0 30px rgba(0, 255, 255, 0.3), inset 0 0 15px rgba(0, 255, 255, 0.1);
      border-color: rgba(0, 255, 255, 0.4);
    }

    50% {
      box-shadow: 0 0 50px rgba(0, 255, 255, 0.6), inset 0 0 25px rgba(0, 255, 255, 0.3);
      border-color: rgba(0, 255, 255, 0.8);
    }
  }

  .flow-input::placeholder {
    color: rgba(255, 255, 255, 0.4);
  }
</style>
