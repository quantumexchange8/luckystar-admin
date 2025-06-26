<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref } from 'vue';
import TopUpOverview from "./TopUpOverview.vue";
import TopUpTable from "./TopUpTable.vue";

const totalTopUpAmount = ref(null);
const topUpCounts = ref(null);

const handleOverview = (data) => {
    totalTopUpAmount.value = Number(data.totalTopUpAmount);
    topUpCounts.value = data.topUpCounts;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.top_up')">
        <div class="flex flex-col gap-5 items-center self-stretch w-full">
            <TopUpOverview 
                :totalTopUpAmount="totalTopUpAmount"
                :topUpCounts="topUpCounts"
            />

            <TopUpTable 
                @updateTopUp="handleOverview"
            />
        </div>
    </AuthenticatedLayout>
</template>
