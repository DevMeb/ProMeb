<template>
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg flex flex-col space-y-4">
        <!-- En-tête -->
        <div class="flex justify-between items-center border-b pb-3">
            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
              🧾 Facture #{{ invoice.id }}
            </h2>

            <span :class="getStatusBadge(invoice.statut)">
                {{ getStatusText(invoice.statut) }}
            </span>
          </div>
          
          <!-- Détails de la facturation -->
          <div class="bg-gray-900 p-4 rounded-md space-y-2">
            <p class="text-gray-300 text-lg">
              ⏱️ <span class="font-semibold text-white">{{ invoice.heures_total }} h</span>  
            </p>
            <p class="text-gray-300 text-lg">
              💶 <span class="font-semibold text-white">{{ invoice.montant_total }} €</span> 
            </p>
          </div>
          
          <!-- Informations Client -->
          <div class="bg-gray-900 p-4 rounded-lg flex flex-col space-y-3">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
              <span class="text-indigo-300 text-xl">👤</span>
              {{ invoice.prestations[0].client.nom }}
            </h3>
          </div>

          <!-- Informations Prestations -->
          <div class="bg-gray-900 p-4 rounded-lg flex flex-col space-y-3">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
              <span class="text-indigo-300 text-xl">💼</span>
              Prestations
            </h3>

            <div v-for="prestation in invoice.prestations" :key="prestation.id" class="bg-gray-800 p-3 rounded-md grid grid-cols-2">
              <p class="text-gray-300 text-md flex items-center">
                📆 <span class="ml-2 font-semibold text-white">{{ formatDate(prestation.date) }}</span>
              </p>
              <p class="text-gray-300 text-md flex items-center">
                💵 <span class="ml-2 font-semibold text-white">{{ prestation.heures * prestation.taux_horaire.taux }} €</span>
              </p>
              <p class="text-gray-300 text-md flex items-center">
                💰 <span class="ml-2 font-semibold text-white">{{ prestation.taux_horaire.taux }} € / h</span>
              </p>
              <p class="text-gray-300 text-md flex items-center">
                ⏳ <span class="ml-2 font-semibold text-white">{{ prestation.heures }} h</span>
              </p>
              
            </div>
          </div>

        <!-- ⏳ Statuts et dates clés -->
        <div class="bg-gray-900 p-4 rounded-md
        ">
          <p class="text-gray-300 text-lg">
            ✅ <span class="font-semibold text-white">Payée le :</span> {{ invoice.paye_le !== null ? invoice.paye_le : "Non payée" }}
          </p>
      </div>

        <!-- 🔘 Actions -->
        <div class="flex flex-wrap justify-center gap-3 mt-4">
          <button @click="showPdfModal = true" class="btn-action bg-gray-600">
              📄 Voir
          </button>

          <button v-if="invoice.statut === 'en_attente_paiement'" @click="showDeleteModal = true" class="btn-action bg-red-500">
              🗑️ Supprimer
          </button>

          <button v-if="invoice.statut === 'en_attente_paiement'" @click="markAsPaid(invoice)" :disabled="loading.paid"
              class="btn-action bg-green-500 disabled:opacity-50">
              <span v-if="loading.paid" class="animate-spin border-4 border-white border-t-transparent rounded-full w-4 h-4"></span>
              ✅ Payé
          </button>
      </div>
    </div>

    <!-- Modals -->
    <FacturePdfModal v-if="showPdfModal" :invoice="invoice" @close="showPdfModal = false" />
    <FactureDeleteModal v-if="showDeleteModal" :invoice="invoice" @close="showDeleteModal = false" />
    <FactureMailModal v-if="showMailModal" :invoice="invoice" @close="showMailModal = false" />
</template>

<script setup>
import { ref } from 'vue'
import { 
    FacturePdfModal,
    FactureDeleteModal, 
    FactureMailModal,
} from '@/components/factures/'

import { formatDate } from '@/utils'
import { useInvoicesStore } from '@/stores/factures'
import { storeToRefs } from 'pinia'

const invoicesStore = useInvoicesStore()
const { paid } = invoicesStore
const { loading } = storeToRefs(invoicesStore)

const props = defineProps({
    invoice: Object,
})

const showDeleteModal = ref(false)
const showPdfModal = ref(false)
const showMailModal = ref(false)

/**
 * Retourne la classe CSS du badge en fonction du statut.
 */
 function getStatusBadge(status) {
  switch (status) {
    case 'en_attente_paiement':
      return "bg-red-500 text-white px-3 py-1 rounded-full text-xs";
    case 'payé':
      return "bg-green-500 text-white px-3 py-1 rounded-full text-xs";
    default:
      return "bg-gray-500 text-white px-3 py-1 rounded-full text-xs";
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

// 📌 Marquer la facture comme payée avec chargement
async function markAsPaid(invoice) {
    await paid(invoice.id)
}
</script>