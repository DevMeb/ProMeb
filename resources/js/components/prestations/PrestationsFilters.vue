<template>
    <div class="bg-gray-800 p-4 rounded-lg shadow-lg flex flex-col sm:flex-row gap-4">
      <!-- 🔍 Filtrer par date de prestation -->
      <div>
        <label class="text-sm text-gray-300 font-semibold">Date :</label>
        <input 
          type="date" 
          v-model="activeFilters.date_prestation" 
          class="filter-input"
        />
      </div>
  
      <!-- 🔍 Filtrer par adresse -->
      <div>
        <label class="text-sm text-gray-300 font-semibold">Adresse :</label>
        <input 
          type="text" 
          v-model="activeFilters.adresse" 
          placeholder="Rechercher une adresse..." 
          class="filter-input"
        />
      </div>
  
      <!-- 🔍 Optionnel : Filtrer par nombre d'heures (min et max) -->
      <div>
        <label class="text-sm text-gray-300 font-semibold">Heures min :</label>
        <input 
          type="number" 
          v-model="activeFilters.min_hours" 
          placeholder="Min" 
          class="filter-input w-24"
        />
      </div>
      <div>
        <label class="text-sm text-gray-300 font-semibold">Heures max :</label>
        <input 
          type="number" 
          v-model="activeFilters.max_hours" 
          placeholder="Max" 
          class="filter-input w-24"
        />
      </div>
  
      <!-- Bouton de réinitialisation -->
      <button @click="resetFilters" v-if="isAnyFilterActive" class="btn-secondary">
        Réinitialiser
      </button>
    </div>
  </template>
  
  <script setup>
  import { watch } from "vue";
  import { usePrestationsStore } from "@/stores/prestations";
  import { storeToRefs } from "pinia";
  
  const prestationsStore = usePrestationsStore();
  const { activeFilters, isAnyFilterActive } = storeToRefs(prestationsStore);
  const { updateFilters } = prestationsStore;
  
  // Met à jour automatiquement les filtres dans le store lorsqu'ils changent
  watch(activeFilters, (newFilters) => {
    updateFilters(newFilters);
  }, { deep: true });
  
  // Réinitialisation des filtres
  const resetFilters = () => {
    updateFilters({
      date_prestation: "",
      adresse: "",
      min_hours: "",
      max_hours: "",
    });
  };
  </script>
  