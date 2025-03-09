<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Prestations -->
    <DashboardCard
      title="Activité totale"
      :value="dashboardData.prestationsDetails.count_prestations"
      description="Total des interventions"
      icon="📦"
      gradient="from-indigo-500 to-indigo-600"
      textColor="text-indigo-100"
    />

    <!-- Prestations Facturées -->
    <DashboardCard
      title="Prestations soldées"
      :value="dashboardData.prestationsDetails.count_prestations_factured"
      description="Factures régularisées"
      icon="📑"
      gradient="from-green-500 to-green-600"
      textColor="text-green-100"
      badge="✔️ Clôturé"
    />

    <!-- Prestations Non Facturées -->
    <DashboardCard
      title="En attente"
      :value="dashboardData.prestationsDetails.count_prestations_non_factured"
      description="Factures à établir"
      icon="⏳"
      gradient="from-orange-400 to-orange-500"
      textColor="text-orange-100"
      badge="⚠️ En suspens"
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