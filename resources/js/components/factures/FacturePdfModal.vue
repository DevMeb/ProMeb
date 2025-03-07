<template>
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

      <div class="bg-white p-6 rounded-lg shadow-lg w-4/5 h-4/5 relative">

        <!-- Bouton de fermeture -->
        <button @click="emit('close')" class="absolute top-3 right-3 text-gray-600 hover:text-gray-800">
          ✖
        </button>
  
        <h2 class="text-xl font-semibold text-gray-800 mb-4">🧾 Facture #{{ invoice.id }}</h2>
  
        <!-- Affichage du PDF -->
        <iframe v-if="pdfUrl" :src="pdfUrl" class="w-full h-full"></iframe>
  
        <!-- 🔄 Affichage du chargement -->
        <div v-else class="flex flex-col items-center justify-center h-full">
          <div v-if="loading.pdf" class="flex flex-col items-center space-y-2">
            <span class="animate-spin border-4 border-gray-400 border-t-transparent rounded-full w-10 h-10"></span>
            <p class="text-gray-500">Chargement du PDF...</p>
          </div>
          <div v-else-if="error" class="text-red-500 text-center">
            <p>❌ Erreur lors du chargement du PDF.</p>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { onMounted, ref } from "vue";
  import { useInvoicesStore } from "@/stores/factures";
  import { storeToRefs } from "pinia";
  
  const props = defineProps({
    invoice: Object,
  });
  
  const emit = defineEmits(["close"]);
  
  const invoicesStore = useInvoicesStore();
  const { getInvoicePdf } = invoicesStore
  const { loading } = storeToRefs(invoicesStore)
  const pdfUrl = ref("");
  const error = ref(null);
  
  onMounted(async () => {
    try {
      error.value = null;
      pdfUrl.value = null;

      pdfUrl.value = await getInvoicePdf(props.invoice.id);

    } catch (err) {
      error.value = "Impossible de charger le PDF.";
    }
  });

</script>
  