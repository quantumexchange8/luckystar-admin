<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import Card from "primevue/card";
</script>

<template>
    <div
        class="flex min-h-screen flex-col gap-8 items-center bg-surface-100 pt-6 sm:justify-center dark:bg-surface-950"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
            </Link>
        </div>

        <Card class="w-full max-w-[300px] sm:max-w-md">
            <template #content>
                <slot />
            </template>
        </Card>
    </div>
</template>
