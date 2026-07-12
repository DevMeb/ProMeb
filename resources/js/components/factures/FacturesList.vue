<template>
  <div class="mt-6">
    <FactureFilters />

    <!-- Chargement : des lignes fantômes, pas un spinner, pour que la page ne saute pas -->
    <div v-if="loading.fetch" class="mt-6 bg-gray-800 rounded-lg ring-1 ring-gray-700 overflow-hidden">
      <div v-for="n in 3" :key="n" class="flex items-center gap-4 px-4 py-4 border-b border-gray-700 last:border-b-0">
        <div class="h-4 w-12 bg-gray-700 rounded animate-pulse"></div>
        <div class="h-4 flex-1 bg-gray-700 rounded animate-pulse"></div>
        <div class="h-4 w-20 bg-gray-700 rounded animate-pulse"></div>
        <div class="h-4 w-24 bg-gray-700 rounded animate-pulse"></div>
        <div class="h-6 w-28 bg-gray-700 rounded-full animate-pulse"></div>
      </div>
    </div>

    <!-- Erreur -->
    <div v-else-if="errors.fetch" class="mt-6 flex justify-center bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
      <span class="text-xl">❌</span>
      <p class="text-lg font-medium ml-2">{{ errors.fetch }}</p>
    </div>

    <!-- Aucune facture du tout -->
    <div v-else-if="invoices.length === 0" class="mt-6 bg-gray-800 rounded-lg ring-1 ring-gray-700 px-6 py-12 text-center">
      <p class="text-3xl mb-2">📭</p>
      <p class="text-white font-semibold mb-1">Aucune facture</p>
      <p class="text-gray-400 mb-5">Créez votre première facture à partir de prestations non facturées.</p>
      <button @click="emit('create')" class="btn-primary">Ajouter une facture</button>
    </div>

    <!-- Aucun résultat pour les filtres actifs : message distinct du précédent -->
    <div v-else-if="filteredInvoices.length === 0" class="mt-6 bg-gray-800 rounded-lg ring-1 ring-gray-700 px-6 py-12 text-center">
      <p class="text-3xl mb-2">🔍</p>
      <p class="text-white font-semibold mb-1">Aucun résultat</p>
      <p class="text-gray-400 mb-5">Aucune facture ne correspond à ces filtres.</p>
      <button @click="resetFilters" class="btn-secondary">Réinitialiser les filtres</button>
    </div>

    <!-- La liste -->
    <div v-else class="mt-6 bg-gray-800 rounded-lg ring-1 ring-gray-700 overflow-hidden">
      <!-- En-tête de colonnes : masqué sur mobile, où chaque ligne devient une carte -->
      <div class="hidden lg:flex items-center px-4 py-2 border-b border-gray-700 text-xs uppercase tracking-wide text-gray-400">
        <span class="w-[100px] shrink-0">N°</span>
        <span class="flex-1">Client</span>
        <span class="w-[100px] text-right">Heures</span>
        <span class="w-[130px] text-right">Montant</span>
        <span class="w-[180px] pl-4">Statut</span>
        <span class="w-[240px]"></span>
      </div>

      <FactureListItem
        v-for="invoice in filteredInvoices"
        :key="invoice.id"
        :invoice="invoice"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useInvoicesStore } from '@/stores/factures';
import { FactureListItem, FactureFilters } from '@/components/factures/';

const emit = defineEmits(['create']);

const invoicesStore = useInvoicesStore();
const { fetchInvoices, updateFilters } = invoicesStore;
const { invoices, filteredInvoices, errors, loading } = storeToRefs(invoicesStore);

onMounted(() => {
  fetchInvoices();
});

const resetFilters = () => {
  updateFilters({
    statut: '',
    client_id: '',
    month_year: '',
  });
};
</script>
