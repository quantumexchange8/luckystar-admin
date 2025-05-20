<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PendingInvestmentTable from "@/Pages/Pending/Investment/PendingInvestmentTable.vue";
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pending_investment')">
        <div class="flex flex-col gap-5 items-center self-stretch">
            <PendingInvestmentTable />
        </div>
    </AuthenticatedLayout>
</template>
