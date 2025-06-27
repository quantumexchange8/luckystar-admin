<script setup>
import { IconCurrencyDollar, IconBellDollar } from '@tabler/icons-vue';
import { Card } from 'primevue';
import { generalFormat } from '@/Composables/format';

const props = defineProps({
    totalRedemptionAmount: Number,
    redemptionCounts: Number,
})

const { formatAmount } = generalFormat();
</script>

<template>
    <div class="flex flex-col md:flex-row gap-5 items-center w-full self-stretch">
        <Card class="w-full">
            <template #content>
                <div class="flex gap-3 items-center">
                    <div class="flex flex-col w-full">
                        <span class="text-sm w-full text-surface-400 dark:text-surface-500">{{ $t('public.total_redemption') }}</span>
                        <div
                            v-if="redemptionCounts !== null"
                            class="flex gap-2 items-end"
                        >
                            <span class="text-xl font-medium">{{ redemptionCounts }}</span>
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
                        <span class="text-sm w-full text-surface-400 dark:text-surface-500">{{ $t('public.total_redemption_amount') }}</span>
                        <div
                            v-if="totalRedemptionAmount !== null"
                            class="flex gap-2 items-end"
                        >
                            <span class="text-xl font-medium">{{ formatAmount(totalRedemptionAmount ?? 0, 2) }}</span>
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
