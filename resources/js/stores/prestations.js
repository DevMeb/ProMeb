import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { notify } from '@/utils';
import { useStorage } from '@vueuse/core';
import dayjs from 'dayjs';

export const usePrestationsStore = defineStore('prestations', () => {
  const prestations = ref([]);
  const errors = ref({});
  const loading = ref({});

  const defaultMonthYear = dayjs().format('YYYY-MM');

  // Filtres pour les prestations (par exemple, filtrer par date et adresse)
  const activeFilters = useStorage("prestation-filters", {
    month_year: defaultMonthYear,
  });

  // Si la valeur est vide, on la met à jour
  if (!activeFilters.value.month_year) {
    activeFilters.value.month_year = defaultMonthYear;
  }

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

  async function fetchPrestations() {
    return apiCall({
      operation: 'fetch',
      request: () => axios.get('/api/prestations'),
      onSuccess: (response) => {
        // Si l'API renvoie les données dans une clé data, ajustez ici
        prestations.value = response.data;
      },
    });
  }

  async function addPrestation(prestation) {
    return apiCall({
      operation: 'add',
      request: () => axios.post('/api/prestations', prestation),
      onSuccess: (response) => {
        prestations.value.push(response.data);
        notify('success', response.data.message || 'Prestation ajoutée avec succès.');
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
          prestations.value[index] = response.data.data || response.data;
        }
        notify('success', response.data.message || 'Prestation mise à jour avec succès.');
      },
    });
  }

  async function deletePrestation(prestationId) {
    return apiCall({
      operation: 'delete',
      request: () => axios.delete(`/api/prestations/${prestationId}`),
      onSuccess: (response) => {
        prestations.value = prestations.value.filter(p => p.id !== prestationId);
        notify('success', response.data.message || 'Prestation supprimée avec succès.');
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
  };
});
