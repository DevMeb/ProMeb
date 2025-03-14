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

    <!-- Prestations Facturées -->
    <DashboardCard
      title="Factures payées"
      :value="dashboardData.factures_paid.length"
      description="Factures régularisées"
      icon="📑"
      gradient="from-green-500 to-green-600"
      textColor="text-green-100"
      badge="✔️ Payées"
      @click="showPrestationsBilledModal = true"
    />

    <!-- Prestations Non Facturées -->
    <DashboardCard
      title="Prestations en attente de facturation"
      :value="dashboardData.prestations_unbilled.length"
      description="Prestations en attente de facturation"
      icon="⏳"
      gradient="from-orange-400 to-orange-500"
      textColor="text-orange-100"
      badge="⚠️ En attente de facturation"
      @click="showPrestationsUnbilledModal = true"
    />

    <DashboardCard
      title="Factures en attente de paiement"
      :value="dashboardData.factures_unpaid.length"
      description="Prestations en attente de facturation"
      icon="⏳"
      gradient="from-red-400 to-red-700"
      textColor="text-orange-100"
      badge="⚠️ En attente de paiement"
      @click="showPrestationsUnbilledModal = true"
    />
  </div>

  <FactureFormModal v-if="showPrestationsUnbilledModal" @close="showPrestationsUnbilledModal = false" />
  <PrestationsModal v-if="showPrestationsModal" @close="showPrestationsModal = false" />
  <FacturesModal v-if="showPrestationsBilledModal" @close="showPrestationsBilledModal = false" />

</template>

<script setup>
import { ref } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import { storeToRefs } from 'pinia';
import { DashboardCard, PrestationsModal, FacturesModal } from '@/components/dashboard';
import { FactureFormModal } from '@/components/factures';

const dashboardStore = useDashboardStore();
const { dashboardData } = storeToRefs(dashboardStore);

const showPrestationsModal = ref(false)
const showPrestationsBilledModal = ref(false)
const showPrestationsUnbilledModal = ref(false)
</script>