<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- CA Facturé -->
    <DashboardCard
      title="CA facturé"
      :value="dashboardData.caDetails.ca_facture"
      description="Revenus confirmés"
      icon="💶"
      gradient="from-blue-600 to-blue-700"
      textColor="text-blue-100"
    />

    <!-- CA Attendu -->
    <DashboardCard
      title="CA attendu"
      :value="dashboardData.caDetails.ca_attendu"
      description="Revenus prévisionnels"
      icon="📈"
      gradient="from-amber-500 to-amber-600"
      textColor="text-amber-100"
    />

    <!-- Différence -->
    <DashboardCard
      title="Écart"
      :value="dashboardData.caDetails.difference"
      description="Différence CA réel/prévisionnel"
      icon="⚖️"
      :gradient="dashboardData.caDetails.difference >= 0 ? 'from-green-600 to-green-700' : 'from-red-500 to-red-600'"
      :textColor="dashboardData.caDetails.difference >= 0 ? 'text-green-100' : 'text-red-100'"
      :isDifference="true"
    />
  </div>
</template>

<script setup>
import { useDashboardStore } from '@/stores/dashboard';
import { storeToRefs } from 'pinia';
import DashboardCard from '@/components/dashboard/DashboardCard.vue';

const dashboardStore = useDashboardStore();
const { dashboardData } = storeToRefs(dashboardStore);
</script>