<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref } from 'vue';
import TransferOverview from "./TransferOverview.vue";
import TransferTable from "./TransferTable.vue";

const totalTransferAmount = ref(null);
const transferCounts = ref(null);

const handleOverview = (data) => {
    totalTransferAmount.value = Number(data.totalTransferAmount);
    transferCounts.value = data.transferCounts;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.transfer')">
        <div class="flex flex-col gap-5 items-center self-stretch w-full">
            <TransferOverview
                :totalTransferAmount="totalTransferAmount"
                :transferCounts="transferCounts"
            />

            <TransferTable
                @updateTransfer="handleOverview"
            />
        </div>
    </AuthenticatedLayout>
</template>
