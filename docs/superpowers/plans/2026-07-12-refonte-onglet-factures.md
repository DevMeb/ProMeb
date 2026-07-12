# Refonte de l'onglet Factures — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remplacer la grille de cartes de l'onglet Factures par une liste dense en lignes, dont les prestations se déplient, avec des filtres statut / client / mois.

**Architecture :** Le store des factures gagne des filtres côté client (même pattern que le store des prestations). `FactureListItem` devient une ligne dépliable au lieu d'une carte, et délègue le détail à un nouveau `FacturePrestationsTable`. `FacturesList` orchestre les filtres et les quatre états d'affichage. Aucun changement backend.

**Tech Stack :** Vue 3 (`<script setup>`, Composition API), Pinia, Tailwind CSS v4, Vite.

## Global Constraints

- Spec de référence : `docs/superpowers/specs/2026-07-12-refonte-onglet-factures-design.md`
- **Aucune modification backend** : pas de migration, pas de contrôleur, pas de ressource API, pas de route. Si une tâche semble l'exiger, c'est une erreur — s'arrêter et le signaler.
- **Tri : `invoice.id` décroissant.** NE PAS trier sur `created_at` : `FactureResource` le renvoie au format `d/m/Y H:i:s`, que JavaScript ne sait ni trier ni parser nativement. Les identifiants croissent avec le temps et donnent le même ordre.
- **Filtre par mois : comparaison de préfixe.** `PrestationResource` renvoie `date` au format `Y-m-d`, donc `prestation.date.startsWith(month_year)` suffit. Aucun parsing de date.
- `invoice.prestations[0]` doit toujours être accédé avec `?.` — une facture sans prestation ferait planter le rendu de toute la liste.
- Les horaires s'affichent dans cette vue même si le client a `afficher_horaires = false` : ce réglage ne concerne que le PDF envoyé au client.
- Statuts possibles : `en_attente_envoi`, `en_attente_paiement`, `payé`.
- **Pas de tests front sur ce projet.** La vérification de chaque tâche est : `npm run build` (attrape les erreurs de compilation Vue) + un contrôle visuel dans l'app sur http://localhost:8080. La suite `php artisan test --testsuite=Feature` doit rester verte (68 tests) — aucune tâche ne touche au backend.
- Vue 3 `<script setup>`, Tailwind, classes globales existantes (`.btn-action`, `.filter-input`, `.btn-secondary`) — suivre le style des composants voisins.
- Commits en français, format `type: description`.

---

### Task 1 : Les filtres dans le store

**Files:**
- Modify: `resources/js/stores/factures.js`

**Interfaces:**
- Consumes: rien (première tâche).
- Produces: le store expose en plus `activeFilters` (`{ statut, client_id, month_year }`), `updateFilters(filters)`, `isAnyFilterActive` (computed booléen), et `filteredInvoices` (ref, triée par `id` décroissant). Les tâches 2, 4 et 5 en dépendent.

Le store des prestations (`resources/js/stores/prestations.js`, lignes 12-43) fait déjà exactement ça. On calque, en adaptant les critères.

- [ ] **Step 1: Ajouter les filtres au store**

Dans `resources/js/stores/factures.js`, remplacer la ligne d'import de Vue :

```js
import { ref, computed, watch } from 'vue';
```

Puis, juste après `const loading = ref({});`, ajouter :

```js
  // Filtres de la liste des factures (appliqués côté client)
  const activeFilters = ref({
    statut: '',
    client_id: '',
    month_year: '',
  });

  function updateFilters(filters) {
    activeFilters.value = filters;
  }

  const isAnyFilterActive = computed(() => {
    return Object.values(activeFilters.value).some(value => value !== "");
  });

  // Factures filtrées, les plus récentes en tête.
  // Le tri se fait sur l'id : created_at arrive au format d/m/Y H:i:s,
  // que JavaScript ne sait pas trier.
  const filteredInvoices = ref([]);
  watch([invoices, activeFilters], () => {
    filteredInvoices.value = invoices.value
      .filter(invoice => {
        const { statut, client_id, month_year } = activeFilters.value;

        if (statut && invoice.statut !== statut) return false;

        if (client_id && invoice.prestations?.[0]?.client_id !== client_id) return false;

        // La facture est retenue si au moins une de ses prestations
        // tombe dans le mois demandé. prestation.date est au format Y-m-d.
        if (month_year && !invoice.prestations?.some(p => p.date?.startsWith(month_year))) return false;

        return true;
      })
      .sort((a, b) => b.id - a.id);
  }, { deep: true, immediate: true });
```

