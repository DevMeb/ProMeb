import { defineStore } from 'pinia';
import axios from 'axios';
import { ref } from 'vue';

export const useDashboardStore = defineStore('dashboard', () => {
  const dashboardData = ref(null);
  const period = ref(new Date().toISOString().substring(0, 7));
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

  return { dashboardData, period, loading, error, fetchDashboard };
});
