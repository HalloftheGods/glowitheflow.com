import { defineNuxtModule } from '@nuxt/kit'
import piniaModule from '@pinia/nuxt'

const pinia = defineNuxtModule({
  meta: {
    name: 'pinia',
    configKey: 'pinia',
    compatibility: {
      nuxt: '>=3.0.0'
    }
  },
  setup(options, nuxt) {
    const fn = (piniaModule as any).default || piniaModule
    return fn(options, nuxt)
  }
})

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  ssr: false,
  runtimeConfig: {
    public: {
      apiBaseUrl: 'https://api.glowitheflow.com'
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
    pinia
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
