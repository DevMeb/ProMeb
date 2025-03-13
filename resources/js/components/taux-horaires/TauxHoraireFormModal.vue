<template>
    <div class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50">
      <!-- Zone cliquable en arrière-plan pour fermer la modal -->
      <div @click.self="closeModal" class="fixed inset-0"></div>
  
      <div class="bg-white p-6 rounded-xl shadow-xl w-[90%] sm:w-96 transform transition-all animate-fade-in">
        <div class="flex items-center justify-between border-b pb-2">
          <h2 class="text-xl font-semibold text-gray-800 flex items-center">
            <span v-if="tauxHoraireData.id" class="mr-2">✏️</span>
            <span v-else class="mr-2">➕</span>
            {{ tauxHoraireData.id ? 'Modifier le taux horaire' : 'Ajouter un taux horaire' }}
          </h2>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-700 transition">✖️</button>
        </div>
  
        <form @submit.prevent="submitForm" class="mt-4 space-y-4">
          <!-- Taux Horaire -->
          <div>
            <label for="taux" class="block text-sm font-medium text-gray-700">Taux Horaire (€ / h) *</label>
            <input
              ref="firstInput"
              type="number"
              id="taux"
              step="0.01"
              v-model="tauxHoraireData.taux"
              class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :class="{ 'border-red-500': errors.validationErrors?.taux }"
            />
            <p v-if="errors.validationErrors?.taux" class="text-red-500 text-xs mt-1">
              {{ errors.validationErrors.taux.join(', ') }}
            </p>
          </div>
  
          <!-- Boutons -->
          <div class="flex justify-end space-x-3 mt-4">
            <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 transition">
              Annuler
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-indigo-600 text-white rounded flex items-center hover:bg-indigo-700 transition"
              :disabled="tauxHoraireData.id ? loading.update : loading.add"
            >
              <span v-if="tauxHoraireData.id ? loading.update : loading.add" class="animate-spin mr-2">⏳</span>
              {{ tauxHoraireData.id ? 'Mettre à jour' : 'Ajouter' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, watchEffect } from 'vue';
  import { useTauxHorairesStore } from '@/stores/taux-horaires';
  import { storeToRefs } from 'pinia';
  
  // Définir les propriétés et l'émission d'événements
  const props = defineProps({
    tauxHoraire: Object,
  });
  const emit = defineEmits(['close']);
  
  // Accès au store des taux horaires
  const tauxHoraireStore = useTauxHorairesStore();
  const { addTauxHoraire, updateTauxHoraire, clearErrors } = tauxHoraireStore;
  const { errors, loading } = storeToRefs(tauxHoraireStore);
  
  // Données réactives pour le formulaire
  const tauxHoraireData = ref({
    id: null,
    taux: '',
  });
  
  // Initialiser les données avec les props si elles existent (édition) ou avec des valeurs par défaut
  watchEffect(() => {
    tauxHoraireData.value = props.tauxHoraire
      ? { ...props.tauxHoraire }
      : {
          id: null,
          taux: '',
        };
  });
  
  // Soumission du formulaire
  const submitForm = async () => {
    const success = tauxHoraireData.value.id
      ? await updateTauxHoraire(tauxHoraireData.value)
      : await addTauxHoraire(tauxHoraireData.value);
    if (success) {
      closeModal();
    }
  };
  
  // Fermeture de la modal
  const closeModal = () => {
    clearErrors('validationErrors');
    emit('close');
  };
  </script>
  