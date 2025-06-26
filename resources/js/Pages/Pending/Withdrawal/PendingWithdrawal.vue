<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PendingWithdrawalOverview from "./PendingWithdrawalOverview.vue";
import PendingWithdrawalTable from "./PendingWithdrawalTable.vue";
import { ref } from 'vue';

const totalWithdrawalAmount = ref(null);
const pendingWithdrawalCounts = ref(null);

const handleOverview = (data) => {
    totalWithdrawalAmount.value = Number(data.totalWithdrawalAmount);
    pendingWithdrawalCounts.value = data.pendingWithdrawalCounts;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pending_withdrawal')">
        <div class="flex flex-col gap-5 items-center self-stretch w-full">
            <PendingWithdrawalOverview 
                :totalWithdrawalAmount="totalWithdrawalAmount"
                :pendingWithdrawalCounts="pendingWithdrawalCounts"
            />

            <PendingWithdrawalTable 
                @updatePendingWithdrawal="handleOverview"
            />
        </div>
    </AuthenticatedLayout>
</template>
