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
      gradient="from-emerald-600 to-emerald-700"
    />

    <!-- CA Attendu -->
    <DashboardCard
      title="CA attendu"
      :value="caAttendu"
      description="Revenus prévisionnels"
      icon="📈"
      gradient="from-sky-500 to-sky-600"
    />

    <!-- Différence -->
    <DashboardCard
      title="Écart"
      :value="difference"
      description="Différence CA réel/prévisionnel"
      icon="⚖️"
      :gradient="difference >= 0 ? 'from-emerald-600 to-emerald-700' : 'from-rose-500 to-rose-600'"
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