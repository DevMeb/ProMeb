<template>
  <div class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-md z-50">
    <!-- Overlay cliquable pour fermer la modale -->
    <div @click.self="close" class="absolute inset-0"></div>

    <div class="relative bg-gray-900 p-6 sm:p-8 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-auto border border-gray-800">
      <!-- En-tête -->
      <div class="flex justify-between items-center pb-4 border-b border-gray-800">
        <div>
          <h2 class="text-2xl font-bold text-white">🧾 Nouvelle facture</h2>
          <p v-if="clientChoisi" class="text-sm text-gray-400 mt-1">
            {{ clientChoisi.nom }}
          </p>
        </div>
        <button @click="close" class="text-gray-400 hover:text-white transition" aria-label="Fermer">✕</button>
      </div>

      <!-- Chargement -->
      <div v-if="loadingPrestations.fetch" class="mt-8 space-y-3">
        <div v-for="n in 3" :key="n" class="h-16 bg-gray-800 rounded-xl animate-pulse"></div>
      </div>

      <!-- Aucune prestation à facturer, tous clients confondus -->
      <div
        v-else-if="unbilledPrestations.length === 0"
        class="mt-8 p-8 text-center rounded-xl border-2 border-dashed border-gray-800"
      >
        <div class="text-4xl mb-3">🎉</div>
        <p class="text-white font-semibold mb-1">Tout est facturé</p>
        <p class="text-gray-400">Aucune prestation en attente de facturation.</p>
      </div>

      <!-- ÉTAPE 1 — choisir le client -->
      <!-- Une facture ne peut concerner qu'un seul client : en le choisissant
           d'abord, on rend l'erreur « clients différents » impossible. -->
      <div v-else-if="!clientChoisi" class="mt-6">
        <h3 class="text-sm uppercase tracking-wide text-gray-400 mb-3">
          Pour quel client ?
        </h3>

        <div class="space-y-2">
          <button
            v-for="entree in clientsAFacturer"
            :key="entree.client.id"
            @click="choisirClient(entree.client)"
            class="w-full flex items-center justify-between p-4 rounded-xl border border-gray-800 bg-gray-800/50 hover:border-indigo-500 hover:bg-indigo-500/10 transition text-left"
          >
            <span class="font-semibold text-white">{{ entree.client.nom }}</span>
            <span class="text-sm text-gray-400 tabular-nums">
              {{ entree.nombre }} prestation{{ entree.nombre > 1 ? 's' : '' }} ·
              {{ formatNombre(entree.heures) }} h
            </span>
          </button>
        </div>
      </div>

      <!-- ÉTAPE 2 — choisir les prestations de ce client -->
      <div v-else class="mt-6">
        <button
          @click="revenirAuxClients"
          class="text-sm text-gray-400 hover:text-white transition mb-4"
        >
          ← Changer de client
        </button>

        <!-- Filtre par mois : seulement s'il y a plusieurs mois à distinguer -->
        <div v-if="moisDisponibles.length > 1" class="flex flex-wrap gap-2 mb-4">
          <button
            @click="moisFiltre = ''"
            class="px-3 py-1 rounded-full text-sm border transition"
            :class="moisFiltre === ''
              ? 'bg-indigo-500 border-indigo-500 text-white'
              : 'border-gray-700 text-gray-400 hover:border-gray-500'"
          >
            Tous les mois
          </button>
          <button
            v-for="mois in moisDisponibles"
            :key="mois"
            @click="moisFiltre = mois"
            class="px-3 py-1 rounded-full text-sm border transition"
            :class="moisFiltre === mois
              ? 'bg-indigo-500 border-indigo-500 text-white'
              : 'border-gray-700 text-gray-400 hover:border-gray-500'"
          >
            {{ libelleMois(mois) }}
          </button>
        </div>

        <!-- Tout sélectionner : ne porte que sur les prestations visibles -->
        <label class="flex items-center gap-2 p-3 rounded-xl bg-gray-800/50 border border-gray-800 cursor-pointer mb-3">
          <input
            type="checkbox"
            :checked="toutEstSelectionne"
            :indeterminate.prop="selectionPartielle"
            @change="basculerTout"
            class="h-5 w-5 rounded border-gray-700 bg-gray-800 text-indigo-500 focus:ring-2 focus:ring-indigo-500"
          />
          <span class="text-white font-medium">
            Tout sélectionner
            <span class="text-gray-400 font-normal">({{ prestationsVisibles.length }})</span>
          </span>
        </label>

        <div class="space-y-2">
          <label
            v-for="prestation in prestationsVisibles"
            :key="prestation.id"
            class="flex items-center gap-3 p-3 rounded-xl border bg-gray-800/50 cursor-pointer transition"
            :class="idsSelectionnes.includes(prestation.id)
              ? 'border-indigo-500 bg-indigo-500/10'
              : 'border-gray-800 hover:border-gray-600'"
          >
            <!-- On stocke l'identifiant, pas l'objet : une liste rafraîchie par
                 le store recréerait les objets et viderait la sélection. -->
            <input
              type="checkbox"
              v-model="idsSelectionnes"
              :value="prestation.id"
              class="h-5 w-5 rounded border-gray-700 bg-gray-800 text-indigo-500 focus:ring-2 focus:ring-indigo-500"
            />

            <div class="flex-1 min-w-0 flex flex-wrap items-center gap-x-4 gap-y-1">
              <span class="font-medium text-white tabular-nums">{{ formatDate(prestation.date) }}</span>
              <span class="text-gray-400 tabular-nums">{{ formatNombre(prestation.heures) }} h</span>
              <span class="text-gray-400 tabular-nums">{{ formatEuros(prestation.taux_horaire.taux) }} / h</span>
              <span class="text-gray-500 truncate">{{ prestation.adresse }}</span>
            </div>

            <span class="font-semibold text-white tabular-nums whitespace-nowrap">
              {{ formatEuros(prestation.heures * prestation.taux_horaire.taux) }}
            </span>
          </label>
        </div>

        <!-- Récapitulatif -->
        <div v-if="idsSelectionnes.length > 0" class="mt-5 p-4 rounded-xl bg-gray-800 border border-gray-700">
          <div class="flex justify-between items-center text-sm mb-2">
            <span class="text-gray-400">
              {{ idsSelectionnes.length }} prestation{{ idsSelectionnes.length > 1 ? 's' : '' }} ·
              {{ formatNombre(totalHeures) }} h
            </span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-white font-semibold">Montant HT</span>
            <span class="text-2xl font-bold text-white tabular-nums">{{ formatEuros(totalHT) }}</span>
          </div>
        </div>

        <!-- L'erreur s'affiche DANS la modale : la sélection n'est jamais perdue. -->
        <div
          v-if="messageErreur"
          class="mt-4 p-3 bg-red-500/10 text-red-400 rounded-lg border border-red-500/30"
        >
          ⚠️ {{ messageErreur }}
        </div>

        <!-- Actions -->
        <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
          <button
            @click="close"
            class="px-6 py-3 rounded-xl font-semibold bg-gray-700 hover:bg-gray-600 text-white transition"
          >
            Annuler
          </button>
          <button
            @click="creerLaFacture"
            :disabled="loading.add || idsSelectionnes.length === 0"
            class="px-6 py-3 rounded-xl font-semibold bg-indigo-500 hover:bg-indigo-400 text-white transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <span v-if="loading.add" class="animate-spin border-2 border-white border-t-transparent rounded-full w-4 h-4"></span>
            <span v-else>✅</span>
            Créer la facture
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { usePrestationsStore } from '@/stores/prestations';
import { useInvoicesStore } from '@/stores/factures';
import { formatDate, formatNombre, formatEuros } from '@/utils';

