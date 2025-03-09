<template>
    <div class="bg-gray-900 p-4 rounded-md">
        <!-- En-tête de la facture -->
        <div class="flex justify-between items-center mb-2">
          <div>
            <p class="text-gray-300 text-sm"><strong>ID:</strong> {{ facture.id }}</p>
            <p class="text-gray-300 text-sm"><strong>Date:</strong> {{ facture.created_at }}</p>
            <p class="text-gray-300 text-sm"><strong>Heures total:</strong> {{ facture.heures_total }} h</p>
            <p class="text-gray-300 text-sm"><strong>Montant total:</strong> {{ facture.montant_total }} €</p>
            <p class="text-gray-300 text-sm"><strong>Statut:</strong> {{ facture.statut }}</p>
          </div>
          <button @click="showPdfModal = true" class="btn-action bg-blue-500 text-white px-3 py-1 rounded-md text-sm">
            📄 Voir la facture
          </button>

          <button v-if="facture.statut === 'en_attente_envoi'" @click="showMailModal = true" class="btn-action bg-yellow-500">
            📩 Envoyer
          </button>

          <button v-if="facture.statut === 'en_attente_paiement'" @click="markAsPaid(facture)" :disabled="loading.paid"
              class="btn-action bg-green-500 disabled:opacity-50">
              <span v-if="loading.paid" class="animate-spin border-4 border-white border-t-transparent rounded-full w-4 h-4"></span>
              ✅ Payé
          </button>

        </div>
        <!-- Liste des prestations associées à la facture -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-700">
            <thead>
              <tr>
                <th class="px-4 py-2 text-left text-white">ID</th>
                <th class="px-4 py-2 text-left text-white">Date</th>
                <th class="px-4 py-2 text-left text-white">Heures</th>
                <th class="px-4 py-2 text-left text-white">Horaires</th>
                <th class="px-4 py-2 text-left text-white">Adresse</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <FacturePrestationsListItem v-for="prestation in facture.prestations" :key="prestation.id" :prestation="prestation" />
            </tbody>
          </table>
        </div>
      </div>

      <FacturePdfModal v-if="showPdfModal" :invoice="facture" @close="showPdfModal = false" />
      <FactureMailModal v-if="showMailModal" :invoice="facture" @close="showMailModal = false" />
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

// 📌 Marquer la facture comme payée avec chargement
async function markAsPaid(facture) {
    await invoicePaid(facture)
}

const props = defineProps({ 
    facture: Object,
});

const showPdfModal = ref(false);
const showMailModal = ref(false);
</script>