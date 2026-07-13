// src/stores/invoices.js
import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { notify } from '@/utils';
import { useDashboardStore } from "@/stores/dashboard";
import { creerApiCall } from '@/stores/apiCall';


export const useInvoicesStore = defineStore('invoices', () => {
  const invoices = ref([]);
  const errors = ref({});
  const loading = ref({});

  // Filtres de la liste des factures (appliqués côté client)
  const activeFilters = ref({
    statut: '',
    client_id: '',
    month_year: '',
  });

  function updateFilters(filters) {
    activeFilters.value = filters;
  }

  const isAnyFilterActive = computed(() => {
    return Object.values(activeFilters.value).some(value => value !== "");
  });

  // Factures filtrées, les plus récentes en tête.
  // Le tri se fait sur l'id : created_at arrive au format d/m/Y H:i:s,
  // que JavaScript ne sait pas trier.
  const filteredInvoices = ref([]);
  watch([invoices, activeFilters], () => {
    filteredInvoices.value = invoices.value
      .filter(invoice => {
        const { statut, client_id, month_year } = activeFilters.value;

        if (statut && invoice.statut !== statut) return false;

        if (client_id && invoice.prestations?.[0]?.client_id !== client_id) return false;

        // La facture est retenue si au moins une de ses prestations
        // tombe dans le mois demandé. prestation.date est au format Y-m-d.
        if (month_year && !invoice.prestations?.some(p => p.date?.startsWith(month_year))) return false;

        return true;
      })
      .sort((a, b) => b.id - a.id);
  }, { deep: true, immediate: true });

  const dashboardStore = useDashboardStore();

  const { apiCall, clearErrors } = creerApiCall({ errors, loading });

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
    const response = await apiCall({
      operation: "pdf",
      request: () => axios.get(`/api/factures/${invoiceId}/pdf`, { responseType: "blob" }),
      onError: async (err) => {
        // 1) Erreurs réseau (aucune réponse HTTP)
        if (!err.response) {
          const msg =
            err.code === "ECONNABORTED"
              ? "Délai d’attente dépassé. Vérifiez votre connexion et réessayez."
              : "Impossible de contacter le serveur. Vérifiez votre connexion Internet.";

          errors.value.pdf = msg;   // modale
          notify("error", msg);     // toast
          return;
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
        if (status === 422 || status === 403) {
          const msg =
            (await readJsonMessage()) ||
            "Votre profil est incomplet. Complétez vos informations dans les paramètres.";

          errors.value.pdf = msg;   // modale
          notify("error", msg);     // toast
          return;
        }

        // 3) Autres erreurs HTTP (serveur, permissions, etc.)
        const msg =
          (await readJsonMessage()) ||
          "Erreur technique lors de la génération du PDF. Réessayez dans quelques instants.";

        errors.value.pdf = msg;     // modale
        notify("error", msg);       // toast
      },
    });

    // L'URL est construite APRÈS l'appel : apiCall retourne désormais la réponse,
    // et non plus ce que retourne onSuccess.
    if (!response) return "";

    return URL.createObjectURL(new Blob([response.data], { type: "application/pdf" }));
  }

  async function paid(invoiceId) {
    return apiCall({
      // La clé est indexée par facture : une clé unique ("paid") ferait réagir
      // le bouton de TOUTES les lignes en attente de paiement, pas seulement
      // celle sur laquelle on a cliqué.
      operation: `paid_${invoiceId}`,
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
    filteredInvoices,
    activeFilters,
    updateFilters,
    isAnyFilterActive,
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
