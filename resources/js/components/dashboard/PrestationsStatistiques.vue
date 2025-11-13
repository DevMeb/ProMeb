<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Total Prestations -->
    <DashboardCard
      title="Activité totale"
      :value="dashboardData.prestations.length"
      description="Total des prestations"
      icon="📦"
      gradient="from-indigo-500 to-indigo-600"
      textColor="text-indigo-100"
      @click="showPrestationsModal = true"
    />

    <!-- Prestations Non Facturées -->
    <DashboardCard
      title="Prestations en attente de facturation"
      :value="dashboardData.prestations_unbilled.length"
      description="Prestations en attente de facturation"
      icon="⏳"
      gradient="from-orange-400 to-orange-500"
      textColor="text-orange-100"
      @click="showPrestationsUnbilledModal = true"
    />

    <!-- Factures payées -->
    <DashboardCard
      title="Factures payées"
      :value="dashboardData.factures_paid.length"
      description="Factures régularisées"
      icon="📑"
      gradient="from-green-500 to-green-600"
      textColor="text-green-100"
      @click="showFacturesBilledModal = true"
    />

    <!-- Factures non payées -->
    <DashboardCard
      title="Factures non payées"
      :value="dashboardData.factures_unpaid.length"
      description="Factures en attente de facturation"
      icon="⏳"
      gradient="from-red-400 to-red-700"
      textColor="text-orange-100"
      @click="showFacturesUnbilledModal = true"
    />
  </div>

  <FactureFormModal
    v-if="showPrestationsUnbilledModal"
    @close="showPrestationsUnbilledModal = false" 
  />
  
  <PrestationsModal 
    v-if="showPrestationsModal"
    @close="showPrestationsModal = false"
    :prestations="dashboardData.prestations"
  />

  <FacturesModal 
    v-if="showFacturesBilledModal"
    :factures="dashboardData.factures_paid"
    title="Factures payées"
    @close="showFacturesBilledModal = false" 
  />

  <FacturesModal 
    v-if="showFacturesUnbilledModal"
    :factures="dashboardData.factures_unpaid"
    title="Factures non payées"
    @close="showFacturesUnbilledModal = false" 
  />

</template>

<script setup>
import { ref } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import { storeToRefs } from 'pinia';
import { DashboardCard, PrestationsModal, FacturesModal } from '@/components/dashboard';
import { FactureFormModal } from '@/components/factures';

const dashboardStore = useDashboardStore();
const { dashboardData } = storeToRefs(dashboardStore);

console.log(dashboardData.value.prestations)
const showPrestationsModal = ref(false)
const showPrestationsUnbilledModal = ref(false)
const showFacturesBilledModal = ref(false)
const showFacturesUnbilledModal = ref(false)
</script>