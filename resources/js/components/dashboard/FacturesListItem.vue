<template>
  <div 
    class="bg-gray-900 p-6 rounded-md shadow-md border-l-8 flex flex-col gap-4"
    :class="getStatusColor(facture.statut)"
  >
    <!-- En-tête de la facture -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
          🧾 Facture #{{ facture.id }}
        </h2>
        <span class="px-3 py-1 text-xs font-semibold rounded-full" :class="getBadgeColor(facture.statut)">
          {{ getStatusText(facture.statut) }}
        </span>
      </div>

      <!-- Boutons d'action -->
      <div class="flex flex-wrap gap-2">
        <button 
          @click="showPdfModal = true" 
          class="flex items-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition shadow-md">
          👁️ Voir
        </button>
        <button 
          v-if="facture.statut === 'en_attente_envoi'" 
          @click="showMailModal = true" 
          class="flex items-center bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm transition shadow-md">
          📩 Envoyer
        </button>
        <button 
          v-if="facture.statut === 'en_attente_paiement'" 
          @click="markAsPaid(facture)" 
          :disabled="loading.paid"
          class="flex items-center bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm transition shadow-md disabled:opacity-50">
          <span v-if="loading.paid" class="animate-spin mr-2 border-4 border-white border-t-transparent rounded-full w-4 h-4"></span>
          ✅ Payé
        </button>
      </div>
    </div>

    <!-- Informations détaillées -->
    <div class="bg-gray-800 p-4 rounded-md space-y-2">
      <p class="text-gray-300 text-sm flex items-center">
        📅 <span class="ml-2 font-semibold text-white">Date :</span>
        <span class="text-indigo-400 ml-1">{{ formatDate(facture.created_at) }}</span>
      </p>
      <p class="text-gray-300 text-sm flex items-center">
        ⏱️ <span class="ml-2 font-semibold text-white">Heures totales :</span>
        <span class="text-indigo-400 ml-1">{{ facture.heures_total }} h</span>
      </p>
      <p class="text-gray-300 text-sm flex items-center">
        💶 <span class="ml-2 font-semibold text-white">Montant total :</span>
        <span class="text-indigo-400 ml-1">{{ facture.montant_total }} €</span>
      </p>
    </div>

    <!-- Nouveau design pour les prestations -->
    <div class="space-y-3">
      <h3 class="text-md font-semibold text-gray-400 flex items-center gap-2">
        📋 Prestations associées
        <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded-full">
          {{ facture.prestations.length }} élément(s)
        </span>
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <FacturePrestationsListItem 
          v-for="prestation in facture.prestations" 
          :key="prestation.id" 
          :prestation="prestation" 
        />
      </div>
    </div>

    <FacturePdfModal v-if="showPdfModal" :invoice="facture" @close="showPdfModal = false" />
    <FactureMailModal v-if="showMailModal" :invoice="facture" @close="showMailModal = false" />
  </div>
</template>

<script setup>
import { FacturePdfModal, FactureMailModal } from '@/components/factures';
import { FacturePrestationsListItem } from '@/components/dashboard';
import { ref } from 'vue';
import { formatDate } from '@/utils';
import { useInvoicesStore } from '@/stores/factures';
import { storeToRefs } from 'pinia';

const invoicesStore = useInvoicesStore();
const { invoicePaid } = invoicesStore;
const { loading } = storeToRefs(invoicesStore);

const props = defineProps({ 
  facture: Object,
});

const showPdfModal = ref(false);
const showMailModal = ref(false);

// 📌 Marquer la facture comme payée avec animation de chargement
async function markAsPaid(facture) {
    await invoicePaid(facture);
}

// 📌 Texte du statut de la facture
function getStatusText(status) {
  switch (status) {
    case 'en_attente_envoi': return "🟡 En attente d'envoi";
    case 'en_attente_paiement': return "🔴 En attente de paiement";
    case 'payé': return "🟢 Payé";
    default: return status;
  }
}

// 📌 Couleur de la bordure latérale selon le statut
function getStatusColor(status) {
  switch (status) {
    case 'en_attente_envoi': return "border-yellow-500";
    case 'en_attente_paiement': return "border-red-500";
    case 'payé': return "border-green-500";
    default: return "border-gray-500";
  }
}

// 📌 Couleur du badge statut
function getBadgeColor(status) {
  switch (status) {
    case 'en_attente_envoi': return "bg-yellow-500 text-gray-900";
    case 'en_attente_paiement': return "bg-red-500 text-white";
    case 'payé': return "bg-green-500 text-white";
    default: return "bg-gray-500 text-white";
  }
}
</script>