- [ ] **Step 2: Exposer les nouvelles clés**

Toujours dans `resources/js/stores/factures.js`, compléter l'objet retourné en fin de store :

```js
  return {
    invoices,
    filteredInvoices,
    activeFilters,
    updateFilters,
    isAnyFilterActive,
    errors,
    loading,
    fetchInvoices,
    addInvoice,
    deleteInvoice,
    clearErrors,
    getInvoicePdf,
    paid,
  };
```

- [ ] **Step 3: Vérifier le build**

Run: `npm run build`
Expected: build Vite réussi, aucune erreur.

- [ ] **Step 4: Commit**

```bash
git add resources/js/stores/factures.js
git commit -m "feat: ajoute les filtres et le tri au store des factures"
```

---

### Task 2 : Le formatage des nombres, et le tableau des prestations dépliées

Composant isolé : il ne connaît que sa liste de prestations et ne sait rien de la ligne qui le contient.

**Files:**
- Modify: `resources/js/utils.js`
- Create: `resources/js/components/factures/FacturePrestationsTable.vue`
- Modify: `resources/js/components/factures/index.js`

**Interfaces:**
- Consumes: rien du store.
- Produces:
  - `formatNombre(valeur)` et `formatEuros(valeur)`, exportés depuis `@/utils` — la tâche 4 les importe aussi. Ne PAS les redéfinir dans les composants.
  - `<FacturePrestationsTable :prestations="invoice.prestations" />`. Prop unique `prestations` (Array, requis). La tâche 4 l'utilise.

- [ ] **Step 1: Ajouter les formateurs aux utilitaires**

`resources/js/utils.js` porte déjà `formatDate`. Y ajouter, à la fin du fichier, les deux formateurs partagés — ils servent dans le tableau (tâche 2) ET dans la ligne (tâche 4), donc ils n'ont pas leur place dans un composant :

```js
export function formatNombre(valeur) {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(valeur);
}

export function formatEuros(valeur) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(valeur);
}
```

- [ ] **Step 2: Créer le composant**

Créer `resources/js/components/factures/FacturePrestationsTable.vue` :

```vue
<template>
  <div class="overflow-x-auto rounded-lg bg-gray-950/60 ring-1 ring-gray-700">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
          <th class="px-3 py-2 font-medium">Date</th>
          <th class="px-3 py-2 font-medium">Horaires</th>
          <th class="px-3 py-2 font-medium text-right">Heures</th>
          <th class="px-3 py-2 font-medium text-right">Taux</th>
          <th class="px-3 py-2 font-medium text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="prestation in prestations"
          :key="prestation.id"
          class="border-t border-gray-800 text-gray-200"
        >
          <td class="px-3 py-2 whitespace-nowrap tabular-nums">{{ formatDate(prestation.date) }}</td>
          <td class="px-3 py-2 text-gray-400">{{ prestation.horaires || '—' }}</td>
          <td class="px-3 py-2 text-right tabular-nums">{{ formatNombre(prestation.heures) }}</td>
          <td class="px-3 py-2 text-right tabular-nums">{{ formatEuros(prestation.taux_horaire.taux) }}</td>
          <td class="px-3 py-2 text-right tabular-nums font-medium text-white">
            {{ formatEuros(prestation.heures * prestation.taux_horaire.taux) }}
          </td>
        </tr>
      </tbody>
      <tfoot>
        <tr class="border-t border-gray-700 font-semibold text-white">
          <td class="px-3 py-2" colspan="2">
            {{ prestations.length }} prestation{{ prestations.length > 1 ? 's' : '' }}
          </td>
          <td class="px-3 py-2 text-right tabular-nums">{{ formatNombre(totalHeures) }}</td>
          <td class="px-3 py-2"></td>
          <td class="px-3 py-2 text-right tabular-nums">{{ formatEuros(totalMontant) }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDate, formatNombre, formatEuros } from '@/utils';

const props = defineProps({
  prestations: {
    type: Array,
    required: true,
  },
});

const totalHeures = computed(() =>
  props.prestations.reduce((acc, p) => acc + parseFloat(p.heures), 0)
);

const totalMontant = computed(() =>
  props.prestations.reduce((acc, p) => acc + parseFloat(p.heures) * parseFloat(p.taux_horaire.taux), 0)
);
</script>
```

