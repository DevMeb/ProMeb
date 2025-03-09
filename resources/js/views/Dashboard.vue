<template>
  <div class="p-6 bg-gray-900 min-h-screen">
    <h1 class="text-3xl font-bold text-white mb-8">Tableau de bord</h1>

    <!-- Sélecteur de période -->
    <div class="mb-8">
      <label for="period" class="text-white font-semibold mr-2">Période :</label>
      <input
        type="month"
        id="period"
        v-model="period"
        @change="onPeriodChange"
        class="p-2 rounded border border-gray-600 bg-gray-800 text-white"
      />
    </div>

    <!-- Gestion des états de chargement et d'erreur -->
    <div v-if="loading" class="text-center text-gray-300">Chargement...</div>
    <div v-else-if="error" class="text-center text-red-400">{{ error }}</div>
    <div v-else-if="dashboardData">
      
      <PrestationsStatistiques />

      <CAStatistiques />

      <FacturesPrestationsList />

      <PrestationsNonFactured />
    </div>
  </div>

</template>

<script setup>
import { onMounted } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import { storeToRefs } from 'pinia';
import { formatDate } from '@/utils';
import { PrestationsStatistiques, CAStatistiques, FacturesPrestationsList, PrestationsNonFactured } from '@/components/dashboard';
const dashboardStore = useDashboardStore();
const { dashboardData, period, loading, error } = storeToRefs(dashboardStore);
const { fetchDashboard } = dashboardStore;


function onPeriodChange() {
  fetchDashboard();
}

onMounted(() => {
  fetchDashboard();
});
</script>
