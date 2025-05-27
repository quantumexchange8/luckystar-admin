<script setup>
import { ref, watchEffect } from 'vue';
import Empty from '@/Components/Empty.vue';
import { generalFormat } from "@/Composables/format.js";
import { usePage } from "@inertiajs/vue3";
import AccountAction from "@/Pages/Member/Listing/MemberDetail/Partials/AccountAction.vue";
import {Tag, Card, Skeleton, Button} from "primevue";
import dayjs from "dayjs";
import {IconDots} from "@tabler/icons-vue";

const props = defineProps({
    user_id: Number,
    tradingAccountsCount: Number
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

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getTradingAccounts();
    }
});

</script>

<template>
    <div v-if="isLoading" class="grid md:grid-cols-2 gap-5">
        <Card
            class="w-full"
            v-for="(account, index) in tradingAccountsCount"
            :key="index"
        >
            <template #content>
                <div class="flex gap-3 items-center self-stretch relative">
                    <div
                        class="absolute -left-2 w-2.5 rounded-full h-full bg-primary"
                    ></div>
                    <div class="flex flex-col gap-5 w-full self-stretch pl-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex flex-col items-center self-stretch">
                                <div class="flex items-center gap-2 self-stretch">
                                    <div class="self-stretch flex items-center gap-4 truncate text-sm text-surface-600 dark:text-surface-500">
                                        <Skeleton
                                            width="8rem"
                                            class="mt-0.5 mb-1"
                                            height="1.25rem"
                                            borderRadius="2rem"
                                        />
                                    </div>
                                </div>
                                <div class="flex flex-row gap-2 items-center self-stretch text-xs">
                                    <span class="text-surface-500">{{ $t('public.name') }}:</span>
                                    <Skeleton
                                        width="8rem"
                                        class="my-0.5"
                                        borderRadius="2rem"
                                    />
                                </div>
                            </div>
                            <Button
                                type="button"
                                severity="secondary"
                                text
                                size="small"
                                icon="IconDots"
                                rounded
                                disabled
                            >
                                <IconDots size="16" stroke-width="1.5" />
                            </Button>
                        </div>

                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.balance') }}:</span>
                                <Skeleton
                                    width="8rem"
                                    class="my-0.5"
                                    borderRadius="2rem"
                                />
                            </div>

                            <div class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.equity') }}:</span>
                                <Skeleton
                                    width="8rem"
                                    class="my-0.5"
                                    borderRadius="2rem"
                                />
                            </div>

                            <div class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.credit') }}:</span>
                                <Skeleton
                                    width="8rem"
                                    class="my-0.5"
                                    borderRadius="2rem"
                                />
                            </div>

                            <div class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.leverage') }}:</span>
                                <Skeleton
                                    width="8rem"
                                    class="my-0.5"
                                    borderRadius="2rem"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </Card>
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
                        :style="{'backgroundColor': `#${account.account_type.color}`}"
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
                                            backgroundColor: formatRgbaColor(account.account_type.color, 0.2),
                                            color: `#${account.account_type.color}`,
                                        }"
                                            :value="account.account_type.name"
                                        />
                                    </div>
                                </div>
                                <div class="flex flex-row gap-2 items-center self-stretch text-xs">
                                    <span class="text-surface-500">{{ $t('public.name') }}:</span>
                                    <span class="font-medium">{{ account.trading_user.name }}</span>
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

                            <div v-if="account.active_subscriptions.length > 0" class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.strategy') }}:</span>
                                <span class="font-medium">{{ account.active_subscriptions[0].trading_master.master_name }}</span>
                            </div>
                            <div v-else class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.credit') }}:</span>
                                <span class="font-medium">{{ formatAmount(account.credit, 2) }}</span>
                            </div>

                            <div v-if="account.active_subscriptions.length > 0" class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.status') }}:</span>
                                <Tag
                                    severity="warn"
                                    :value="dayjs().diff(dayjs(account.active_subscriptions[0].approval_at), 'day') + ' ' + $t('public.days')"
                                    class="text-xxs lowercase"
                                />
                            </div>
                            <div v-else class="flex flex-row gap-1 items-center self-stretch text-xs">
                                <span class="text-surface-500 w-20">{{ $t('public.leverage') }}:</span>
                                <span class="font-medium">1:{{ account.margin_leverage }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
