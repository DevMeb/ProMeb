<template>
<!-- Section Prestations non facturées -->
<div class="bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
    <h2 class="text-2xl font-semibold text-white mb-4">Prestations non facturées</h2>
    <div v-if="dashboardData.prestations_non_factured.length === 0" class="text-gray-400">
      Aucune prestations à facturée ou toutes les prestations ont été facturées.
    </div>
    <div v-else class="space-y-4">
      <div v-for="prestation in dashboardData.prestations_non_factured" :key="prestation.id" class="bg-gray-900 p-4 rounded-md flex items-center">
        <input type="checkbox" v-model="selectedPrestations" :value="prestation" class="mr-4 h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
        <div>
          <p class="text-gray-300 text-sm"><strong>ID:</strong> {{ prestation.id }}</p>
          <p class="text-gray-300 text-sm"><strong>Date:</strong> {{ formatDate(prestation.date) }}</p>
          <p class="text-gray-300 text-sm"><strong>Heures:</strong> {{ prestation.heures }} h</p>
          <p class="text-gray-300 text-sm"><strong>Horaires:</strong> {{ prestation.horaires }}</p>
          <p class="text-gray-300 text-sm"><strong>Adresse:</strong> {{ prestation.adresse }}</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Bouton pour créer une facture pour les prestations non facturées sélectionnées -->
  <div v-if="dashboardData.prestations_non_factured.length > 0" class="mt-6 flex justify-end">
    <button @click="createInvoice" :disabled="selectedPrestations.length === 0" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">
      Créer une facture pour les prestations sélectionnées ({{ selectedPrestations.length }})
    </button>

    <button @click="showFormModal = true" class="bg-indigo-500 text-white px-4 py-2 rounded-md">Ajouter une facture</button>
  </div>

  <FactureFormModal v-if="showFormModal" @close="showFormModal = false" />

</template>

<script setup>
import { ref } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import { storeToRefs } from 'pinia';
import { formatDate } from '@/utils';
import { FactureFormModal } from '@/components/factures';

const dashbordStore = useDashboardStore();
const { dashboardData } = storeToRefs(dashbordStore);

const selectedPrestations = ref([]);

const showFormModal = ref(false);

</script>