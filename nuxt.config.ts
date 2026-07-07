// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBaseUrl: 'https://api.glowitheflow.com'
    }
  },
  nitro: {
    preset: 'github-pages'
  },
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: [
    '@nuxt/fonts',
    'nuxt-gtag'
  ],
  gtag: {
    id: 'G-SYYZTC5HFC'
  },
  postcss: {
    plugins: {
      '@tailwindcss/postcss': {}
    }
  },
  css: ['~/assets/css/main.css']
})
