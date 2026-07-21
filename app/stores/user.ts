import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', {
  state: () => ({
    isAuthenticated: false,
    username: '',
    dropletBalance: 0,
    dripletBalance: 0,
    depthMultiplier: 1.0,
    lifetimeValue: 0.0,
  }),
  getters: {
    formattedBalance: (state) => {
      const drop = state.dropletBalance;
      const drip = state.dripletBalance;
      return `${drop}.${drip.toString().padStart(2, '0')} 💧`;
    },
  },
  actions: {
    async fetchUser() {
      try {
        const data = await useGlowApi('/glow/v1/user');
        const hasData = !!data;
        if (hasData) {
          this.isAuthenticated = true;
          const hasUsername = typeof data.username === 'string';
          this.username = hasUsername ? data.username : '';
          
          const hasDropletBalance = typeof data.droplet_balance === 'number';
          this.dropletBalance = hasDropletBalance ? data.droplet_balance : 0;
          
          const hasDripletBalance = typeof data.driplet_balance === 'number';
          this.dripletBalance = hasDripletBalance ? data.driplet_balance : 0;
          
          const hasDepthMultiplier = typeof data.depth_multiplier === 'number';
          this.depthMultiplier = hasDepthMultiplier ? data.depth_multiplier : 1.0;
          
          const isLocalStorageAvailable = typeof window !== 'undefined' && window.localStorage;
          const storedLTV = isLocalStorageAvailable ? window.localStorage.getItem('glow_lifetime_value') : null;
          const hasStoredLTV = !!storedLTV;
          if (hasStoredLTV) {
            this.lifetimeValue = parseFloat(storedLTV);
          } else {
            this.lifetimeValue = this.dropletBalance + (this.dripletBalance / 100);
          }
        }
      } catch (error) {
        this.isAuthenticated = false;
        this.username = '';
        this.dropletBalance = 0;
        this.dripletBalance = 0;
        this.depthMultiplier = 1.0;
        this.lifetimeValue = 0.0;
        throw error;
      }
    },
    async submitPost(type: 'drop' | 'thought', content: string, extra?: { link?: string; title?: string; tributary?: string }) {
      try {
        const data = await useGlowApi('/glow/v1/submit', {
          method: 'POST',
          body: {
            type,
            content,
            ...extra,
          },
        });
        const isSubmitSuccessful = data && data.success && data.balance;
        if (isSubmitSuccessful) {
          const hasDropletBalance = typeof data.balance.droplet_balance === 'number';
          this.dropletBalance = hasDropletBalance ? data.balance.droplet_balance : this.dropletBalance;
          
          const hasDripletBalance = typeof data.balance.driplet_balance === 'number';
          this.dripletBalance = hasDripletBalance ? data.balance.driplet_balance : this.dripletBalance;
          
          const hasDepthMultiplier = typeof data.balance.depth_multiplier === 'number';
          this.depthMultiplier = hasDepthMultiplier ? data.balance.depth_multiplier : this.depthMultiplier;
          
          const isLocalStorageAvailable = typeof window !== 'undefined' && window.localStorage;
          if (isLocalStorageAvailable) {
            window.localStorage.setItem('glow_lifetime_value', String(this.lifetimeValue.toFixed(4)));
          }
        }
        return data;
      } catch (error) {
        throw error;
      }
    },
    async boostPost(postId: number) {
      try {
        const data = await useGlowApi('/glow/v1/boost', {
          method: 'POST',
          body: {
            post_id: postId,
          },
        });
        const isBoostSuccessful = data && data.success && data.user_balance;
        if (isBoostSuccessful) {
          const hasDropletBalance = typeof data.user_balance.droplet_balance === 'number';
          this.dropletBalance = hasDropletBalance ? data.user_balance.droplet_balance : this.dropletBalance;
          
          const hasDripletBalance = typeof data.user_balance.driplet_balance === 'number';
          this.dripletBalance = hasDripletBalance ? data.user_balance.driplet_balance : this.dripletBalance;
          
          const hasDepthMultiplier = typeof data.user_balance.depth_multiplier === 'number';
          this.depthMultiplier = hasDepthMultiplier ? data.user_balance.depth_multiplier : this.depthMultiplier;
          
          const earnedDriplets = data.earned_driplets || 15;
          this.lifetimeValue += (earnedDriplets / 100);
          
          const isLocalStorageAvailable = typeof window !== 'undefined' && window.localStorage;
          if (isLocalStorageAvailable) {
            window.localStorage.setItem('glow_lifetime_value', String(this.lifetimeValue.toFixed(4)));
          }
        }
        return data;
      } catch (error) {
        throw error;
      }
    },
    earnDriplets(amount: number) {
      this.dripletBalance += amount;
      const hasDripletRollover = this.dripletBalance >= 100;
      if (hasDripletRollover) {
        this.dropletBalance += Math.floor(this.dripletBalance / 100);
        this.dripletBalance = this.dripletBalance % 100;
      }
      this.lifetimeValue += amount / 100;
      
      const isLocalStorageAvailable = typeof window !== 'undefined' && window.localStorage;
      if (isLocalStorageAvailable) {
        window.localStorage.setItem('glow_driplet_balance', String(this.dripletBalance));
        window.localStorage.setItem('glow_droplet_balance', String(this.dropletBalance));
        window.localStorage.setItem('glow_lifetime_value', String(this.lifetimeValue.toFixed(4)));
      }
    },
  },
});
