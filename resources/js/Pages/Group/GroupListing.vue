<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import GroupOverview from "@/Pages/Group/GroupOverview.vue";
import GroupListView from "@/Pages/Group/GroupListView.vue";
import {ref} from "vue";

defineProps({
    groupsCount: Number,
})

const totalWalletTopUp = ref(null);
const totalWalletWithdrawal = ref(null);
const totalActiveCapital = ref(null);

const handleUpdateTotals = (data) => {
    totalWalletTopUp.value = data.totalWalletTopUp;
    totalWalletWithdrawal.value = data.totalWalletWithdrawal;
    totalActiveCapital.value = data.totalActiveCapital;
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.group')">
        <div class="flex flex-col gap-5 items-center self-stretch">
            <!-- Overview -->
            <GroupOverview
                :totalWalletTopUp="totalWalletTopUp"
                :totalWalletWithdrawal="totalWalletWithdrawal"
                :totalActiveCapital="totalActiveCapital"
            />

            <!-- Group Grid -->
            <GroupListView
                :groupsCount="groupsCount"
                @update-totals="handleUpdateTotals"
            />
        </div>
    </AuthenticatedLayout>
</template>
