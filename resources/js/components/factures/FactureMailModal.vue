<template>
  <div class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md">
    <!-- Overlay cliquable pour fermer la modale -->
    <div @click.self="closeModal" class="fixed inset-0"></div>

    <div class="bg-white p-6 rounded-xl shadow-xl w-[90%] sm:w-96 transform transition-all animate-fade-in">
      <!-- ✨ Titre de la modale -->
      <div class="flex items-center justify-between border-b pb-2">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
          📩 Envoyer la facture par email
        </h2>
        <button @click="closeModal" class="text-gray-500 hover:text-gray-700 transition">✖️</button>
      </div>

      <!-- 📋 Formulaire -->
      <form @submit.prevent="submitForm" class="mt-4 space-y-4">
        <!-- Champ Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">
            Adresse(s) email(s) <span class="text-gray-500">(séparées par ",")</span>
          </label>
          <input 
            type="text" 
            v-model="emails" 
            class="w-full border p-2 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500" 
            placeholder="ex: user1@example.com; user2@example.com"
          />
          <p v-if="errors.emailAddress" class="error-message">{{ errors.emailAddress }}</p>
        </div>

        <!-- ⚡️ Boutons d'action -->
        <div class="flex justify-end space-x-3 mt-4">
          <button type="button" @click="closeModal" class="btn-secondary">Annuler</button>
          <button type="submit" class="btn-primary flex items-center" :disabled="loading.sendEmail">
            <span v-if="loading.sendEmail" class="animate-spin mr-2">⏳</span>
            Envoyer
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useInvoicesStore } from '@/stores/factures';
import { validateEmail } from '@/utils';
import { storeToRefs } from 'pinia';

const props = defineProps({
  invoice: Object, // L'objet facture
});
const emit = defineEmits(["close"]);

const invoicesStore = useInvoicesStore();
const { sendEmail } = invoicesStore;
const { loading } = storeToRefs(invoicesStore);

const emails = ref(props.invoice?.reservation?.renter?.tutor?.email || "");
const errors = ref({});

// Validation et envoi des emails
const submitForm = async () => {
  errors.value = {};

  // Découper la chaîne par ";" et supprimer les espaces
  const emailsArray = emails.value.split(",").map(email => email.trim()).filter(email => email.length > 0);

  // Vérifier la validité de chaque email
  const invalidEmails = emailsArray.filter(email => !validateEmail(email));

  if (invalidEmails.length > 0) {
    errors.value.emailAddress = "Adresse(s) email invalide(s)";
    return;
  }

  try {
    await sendEmail(props.invoice.id, emailsArray);
    closeModal();
  } catch (err) {
    console.error("Erreur lors de l'envoi de la facture par email :", err);
  }
};

// Fermeture de la modale
const closeModal = () => {
  errors.value = {};
  emit('close');
};
</script>
