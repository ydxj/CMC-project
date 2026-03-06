<template>
  <div>
    <NuxtPage />
  </div>
</template>

<script setup lang="ts">
import { useHead } from 'nuxt/app'

const logoHref = '/logo.png'

useHead({
  link: [
    { rel: 'icon', type: 'image/png', href: logoHref },
    { rel: 'apple-touch-icon', href: logoHref },
  ],
})
</script>