- [ ] **Step 3: Exporter le composant**

Dans `resources/js/components/factures/index.js`, ajouter la ligne d'export en suivant le format des lignes existantes du fichier (lire le fichier d'abord pour reproduire son style exact) :

```js
export { default as FacturePrestationsTable } from './FacturePrestationsTable.vue';
```

- [ ] **Step 4: Vérifier le build**

Run: `npm run build`
Expected: build Vite réussi.

- [ ] **Step 5: Commit**

```bash
git add resources/js/utils.js resources/js/components/factures/FacturePrestationsTable.vue resources/js/components/factures/index.js
git commit -m "feat: ajoute le tableau des prestations d'une facture"
```

---

### Task 3 : Les filtres de la liste

**Files:**
- Create: `resources/js/components/factures/FactureFilters.vue`
- Modify: `resources/js/components/factures/index.js`

**Interfaces:**
- Consumes: du store des factures (tâche 1) — `activeFilters`, `updateFilters`, `isAnyFilterActive`.
- Produces: `<FactureFilters />`, sans prop. La tâche 5 le monte.

Ce composant calque `resources/js/components/prestations/PrestationsFilters.vue` : les filtres vivent dans le store, un `watch` profond les propage, le bouton de réinitialisation n'apparaît que si un filtre est actif.

- [ ] **Step 1: Créer le composant**

Créer `resources/js/components/factures/FactureFilters.vue` :

```vue
<template>
  <div class="bg-gray-800 p-4 rounded-lg shadow-lg flex flex-col sm:flex-row gap-4 sm:items-end">
    <div class="flex-1">
      <label for="filtre-statut" class="text-sm text-gray-300 font-semibold">Statut :</label>
      <select id="filtre-statut" v-model="activeFilters.statut" class="filter-input">
        <option value="">Tous les statuts</option>
        <option value="en_attente_envoi">En attente d'envoi</option>
        <option value="en_attente_paiement">En attente de paiement</option>
        <option value="payé">Payé</option>
      </select>
    </div>

    <div class="flex-1">
      <label for="filtre-client" class="text-sm text-gray-300 font-semibold">Client :</label>
      <select id="filtre-client" v-model="activeFilters.client_id" class="filter-input">
        <option value="">Tous les clients</option>
        <option v-for="client in clients" :key="client.id" :value="client.id">
          {{ client.nom }}
        </option>
      </select>
    </div>

    <div class="flex-1">
      <label for="filtre-mois" class="text-sm text-gray-300 font-semibold">Mois des prestations :</label>
      <input id="filtre-mois" type="month" v-model="activeFilters.month_year" class="filter-input" />
    </div>

    <button v-if="isAnyFilterActive" @click="resetFilters" class="btn-secondary whitespace-nowrap">
      Réinitialiser
    </button>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useInvoicesStore } from '@/stores/factures';
import { useClientsStore } from '@/stores/clients';
import { storeToRefs } from 'pinia';

const invoicesStore = useInvoicesStore();
const clientsStore = useClientsStore();

const { activeFilters, isAnyFilterActive } = storeToRefs(invoicesStore);
const { updateFilters } = invoicesStore;

const { fetchClients } = clientsStore;
const { clients } = storeToRefs(clientsStore);

onMounted(() => {
  fetchClients();
});

watch(activeFilters, (newFilters) => {
  updateFilters(newFilters);
}, { deep: true });

const resetFilters = () => {
  updateFilters({
    statut: '',
    client_id: '',
    month_year: '',
  });
};
</script>
```

- [ ] **Step 2: Exporter le composant**

Dans `resources/js/components/factures/index.js`, ajouter (en suivant le style du fichier) :

```js
export { default as FactureFilters } from './FactureFilters.vue';
```

- [ ] **Step 3: Vérifier le build**

