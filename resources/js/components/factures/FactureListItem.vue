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
            <p class="text-gray-300 text-sm">
              ⏱️ <span class="font-semibold text-white">Nombre d'heures :</span> {{ invoice.quantite_heures }}
            </p>
            <p class="text-gray-300 text-sm">
              💶 <span class="font-semibold text-white">Montant :</span> {{ invoice.total_ht }} €
            </p>
          </div>
          

        <!-- ⏳ Statuts et dates clés -->
        <div class="bg-gray-900 p-4 rounded-md grid grid-cols-2 gap-4">
          <p class="text-gray-300 text-sm">
              🕒 <span class="font-semibold text-white">Créée le :</span> {{ invoice.created_at }}
          </p>
          <p class="text-gray-300 text-sm">
              🔄 <span class="font-semibold text-white">Mise à jour le :</span> {{ invoice.updated_at }}
          </p>
          <p class="text-gray-300 text-sm">
              💰 <span class="font-semibold text-white">Payée le :</span> {{ dayjs(invoice.paye_le).format('DD/MM/YYYY HH:mm:ss') || "Non payée" }}
          </p>
          <p class="text-gray-300 text-sm">
              📩 <span class="font-semibold text-white">Émise le :</span> {{ dayjs(invoice.envoye_le).format('DD/MM/YYYY HH:mm:ss') || "Non envoyé" }}
          </p>
      </div>

        <!-- 🔘 Actions -->
        <div class="flex flex-wrap justify-center gap-3 mt-4">
          <button @click="showPdfModal = true" class="btn-action bg-gray-600">
              📄 Voir
          </button>

          <button v-if="invoice.statut === 'en_attente_envoi'" @click="showFormModal = true" class="btn-action bg-blue-500">
              ✏️ Modifier
          </button>

          <button v-if="invoice.statut === 'en_attente_envoi'" @click="showDeleteModal = true" class="btn-action bg-red-500">
              🗑️ Supprimer
          </button>

          <button v-if="invoice.statut === 'en_attente_envoi'" @click="showMailModal = true" class="btn-action bg-yellow-500">
              📩 Envoyer
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

import dayjs from 'dayjs'

import { useInvoicesStore } from '@/stores/factures'
import { storeToRefs } from 'pinia'

const invoicesStore = useInvoicesStore()
const { invoicePaid } = invoicesStore
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
    case 'en_attente_envoi':
      return "bg-yellow-500 text-white px-3 py-1 rounded-full text-xs";
    case 'en_attente_paiement':
      return "bg-blue-500 text-white px-3 py-1 rounded-full text-xs";
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
    await invoicePaid(invoice)
}
</script>