<template>
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg flex flex-col space-y-4">
        <!-- En-tête -->
        <div class="flex justify-between items-center border-b pb-3">
            <h2 class="text-xl font-semibold text-white flex items-center gap-2">
              🧾 Facture #{{ invoice.id }}
            </h2>
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
                🕒 <span class="font-semibold text-white">Créée le :</span> {{ formatDate(invoice.created_at) }}
            </p>
            <p class="text-gray-300 text-sm">
                🔄 <span class="font-semibold text-white">Mise à jour le :</span> {{ formatDate(invoice.updated_at) }}
            </p>
        </div>

        <!-- 🔘 Actions -->
        <div class="flex flex-wrap justify-center gap-3 mt-4">
            <button @click="showPdfModal = true" class="btn-action bg-gray-600">
                📄 Voir
            </button>

            <button @click="showDeleteModal = true" class="btn-action bg-red-500">
                🗑️ Supprimer
            </button>
        </div>
    </div>

    <!-- Modals -->
    <FacturePdfModal v-if="showPdfModal" :invoice="invoice" @close="showPdfModal = false" />
    <FactureDeleteModal v-if="showDeleteModal" :invoice="invoice" @close="showDeleteModal = false" />
</template>

<script setup>
import { ref } from 'vue'
import { useInvoicesStore } from '@/stores/factures'
import { storeToRefs } from 'pinia'
import dayjs from 'dayjs'
import { 
    FacturePdfModal,
    FactureDeleteModal, 
} from '@/components/factures/'

const props = defineProps({
    invoice: Object,
})

const invoicesStore = useInvoicesStore()
const { loading } = storeToRefs(invoicesStore)

const showDeleteModal = ref(false)
const showPdfModal = ref(false)

// 📌 Styles des badges de statut
function getStatusBadge(status) {
    return {
        pending: "bg-yellow-500 text-white px-3 py-1 rounded-full text-xs",
        issued: "bg-blue-500 text-white px-3 py-1 rounded-full text-xs",
        paid: "bg-green-500 text-white px-3 py-1 rounded-full text-xs",
    }[status] || "bg-gray-500 text-white px-3 py-1 rounded-full text-xs"
}

// Fonction utilitaire pour formater les dates
function formatDate(date) {
    return dayjs(date).format('DD/MM/YYYY'); // format dd/mm/yyyy
  }
</script>