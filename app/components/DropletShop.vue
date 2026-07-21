<template>
  <div class="droplet-shop bg-cyan-950/40 backdrop-blur-md border border-cyan-500/30 rounded-2xl shadow-[0_0_20px_rgba(0,255,255,0.15)] p-6 md:p-8 font-mono text-cyan-100">
    <div class="text-center mb-8">
      <h3 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-cyan-300 to-blue-400 bg-clip-text text-transparent drop-shadow-[0_0_8px_rgba(0,255,255,0.3)]">
        Droplet Shop
      </h3>
      <p class="text-cyan-300/60 text-sm mt-2">
        Acquire Droplets to broadcast your links to the stream.
      </p>
    </div>

    <!-- Package Selection Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div
        v-for="pack in packages"
        :key="pack.id"
        @click="selectPackage(pack.id)"
        class="cursor-pointer transition-all duration-300 rounded-xl p-5 border text-center flex flex-col justify-between"
        :class="[
          selectedPackId === pack.id
            ? 'bg-cyan-900/40 border-cyan-400 shadow-[0_0_15px_rgba(0,255,255,0.3)] scale-[1.02]'
            : 'bg-cyan-950/20 border-cyan-500/15 hover:border-cyan-500/40 hover:bg-cyan-950/30'
        ]"
      >
        <div>
          <h4 class="text-lg font-bold mb-2">{{ pack.name }}</h4>
          <div class="text-3xl font-extrabold text-cyan-300 my-4 flex items-center justify-center gap-1">
            <span>{{ pack.droplets }}</span>
            <span class="text-2xl">💧</span>
          </div>
        </div>
        <div class="mt-4">
          <div class="text-xl font-bold text-white mb-2">{{ pack.price }}</div>
          <span
            class="text-[10px] uppercase tracking-wider px-3 py-1 rounded-full"
            :class="[
              selectedPackId === pack.id
                ? 'bg-cyan-400/20 text-cyan-200'
                : 'bg-cyan-950/50 text-cyan-400/60'
            ]"
          >
            {{ selectedPackId === pack.id ? 'Selected' : 'Select' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Coupon Code Input -->
    <div class="max-w-md mx-auto mb-6">
      <label for="coupon-input" class="block text-xs uppercase tracking-widest text-cyan-300/60 mb-2">
        Coupon / Promo Code
      </label>
      <input
        id="coupon-input"
        type="text"
        v-model="coupon"
        placeholder="Enter promo code (e.g. SAVE20)"
        class="w-full bg-cyan-950/60 border border-cyan-500/30 rounded-xl px-4 py-2 text-white placeholder-cyan-500/40 font-mono text-center uppercase tracking-wider focus:border-cyan-400 focus:outline-none transition-colors"
      />
    </div>

    <!-- Error Message Display -->
    <div
      v-if="errorMessage"
      class="max-w-md mx-auto mb-6 p-4 rounded-xl bg-red-950/40 border border-red-500/30 text-red-300 text-sm text-center"
    >
      {{ errorMessage }}
    </div>

    <!-- Checkout Button -->
    <div class="max-w-md mx-auto text-center">
      <button
        @click="handleCheckout"
        :disabled="isLoading"
        class="w-full py-4 rounded-xl font-bold text-base uppercase tracking-wider bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-[0_0_15px_rgba(0,255,255,0.2)] hover:shadow-[0_0_25px_rgba(0,255,255,0.4)] hover:scale-[1.01] active:scale-[0.99] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
      >
        <span v-if="isLoading">Processing Checkout...</span>
        <span v-else>Proceed to Checkout</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useGlowApi } from '../composables/useGlowApi';

interface Package {
  id: 'starter' | 'pro' | 'whale';
  name: string;
  price: string;
  droplets: number;
}

const packages: Package[] = [
  {
    id: 'starter',
    name: 'Starter Pack',
    price: '$5.00',
    droplets: 50,
  },
  {
    id: 'pro',
    name: 'Pro Pack',
    price: '$15.00',
    droplets: 200,
  },
  {
    id: 'whale',
    name: 'Whale Pack',
    price: '$50.00',
    droplets: 1000,
  },
];

const selectedPackId = ref<'starter' | 'pro' | 'whale'>('starter');
const coupon = ref('');
const errorMessage = ref('');
const isLoading = ref(false);

const selectPackage = (id: 'starter' | 'pro' | 'whale') => {
  selectedPackId.value = id;
};

const handleCheckout = async () => {
  errorMessage.value = '';
  isLoading.value = true;

  try {
    const isWindowDefined = typeof window !== 'undefined';
    const origin = isWindowDefined ? window.location.origin : 'http://localhost:3000';
    const trimmedCoupon = coupon.value.trim().toUpperCase();

    const payload = {
      pack_id: selectedPackId.value,
      coupon: trimmedCoupon || undefined,
      success_url: `${origin}/?success=true`,
      cancel_url: `${origin}/?cancel=true`,
    };

    const response = await useGlowApi('/glow/v1/stripe/checkout', {
      method: 'POST',
      body: payload,
    });

    const hasCheckoutUrl = !!(response && response.checkout_url);
    if (hasCheckoutUrl) {
      window.location.href = response.checkout_url;
    } else {
      errorMessage.value = 'Failed to retrieve checkout URL from Stripe response.';
      isLoading.value = false;
    }
  } catch (error: any) {
    isLoading.value = false;
    const rawError = error?.data?.message || error?.message || 'Unknown network error';
    errorMessage.value = `Checkout error: ${rawError}`;
  }
};
</script>
