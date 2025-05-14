<script setup>
import {Card} from "primevue";
import {generalFormat} from "@/Composables/format.js";

defineProps({
    totalWalletTopUp: Number,
    totalWalletWithdrawal: Number,
    totalActiveCapital: Number,
})

const {formatAmount} = generalFormat();
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div class="flex flex-col gap-5 items-center self-stretch w-full">
                <div class="flex flex-col md:flex-row gap-1 md:gap-5 items-start md:items-center md:justify-between self-stretch">
                    <span class="text-sm md:text-base font-semibold">{{ $t('public.total_net_balance') }} ($)</span>
                    <div class="text-xxl font-semibold">
                        {{ formatAmount(Number(totalWalletTopUp) - Number(totalWalletWithdrawal)) }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row gap-5 items-center self-stretch">
                    <div class="w-full flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                        <div class="py-4 px-6 flex flex-col items-center gap-2 self-stretch md:gap-3 md:flex-1 border-b-4 border-green-500 bg-gradient-to-t from-[#06d00114] to-[#ffffff14] dark:to-[#18181b] transition-all duration-200">
                            <div class="self-stretch text-lg font-semibold md:text-xl">
                                {{ formatAmount(totalWalletTopUp) }}
                            </div>
                            <div class="text-left w-full text-green-700 dark:text-green-400 text-sm md:text-xs xl:text-sm">
                                {{ $t('public.total_wallet_top_up') }}
                            </div>
                        </div>
                    </div>
                    <div class="w-full flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                        <div class="py-4 px-6 flex flex-col items-center gap-2 self-stretch md:gap-3 md:flex-1 border-b-4 border-rose-500 bg-gradient-to-t from-[#ff2d5814] to-[#ffffff14] dark:to-[#18181b] transition-all duration-200">
                            <div class="self-stretch text-lg font-semibold md:text-xl">
                                {{ formatAmount(totalWalletWithdrawal) }}
                            </div>
                            <div class="text-left w-full text-rose-700 dark:text-rose-500 text-sm md:text-xs xl:text-sm">
                                {{ $t('public.total_wallet_withdrawal') }}
                            </div>
                        </div>
                    </div>
                    <div class="w-full flex flex-col items-center gap-3 self-stretch md:flex-row md:gap-5">
                        <div class="py-4 px-6 flex flex-col items-center gap-2 self-stretch md:gap-3 md:flex-1 border-b-4 border-blue-500 bg-gradient-to-t from-[#e0f2fe] dark:from-[#1e3a8a33] to-[#e0f2fe14] dark:to-[#18181b] transition-all duration-200">
                            <div class="self-stretch text-lg font-semibold md:text-xl">
                                {{ formatAmount(totalActiveCapital) }}
                            </div>
                            <div class="text-left w-full text-blue-700 dark:text-blue-500 text-sm md:text-xs xl:text-sm">
                                {{ $t('public.total_active_capital') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Card>
</template>
