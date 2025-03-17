// src/stores/invoices.js
import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { notify } from '@/utils';
import { useDashboardStore } from "@/stores/dashboard";


export const useInvoicesStore = defineStore('invoices', () => {
  const invoices = ref([]);
  const errors = ref({});
  const loading = ref({});

  const dashboardStore = useDashboardStore();

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

  async function apiCall({ operation, request, onSuccess, onError }) {
    clearErrors(operation);
    setLoading(operation, true);
    try {
      const response = await request();
      return onSuccess ? onSuccess(response) : response;
    } catch (err) {
      console.error(err)
      if (onError) {
        onError(err);
      } else if (err.response?.status === 422) {
        errors.value.validationErrors = err.response.data.errors;
      } else {
        errors.value[operation] = err.response?.data?.message || "Une erreur est survenue.";
        notify('error', errors.value[operation]);
      }
    } finally {
      setLoading(operation, false);
    }
  }

  async function fetchInvoices() {
    return apiCall({
      operation: 'fetch',
      request: () => axios.get('/api/factures'),
      onSuccess: (response) => {
        invoices.value = response.data.factures;
      },
    });
  }

  async function addInvoice(invoice) {
    return apiCall({
      operation: 'add',
      request: () => axios.post('/api/factures', invoice),
      onSuccess: (response) => {
        invoices.value.push(response.data.facture);
        
        if(dashboardStore.dashboardData) {
          dashboardStore.fetchDashboard();
        }

        notify('success', response.data.message);
      },
    });
  }

  async function deleteInvoice(invoiceId) {
    return apiCall({
      operation: 'delete',
      request: () => axios.delete(`/api/factures/${invoiceId}`),
      onSuccess: (response) => {
        invoices.value = invoices.value.filter(i => i.id !== invoiceId);
        notify('success', response.data.message);
      },
    });
  }

  // Récupère et affiche le PDF d'une facture
  async function getInvoicePdf(invoiceId) {
    return apiCall({
      operation: "pdf",
      request: () => axios.get(`/api/factures/${invoiceId}/pdf`, { responseType: "blob" }),
      onSuccess: (response) => {
        return URL.createObjectURL(new Blob([response.data], { type: "application/pdf" }));
      },
      onError: async (err) => {
        const contentType = err.response.headers["content-type"];
        if (contentType === "application/json") {
          const errorText = await err.response.data.text();
          const errorJson = JSON.parse(errorText);
          errors.value['pdf'] = 'Impossible de charger le PDF'
          notify('error', errorJson.message);
        } 
      }
    });
  }

  async function paid(invoiceId) {
    return apiCall({
      operation: "paid",
      request: () => axios.patch(`/api/factures/${invoiceId}/paid`),
      onSuccess: (response) => {
        if (dashboardStore.dashboardData) {
          const factureHasPaid = response.data.facture
          dashboardStore.factureFromUnpaidToPaid(factureHasPaid)
        }
        
        const index = invoices.value.findIndex(f => f.id === invoiceId);
        if (index !== -1) {
          invoices.value[index] = response.data.facture;
        }
        notify('success', response.data.message);
      }
    });
  }


  return { 
    invoices, 
    errors, 
    loading, 
    fetchInvoices,
    addInvoice, 
    deleteInvoice, 
    clearErrors,
    getInvoicePdf,
    paid,
  };
});