const emit = defineEmits(['close']);

const prestationsStore = usePrestationsStore();
const { unbilledPrestations, loading: loadingPrestations } = storeToRefs(prestationsStore);
const { fetchPrestations } = prestationsStore;

const invoicesStore = useInvoicesStore();
const { addInvoice, clearErrors } = invoicesStore;
const { loading, errors } = storeToRefs(invoicesStore);

onMounted(() => {
  fetchPrestations();
});

const clientChoisi = ref(null);
const idsSelectionnes = ref([]);
const moisFiltre = ref('');

/**
 * Les clients ayant au moins une prestation à facturer, avec leur volume.
 * C'est l'étape 1 : choisir le client rend impossible une facture à cheval
 * sur plusieurs clients — ce que le serveur refuse.
 */
const clientsAFacturer = computed(() => {
  const parClient = new Map();

  for (const prestation of unbilledPrestations.value) {
    const client = prestation.client;
    if (!client) continue;

    if (!parClient.has(client.id)) {
      parClient.set(client.id, { client, nombre: 0, heures: 0 });
    }

    const entree = parClient.get(client.id);
    entree.nombre += 1;
    entree.heures += parseFloat(prestation.heures);
  }

  return [...parClient.values()].sort((a, b) => a.client.nom.localeCompare(b.client.nom));
});