Run: `npm run build`
Expected: build Vite réussi.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/factures/FactureFilters.vue resources/js/components/factures/index.js
git commit -m "feat: ajoute les filtres de la liste des factures"
```

---

### Task 4 : La ligne de facture dépliable

Le cœur de la refonte. `FactureListItem.vue` cesse d'être une carte : il devient une ligne, et son détail est délégué au tableau de la tâche 2.

**Files:**
- Modify (récriture complète) : `resources/js/components/factures/FactureListItem.vue`

**Interfaces:**
- Consumes: `<FacturePrestationsTable :prestations="..." />` et les formateurs `formatNombre` / `formatEuros` de `@/utils` (tâche 2). Les importer, ne pas les redéfinir ici.
- Produces: `<FactureListItem :invoice="invoice" />`. Prop unique `invoice` (Object, requis). La tâche 5 l'utilise.

Points de conception à respecter :
- La ligne entière est cliquable pour déplier, **mais les boutons d'action ne doivent pas déclencher le dépliement** — d'où `@click.stop` sur le conteneur des actions.
- Le déclencheur porte `aria-expanded` et `aria-controls` pour rester utilisable au clavier.
- Sous le point de rupture `sm`, la ligne se réorganise en carte : les colonnes deviennent des paires libellé / valeur. Les libellés sont masqués sur grand écran (`sm:hidden`), puisque l'en-tête de colonnes les porte déjà.
- L'état d'ouverture est local à la ligne : plusieurs factures peuvent rester dépliées en même temps.

- [ ] **Step 1: Récrire le composant**

Remplacer entièrement le contenu de `resources/js/components/factures/FactureListItem.vue` par :

```vue
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
```

Note : l'ancien composant importait `FactureMailModal` et `formatDate` sans les utiliser réellement dans la nouvelle structure — ils ne sont volontairement pas repris. Si `FactureMailModal` était monté ailleurs, ne pas y toucher : il n'est simplement plus monté ici (il ne l'était que derrière `showMailModal`, jamais mis à `true` — code mort, comme le confirme une lecture de l'ancien fichier).

- [ ] **Step 2: Vérifier le build**

Run: `npm run build`
Expected: build Vite réussi, aucun import non résolu.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/factures/FactureListItem.vue
git commit -m "feat: transforme la carte de facture en ligne depliable"
```

---

### Task 5 : La liste, ses filtres et ses états

**Files:**
- Modify (récriture complète) : `resources/js/components/factures/FacturesList.vue`
- Modify : `resources/js/views/Facture.vue`

**Interfaces:**
- Consumes: `filteredInvoices`, `isAnyFilterActive`, `updateFilters`, `errors`, `loading` du store (tâche 1) ; `<FactureFilters />` (tâche 3) ; `<FactureListItem :invoice="…" />` (tâche 4).
- Produces: `<FacturesList @create="…" />` — émet `create` quand l'utilisateur clique sur le bouton de l'état vide. `Facture.vue` l'écoute pour ouvrir le modal de création.

- [ ] **Step 1: Récrire la liste**

Remplacer entièrement le contenu de `resources/js/components/factures/FacturesList.vue` par :

