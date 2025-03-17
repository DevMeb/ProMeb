<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- CA Facturé -->
    <DashboardCard
      title="CA facturé"
      :value="caBilled"
      :taxe="taxe"
      :afterTaxe="caBilledWithTax"
      :badge="taxe"
      badgeColor="text-red-500"
      description="Revenus confirmés"
      icon="💶"
      gradient="from-green-600 to-green-700"
    />

    <!-- CA Attendu -->
    <DashboardCard
      title="CA attendu"
      :value="caAttendu"
      description="Revenus prévisionnels"
      icon="📈"
      gradient="from-amber-500 to-amber-600"
    />

    <!-- Différence -->
    <DashboardCard
      title="Écart"
      :value="difference"
      description="Différence CA réel/prévisionnel"
      icon="⚖️"
      :gradient="difference >= 0 ? 'from-green-600 to-green-700' : 'from-red-500 to-red-600'"
      :isDifference="true"
    />
  </div>
</template>

<script setup>
import { useDashboardStore } from '@/stores/dashboard';
import { storeToRefs } from 'pinia';
import DashboardCard from '@/components/dashboard/DashboardCard.vue';

const dashboardStore = useDashboardStore();
const { 
  dashboardData,
  taxe, 
  caBilledWithTax,
  caBilled,
  caAttendu,
  difference,
} = storeToRefs(dashboardStore);
</script>