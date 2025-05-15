<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import AddAccountType from "@/Pages/AccountType/Partials/AddAccountType.vue";
import AccountTypeTable from "@/Pages/AccountType/AccountTypeTable.vue";

defineProps({
    accountTypesCount: Number,
    leverageOptions: Array,
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.account_type')">
        <div class="flex flex-col gap-5 items-center self-stretch">
            <AddAccountType
                :leverageOptions="leverageOptions"
            />

            <AccountTypeTable :accountTypesCount="accountTypesCount" :leverageOptions="leverageOptions" />
        </div>
    </AuthenticatedLayout>
</template>
