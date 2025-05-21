<script setup>
import {Card} from "primevue";
import {generalFormat} from "@/Composables/format.js";
import {
    IconUserDollar,
    IconCurrencyDollar,
    IconTrendingUp,
    IconTrendingDown,
} from "@tabler/icons-vue";

defineProps({
    investorsCount: Number,
    investorsTrend: Number,
    fundSize: Number,
    fundSizeTrend: Number,
})

const {formatAmount} = generalFormat();
</script>

<template>
    <div class="flex flex-col md:flex-row gap-5 items-center w-full self-stretch">
        <Card class="w-full">
            <template #content>
                <div class="flex gap-3 items-center">
                    <div class="flex flex-col w-full">
                        <span class="text-sm w-full text-surface-400 dark:text-surface-500">{{ $t('public.investors') }}</span>
                        <div
                            v-if="investorsCount !== null"
                            class="flex gap-2 items-end"
                        >
                            <span class="text-xl font-medium">{{ formatAmount(investorsCount, 0, '') }}</span>
                            <div
                                v-if="investorsTrend !== 0"
                                :class="[
                                    'flex gap-1 items-center',
                                    {
                                        'text-green-500': investorsTrend > 0,
                                        'text-red-500': investorsTrend < 0,
                                    }
                                ]">
                                <IconTrendingUp
                                    v-if="investorsTrend > 0"
                                    size="18"
                                    stroke-width="1.5"
                                />
                                <IconTrendingDown
                                    v-else-if="investorsTrend < 0"
                                    size="18"
                                    stroke-width="1.5"
                                />
                                {{ formatAmount(investorsTrend) }}% <span class="text-xs text-surface-500">{{ $t('public.compare_last_month') }}</span>
                            </div>
                        </div>
                        <div v-else class="text-xl">
                            {{ $t('public.calculating') }}...
                        </div>
                    </div>
                    <div class="bg-cyan-100 dark:bg-cyan-900/30 rounded-md text-cyan-500 p-2">
                        <IconUserDollar size="28" stroke-width="1.5" />
                    </div>
                </div>
            </template>
        </Card>

        <Card class="w-full">
            <template #content>
                <div class="flex gap-3 items-center">
                    <div class="flex flex-col w-full">
                        <span class="text-sm w-full text-surface-400 dark:text-surface-500">{{ $t('public.fund_size') }}</span>
                        <div
                            v-if="fundSize !== null"
                            class="flex gap-2 items-end"
                        >
                            <span class="text-xl font-medium">{{ formatAmount(fundSize) }}</span>
                            <div
                                v-if="fundSizeTrend !== 0"
                                :class="[
                                    'flex gap-1 items-center',
                                    {
                                        'text-green-500': fundSizeTrend > 0,
                                        'text-red-500': fundSizeTrend < 0,
                                    }
                                ]">
                                <IconTrendingUp
                                    v-if="fundSizeTrend > 0"
                                    size="18"
                                    stroke-width="1.5"
                                />
                                <IconTrendingDown
                                    v-else-if="fundSizeTrend < 0"
                                    size="18"
                                    stroke-width="1.5"
                                />
                                {{ formatAmount(fundSizeTrend) }}% <span class="text-xs text-surface-500">{{ $t('public.compare_last_month') }}</span>
                            </div>
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
