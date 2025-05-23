<script setup>
import {IconAlertCircleFilled} from '@tabler/icons-vue';
import { ref, computed, watchEffect } from 'vue';
import Empty from '@/Components/Empty.vue';
import { generalFormat } from "@/Composables/format.js";
import { usePage } from "@inertiajs/vue3";
import AccountAction from "@/Pages/Member/Listing/MemberDetail/Partials/AccountAction.vue";
import { Tag, Card, ProgressSpinner } from "primevue";

const props = defineProps({
    user_id: Number
})

const { formatRgbaColor, formatAmount } = generalFormat()
const tradingAccounts = ref();
const isLoading = ref(false);

const getTradingAccounts = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/member/getTradingAccounts?id=${props.user_id}`);

        tradingAccounts.value = response.data.tradingAccounts;
        // console.log(tradingAccounts);
    } catch (error) {
        console.error('Error get trading accounts:', error);
    } finally {
        isLoading.value = false;
    }
};
getTradingAccounts();

// Function to check if an account is inactive for 90 days
function isInactive(date) {
  const updatedAtDate = new Date(date);
  const currentDate = new Date();

  // Get only the date part (remove time)
  const updatedAtDay = new Date(updatedAtDate.getFullYear(), updatedAtDate.getMonth(), updatedAtDate.getDate());
  const currentDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate());

  // Calculate the difference in days by direct subtraction
  const diffDays = (currentDay - updatedAtDay) / (1000 * 60 * 60 * 24);

  // Determine if inactive (more than 90 days)
  return diffDays > 90;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getTradingAccounts();
    }
});

</script>

<template>
    <div v-if="isLoading" class="flex flex-col gap-2 items-center justify-center">
        <ProgressSpinner strokeWidth="4" />
    </div>

    <div v-else-if="!isLoading && tradingAccounts?.length <= 0">
        <Empty :message="$t('public.empty_trading_account_message')" />
    </div>
    <div v-else class="grid md:grid-cols-2 gap-5">
        <Card
            class="w-full"
            v-for="(account, index) in tradingAccounts"
            :key="index"
        >
            <template #content>
                <div class="flex gap-3 items-center self-stretch relative">
                    <div
                        class="absolute -left-2 w-2.5 rounded-full h-full"
                        :style="{'backgroundColor': `#${account.account_type_color}`}"
                    ></div>
                    <div class="flex flex-col gap-5 w-full self-stretch pl-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex flex-col items-center self-stretch">
                                <div class="flex items-center gap-2 self-stretch">
                                    <div class="self-stretch flex items-center gap-4 truncate text-sm text-surface-600 dark:text-surface-500">
                                        <span class="font-semibold text-lg">
                                            #{{ account.meta_login }}
                                        </span>
                                        <Tag
                                            :style="{
                                            backgroundColor: formatRgbaColor(account.account_type_color, 0.2),
                                            color: `#${account.account_type_color}`,
                                        }"
                                            :value="account.account_type_name"
                                        />
                                    </div>
                                </div>
                                <div class="flex flex-row gap-2 items-center self-stretch text-xs">
                                    <span class="text-surface-500">{{ $t('public.name') }}:</span>
                                    <span class="font-medium">{{ account.user_name }}</span>
                                </div>
                            </div>
                            <AccountAction
                                :account="account"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.balance') }}:</span>
                                <span class="font-medium">{{ formatAmount(account.balance, 2)}}</span>
                            </div>

                            <div class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.equity') }}:</span>
                                <span class="font-medium">{{ formatAmount(account.equity, 2) }}</span>
                            </div>

                            <div class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.credit') }}:</span>
                                <span class="font-medium">{{ formatAmount(account.credit, 2) }}</span>
                            </div>

                            <div class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.leverage') }}:</span>
                                <span class="font-medium">1:{{ account.margin_leverage }}</span>
                            </div>

                            <!-- <div class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">
                                    {{ account.account_category === 'managed' ? $t('public.pamm') : $t('public.credit') }}:
                                </span>
                                <span class="font-medium">
                                    {{ account.account_category === 'managed' ? account.pamm : formatAmount(account.credit, 2) }}
                                </span>
                            </div>

                            <div class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">
                                    {{ account.account_category === 'managed' ? $t('public.mature_in') : $t('public.leverage') }}:
                                </span>
                                <span class="font-medium">
                                    {{ account.account_category === 'managed' ? account.mature_in : '1:' + account.margin_leverage }}
                                </span>
                            </div> -->
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <!-- <div
            v-for="tradingAccount in tradingAccounts" :key="tradingAccount.id"
            class="flex flex-col min-w-[300px] items-center px-5 py-4 gap-3 rounded-2xl border-l-8 bg-white dark:bg-surface-800 shadow-toast"
            :style="{'borderColor': `#${tradingAccount.account_type_color}`}"
        >
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center gap-4">
                    <div class="text-surface-950 dark:text-white text-lg font-semibold">#{{ tradingAccount.meta_login }}</div>
                    <div
                        class="flex px-2 py-1 justify-center items-center text-xs font-semibold hover:-translate-y-1 transition-all duration-300 ease-in-out rounded"
                        :style="{
                            backgroundColor: formatRgbaColor(tradingAccount.account_type_color, 0.15),
                            color: `#${tradingAccount.account_type_color}`,
                        }"
                    >
                        {{ $t('public.' + tradingAccount.account_type) }}
                    </div>
                    <div v-if="isInactive(tradingAccount.updated_at)" class="text-red-500">
                        <IconAlertCircleFilled :size="20" stroke-width="1.25" />
                    </div>
                </div>
                <AccountAction
                    :account="tradingAccount"
                />
            </div>
            <div class="grid grid-cols-2 gap-2 self-stretch">
                <div class="w-full flex items-center gap-1 flex-grow">
                    <span class="text-surface-500 dark:text-surface-300 text-xs w-16">{{ $t('public.balance') }}:</span>
                    <span class="text-surface-950 dark:text-white text-xs font-medium">{{ tradingAccount.balance ? formatAmount(tradingAccount.balance) : formatAmount(0) }}</span>
                </div>
                <div class="w-full flex items-center gap-1 flex-grow">
                    <span class="text-surface-500 dark:text-surface-300 text-xs w-16">{{ $t('public.equity') }}:</span>
                    <span class="text-surface-950 dark:text-white text-xs font-medium">{{ tradingAccount.equity ? formatAmount(tradingAccount.equity) : formatAmount(0) }}</span>
                </div>
                <div class="w-full flex items-center gap-1 flex-grow">
                    <span class="text-surface-500 dark:text-surface-300 text-xs w-16">{{ $t('public.credit') }}:</span>
                    <div class="text-surface-950 dark:text-white text-xs font-medium">
                        <span>{{ tradingAccount.credit ? formatAmount(tradingAccount.credit) : formatAmount(0) }}</span>
                    </div>
                </div>
                <div class="w-full flex items-center gap-1 flex-grow">
                    <span class="text-surface-500 dark:text-surface-300 text-xs w-16">{{ $t('public.leverage') }}:</span>
                    <div class="text-surface-950 dark:text-white text-xs font-medium">
                        <span>{{ tradingAccount.leverage === 0 ? $t('public.free') : `1:${tradingAccount.leverage}` }}</span>
                    </div>
                </div>
            </div>
        </div> -->
    </div>
</template>
