import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useInvoiceStore = defineStore('invoices', () => {
  // Stocke la liste des factures
  const invoices = ref([]);
  // Gère l'état de chargement et d'erreur
  const loading = ref(false);
  const error = ref(null);

  /**
   * Récupère la liste des factures
   */
  async function fetchInvoices() {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/api/factures', { withCredentials: true });
      invoices.value = response.data;
    } catch (e) {
      error.value = e.response?.data?.error || e.message;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Supprime une facture par son ID et met à jour la liste
   * @param {number} id - ID de la facture à supprimer
   */
  async function deleteInvoice(id) {
    loading.value = true;
    error.value = null;
    try {
      await axios.delete(`/api/factures/${id}`, { withCredentials: true });
      invoices.value = invoices.value.filter(invoice => invoice.id !== id);
    } catch (e) {
      error.value = e.response?.data?.error || e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Crée une facture à partir des données fournies.
   * Ici, l'API renvoie un PDF pour téléchargement. 
   * Après la création, on actualise la liste des factures.
   * @param {Object} payload - Données de la facture (prestations, total_heures, total_ht)
   * @returns {Blob} Le PDF généré (en réponse)
   */
  async function createInvoice(payload) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.post('/api/factures', payload, {
        withCredentials: true,
        responseType: 'blob'  // Pour récupérer le PDF en blob
      });
      // Actualiser la liste des factures après création
      await fetchInvoices();
      return response;
    } catch (e) {
      error.value = e.response?.data?.error || e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  return {
    invoices,
    loading,
    error,
    fetchInvoices,
    deleteInvoice,
    createInvoice,
  };
});
