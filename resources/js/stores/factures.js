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
      // 1) Erreurs réseau (aucune réponse HTTP)
      if (!err.response) {
        const msg =
          err.code === "ECONNABORTED"
            ? "Délai d’attente dépassé. Vérifiez votre connexion et réessayez."
            : "Impossible de contacter le serveur. Vérifiez votre connexion Internet.";

        errors.value.pdf = msg;   // modale
        notify("error", msg);     // toast
        return "";
      }

      const status = err.response.status;
      const contentType = (err.response.headers?.["content-type"] || "").toLowerCase();

      // Helper: extraire un message JSON même si responseType=blob
      const readJsonMessage = async () => {
        if (!contentType.includes("application/json")) return null;

        const text = await err.response.data.text();
        try {
          const payload = JSON.parse(text);
          return payload?.message || null;
        } catch {
          return null;
        }
      };

      // 2) Erreur métier "profil incomplet" (ou autre validation)
      // Si vous ne voulez viser QUE le profil incomplet, vous pouvez aussi matcher sur le texte.
      if (status === 422 || status === 403) {
        const msg =
          (await readJsonMessage()) ||
          "Votre profil est incomplet. Complétez vos informations dans les paramètres.";

        errors.value.pdf = msg;   // modale
        notify("error", msg);     // toast
        return "";
      }

      // 3) Autres erreurs HTTP (serveur, permissions, etc.)
      const msg =
        (await readJsonMessage()) ||
        "Erreur technique lors de la génération du PDF. Réessayez dans quelques instants.";

      errors.value.pdf = msg;     // modale
      notify("error", msg);       // toast
      return "";
    },
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
