<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref } from 'vue';
import RedemptionOverview from "./RedemptionOverview.vue";
import RedemptionTable from "./RedemptionTable.vue";

const totalRedemptionAmount = ref(null);
const redemptionCounts = ref(null);

const handleOverview = (data) => {
    totalRedemptionAmount.value = Number(data.totalRedemptionAmount);
    redemptionCounts.value = data.redemptionCounts;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.redemption')">
        <div class="flex flex-col gap-5 items-center self-stretch w-full">
            <RedemptionOverview 
                :totalRedemptionAmount="totalRedemptionAmount"
                :redemptionCounts="redemptionCounts"
            />

            <RedemptionTable 
                @updateRedemption="handleOverview"
            />
        </div>
    </AuthenticatedLayout>
</template>
