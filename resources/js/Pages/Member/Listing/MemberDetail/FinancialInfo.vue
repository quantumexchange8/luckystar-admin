<script setup>
import { IconCoins } from '@tabler/icons-vue';
import { LockedIcon, DepositIcon, WithdrawalIcon } from '@/Components/Icons/solid';
import { computed, h, ref, watchEffect } from "vue";
import Empty from '@/Components/Empty.vue';
import WalletAdjustment from '@/Pages/Member/Listing/MemberDetail/Partials/WalletAdjustment.vue'
import { wTrans } from "laravel-vue-i18n";
import { generalFormat } from "@/Composables/format.js";
import { usePage } from "@inertiajs/vue3";
import Loader from '@/Components/Loader.vue';

const props = defineProps({
    user_id: Number
})

const isLoading = ref(false);
const wallets = ref(null);
const transactionHistory = ref([]);
const { formatAmount, formatDateTime } = generalFormat();

const getFinancialData = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get(`/member/getFinancialInfoData?id=${props.user_id}`);

        wallets.value = response.data.wallets;
        transactionHistory.value = response.data.transactionHistory;
    } catch (error) {
        console.error('Error get info:', error);
    } finally {
        isLoading.value = false;
    }
};

getFinancialData();

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getFinancialData();
    }
});
</script>

<template>
    <div class="flex flex-col xl:flex-row items-center xl:items-start justify-center gap-5 self-stretch">
        <Loader v-if="isLoading" />

        <template v-else>
            <div class="flex flex-col md:flex-row xl:flex-col gap-5 w-full xl:max-w-[438px]">
                <!-- Wallet -->
                <div v-for="index in 2" :key="index" class="w-full xl:w-[438px] h-[200px] self-stretch relative">
                    <div 
                        class="flex flex-col justify-center items-start px-6 py-5 gap-5 rounded-2xl relative z-0 h-[200px]"
                        :class="[
                            index === 1 ? 'bg-primary-500' : 'bg-blue-500'
                        ]"
                    >
                        <div class="flex justify-between items-start self-stretch">
                            <div class="flex justify-center items-center gap-2">
                                <div 
                                    class="w-10 h-10 p-2.5 flex justify-center items-center rounded-lg bg-[#FAFAFF]"
                                    :class="[
                                        index === 1 ? 'text-primary-500' : 'text-blue-500'
                                    ]"
                                >
                                    <IconCoins size="20" stroke-width="1.25" />
                                </div>
                                <span class="text-surface-100 font-medium">
                                    {{ $t('public.' + (wallets?.[index - 1]?.type || '-')) }}
                                </span>
                            </div>
                            <WalletAdjustment v-if="wallets?.[index - 1]" :wallet="wallets[index - 1]" />
                        </div>
                        <div class="flex flex-col justify-center items-start px-5 py-4 gap-2 self-stretch bg-white bg-opacity-30">
                            <div class="text-surface-100 text-xs font-medium">{{ $t('public.wallet_balance') }}</div>
                            <div class="text-white text-xxl font-semibold">
                                {{ wallets?.[index - 1] ? formatAmount(wallets[index - 1].balance) : '-' }}
                            </div>
                        </div>
                    </div>
                    <div 
                        v-if="!wallets?.[index - 1]"
                        class="absolute inset-0 flex flex-col justify-center items-center rounded-2xl shadow-input backdrop-blur-sm z-10"
                    >
                        <LockedIcon class="w-[100px] h-[100px] flex-shrink-0" />
                        <div class="text-surface-950 dark:text-white text-center text-sm font-semibold">
                            {{ $t('public.wallet_lock_desc') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white dark:bg-surface-800 flex flex-col p-4 md:py-6 md:px-8 gap-3 w-full self-stretch shadow-toast rounded-2xl max-h-[360px] md:max-h-[420px]">
                <div class="flex py-2 items-center self-stretch">
                    <div class="text-surface-950 dark:text-white text-sm font-bold">{{ $t('public.recent_transaction') }}</div>
                </div>

                <div v-if="transactionHistory?.length <= 0" class="flex flex-col items-center flex-1 self-stretch">
                    <Empty :message="$t('public.empty_transaction_message')" />
                </div>
                <div v-else 
                    class="flex flex-col items-center flex-1 self-stretch overflow-auto"
                    style="-ms-overflow-style: none; scrollbar-width: none;"
                >
                    <div v-for="(transaction, index) in transactionHistory" :key="index"
                        class="flex py-2 items-center gap-5 self-stretch border-b border-surface-200"
                        :class="{ 'border-transparent': index === transactionHistory.length - 1 }"
                    >
                        <div class="flex flex-col items-start justify-center gap-1 w-full">
                            <span class="text-surface-950 dark:text-white text-sm font-semibold">
                                {{ transaction.transaction_type === 'deposit' ? transaction.to_meta_login : transaction.from_meta_login }}
                            </span>
                            <span class="text-surface-500 dark:text-surface-300 text-xs">{{ formatDateTime(transaction.approval_at) }}</span>
                        </div>
                        <div 
                            class="w-[120px] truncate text-right font-semibold" 
                            :class="{
                                'text-green-500': transaction.transaction_type === 'deposit',
                                'text-red-500': transaction.transaction_type === 'withdrawal',
                            }"
                        >
                            {{ formatAmount(transaction.transaction_type === 'deposit' ? transaction.transaction_amount : transaction.amount) }}
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
