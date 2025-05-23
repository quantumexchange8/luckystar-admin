<script setup>
import {
    DataTable,
    Column,
    Dialog,
} from "primevue";
import { ref, watchEffect } from "vue";
import { generalFormat } from "@/Composables/format.js";
import { usePage } from "@inertiajs/vue3";
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";

const props = defineProps({
    user_id: Number
})

const adjustmentHistories = ref([]);
const { formatAmount, formatDateTime } = generalFormat();
const isLoading = ref(false);
const visible = ref(false);
const selected_row = ref();

const getAdjustmentHistoryData = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get(`/member/getAdjustmentHistoryData?id=${props.user_id}`);
        adjustmentHistories.value = response.data;
    } catch (error) {
        console.error('Error fetching adjustment history data:', error);
    } finally {
        isLoading.value = false;
    }
}

getAdjustmentHistoryData();

const rowClicked = (data) => {
    visible.value = true;
    selected_row.value = data;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getAdjustmentHistoryData();
    }
});

</script>

<template>
    <div class="flex flex-col items-center justify-center gap-5 self-stretch">
        <Loader v-if="isLoading" />

        <template v-else>
            <div v-if="adjustmentHistories?.length <= 0" class="w-full flex flex-col items-center flex-1 self-stretch">
                <Empty :message="$t('public.empty_adjustment_history_message')" />
            </div>

            <!-- data table -->
            <div v-else class="w-full p-6 flex flex-col items-center justify-center self-stretch gap-6 bg-white dark:bg-surface-800 shadow-toast rounded-2xl max-h-[360px]">
                <DataTable
                    :value="adjustmentHistories"
                    removableSort
                    :loading="isLoading"
                    @row-click="rowClicked($event.data)"
                    selectionMode="single"
                    scrollable
                    scrollHeight="300px"
                >
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loader />
                            <span class="text-sm text-surface-700">{{ $t('public.loading_users_caption') }}</span>
                        </div>
                    </template>
                    <Column field="created_at" sortable class="hidden md:table-cell" headerClass="hidden md:table-cell">
                        <template #header>
                            <span class="hidden md:block">{{ $t('public.date') }}</span>
                        </template>
                        <template #body="slotProps">
                            <div class="py-1">
                                {{ formatDateTime(slotProps.data.created_at) }}
                            </div>
                        </template>
                    </Column>
                    <Column field="transaction_type" class="hidden md:table-cell" headerClass="hidden md:table-cell">
                        <template #header>
                            <span class="hidden md:block">{{ $t('public.type') }}</span>
                        </template>
                        <template #body="slotProps">
                            <div class="py-1">
                                {{ $t(`public.${slotProps.data.transaction_type}`) }}
                            </div>
                        </template>
                    </Column>
                    <Column field="account_no" class="hidden md:table-cell" headerClass="hidden md:table-cell">
                        <template #header>
                            <span class="hidden md:block">{{ $t('public.account') }}</span>
                        </template>
                        <template #body="slotProps">
                            <div class="py-1">
                                <span v-if="['balance_in', 'credit_in'].includes(slotProps.data.transaction_type)">{{ slotProps.data.to_meta_login }}</span>
                                <span v-else-if="['balance_out', 'credit_out'].includes(slotProps.data.transaction_type)">{{ slotProps.data.from_meta_login }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="amount" sortable class="hidden md:table-cell" headerClass="hidden md:table-cell">
                        <template #header>
                            <span class="hidden md:block">{{ $t('public.amount') }} ($)</span>
                        </template>
                        <template #body="slotProps">
                            <div class="py-1">
                                {{ formatAmount(slotProps.data.transaction_amount,2,'') }}
                            </div>
                        </template>
                    </Column>
                    <Column class="md:hidden" headerClass="hidden">
                        <template #body="slotProps">
                            <div class="flex items-center gap-5">
                                <div class="flex flex-col items-start justify-center gap-1 w-full">
                                    <div class="flex gap-2 items-center text-surface-950 dark:text-white">
                                        <span class="text-sm font-semibold">{{ $t(`public.${slotProps.data.transaction_type}`) }}</span>
                                        <span v-if="['balance_in', 'credit_in'].includes(slotProps.data.transaction_type)">{{ slotProps.data.to_meta_login }}</span>
                                        <span v-else-if="['balance_out', 'credit_out'].includes(slotProps.data.transaction_type)">{{ slotProps.data.from_meta_login }}</span>
                                    </div>
                                    <span class="text-surface-500 dark:text-surface-300 text-xs"> {{ formatDateTime(slotProps.data.created_at) }}</span>
                                </div>
                                <div class="w-[120px] truncate text-right font-semibold text-surface-950 dark:text-white">
                                    {{ formatAmount(slotProps.data.transaction_amount) }}
                                </div>
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <Dialog
                    v-model:visible="visible"
                    modal
                    :header="$t('public.details')"
                    class="dialog-xs md:dialog-sm"
                >
                    <div class="flex flex-col items-center gap-3 self-stretch">
                        <div class="flex items-center gap-1 self-stretch">
                            <div class="self-stretch text-surface-500 dark:text-surface-300 text-xs font-medium w-[120px] shrink-0">
                                {{ $t('public.date') }}
                            </div>
                            <div class="text-surface-950 dark:text-white text-sm font-medium">
                                {{ formatDateTime(selected_row.created_at) }}
                            </div>
                        </div>
                        <div class="flex items-center gap-1 self-stretch">
                            <div class="self-stretch text-surface-500 dark:text-surface-300 text-xs font-medium w-[120px] shrink-0">
                                {{ $t('public.adjustment_type') }}
                            </div>
                            <div class="text-surface-950 dark:text-white text-sm font-medium">
                                {{ $t(`public.${selected_row.transaction_type}`) }}
                            </div>
                        </div>
                        <div class="flex items-center gap-1 self-stretch">
                            <div class="self-stretch text-surface-500 dark:text-surface-300 text-xs font-medium w-[120px] shrink-0">
                                {{ $t('public.account') }}
                            </div>
                            <div class="text-surface-950 dark:text-white text-sm font-medium">
                                <span v-if="selected_row.transaction_type === 'balance_in' || selected_row.transaction_type === 'credit_in'">{{ selected_row.to_meta_login }}</span>
                                <span v-else-if="selected_row.transaction_type === 'balance_out' || selected_row.transaction_type === 'credit_out'">{{ selected_row.from_meta_login }}</span>
                                <span v-else>-</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 self-stretch">
                            <div class="self-stretch text-surface-500 dark:text-surface-300 text-xs font-medium w-[120px] shrink-0">
                                {{ $t('public.amount') }}
                            </div>
                            <div class="text-surface-950 dark:text-white text-sm font-medium">
                                {{ formatAmount(selected_row.transaction_amount) }}
                            </div>
                        </div>
                        <div class="flex items-start gap-1 self-stretch">
                            <div class="text-surface-500 dark:text-surface-300 text-xs font-medium w-[120px] shrink-0">
                                {{ $t('public.remarks') }}
                            </div>
                            <div class="text-surface-950 dark:text-white text-sm font-medium">
                                {{ selected_row.remarks }}
                            </div>
                        </div>
                    </div>
                </Dialog>
            </div>
        </template>
    </div>
</template>
