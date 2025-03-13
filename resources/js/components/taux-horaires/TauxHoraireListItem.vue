<template>
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg flex flex-col space-y-4 hover:shadow-xl transition-all transform hover:scale-[1.02] border border-gray-700">
      <!-- En-tête -->
      <div class="flex justify-between items-center border-b pb-3">
        <h2 class="text-xl font-semibold text-white flex items-center gap-2">
          <!-- Icône taux horaire -->
          <span class="text-indigo-300 text-2xl">⏳</span>
          <!-- Nom du taux horaire -->
          {{ tauxHoraire.nom }}
        </h2>
      </div>
  
      <!-- Informations générales -->
      <div class="bg-gray-900 p-4 rounded-md space-y-3">
        <p class="text-gray-300 text-sm flex items-center">
          💰 <span class="ml-2 font-semibold text-white">Tarif :</span>
          <span class="text-indigo-400 ml-1">{{ tauxHoraire.taux }} €/h</span>
        </p>
      </div>
  
      <!-- Dates clés -->
      <div class="bg-gray-900 p-4 rounded-lg flex flex-col space-y-2">
        <p class="text-gray-300 text-sm flex items-center">
          🕒 <span class="ml-2 font-semibold text-white">Ajouté le :</span>
          <span class="ml-1 text-indigo-300">{{ tauxHoraire.created_at }}</span>
        </p>
        <p class="text-gray-300 text-sm flex items-center">
          🔄 <span class="ml-2 font-semibold text-white">Mis à jour le :</span>
          <span class="ml-1 text-indigo-300">{{ tauxHoraire.updated_at }}</span>
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
    <TauxHoraireFormModal v-if="showFormModal" :tauxHoraire="tauxHoraire" @close="closeFormModal" />
    <TauxHoraireDeleteModal v-if="showDeleteModal" :tauxHoraire="tauxHoraire" @close="closeDeleteModal" />
  </template>
  
  <script setup>
  import { ref } from "vue";
  import { TauxHoraireFormModal, TauxHoraireDeleteModal } from "@/components/taux-horaires/";
  
  const props = defineProps({
    tauxHoraire: {
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
  </script>
  