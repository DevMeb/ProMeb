<template>
    <div>
      <!-- Afficher la Navbar uniquement si l'utilisateur est authentifié -->
      <Navbar v-if="authStore.isAuthenticated" class="fixed top-0 left-0 w-full z-50" />
      <!-- Ajouter un padding en haut si la Navbar est affichée pour éviter qu'elle ne chevauche le contenu -->
      <div :class="authStore.isAuthenticated ? 'pt-16' : ''">
        <router-view />
        <PwaUpdateBanner />
      </div>
    </div>
  </template>
  
  <script setup>
  import { useAuthStore } from '@/stores/auth';
  import PwaUpdateBanner from '@/components/pwa/PwaUpdateBanner.vue';
  import Navbar from '@/components/Navbar.vue';
  
  const authStore = useAuthStore();
  </script>
  