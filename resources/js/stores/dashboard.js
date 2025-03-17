import { defineStore } from 'pinia';
import axios from 'axios';
import { ref, computed } from 'vue';

export const useDashboardStore = defineStore('dashboard', () => {
  const dashboardData = ref(null);
  const period = ref(new Date().toISOString().substring(0, 7));
  const taxe = ref(0);
  const loading = ref(false);
  const error = ref(null);

  async function fetchDashboard() {
    loading.value = true;
    error.value = null;
    try {
      // On passe la période (ex: "2025-03") en paramètre
      const response = await axios.get('/api/dashboard', {
        params: { month: period.value },
      });
      dashboardData.value = response.data;
    } catch (err) {
      error.value = err.response?.data?.error || err.message;
    } finally {
      loading.value = false;
    }
  }

  const caBilled = computed(() => {
    return dashboardData.value.factures_paid.reduce(
      (total, facture) => total + facture.montant_total,
      0
    );
  });

  const caUnpaid = computed(() => {
    return dashboardData.value.factures_unpaid.reduce(
      (total, facture) => total + facture.montant_total,
      0
    );
  });

  const caUnbilled = computed(() => {
    return dashboardData.value.prestations_unbilled.reduce(
      (total, prestation) => total + prestation.heures * (prestation.taux_horaire?.taux ?? 0),
      0
    );
  });

  const caAttendu = computed(() => {
    return caBilled.value + caUnpaid.value + caUnbilled.value;
  });

  const difference = computed(() => {
    return caBilled.value - caAttendu.value;
  });

  const caBilledWithTax = computed(() => {
    if (!dashboardData.value) return 0;
    
    return parseFloat((caBilled.value * (1 - taxe.value / 100)).toFixed(2));
  });

  function factureFromUnpaidToPaid(facture) 
  {
    dashboardData.value.factures_unpaid = dashboardData.value.factures_unpaid.filter(f => f.id !== facture.id)
    dashboardData.value.factures_paid.push(facture)
  }

  return { 
    dashboardData,
    period,
    loading,
    error,
    fetchDashboard,
    factureFromUnpaidToPaid,
    caBilledWithTax,
    taxe,
    caBilled,
    caAttendu,
    difference
 }
});
