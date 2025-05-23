<script setup>
import { Button, Card } from "primevue";
import { IconAlertSquareRoundedFilled, IconEdit } from "@tabler/icons-vue";
import { ref, watchEffect } from "vue";
import { generalFormat } from "@/Composables/format.js";
import { usePage } from "@inertiajs/vue3";
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import EditWorkInformation from "@/Pages/Member/Listing/MemberDetail/Partials/EditWorkInformation.vue";
import EditTradingBackground from "@/Pages/Member/Listing/MemberDetail/Partials/EditTradingBackground.vue";

const props = defineProps({
    user_id: Number
})

const { formatAmount, formatDateTime } = generalFormat();
const backgroundInfo = ref([]);
const isLoading = ref(false);

const getBackgroundInfoData = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get(`/member/getBackgroundInfoData?id=${props.user_id}`);
        backgroundInfo.value = response.data;
    } catch (error) {
        console.error('Error fetching adjustment history data:', error);
    } finally {
        isLoading.value = false;
    }
}

// getBackgroundInfoData();

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getBackgroundInfoData();
    }
});

</script>

<template>
    <div class="w-full flex flex-col items-center justify-center gap-5">
        <Loader v-if="isLoading" />

        <template v-else>
            <Card class="w-full">
                <template #title>
                    <div class="flex gap-5 items-center justify-between">
                        {{ $t('public.work_information') }}
                        <EditWorkInformation :background="backgroundInfo" :isLoading="isLoading" />
                    </div>
                </template>

                <template #content>
                    <div class="flex flex-col gap-3 items-center">
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.employment_status') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">Self Employed</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.occupation') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">Software Consultant</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.industry') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">Finance</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.source_of_income') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">Salary</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.annual_income') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">$100,000+</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.net_worth') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">$200,000+</span>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="w-full">
                <template #title>
                    <div class="flex gap-5 items-center justify-between">
                        {{ $t('public.trading_background') }}
                        <EditTradingBackground :background="backgroundInfo" :isLoading="isLoading" />
                    </div>
                </template>

                <template #content>
                    <div class="flex flex-col gap-3 items-center">
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.trading_experience') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">>1 {{ $t('public.years') }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.types_of_products_traded') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">Forex, Crypto, Stocks</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.number_of_trades_per_month') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">1-5</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.familiarity_with_trading_concepts') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">Leverage, Margin, Stop Loss</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.experience_in_trading_platforms') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">MT4, MT5, cTrader</span>
                        </div>
                    </div>
                </template>
            </Card>
        </template>
    </div>
</template>