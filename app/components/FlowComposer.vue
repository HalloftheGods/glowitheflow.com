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

    <!-- Dropdown and Optional Textarea when hasLink is true -->
    <div v-if="hasLink" class="mt-4 border-t border-cyan-500/20 pt-4 flex flex-col gap-4 text-left">
      <div class="flex flex-col md:flex-row md:items-center gap-2">
        <label for="tributary-select" class="text-sm font-mono text-cyan-300">Tributary:</label>
        <select
          id="tributary-select"
          v-model="selectedTributary"
          class="bg-cyan-950 border border-cyan-500/30 text-white rounded-lg px-3 py-1.5 outline-none font-mono text-sm focus:border-cyan-300"
        >
          <option value="t/dev">t/dev</option>
          <option value="t/art">t/art</option>
          <option value="t/memes">t/memes</option>
          <option value="t/music">t/music</option>
        </select>
      </div>
      <div class="flex flex-col gap-1">
        <label for="body-text" class="text-sm font-mono text-cyan-300">Body Text (Optional):</label>
        <textarea
          id="body-text"
          v-model="bodyText"
          placeholder="Optional text to go with your link..."
          rows="3"
          class="w-full bg-cyan-950/40 border border-cyan-500/20 text-white text-sm rounded-xl p-3 outline-none focus:border-cyan-500/50 resize-none font-sans"
        ></textarea>
      </div>
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
import { useUserStore } from '../stores/user';

const props = defineProps<{
  knownThoughts: { text: string; count: number }[];
  currentLinkPrice?: number;
}>();

const emit = defineEmits(['submit']);
const content = ref('');
const selectedTributary = ref('t/dev');
const bodyText = ref('');

const userStore = useUserStore();

const urlRegex = /(https?:\/\/[^\s]+)|(www\.[^\s]+)|([a-zA-Z0-9]+(\.[a-zA-Z0-9]+)+([^\s]?)+)/i;
const hasLink = computed(() => urlRegex.test(content.value));

const formattedPrice = computed(() => Math.round(props.currentLinkPrice || 10));

const hasEnoughDroplets = computed(() => {
  const price = props.currentLinkPrice || 10;
  return userStore.dropletBalance >= price;
});

const mapToThoughtWithScore = (thought: { text: string; count: number }) => {
  const query = content.value.trim();
  return {
    ...thought,
    score: getSimilarity(query, thought.text)
  };
};

const filterByScoreThreshold = (thought: { score: number }) => thought.score > 0.2;

const sortByScoreDesc = (a: { score: number }, b: { score: number }) => b.score - a.score;

const suggestions = computed(() => {
  const query = content.value.trim();
  const isQueryEmpty = !query;
  if (isQueryEmpty) return [];

  return props.knownThoughts
    .map(mapToThoughtWithScore)
    .filter(filterByScoreThreshold)
    .sort(sortByScoreDesc)
    .slice(0, 5);
});

const selectSuggestion = async (text: string) => {
  try {
    const response = await userStore.submitPost('thought', text);
    emit('submit', {
      type: 'thought',
      content: text,
      response,
    });
    content.value = '';
  } catch (error) {
    console.error('Failed to submit suggestion:', error);
  }
};

const submit = async () => {
  const queryText = content.value.trim();
  if (!queryText) return;

  const isLink = hasLink.value;
  const type = isLink ? 'drop' : 'thought';

  if (isLink && !hasEnoughDroplets.value) {
    return;
  }

  try {
    const extra = isLink ? {
      link: queryText,
      tributary: selectedTributary.value,
    } : undefined;

    const postContent = isLink ? (bodyText.value.trim() || queryText) : queryText;

    const response = await userStore.submitPost(type, postContent, extra);
    emit('submit', {
      type,
      content: postContent,
      link: queryText,
      tributary: selectedTributary.value,
      response,
    });

    content.value = '';
    bodyText.value = '';
  } catch (error) {
    console.error('Failed to submit post:', error);
  }
};
</script>

<style scoped>
  .flow-composer-card {
    backdrop-filter: blur(20px);
    border-radius: 24px;
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