```vue
<template>
  <div class="mt-6">
    <FactureFilters />

    <!-- Chargement : des lignes fantômes, pas un spinner, pour que la page ne saute pas -->
    <div v-if="loading.fetch" class="mt-6 bg-gray-800 rounded-lg ring-1 ring-gray-700 overflow-hidden">
      <div v-for="n in 3" :key="n" class="flex items-center gap-4 px-4 py-4 border-b border-gray-700 last:border-b-0">
        <div class="h-4 w-12 bg-gray-700 rounded animate-pulse"></div>
        <div class="h-4 flex-1 bg-gray-700 rounded animate-pulse"></div>
        <div class="h-4 w-20 bg-gray-700 rounded animate-pulse"></div>
        <div class="h-4 w-24 bg-gray-700 rounded animate-pulse"></div>
        <div class="h-6 w-28 bg-gray-700 rounded-full animate-pulse"></div>
      </div>
    </div>

    <!-- Erreur -->
    <div v-else-if="errors.fetch" class="mt-6 flex justify-center bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
      <span class="text-xl">❌</span>
      <p class="text-lg font-medium ml-2">{{ errors.fetch }}</p>
    </div>

    <!-- Aucune facture du tout -->
    <div v-else-if="invoices.length === 0" class="mt-6 bg-gray-800 rounded-lg ring-1 ring-gray-700 px-6 py-12 text-center">
      <p class="text-3xl mb-2">📭</p>
      <p class="text-white font-semibold mb-1">Aucune facture</p>
      <p class="text-gray-400 mb-5">Créez votre première facture à partir de prestations non facturées.</p>
      <button @click="emit('create')" class="btn-primary">Ajouter une facture</button>
    </div>

    <!-- Aucun résultat pour les filtres actifs : message distinct du précédent -->
    <div v-else-if="filteredInvoices.length === 0" class="mt-6 bg-gray-800 rounded-lg ring-1 ring-gray-700 px-6 py-12 text-center">
      <p class="text-3xl mb-2">🔍</p>
      <p class="text-white font-semibold mb-1">Aucun résultat</p>
      <p class="text-gray-400 mb-5">Aucune facture ne correspond à ces filtres.</p>
      <button @click="resetFilters" class="btn-secondary">Réinitialiser les filtres</button>
    </div>

    <!-- La liste -->
    <div v-else class="mt-6 bg-gray-800 rounded-lg ring-1 ring-gray-700 overflow-hidden">
      <!-- En-tête de colonnes : masqué sur mobile, où chaque ligne devient une carte -->
      <div class="hidden sm:flex items-center px-4 py-2 border-b border-gray-700 text-xs uppercase tracking-wide text-gray-400">
        <span class="w-[100px] shrink-0">N°</span>
        <span class="flex-1">Client</span>
        <span class="w-[100px] text-right">Heures</span>
        <span class="w-[130px] text-right">Montant</span>
        <span class="w-[160px] pl-4">Statut</span>
        <span class="w-[200px]"></span>
      </div>

      <FactureListItem
        v-for="invoice in filteredInvoices"
        :key="invoice.id"
        :invoice="invoice"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useInvoicesStore } from '@/stores/factures';
import { FactureListItem, FactureFilters } from '@/components/factures/';

const emit = defineEmits(['create']);

const invoicesStore = useInvoicesStore();
const { fetchInvoices, updateFilters } = invoicesStore;
const { invoices, filteredInvoices, errors, loading } = storeToRefs(invoicesStore);

onMounted(() => {
  fetchInvoices();
});

const resetFilters = () => {
  updateFilters({
    statut: '',
    client_id: '',
    month_year: '',
  });
};
</script>
```

- [ ] **Step 2: Brancher l'événement de création dans la vue**

Dans `resources/js/views/Facture.vue`, la liste est montée par `<FacturesList />`. La remplacer par :

```html
            <FacturesList @create="showFormModal = true" />
```

Le reste du fichier ne change pas : `showFormModal` existe déjà et pilote `<FactureFormModal>`.

- [ ] **Step 3: Vérifier le build**

Run: `npm run build`
Expected: build Vite réussi.

- [ ] **Step 4: Vérifier la suite backend (aucune régression attendue)**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 68 tests. Aucune tâche de ce plan ne touche au backend ; un échec ici signalerait une erreur.

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/factures/FacturesList.vue resources/js/views/Facture.vue
git commit -m "feat: liste dense des factures avec filtres et etats"
```

---

## Vérification finale

Contrôle manuel dans l'application (http://localhost:8080, onglet Factures) :

- [ ] La liste s'affiche en lignes, la plus récente en haut.
- [ ] Cliquer une ligne déplie le tableau des prestations ; recliquer le replie.
- [ ] Deux lignes peuvent être dépliées en même temps.
- [ ] Cliquer un bouton d'action (PDF, supprimer, payé) ne déplie PAS la ligne.
- [ ] Le total du tableau déplié correspond au montant affiché sur la ligne.
- [ ] Filtre statut : sélectionner « Payé » ne laisse que les factures payées.
- [ ] Filtre client : sélectionner un client ne laisse que ses factures.
- [ ] Filtre mois : saisir le mois des prestations (juin 2026) remonte bien la facture correspondante, même si elle a été créée en juillet.
- [ ] Filtres combinés donnant zéro résultat : le message « Aucun résultat » s'affiche, distinct de « Aucune facture », avec un bouton de réinitialisation qui fonctionne.
- [ ] Sur mobile (fenêtre étroite) : chaque ligne devient une carte, aucun défilement horizontal de la page.
- [ ] Le tableau déplié défile horizontalement dans son propre conteneur sur petit écran, sans déborder.
- [ ] Navigation clavier : le chevron est atteignable au Tab et actionnable à l'Entrée.
