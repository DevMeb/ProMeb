<template>
    <div class="fixed inset-0 bg-black/50 backdrop-blur-md flex items-center justify-center z-50">
      <!-- Overlay cliquable pour fermer la modale -->
      <div @click.self="close" class="absolute inset-0"></div>
  
      <!-- Boîte de dialogue -->
      <div class="bg-white p-6 rounded-lg shadow-lg w-[24rem] animate-fade-in transform transition-transform scale-95">
        <!-- En-tête -->
        <div class="flex items-center space-x-3">
          <span class="text-red-600 text-2xl">⚠️</span>
          <h2 class="text-lg font-semibold text-red-600">Confirmation de suppression</h2>
        </div>
  
        <!-- Contenu -->
        <p class="text-gray-700 mt-3">
          Êtes-vous sûr de vouloir supprimer le client
          <strong class="text-gray-900">{{ client.nom }}</strong> ?
        </p>
        <p class="text-sm text-gray-500 mt-2">
          Cette action est <span class="font-semibold text-red-500">irréversible</span>.
        </p>
  
        <!-- Actions -->
        <div class="flex justify-end mt-6 space-x-3">
          <button 
            @click="close"
            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-100 transition"
          >
            Annuler
          </button>
          <button 
            @click="destroyClient"
            class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition flex items-center"
          >
            <span class="mr-2">🗑️</span> Supprimer
          </button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { useClientsStore } from '@/stores/clients';
  
  const props = defineProps({
    client: {
      type: Object,
      required: true,
    },
  });
  
  const emit = defineEmits(["close"]);
  
  const clientsStore = useClientsStore();
  const { deleteClient } = clientsStore;
  
  // Fonction de fermeture de la modal
  function close() {
    emit("close");
  }
  
  // Fonction pour supprimer le client
  async function destroyClient() {
    await deleteClient(props.client.id);
    close();
  }
  </script>
  