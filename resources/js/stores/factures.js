// src/stores/invoices.js
import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { notify } from '@/utils';
import { useStorage } from '@vueuse/core';

export const useInvoicesStore = defineStore('invoices', () => {
  // Liste des factures
  const invoices = ref([]);
  // Erreurs pour chaque opération
  const errors = ref({});
  // États de chargement par opération
  const loading = ref({});

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
      console.error(err);
      throw err;
    } finally {
      setLoading(operation, false);
    }
  }

  // Récupère la liste des factures via l'API
  async function fetchInvoices() {
    return apiCall({
      operation: 'fetch',
      request: () => axios.get('/api/factures', { withCredentials: true }),
      onSuccess: (response) => {
        invoices.value = response.data;
      },
    });
  }

  // Ajoute une facture (si vous avez besoin de créer une facture sans PDF, sinon on utilise la méthode store de l'InvoiceController)
  async function addInvoice(invoice) {
    return apiCall({
      operation: 'add',
      request: () => axios.post('/api/factures', invoice),
      onSuccess: (response) => {
        invoices.value.push(response.data.data || response.data);
        notify('success', response.data.message || 'Facture ajoutée avec succès.');
      },
    });
  }

  // Supprime une facture
  async function deleteInvoice(invoiceId) {
    return apiCall({
      operation: 'delete',
      request: () => axios.delete(`/api/factures/${invoiceId}`),
      onSuccess: (response) => {
        invoices.value = invoices.value.filter(i => i.id !== invoiceId);
        notify('success', response.data.message || 'Facture supprimée avec succès.');
      },
    });
  }

  // Cache pour les URLs Blob des PDFs
  const pdfCache = new Map();

  // Récupère le PDF d'une facture
  async function getInvoicePdf(invoiceId) {
    if (pdfCache.has(invoiceId)) {
      return pdfCache.get(invoiceId);
    }
    try {
      const response = await apiCall({
        operation: 'pdf',
        request: () => axios.get(`/api/factures/${invoiceId}/pdf`, { 
          withCredentials: true,
          responseType: 'blob' 
        }),
      });
      if (!response || !response.data) {
        throw new Error("Réponse invalide lors de la récupération du PDF.");
      }
      const blobUrl = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
      pdfCache.set(invoiceId, blobUrl);
      return blobUrl;
    } catch (err) {
      console.error(`Erreur lors de la récupération du PDF de la facture ${invoiceId} :`, err);
      throw new Error("Impossible de récupérer le PDF.");
    }
  }

  async function sendEmail(invoiceId, emails) {
    return apiCall({
      operation: 'sendEmail',
      request: () => axios.post(`/api/factures/${invoiceId}/send-email`, { emails }),
      onSuccess: (response) => {
        const updatedInvoice = response.data.data;
        const index = invoices.value.findIndex(i => i.id === invoiceId);

        if (index !== -1) {
          invoices.value[index] = updatedInvoice;
        } else {
          console.warn(`⚠️ Facture ${invoiceId} non trouvée dans le store invoices.`);
        }

        notify('success', response.data.message | 'Facture envoyée avec succès.');
      },
    });
  }

  async function invoicePaid(invoice) {
    return apiCall({
      operation: 'paid',
      request: () => axios.patch(`/api/factures/${invoice.id}/paid`),
      onSuccess: (response) => {
        const updatedInvoice = response.data.data;
        const index = invoices.value.findIndex(i => i.id === invoice.id);

        if (index !== -1) {
          invoices.value[index] = updatedInvoice;
        } else {
          console.warn(`⚠️ Facture ${invoice.id} non trouvée dans le store invoices.`);
        }

        notify('success', response.data.message | 'Facture marquée comme payée.');
      },
    });
  }

  return { 
    invoices, 
    errors, 
    loading, 
    fetchInvoices,
    addInvoice, 
    deleteInvoice, 
    getInvoicePdf, 
    clearErrors,
    sendEmail,
    invoicePaid,
  };
});
