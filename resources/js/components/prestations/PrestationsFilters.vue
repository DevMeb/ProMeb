<template>
  <div class="bg-gray-800 p-4 rounded-lg shadow-lg flex flex-col sm:flex-row gap-4">
    <!-- Instruction pour l'utilisateur -->
    <div class="flex-1">
      <p class="text-gray-300 text-sm mb-2">
        Utilisez ce filtre pour afficher les prestations par mois et année.
      </p>
    </div>
    <!-- Filtrer par mois & année -->
    <div>
      <label class="text-sm text-gray-300 font-semibold">Mois & Année :</label>
      <input type="month" v-model="activeFilters.month_year" class="filter-input" />
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

watch(activeFilters, (newFilters) => {
  updateFilters(newFilters);
}, { deep: true });

const resetFilters = () => {
  updateFilters({
    month_year: "",
    avec_facture: false,
  });
};
</script>
