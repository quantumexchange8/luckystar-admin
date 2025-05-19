<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import StrategyOverview from "@/Pages/Strategy/StrategyOverview.vue";
import CreateStrategy from "@/Pages/Strategy/CreateStrategy.vue";
import StrategyListView from "@/Pages/Strategy/StrategyListView.vue";

defineProps({
    accountTypes: Array,
    strategiesCount: Number,
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.strategy')">
        <div class="flex flex-col gap-5 items-center self-stretch">
            <!-- Add Strategy -->
            <CreateStrategy
                :accountTypes="accountTypes"
            />

            <!-- Overview -->
            <StrategyOverview />

            <StrategyListView
                :strategiesCount="strategiesCount"
            />
        </div>
    </AuthenticatedLayout>
</template>
