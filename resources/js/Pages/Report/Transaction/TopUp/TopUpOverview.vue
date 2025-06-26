<script setup>
import { IconCurrencyDollar, IconBellDollar } from '@tabler/icons-vue';
import { Card } from 'primevue';
import { generalFormat } from '@/Composables/format';

const props = defineProps({
    totalTopUpAmount: Number,
    topUpCounts: Number,
})

const { formatAmount } = generalFormat();
</script>

<template>
    <div class="flex flex-col md:flex-row gap-5 items-center w-full self-stretch">
        <Card class="w-full">
            <template #content>
                <div class="flex gap-3 items-center">
                    <div class="flex flex-col w-full">
                        <span class="text-sm w-full text-surface-400 dark:text-surface-500">{{ $t('public.total_top_up') }}</span>
                        <div
                            v-if="topUpCounts !== null"
                            class="flex gap-2 items-end"
                        >
                            <span class="text-xl font-medium">{{ topUpCounts }}</span>
                        </div>
                        <div v-else class="text-xl">
                            {{ $t('public.calculating') }}...
                        </div>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900/30 rounded-md text-yellow-500 p-2">
                        <IconBellDollar size="28" stroke-width="1.5" />
                    </div>
                </div>
            </template>
        </Card>

        <Card class="w-full">
            <template #content>
                <div class="flex gap-3 items-center">
                    <div class="flex flex-col w-full">
                        <span class="text-sm w-full text-surface-400 dark:text-surface-500">{{ $t('public.total_top_up_amount') }}</span>
                        <div
                            v-if="totalTopUpAmount !== null"
                            class="flex gap-2 items-end"
                        >
                            <span class="text-xl font-medium">{{ formatAmount(totalTopUpAmount ?? 0, 2) }}</span>
                        </div>
                        <div v-else class="text-xl">
                            {{ $t('public.calculating') }}...
                        </div>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/30 rounded-md text-green-500 p-2">
                        <IconCurrencyDollar size="28" stroke-width="1.5" />
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
