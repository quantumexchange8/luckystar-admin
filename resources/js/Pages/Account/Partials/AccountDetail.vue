<script setup>
import {Avatar, Divider, Skeleton, Tag} from "primevue";
import {generalFormat} from "@/Composables/format.js";
import {ref} from "vue";
import dayjs from "dayjs";

const props = defineProps({
    account: Object,
});

const {formatAmount,formatNameLabel} = generalFormat();

const data = ref([]);
const isLoading = ref(false);

const getTradingAccountData = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('member.getTradingAccountData', {
            meta_login: props.account.meta_login,
            type: props.account.account_type.type,
        }));
        data.value = response.data.data;
    } catch (error) {
        console.error('Error fetching strategies:', error);
    } finally {
        isLoading.value = false;
    }
};

getTradingAccountData();
</script>

<template>
    <div class="flex flex-col items-center self-stretch">
        <div class="flex gap-3 items-center self-stretch justify-between">
            <div class="flex gap-3 items-center self-stretch">
                <Avatar
                    v-if="account.user.media.length > 0"
                    :image="account.user.media[0].original_url"
                    shape="circle"
                    class="w-9 h-9 rounded-full overflow-hidden grow-0 shrink-0"
                />
                <Avatar
                    v-else
                    :label="formatNameLabel(account.user.full_name)"
                    shape="circle"
                    class="w-9 h-9 rounded-full overflow-hidden grow-0 shrink-0 text-sm"
                />
                <div class="flex flex-col">
                    <span class="text-sm font-medium">{{ account.user.full_name }}</span>
                    <span class="text-xs text-surface-500">{{ account.user.email }}</span>
                </div>
            </div>
            <div class="flex justify-end">
                <Tag
                    severity="secondary"
                    class="text-xs"
                    :value="account.account_type.name"
                />
            </div>
        </div>
        <Divider />
        <div class="flex flex-col gap-3 items-center self-stretch">
            <div class="grid grid-cols-2 w-full font-medium">
                <div class="text-xs text-surface-500 w-full">
                    {{ $t('public.account') }}
                </div>
                <div class="text-sm w-full">
                    {{ account.meta_login }}
                </div>
            </div>
            <div class="grid grid-cols-2 w-full font-medium">
                <div class="text-xs text-surface-500 w-full">
                    {{ $t('public.balance') }}
                </div>
                <Skeleton
                    v-if="isLoading"
                    width="5rem"
                    class="my-0.5"
                    borderRadius="2rem"
                />
                <div v-else class="text-sm w-full">
                    {{ formatAmount(data.balance) }}
                </div>
            </div>
            <div class="grid grid-cols-2 w-full font-medium">
                <div class="text-xs text-surface-500 w-full">
                    {{ $t('public.equity') }}
                </div>
                <Skeleton
                    v-if="isLoading"
                    width="5rem"
                    class="my-0.5"
                    borderRadius="2rem"
                />
                <div v-else class="text-sm w-full">
                    {{ formatAmount(data.equity) }}
                </div>
            </div>
            <div class="grid grid-cols-2 w-full font-medium">
                <div class="text-xs text-surface-500 w-full">
                    {{ $t('public.credit') }}
                </div>
                <Skeleton
                    v-if="isLoading"
                    width="5rem"
                    class="my-0.5"
                    borderRadius="2rem"
                />
                <div v-else class="text-sm w-full">
                    {{ formatAmount(data.credit) }}
                </div>
            </div>
            <div class="grid grid-cols-2 w-full font-medium">
                <div class="text-xs text-surface-500 w-full">
                    {{ $t('public.leverage') }}
                </div>
                <div class="text-sm w-full">
                    1:{{ account.margin_leverage }}
                </div>
            </div>
        </div>
        <Divider v-if="account.active_subscriptions.length" />
        <div
            v-if="account.active_subscriptions.length"
            class="flex flex-col gap-3 items-center self-stretch"
        >
            <div class="grid grid-cols-2 w-full font-medium">
                <div class="text-xs text-surface-500 w-full">
                    {{ $t('public.strategy') }}
                </div>
                <div class="text-sm w-full">
                    {{ account.active_subscriptions[0].trading_master.master_name }}
                </div>
            </div>
            <div class="grid grid-cols-2 w-full font-medium">
                <div class="text-xs text-surface-500 w-full">
                    {{ $t('public.account') }}
                </div>
                <div class="text-sm w-full">
                    {{ account.active_subscriptions[0].master_meta_login }}
                </div>
            </div>
            <div class="grid grid-cols-2 w-full font-medium">
                <div class="text-xs text-surface-500 w-full">
                    {{ $t('public.capital') }}
                </div>
                <div class="text-sm w-full">
                    {{ formatAmount(account.active_subscriptions_sum_subscription_amount) }}
                </div>
            </div>
            <div class="grid grid-cols-2 place-items-start w-full font-medium">
                <div class="text-xs text-surface-500 w-full">
                    {{ $t('public.status') }}
                </div>
                <Tag
                    severity="warn"
                    :value="dayjs().diff(dayjs(account.active_subscriptions[0].approval_at), 'day') + ' ' + $t('public.days')"
                    class="text-xs lowercase"
                />
            </div>
        </div>
    </div>
</template>
