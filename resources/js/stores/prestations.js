import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { notify } from '@/utils';
import dayjs from 'dayjs';
import { creerApiCall } from '@/stores/apiCall';

export const usePrestationsStore = defineStore('prestations', () => {
  const prestations = ref([]);
  const errors = ref({});
  const loading = ref({});

  const { apiCall, clearErrors } = creerApiCall({ errors, loading });

  // Filtres pour les prestations (par exemple, filtrer par date et adresse)
  const activeFilters = ref({
    month_year: '',
    client_id: '',
    taux_horaire_id: '',
  });

  // Mise à jour des filtres
  function updateFilters(filters) {
    activeFilters.value = filters;
  }

  // Vérification si des filtres sont actifs
  const isAnyFilterActive = computed(() => {
    return Object.values(activeFilters.value).some(value => value !== "");
  });

  // Application des filtres aux prestations
  const filteredPrestations = ref([]);
  watch([prestations, activeFilters], () => {
    filteredPrestations.value = prestations.value.filter(prestation => {
      const { month_year, client_id, taux_horaire_id } = activeFilters.value;

      if (month_year && dayjs(prestation.date).format('YYYY-MM') !== month_year) return false;

      if (client_id && prestation.client_id !== client_id) return false;

      if (taux_horaire_id && prestation.taux_horaire_id !== taux_horaire_id) return false;


      return true;
    });
  }, { deep: true, immediate: true });

  const prestationCount = computed(() => filteredPrestations.value.length);

  const totalHours = computed(() => {
    return filteredPrestations.value
      .reduce((acc, prestation) => acc + parseFloat(prestation.heures), 0)
  });

  const unbilledPrestations = computed(() => {
    return prestations.value.filter((prestation) => prestation.facture_id == null);
  });

  async function fetchPrestations() {
    return apiCall({
      operation: 'fetch',
      request: () => axios.get('/api/prestations'),
      onSuccess: (response) => {
        // Si l'API renvoie les données dans une clé data, ajustez ici
        prestations.value = response.data.prestations;
      },
    });
  }

  async function addPrestation(prestation) {
    return apiCall({
      operation: 'add',
      request: () => axios.post('/api/prestations', prestation),
      onSuccess: (response) => {
        prestations.value.push(response.data.prestation);
        notify('success', response.data.message);
      },
    });
  }

  async function updatePrestation(prestation) {
    return apiCall({
      operation: 'update',
      request: () => axios.put(`/api/prestations/${prestation.id}`, prestation),
      onSuccess: (response) => {
        const index = prestations.value.findIndex(p => p.id === prestation.id);
        if (index !== -1) {
          prestations.value[index] = response.data.prestation;
        }
        notify('success', response.data.message);
      },
    });
  }

  async function deletePrestation(prestationId) {
    return apiCall({
      operation: 'delete',
      request: () => axios.delete(`/api/prestations/${prestationId}`),
      onSuccess: (response) => {
        prestations.value = prestations.value.filter(p => p.id !== prestationId);
        notify('success', response.data.message);
      },
    });
  }

  return {
    prestations,
    errors,
    loading,
    activeFilters,
    updateFilters,
    filteredPrestations,
    isAnyFilterActive,
    fetchPrestations,
    addPrestation,
    updatePrestation,
    deletePrestation,
    clearErrors,
    prestationCount,
    totalHours,
    unbilledPrestations,
  };
});
