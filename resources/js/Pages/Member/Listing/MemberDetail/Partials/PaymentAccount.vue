<script setup>
import { ref } from "vue";
import { Tag } from 'primevue';
import PaymentAccountAction from "@/Pages/Member/Listing/MemberDetail/Partials/PaymentAccountAction.vue";

const props = defineProps({
    paymentAccounts: Array,
})

const activeTag = ref(null);
const tooltipText = ref('copy');
const copyToClipboard = (addressType, text) => {
    const textToCopy = text;

    const textArea = document.createElement('textarea');
    document.body.appendChild(textArea);

    textArea.value = textToCopy;
    textArea.select();

    try {
        const successful = document.execCommand('copy');

        tooltipText.value = 'copied';
        activeTag.value = addressType;
        setTimeout(() => {
            tooltipText.value = 'copy';
            activeTag.value = null;
        }, 1500);
    } catch (err) {
        console.error('Copy to clipboard failed:', err);
    }

    document.body.removeChild(textArea);
}

const paymentAccounts = ref([
    {
        payment_account_id: '1',
        account_no: '123123123123',
        payment_platform: 'bank',
        payment_platform_name: 'Maybank',
        bank_code: '123123qwe',
        payment_account_name: 'Ang Le Xuan',
        platform: 'bank',
    },
    {
        payment_account_id: '2',
        account_no: 'Tsa556df56sf127v12bg3612g312328n31273812Tsa556df56sf127v12bg3612g312328n31273812Tsa556df56sf127v12bg3612g312328n31273812Tsa556df56sf127v12bg3612g312328n31273812',
        payment_platform: 'crypto',
        payment_platform_name: 'USDT (BEP20)',
        payment_account_name: 'Ang Le Xuan',
        platform: 'crypto',
    },
    {
        payment_account_id: '3',
        account_no: 'Tsa556df56sf127v12bg3612g312328n31273812',
        payment_platform: 'crypto',
        payment_platform_name: 'USDT (TRC20)',
        payment_account_name: 'A.L.X.',
        platform: 'crypto',
    }
]);

</script>

<template>
    <div class="flex flex-col h-full max-h-full items-start p-4 md:py-6 md:px-8 gap-3 self-stretch rounded-2xl bg-white dark:bg-surface-800 shadow-toast">
        <div class="flex justify-between items-center self-stretch">
            <div class="text-surface-950 dark:text-white text-sm font-bold">{{ $t('public.payment_account_information') }}</div>
        </div>
        <div v-if="paymentAccounts" class="flex flex-col divide-y items-start self-stretch overflow-y-auto max-h-[250px]">
            <div
                v-for="(account, index) in paymentAccounts"
                :key="index"
                class="flex text-sm gap-1 self-stretch py-3 first:pt-0 last:pb-0"
            >
                <div class="w-full grid grid-cols-1 gap-1">
                    <div class="text-surface-950 dark:text-white font-semibold truncate w-full">{{ account.payment_account_name ? account.payment_account_name : '-' }}</div>
                    <div class="text-surface-500 dark:text-surface-300 font-medium break-all flex w-full relative" @click="copyToClipboard(`${account.payment_account_id}-account_no`, account.account_no)">
                        {{ account.account_no ? account.account_no : '-' }}
                        <Tag
                            v-if="activeTag === `${account.payment_account_id}-account_no` && tooltipText === 'copied'"
                            class="absolute -top-5 -right-3"
                            severity="contrast"
                            :value="$t(`public.${tooltipText}`)"
                        />
                    </div>
                    <div class="w-full flex items-center gap-3">
                        <Tag severity="primary" :value="$t(`public.${account.payment_platform}`)" />
                        <Tag severity="info" :value="$t(`public.${account.platform}`)" />
                    </div>
                </div>
                <div class="flex items-start justify-center">
                    <PaymentAccountAction
                        :paymentAccount="account"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
