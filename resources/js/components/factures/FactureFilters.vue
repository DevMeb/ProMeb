<template>
  <div class="bg-gray-800 p-4 rounded-lg shadow-lg flex flex-col sm:flex-row gap-4 sm:items-end">
    <div class="flex-1">
      <label for="filtre-statut" class="text-sm text-gray-300 font-semibold">Statut :</label>
      <select id="filtre-statut" v-model="activeFilters.statut" class="filter-input">
        <option value="">Tous les statuts</option>
        <option value="en_attente_envoi">En attente d'envoi</option>
        <option value="en_attente_paiement">En attente de paiement</option>
        <option value="payé">Payé</option>
      </select>
    </div>

    <div class="flex-1">
      <label for="filtre-client" class="text-sm text-gray-300 font-semibold">Client :</label>
      <select id="filtre-client" v-model="activeFilters.client_id" class="filter-input">
        <option value="">Tous les clients</option>
        <option v-for="client in clients" :key="client.id" :value="client.id">
          {{ client.nom }}
        </option>
      </select>
    </div>

    <div class="flex-1">
      <label for="filtre-mois" class="text-sm text-gray-300 font-semibold">Mois des prestations :</label>
      <input id="filtre-mois" type="month" v-model="activeFilters.month_year" class="filter-input" />
    </div>

    <button v-if="isAnyFilterActive" @click="resetFilters" class="btn-secondary whitespace-nowrap">
      Réinitialiser
    </button>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useInvoicesStore } from '@/stores/factures';
import { useClientsStore } from '@/stores/clients';
import { storeToRefs } from 'pinia';

const invoicesStore = useInvoicesStore();
const clientsStore = useClientsStore();

const { activeFilters, isAnyFilterActive } = storeToRefs(invoicesStore);
const { updateFilters } = invoicesStore;

const { fetchClients } = clientsStore;
const { clients } = storeToRefs(clientsStore);

onMounted(() => {
  fetchClients();
});

watch(activeFilters, (newFilters) => {
  updateFilters(newFilters);
}, { deep: true });

const resetFilters = () => {
  updateFilters({
    statut: '',
    client_id: '',
    month_year: '',
  });
};
</script>
