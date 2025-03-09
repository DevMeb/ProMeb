<template>
    <!-- Section Factures (prestations facturées regroupées par facture) -->
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-2xl font-semibold text-white mb-4">Factures</h2>
        <div v-if="dashboardData.factures.length === 0" class="text-gray-400">
          Aucune facture trouvée.
        </div>
        <div v-else class="space-y-6">
          <FacturesListItem v-for="facture in dashboardData.factures" :key="facture.id" :facture="facture" />
        </div>
    </div>

</template>
<script setup>
import { FacturesListItem } from '@/components/dashboard';
import { useDashboardStore } from '@/stores/dashboard';
import { storeToRefs } from 'pinia';

const dashbordStore = useDashboardStore();
const { dashboardData } = storeToRefs(dashbordStore);

</script>