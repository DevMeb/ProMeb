import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const usePrestationsStore = defineStore('prestations', () => {
  const prestations = ref([]);
  const loading = ref(false);
  const error = ref(null);

  async function fetchPrestations() {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/api/prestations', { withCredentials: true });
      prestations.value = response.data;
    } catch (e) {
      error.value = e.response?.data?.error || e.message;
    } finally {
      loading.value = false;
    }
  }

  async function fetchPrestation(id) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get(`/api/prestations/${id}`, { withCredentials: true });
      return response.data;
    } catch (e) {
      error.value = e.response?.data?.error || e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function createPrestation(data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.post('/api/prestations', data, { withCredentials: true });
      prestations.value.push(response.data);
      return response.data;
    } catch (e) {
      error.value = e.response?.data?.error || e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function updatePrestation(id, data) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.put(`/api/prestations/${id}`, data, { withCredentials: true });
      const index = prestations.value.findIndex(p => p.id === id);
      if (index !== -1) {
        prestations.value[index] = response.data;
      }
      return response.data;
    } catch (e) {
      error.value = e.response?.data?.error || e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deletePrestation(id) {
    loading.value = true;
    error.value = null;
    try {
      await axios.delete(`/api/prestations/${id}`, { withCredentials: true });
      prestations.value = prestations.value.filter(p => p.id !== id);
    } catch (e) {
      error.value = e.response?.data?.error || e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  return {
    prestations,
    loading,
    error,
    fetchPrestations,
    fetchPrestation,
    createPrestation,
    updatePrestation,
    deletePrestation,
  };
});
