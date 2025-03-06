<template>
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-4">Détail de la Prestation</h1>
      <div v-if="loading" class="text-center">Chargement...</div>
      <div v-if="error" class="text-red-500">{{ error }}</div>
      <div v-if="prestation">
        <p><strong>Date :</strong> {{ prestation.date_prestation }}</p>
        <p><strong>Nombre d'heures :</strong> {{ prestation.nombre_heures }} h</p>
        <p><strong>Adresse :</strong> {{ prestation.adresse }}</p>
        <div class="mt-4">
          <router-link :to="`/prestations/${prestation.id}/edit`" class="text-blue-500">
            Modifier cette prestation
          </router-link>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import { useRoute } from 'vue-router';
  import { usePrestationsStore } from '@/stores/prestations';
  
  const route = useRoute();
  const prestationsStore = usePrestationsStore();
  const prestation = ref(null);
  const loading = ref(false);
  const error = ref(null);
  
  const fetchPrestationData = async () => {
    loading.value = true;
    error.value = null;
    try {
      prestation.value = await prestationsStore.fetchPrestation(route.params.id);
    } catch (e) {
      error.value = e.response?.data?.error || e.message;
    } finally {
      loading.value = false;
    }
  };
  
  onMounted(fetchPrestationData);
  </script>
  