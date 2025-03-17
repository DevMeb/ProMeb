<template>
  <div class="p-6 bg-gray-900 min-h-screen">
    <h1 class="text-3xl font-bold text-white mb-8">Tableau de bord</h1>

    <!-- Sélecteur de période -->
    <div class="mb-8 flex flex-wrap gap-4 items-center">
      <div>
        <label for="period" class="text-white font-semibold mr-2">Période :</label>
        <input
          type="month"
          id="period"
          v-model="period"
          @change="onPeriodChange"
          class="p-2 rounded border border-gray-600 bg-gray-800 text-white"
        />
      </div>

      <div>
        <label for="taxe" class="text-white font-semibold mr-2">Taxe (%) :</label>
        <input
          type="number"
          id="taxe"
          v-model="taxe"
          min="0"
          class="p-2 rounded border border-gray-600 bg-gray-800 text-white w-24 text-center"
        />
      </div>
    </div>

    <!-- Gestion des états de chargement et d'erreur -->
    <div v-if="loading" class="text-center text-gray-300">Chargement...</div>
    <div v-else-if="error" class="text-center text-red-400">{{ error }}</div>
    <div v-else-if="dashboardData && dashboardData.prestations && dashboardData.prestations.length > 0">
      <PrestationsStatistiques />
      <CAStatistiques />
    </div>
    <div v-else class="text-center text-gray-400">
      📉 Aucune donnée disponible pour ce mois.
    </div>
  </div>

</template>

<script setup>
import { onMounted } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import { storeToRefs } from 'pinia';
import { PrestationsStatistiques, CAStatistiques } from '@/components/dashboard';
const dashboardStore = useDashboardStore();
const { dashboardData, period, taxe, loading, error } = storeToRefs(dashboardStore);
const { fetchDashboard } = dashboardStore;

function onPeriodChange() {
  fetchDashboard();
}

onMounted(() => {
  fetchDashboard();
});
</script>
