<template>
  <div class="border-b border-gray-700 last:border-b-0" :class="{ 'bg-indigo-500/5': estDeplie }">
    <!-- Ligne -->
    <div
      class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-0 px-4 py-3 hover:bg-white/[.02] transition cursor-pointer"
      @click="basculer"
    >
      <!-- Chevron + numéro -->
      <button
        type="button"
        class="flex items-center gap-2 text-left sm:w-[100px] shrink-0 text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
        :aria-expanded="estDeplie"
        :aria-controls="`facture-detail-${invoice.id}`"
        @click.stop="basculer"
      >
        <span
          class="inline-block transition-transform duration-200"
          :class="estDeplie ? 'rotate-90' : ''"
          aria-hidden="true"
        >▸</span>
        <span class="text-sm">#{{ invoice.id }}</span>
      </button>

      <!-- Client -->
      <div class="flex-1 min-w-0">
        <span class="sm:hidden text-xs uppercase tracking-wide text-gray-400 mr-2">Client</span>
        <span class="font-semibold text-white truncate">{{ nomClient }}</span>
      </div>

      <!-- Heures -->
      <div class="sm:w-[100px] sm:text-right flex justify-between sm:block">
        <span class="sm:hidden text-xs uppercase tracking-wide text-gray-400">Heures</span>
        <span class="text-gray-200 tabular-nums">{{ formatNombre(invoice.heures_total) }} h</span>
      </div>

      <!-- Montant -->
      <div class="sm:w-[130px] sm:text-right flex justify-between sm:block">
        <span class="sm:hidden text-xs uppercase tracking-wide text-gray-400">Montant</span>
        <span class="font-semibold text-white tabular-nums">{{ formatEuros(invoice.montant_total) }}</span>
      </div>

      <!-- Statut -->
      <div class="sm:w-[160px] sm:pl-4 flex justify-between sm:block">
        <span class="sm:hidden text-xs uppercase tracking-wide text-gray-400">Statut</span>
        <span :class="getStatusBadge(invoice.statut)">{{ getStatusText(invoice.statut) }}</span>
      </div>

      <!-- Actions : ne doivent pas déplier la ligne -->
      <div class="flex gap-2 sm:justify-end sm:w-[200px]" @click.stop>
        <button @click="showPdfModal = true" class="btn-action bg-gray-600 flex-1 sm:flex-none justify-center">
          📄 <span class="sm:hidden md:inline">PDF</span>
        </button>

        <button
          v-if="invoice.statut === 'en_attente_paiement'"
          @click="showDeleteModal = true"
          class="btn-action bg-red-500 flex-1 sm:flex-none justify-center"
          aria-label="Supprimer la facture"
        >
          🗑️
        </button>

        <button
          v-if="invoice.statut === 'en_attente_paiement'"
          @click="markAsPaid(invoice)"
          :disabled="loading.paid"
          class="btn-action bg-green-500 disabled:opacity-50 flex-1 sm:flex-none justify-center"
        >
          <span v-if="loading.paid" class="animate-spin border-2 border-white border-t-transparent rounded-full w-3 h-3"></span>
          <span v-else>✅</span>
          <span class="sm:hidden md:inline">Payé</span>
        </button>
      </div>
    </div>

    <!-- Détail déplié -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="estDeplie" :id="`facture-detail-${invoice.id}`" class="px-4 pb-4 sm:pl-14">
        <FacturePrestationsTable :prestations="invoice.prestations ?? []" />
      </div>
    </Transition>
  </div>

  <!-- Modals -->
  <FacturePdfModal v-if="showPdfModal" :invoice="invoice" @close="showPdfModal = false" />
  <FactureDeleteModal v-if="showDeleteModal" :invoice="invoice" @close="showDeleteModal = false" />
</template>

<script setup>
import { ref, computed } from 'vue';
import {
  FacturePdfModal,
  FactureDeleteModal,
  FacturePrestationsTable,
} from '@/components/factures/';

import { useInvoicesStore } from '@/stores/factures';
import { storeToRefs } from 'pinia';
import { formatNombre, formatEuros } from '@/utils';

const invoicesStore = useInvoicesStore();
const { paid } = invoicesStore;
const { loading } = storeToRefs(invoicesStore);

const props = defineProps({
  invoice: {
    type: Object,
    required: true,
  },
});

const estDeplie = ref(false);
const showDeleteModal = ref(false);
const showPdfModal = ref(false);

function basculer() {
  estDeplie.value = !estDeplie.value;
}

// Une facture sans prestation ferait planter le rendu de toute la liste.
const nomClient = computed(() => props.invoice.prestations?.[0]?.client?.nom ?? 'Client inconnu');

/**
 * Retourne la classe CSS du badge en fonction du statut.
 */
function getStatusBadge(status) {
  const base = "inline-block px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap";
  switch (status) {
    case 'en_attente_envoi':
      return `${base} bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/30`;
    case 'en_attente_paiement':
      return `${base} bg-red-500/15 text-red-400 ring-1 ring-red-500/30`;
    case 'payé':
      return `${base} bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/30`;
    default:
      return `${base} bg-gray-500/15 text-gray-300 ring-1 ring-gray-500/30`;
  }
}

/**
 * Retourne un texte lisible correspondant au statut.
 */
function getStatusText(status) {
  switch (status) {
    case 'en_attente_envoi':
      return "En attente d'envoi";
    case 'en_attente_paiement':
      return "En attente de paiement";
    case 'payé':
      return "Payé";
    default:
      return status;
  }
}

async function markAsPaid(invoice) {
  await paid(invoice.id);
}
</script>
