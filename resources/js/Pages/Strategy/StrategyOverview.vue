<script setup>
import {Avatar, Card} from "primevue";
import {onMounted, ref} from "vue";
import {
    IconTriangleFilled,
    IconTriangleInvertedFilled
} from "@tabler/icons-vue";
import {generalFormat} from "@/Composables/format.js";

const {formatAmount, formatNameLabel} = generalFormat();
const isLoading = ref(false);
const topThreeMaster = ref([]);
const currentAssets = ref(0);
const lastMonthAssetComparison = ref(0);
const currentInvestors = ref(0);
const lastMonthInvestorComparison = ref(0);

const getHighestDeposit = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/strategy/getStrategiesOverview');
        topThreeMaster.value = response.data.topThreeMaster;
        currentAssets.value = response.data.currentAssets;
        lastMonthAssetComparison.value = response.data.lastMonthAssetComparison;
        currentInvestors.value = response.data.currentInvestors;
        lastMonthInvestorComparison.value = response.data.lastMonthInvestorComparison;
    } catch (error) {
        console.error('Error fetching recent approvals:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    getHighestDeposit();
});

const calculatePercentage = (fund) => {
    if (!currentInvestors.value || !fund) {
        return 0;
    }
    return Math.min(((fund / currentInvestors.value) * 100), 100);
};
</script>

<template>
    <div class="w-full flex flex-col md:flex-row gap-5 self-stretch">
        <Card class="w-full">
            <template #content>
                <div class="flex flex-col items-start gap-3">
                    <div class="flex flex-col gap-2 items-start self-stretch md:flex-shrink-0">
                        <div class="flex justify-center items-center">
                            <span class="text-surface-500 text-sm">{{ $t('public.total_active_capital') + `($)` }}</span>
                        </div>
                        <div class="flex items-end gap-5">
                            <span class="text-surface-950 dark:text-white text-xl font-semibold md:text-xxl">{{ currentAssets ? formatAmount(currentAssets) : $t('public.calculating') }}</span>
                            <div class="flex items-center pb-1.5 gap-2">
                                <div v-if="currentAssets" class="flex items-center gap-2">
                                    <div
                                        class="flex items-center gap-2"
                                        :class="
                                        {
                                            'text-green-500': lastMonthInvestorComparison > 0,
                                            'text-rose-500': lastMonthInvestorComparison < 0
                                        }"
                                    >
                                        <IconTriangleFilled v-if="lastMonthAssetComparison > 0" size="12" stroke-width="1.5" />
                                        <IconTriangleInvertedFilled v-if="lastMonthAssetComparison < 0" size="12" stroke-width="1.5" />
                                        <span class="text-xs font-medium md:text-sm">  {{ `${formatAmount(lastMonthAssetComparison)}%` }}</span>
                                    </div>
                                    <span class="text-surface-400 text-xs md:text-sm">{{ $t('public.compare_last_month') }}</span>
                                </div>
                                <span v-else class="text-surface-400 text-xs md:text-sm">{{ $t('public.data_not_available') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 items-center self-stretch w-full">
                        <div v-for="index in 3" :key="index" class="flex items-center py-2 gap-3 md:gap-4 w-full">
                            <div class="w-full flex items-center min-w-[140px] md:min-w-[180px] md:max-w-[240px] gap-3 md:gap-4">
                                <Avatar
                                    v-if="topThreeMaster[index - 1]?.trading_master?.media"
                                    :image="topThreeMaster[index - 1]?.trading_master.group_image"
                                    shape="circle"
                                    class="min-w-10"
                                />
                                <Avatar
                                    v-else
                                    :label="formatNameLabel(topThreeMaster[index - 1]?.trading_master.master_name)"
                                    shape="circle"
                                    class="min-w-10"
                                />
                                <span class="truncate w-full max-w-[180px] md:max-w-[200px] text-surface-950 dark:text-white text-sm font-medium md:text-base">
                                    {{ topThreeMaster[index - 1]?.trading_master.master_name || '------' }}
                                </span>
                            </div>
                            <div class="w-full max-w-[52px] xs:max-w-sm sm:max-w-md md:max-w-full h-1 bg-surface-100 dark:bg-surface-700 rounded-full relative">
                                <div
                                    class="absolute top-0 left-0 h-full rounded-full bg-primary-500 transition-all duration-700 ease-in-out"
                                    :style="{ width: `${calculatePercentage(topThreeMaster[index - 1]?.total_investment)}%` }"
                                />
                            </div>
                            <span class="truncate text-surface-950 dark:text-white text-right text-sm font-medium md:text-base w-full max-w-[88px] md:max-w-[110px]">
                                {{ topThreeMaster[index - 1]?.total_investment ? formatAmount(topThreeMaster[index - 1].total_investment) : formatAmount(0) }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <div class="w-full flex flex-col gap-5 md:max-w-[262px] xl:max-w-[358px] 2xl:max-w-[560px]">
            <Card>
                <template #content>
                    <span class="self-stretch text-surface-500 text-sm">{{ $t('public.current_joining_investors') }}</span>
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <span class="text-xl font-semibold md:text-xxl">{{ currentInvestors ? formatAmount(currentInvestors, 0, '') : 0 }}</span>
                        <div class="flex items-center pb-1.5 gap-2">
                            <div v-if="currentInvestors" class="flex items-center gap-2">
                                <div
                                    class="flex items-center gap-2"
                                    :class="
                                    {
                                        'text-green-500': lastMonthInvestorComparison > 0,
                                        'text-rose-500': lastMonthInvestorComparison < 0
                                    }"
                                >
                                    <IconTriangleFilled v-if="lastMonthInvestorComparison > 0" size="12" stroke-width="1.5" />
                                    <IconTriangleInvertedFilled v-if="lastMonthInvestorComparison < 0" size="12" stroke-width="1.5" />
                                    <span class="text-xs font-medium md:text-sm">  {{ `${lastMonthInvestorComparison} ${$t('public.pax')}` }}</span>
                                </div>
                                <span class="text-surface-400 text-xs md:text-sm">{{ $t('public.compare_last_month') }}</span>
                            </div>
                            <span v-else class="text-surface-400 text-xs md:text-sm">{{ $t('public.data_not_available') }}</span>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #content>
                    <span class="self-stretch text-surface-500 text-sm">{{ $t('public.today_profit_sharing') }}</span>
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <span class="text-xl font-semibold md:text-xxl">{{ currentInvestors ? formatAmount(currentInvestors) : $t('public.calculating') }}</span>
                        <div class="flex items-center pb-1.5 gap-2">
                            <div v-if="currentInvestors" class="flex items-center gap-2">
                                <div
                                    class="flex items-center gap-2"
                                    :class="
                                    {
                                        'text-green-500': lastMonthInvestorComparison > 0,
                                        'text-rose-500': lastMonthInvestorComparison < 0
                                    }"
                                >
                                    <IconTriangleFilled v-if="lastMonthInvestorComparison > 0" size="12" stroke-width="1.5" />
                                    <IconTriangleInvertedFilled v-if="lastMonthInvestorComparison < 0" size="12" stroke-width="1.5" />
                                    <span class="text-xs font-medium md:text-sm">  {{ `${lastMonthInvestorComparison} ${$t('public.pax')}` }}</span>
                                </div>
                                <span class="text-surface-400 text-xs md:text-sm">{{ $t('public.compare_last_month') }}</span>
                            </div>
                            <span v-else class="text-surface-400 text-xs md:text-sm">{{ $t('public.data_not_available') }}</span>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
