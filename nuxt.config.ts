// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  ssr: false,
  app: {
    baseURL: process.env.NUXT_APP_BASE_URL || '/'
  },
  devServer: {
    port: 5177,
    host: '0.0.0.0'
  },
  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || '/wp-json'
    }
  },
  nitro: {
    preset: 'github-pages',
    prerender: {
      failOnError: true
    }
  },
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: [
    '@nuxt/fonts',
    'nuxt-gtag',
    '@pinia/nuxt'
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
