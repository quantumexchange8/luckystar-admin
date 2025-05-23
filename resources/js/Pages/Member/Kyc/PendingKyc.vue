<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PendingKycTable from "@/Pages/Member/Kyc/PendingKycTable.vue";
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pending_investment')">
        <div class="flex flex-col gap-5 items-center self-stretch">
            <PendingKycTable />
        </div>
    </AuthenticatedLayout>
</template>
