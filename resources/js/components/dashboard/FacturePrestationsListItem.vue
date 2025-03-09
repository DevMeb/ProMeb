<template>
    <tr>
        <td class="px-4 py-2 text-gray-300">{{ prestation.id }}</td>
        <td class="px-4 py-2 text-gray-300">{{ formatDate(prestation.date) }}</td>
        <td class="px-4 py-2 text-gray-300">{{ prestation.heures }} h</td>
        <td class="px-4 py-2 text-gray-300">{{ prestation.horaires }}</td>
        <td class="px-4 py-2 text-gray-300">{{ prestation.adresse }}</td>
      </tr>
</template>

<script setup>
import { formatDate } from '@/utils';
const props = defineProps({ 
    prestation: Object,
});
</script>