<template>
    <div class="mt-6">
  
      <!-- État de chargement -->
      <div v-if="loading.fetch" class="flex justify-center my-6">
        <div class="animate-spin inline-block w-6 h-6 border-4 border-white border-t-transparent rounded-full"></div>
        <p class="text-white text-lg font-medium ml-2">Chargement des clients...</p>
      </div>
  
      <!-- Erreurs -->
      <div v-else-if="errors.fetch" class="flex justify-center my-6 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
        <span class="text-xl">❌</span>
        <p class="text-lg font-medium ml-2">{{ errors.fetch }}</p>
      </div>
  
      <!-- Aucun résultat -->
      <div v-else-if="clients.length === 0" class="flex justify-center my-6 bg-gray-800 px-6 py-4 rounded-lg shadow-lg">
        <p class="text-gray-300 text-lg">📭 Aucun client trouvée.</p>
      </div>
  
      <!-- Liste des clients -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
        <ClientListItem
          v-for="client in clients"
          :client="client"
          :key="client.id"
        />
      </div>
    </div>
  </template>
  
  <script setup>
  import { onMounted } from 'vue';
  import { storeToRefs } from 'pinia';
  import { useClientsStore } from '@/stores/clients';
  import { ClientListItem } from '@/components/clients/';
  
  const clientStore = useClientsStore();
  const { fetchClients, clearErrors } = clientStore;
  const { clients, errors, loading } = storeToRefs(clientStore);
  
  onMounted(() => {
    fetchClients();
    clearErrors("fetch");
  });
  </script>
  