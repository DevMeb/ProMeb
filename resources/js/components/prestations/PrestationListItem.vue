<template>
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg flex flex-col space-y-4 hover:shadow-xl transition-all transform hover:scale-[1.02] border border-gray-700">
      <!-- En-tête -->
      <div class="flex justify-between items-center border-b pb-3">
        <h2 class="text-xl font-semibold text-white flex items-center gap-2">
          <!-- Afficher une icône spécifique pour une prestation -->
          <span class="text-indigo-300 text-2xl">💼</span>
          <!-- Affichage de la date de prestation formatée -->
          Prestation N° {{ prestation.id }}
        </h2>
      </div>
  
      <!-- Informations générales -->
      <div class="bg-gray-900 p-4 rounded-md space-y-3">
        <p class="text-gray-300 text-sm flex items-center">
            📅 <span class="ml-2 font-semibold text-white">Date :</span>
            <span class="text-indigo-400 ml-1">{{ formatDate(prestation.date_prestation) }}</span>
          </p>
        <p class="text-gray-300 text-sm flex items-center">
          ⏱️ <span class="ml-2 font-semibold text-white">Heures :</span>
          <span class="text-indigo-400 ml-1">{{ prestation.nombre_heures }} h</span>
        </p>
        <p class="text-gray-300 text-sm flex items-center">
          📍 <span class="ml-2 font-semibold text-white">Adresse :</span>
          <span class="text-indigo-400 ml-1">{{ prestation.adresse }}</span>
        </p>
      </div>
  
      <!-- Dates clés -->
      <div class="bg-gray-900 p-4 rounded-lg flex flex-col space-y-2">
        <p class="text-gray-300 text-sm flex items-center">
          🕒 <span class="ml-2 font-semibold text-white">Créé le :</span>
          <span class="ml-1 text-indigo-300">{{ formatDate(prestation.created_at) }}</span>
        </p>
        <p class="text-gray-300 text-sm flex items-center">
          🔄 <span class="ml-2 font-semibold text-white">Mis à jour le :</span>
          <span class="ml-1 text-indigo-300">{{ formatDate(prestation.updated_at) }}</span>
        </p>
      </div>
  
      <!-- Actions -->
      <div class="flex justify-center gap-3 mt-4">
        <button @click="openFormModal" class="btn-action bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded flex items-center">
          ✏️ Modifier
        </button>
        <button @click="openDeleteModal" class="btn-action bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded flex items-center">
          🗑️ Supprimer
        </button>
      </div>
    </div>
  
    <!-- Modals -->
    <PrestationFormModal v-if="showFormModal" :prestation="prestation" @close="closeFormModal" />
    <PrestationDeleteModal v-if="showDeleteModal" :prestation="prestation" @close="closeDeleteModal" />
  </template>
  
  <script setup>
  import { ref } from "vue";
  import { PrestationFormModal, PrestationDeleteModal } from "@/components/prestations/";
  import dayjs from "dayjs";
  
  const props = defineProps({
    prestation: {
      type: Object,
      required: true,
    },
  });
  
  const emit = defineEmits(["close"]);
  
  const showFormModal = ref(false);
  const showDeleteModal = ref(false);
  
  function openFormModal() {
    showFormModal.value = true;
  }
  
  function closeFormModal() {
    showFormModal.value = false;
  }
  
  function openDeleteModal() {
    showDeleteModal.value = true;
  }
  
  function closeDeleteModal() {
    showDeleteModal.value = false;
  }
  
  // Fonction utilitaire pour formater les dates
  function formatDate(date) {
    return dayjs(date).format('DD/MM/YYYY'); // format dd/mm/yyyy
  }
  </script>
  