<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import AccountTable from "@/Pages/Account/AccountTable.vue";
</script>

<template>
    <AuthenticatedLayout :title="$t('public.accounts')">
        <div class="flex flex-col gap-5 items-center self-stretch">
            <AccountTable />
        </div>
    </AuthenticatedLayout>
</template>
