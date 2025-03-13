import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { notify } from '@/utils';

export const useTauxHorairesStore = defineStore('taux-horaires', () => {
  const tauxHoraires = ref([]);
  const errors = ref({});
  const loading = ref({});

  /*
  const activeFilters = useStorage("prestation-filters", {
    month_year: '',
  });

  function updateFilters(filters) {
    activeFilters.value = filters;
  }

  const isAnyFilterActive = computed(() => {
    return Object.values(activeFilters.value).some(value => value !== "");
  });

  // Application des filtres aux prestations
  const filteredPrestations = ref([]);
  watch([prestations, activeFilters], () => {
    filteredPrestations.value = prestations.value.filter(prestation => {
      const { month_year } = activeFilters.value;

      if (month_year && dayjs(prestation.date).format('YYYY-MM') !== month_year) return false;

      return true;
    });
  }, { deep: true, immediate: true });

  const prestationCount = computed(() => filteredPrestations.value.length);

  const totalHours = computed(() => {
    return filteredPrestations.value
      .reduce((acc, prestation) => acc + prestation.heures, 0)
  });

  const unbilledPrestations = computed(() => {
    return prestations.value.filter((prestation) => !prestation.facture_id);
  });
  */

  function clearErrors(operation) {
    if (operation) {
      errors.value[operation] = null;
    } else {
      errors.value = {};
    }
  }

  function setLoading(operation, state) {
    loading.value[operation] = state;
  }

  async function apiCall({ operation, request, onSuccess }) {
    clearErrors(operation);
    setLoading(operation, true);
    try {
      const response = await request();
      if (onSuccess) onSuccess(response);
      return response;
    } catch (err) {
      if (err.response?.status === 422) {
        errors.value.validationErrors = err.response.data.errors;
      } else {
        errors.value[operation] = err.response?.data?.message || "Une erreur est survenue.";
        notify('error', errors.value[operation]);
      }
      throw err;
    } finally {
      setLoading(operation, false);
    }
  }

  async function fetchTauxHoraires() {
    return apiCall({
      operation: 'fetch',
      request: () => axios.get('/api/taux-horaires'),
      onSuccess: (response) => {
        // Si l'API renvoie les données dans une clé data, ajustez ici
        tauxHoraires.value = response.data.taux_horaires;
      },
    });
  }

  async function addTauxHoraire(client) {
    return apiCall({
      operation: 'add',
      request: () => axios.post('/api/taux-horaires', client),
      onSuccess: (response) => {
        tauxHoraires.value.push(response.data.taux_horaire);
        notify('success', response.data.message);
      },
    });
  }

  async function updateTauxHoraire(tauxHoraire) {
    return apiCall({
      operation: 'update',
      request: () => axios.put(`/api/taux-horaires/${tauxHoraire.id}`, tauxHoraire),
      onSuccess: (response) => {
        const index = tauxHoraires.value.findIndex(th => th.id === tauxHoraire.id);
        if (index !== -1) {
            tauxHoraires.value[index] = response.data.taux_horaire;
        }
        notify('success', response.data.message);
      },
    });
  }

  async function deleteTauxHoraire(tauxHoraireId) {
    return apiCall({
      operation: 'delete',
      request: () => axios.delete(`/api/taux-horaires/${tauxHoraireId}`),
      onSuccess: (response) => {
        tauxHoraires.value = tauxHoraires.value.filter(th => th.id !== tauxHoraireId);
        notify('success', response.data.message);
      },
    });
  }

  return {
    tauxHoraires,
    errors,
    loading,
    fetchTauxHoraires,
    addTauxHoraire,
    updateTauxHoraire,
    deleteTauxHoraire,
    clearErrors,
  };
});