// Les prestations du client choisi, les plus récentes d'abord.
const prestationsDuClient = computed(() => {
  if (!clientChoisi.value) return [];

  return unbilledPrestations.value
    .filter(p => p.client?.id === clientChoisi.value.id)
    .sort((a, b) => b.date.localeCompare(a.date));
});

// `date` arrive au format Y-m-d : le mois est son préfixe.
const moisDisponibles = computed(() => {
  const mois = new Set(prestationsDuClient.value.map(p => p.date.slice(0, 7)));
  return [...mois].sort().reverse();
});

const prestationsVisibles = computed(() => {
  if (!moisFiltre.value) return prestationsDuClient.value;
  return prestationsDuClient.value.filter(p => p.date.startsWith(moisFiltre.value));
});

const toutEstSelectionne = computed(() =>
  prestationsVisibles.value.length > 0 &&
  prestationsVisibles.value.every(p => idsSelectionnes.value.includes(p.id))
);

const selectionPartielle = computed(() =>
  !toutEstSelectionne.value &&
  prestationsVisibles.value.some(p => idsSelectionnes.value.includes(p.id))
);

const prestationsSelectionnees = computed(() =>
  prestationsDuClient.value.filter(p => idsSelectionnes.value.includes(p.id))
);

const totalHeures = computed(() =>
  prestationsSelectionnees.value.reduce((somme, p) => somme + parseFloat(p.heures), 0)
);

const totalHT = computed(() =>
  prestationsSelectionnees.value.reduce(
    (somme, p) => somme + parseFloat(p.heures) * parseFloat(p.taux_horaire.taux),
    0
  )
);

/**
 * L'erreur vient de deux endroits selon le code HTTP, et c'est un piège :
 * apiCall range un 422 dans `errors.validationErrors` (SANS déclencher de toast),
 * et tout le reste dans `errors.add` (avec toast). Or 422 est justement le cas
 * nominal ici — prestation déjà facturée, clients différents. N'afficher que
 * `errors.add` laisserait l'utilisateur devant un bouton qui ne fait « rien ».
 */
const messageErreur = computed(() =>
  errors.value.validationErrors?.prestations?.[0] ?? errors.value.add ?? null
);

function nettoyerErreurs() {
  clearErrors('add');
  clearErrors('validationErrors');
}

function libelleMois(mois) {
  const [annee, m] = mois.split('-');
  const nom = new Intl.DateTimeFormat('fr-FR', { month: 'long' })
    .format(new Date(Number(annee), Number(m) - 1, 1));
  return `${nom} ${annee}`;
}

function choisirClient(client) {
  clientChoisi.value = client;
  idsSelectionnes.value = [];
  moisFiltre.value = '';
  nettoyerErreurs();
}

function revenirAuxClients() {
  clientChoisi.value = null;
  idsSelectionnes.value = [];
  moisFiltre.value = '';
  nettoyerErreurs();
}

// Ne bascule que les prestations visibles : un filtre de mois actif ne doit pas
// embarquer silencieusement les prestations des autres mois.
function basculerTout() {
  const idsVisibles = prestationsVisibles.value.map(p => p.id);

  if (toutEstSelectionne.value) {
    idsSelectionnes.value = idsSelectionnes.value.filter(id => !idsVisibles.includes(id));
  } else {
    idsSelectionnes.value = [...new Set([...idsSelectionnes.value, ...idsVisibles])];
  }
}

async function creerLaFacture() {
  if (!idsSelectionnes.value.length) return;

  const succes = await addInvoice({ prestations: [...idsSelectionnes.value] });

  // On ne ferme QUE si la création a réussi : sinon la sélection serait perdue
  // et le message d'erreur ne serait jamais lu.
  if (succes) {
    close();
  }
}

const close = () => {
  nettoyerErreurs();
  emit('close');
};
</script>
