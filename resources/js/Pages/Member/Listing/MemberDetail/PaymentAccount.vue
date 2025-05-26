<script setup>
import { Card, Tag } from 'primevue';
import { ref } from 'vue';
import PaymentAccountAction from '@/Pages/Member/Listing/MemberDetail/Partials/PaymentAccountAction.vue';

const props = defineProps({
    user_id: Number
})

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
        account_no: 'Tsa556df56sf127v12bg3612g312328n31273812',
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

const tooltipText = ref('copy')
const copiedText = ref('')

function copyToClipboard(text) {
    const textToCopy = text;
    copiedText.value = text;

    const textArea = document.createElement('textarea');
    document.body.appendChild(textArea);

    textArea.value = textToCopy;
    textArea.select();

    try {
        const successful = document.execCommand('copy');

        tooltipText.value = 'copied';
        setTimeout(() => {
            tooltipText.value = 'copy';
        }, 1500);
    } catch (err) {
        console.error('Copy to clipboard failed:', err);
    }

    document.body.removeChild(textArea);
}
</script>

<template>
    <div class="flex flex-col gap-5 items-center self-stretch w-full">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3 md:gap-5 w-full">
            <Card
                v-for="paymentAccount in paymentAccounts"
                class="border-l-8 border-white-600 dark:border-white-800 rounded-md w-full"
            >
                <template #content>
                    <div class="flex flex-col justify-between gap-5">
                        <div class="flex gap-3 justify-between items-center self-stretch">
                            <span class="text-xs text-surface-600 dark:text-surface-400 uppercase">{{ $t(`public.${paymentAccount.payment_platform}`) }}</span>
                            <PaymentAccountAction
                                :paymentAccount="paymentAccount"
                            />
                        </div>

                        <div class="w-full relative">
                            <Tag
                                v-if="tooltipText === 'copied' && copiedText === paymentAccount.account_no"
                                class="absolute w-fit -top-6 left-24"
                                :value="$t(`public.${tooltipText}`)"
                            />
                            <span @click="copyToClipboard(paymentAccount.account_no)" class="text-surface-ground dark:text-white break-words select-none cursor-pointer">
                                {{ paymentAccount.account_no }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center gap-2 text-sm font-medium text-surface-600 dark:text-surface-400">
                            {{ paymentAccount.payment_account_name }}
                            <Tag
                                :severity="paymentAccount.platform === 'bank' ? 'info' : 'secondary'"
                                :value="paymentAccount.payment_platform_name"
                            />
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
