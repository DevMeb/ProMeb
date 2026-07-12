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
