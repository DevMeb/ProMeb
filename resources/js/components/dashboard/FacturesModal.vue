<template>
  <div class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50">
    <div class="bg-gray-900 p-8 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-auto border border-gray-800">
      
      <!-- En-tête -->
      <div class="flex justify-between items-center pb-4 border-b border-gray-800">
        <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">
          🧾 {{ title }}
        </h2>
        <button @click="close" class="text-gray-400 hover:text-white transition-transform hover:scale-110">
          ✕
        </button>
      </div>

      <!-- Liste des factures -->
      <div class="mt-6 space-y-4">

        <!-- Aucune facture -->
        <div v-if="factures.length === 0" class="p-6 text-center rounded-xl border-2 border-dashed border-gray-800 hover:border-indigo-500 transition-colors">
          <div class="text-4xl mb-3">🎉</div>
          <p class="text-gray-400">Aucune facture disponible.</p>
        </div>

        <!-- Factures existantes -->
        <div v-else>
          <div v-for="invoice in factures" :key="invoice.id" class="mt-2 bg-gray-800 p-4 rounded-lg shadow-md border border-gray-700">
            
            <!-- En-tête Facture -->
            <div class="flex items-center justify-between pb-2 border-b border-gray-700">
              <span class="font-bold text-md bg-gray-900 text-gray-400 px-3 py-1 rounded-full">
                📄 Facture #{{ invoice.id }}
              </span>
              <button 
                @click="showPdfModal = invoice.id" 
                class="bg-indigo-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-indigo-500 transition">
                📄 Voir PDF
              </button>

              <button 
                v-if="invoice.statut === 'en_attente_paiement'" 
                @click="markAsPaid(invoice)" 
                :disabled="loading.paid"
                class="btn-action bg-green-500 disabled:opacity-50">
              <span v-if="loading.paid" class="animate-spin border-4 border-white border-t-transparent rounded-full w-4 h-4"></span>
              ✅ Payé
          </button>
            </div>

            <!-- Informations Facture -->
            <div class="grid grid-cols-2 gap-4 mt-3 text-lg">
              <div class="flex items-center gap-2 text-gray-300">
                🕒 <span class="font-semibold">{{ invoice.heures_total }} h</span>
              </div>
              <div class="flex items-center gap-2 text-green-400">
                💰 <span class="font-semibold">{{ invoice.montant_total }} €</span>
              </div>
            </div>

            <!-- Prestations associées -->
            <div class="mt-4 space-y-2">
              <h3 class="text-md font-semibold text-gray-300 flex items-center gap-2">
                📌 Prestations incluses :
              </h3>
              <div v-for="prestation in invoice.prestations" :key="prestation.id" class="bg-gray-800 p-3 rounded-lg border border-gray-700">
                <div class="grid grid-cols-2 gap-4">
                  <div class="flex items-center gap-2 text-gray-400">
                    📅 <span class="font-semibold">{{ formatDate(prestation.date) }}</span>
                  </div>
                  <div class="flex items-center gap-2 text-gray-400">
                    🏢 <span class="font-semibold">{{ prestation.client.nom }}</span>
                  </div>
                  <div class="flex items-center gap-2 text-gray-400">
                    ⏰ <span class="font-semibold">{{ prestation.heures }} h</span>
                  </div>
                  <div class="flex items-center gap-2 text-green-400">
                    💵 <span class="font-semibold">{{ prestation.taux_horaire.taux }} € / h</span>
                  </div>
                </div>
              </div>
            </div>

            <FacturePdfModal v-if="showPdfModal === invoice.id" :invoice="invoice" @close="showPdfModal = null" />
          </div>
        </div>
      </div>

      <!-- Récapitulatif -->
      <div v-if="factures.length !== 0" class="mt-6 bg-gray-800 p-4 rounded-xl border border-gray-700">
        <h3 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
          📝 Récapitulatif
        </h3>
        
        <div class="space-y-2">
          <div class="flex justify-between items-center">
            <span class="text-gray-400">Total heures :</span>
            <span class="text-indigo-400 font-bold">{{ totalHeures }} h</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-400">Montant HT :</span>
            <span class="text-green-400 font-bold">{{ totalHT }} €</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { formatDate } from "@/utils";
import FacturePdfModal from "@/components/factures/FacturePdfModal.vue";
import { useInvoicesStore } from '@/stores/factures'
import { storeToRefs } from "pinia";

// Props et événements
const props = defineProps({
  factures: Array,
  title: String,
  required: true,
});

const emit = defineEmits(["close"]);

const invoiceStore = useInvoicesStore()
const { loading } = storeToRefs(invoiceStore)
const { paid } = invoiceStore

const showPdfModal = ref(null);

// Calcul du total des heures et du montant HT
const totalHeures = computed(() => {
  return props.factures.reduce((sum, invoice) => sum + parseFloat(invoice.heures_total), 0);
});

const totalHT = computed(() => {
  return props.factures.reduce((total, invoice) => total + invoice.montant_total, 0);
});

// 📌 Marquer la facture comme payée avec chargement
async function markAsPaid(invoice) {
    await paid(invoice.id)
}

// Fonction pour fermer la modale
const close = () => {
  emit("close");
};
</script>
