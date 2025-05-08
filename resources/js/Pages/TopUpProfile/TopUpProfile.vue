<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import AddProfile from "@/Pages/TopUpProfile/AddProfile.vue";
</script>

<template>
    <AuthenticatedLayout :title="$t('public.top_up_profile')">
        <div class="flex flex-col gap-5 items-center self-stretch">
            <AddProfile />
        </div>
    </AuthenticatedLayout>
</template>
