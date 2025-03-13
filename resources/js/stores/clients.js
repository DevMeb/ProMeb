import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { notify } from '@/utils';
import { useStorage } from '@vueuse/core';
import dayjs from 'dayjs';

export const useClientsStore = defineStore('clients', () => {
  const clients = ref([]);
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

  async function fetchClients() {
    return apiCall({
      operation: 'fetch',
      request: () => axios.get('/api/clients'),
      onSuccess: (response) => {
        // Si l'API renvoie les données dans une clé data, ajustez ici
        clients.value = response.data.clients;
      },
    });
  }

  async function addClient(client) {
    return apiCall({
      operation: 'add',
      request: () => axios.post('/api/clients', client),
      onSuccess: (response) => {
        clients.value.push(response.data.client);
        notify('success', response.data.message);
      },
    });
  }

  async function updateClient(client) {
    return apiCall({
      operation: 'update',
      request: () => axios.put(`/api/clients/${client.id}`, client),
      onSuccess: (response) => {
        const index = clients.value.findIndex(c => c.id === client.id);
        if (index !== -1) {
            clients.value[index] = response.data.client;
        }
        notify('success', response.data.message);
      },
    });
  }

  async function deleteClient(clientId) {
    return apiCall({
      operation: 'delete',
      request: () => axios.delete(`/api/clients/${clientId}`),
      onSuccess: (response) => {
        clients.value = clients.value.filter(c => c.id !== clientId);
        notify('success', response.data.message);
      },
    });
  }

  return {
    clients,
    errors,
    loading,
    fetchClients,
    addClient,
    updateClient,
    deleteClient,
    clearErrors,
  };
});
