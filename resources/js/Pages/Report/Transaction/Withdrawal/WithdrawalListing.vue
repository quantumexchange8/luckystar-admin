<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref } from 'vue';
import WithdrawalOverview from "./WithdrawalOverview.vue";
import WithdrawalTable from "./WithdrawalTable.vue";

const totalWithdrawalAmount = ref(null);
const withdrawalCounts = ref(null);

const handleOverview = (data) => {
    totalWithdrawalAmount.value = Number(data.totalWithdrawalAmount);
    withdrawalCounts.value = data.withdrawalCounts;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.withdrawal')">
        <div class="flex flex-col gap-5 items-center self-stretch w-full">
            <WithdrawalOverview 
                :totalWithdrawalAmount="totalWithdrawalAmount"
                :withdrawalCounts="withdrawalCounts"
            />

            <WithdrawalTable 
                @updateWithdrawal="handleOverview"
            />
        </div>
    </AuthenticatedLayout>
</template>
