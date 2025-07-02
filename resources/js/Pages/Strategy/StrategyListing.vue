<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import StrategyOverview from "@/Pages/Strategy/StrategyOverview.vue";
import CreateStrategy from "@/Pages/Strategy/CreateStrategy.vue";
import StrategyListView from "@/Pages/Strategy/StrategyListView.vue";
import {Button} from "primevue";
import {IconPlus} from "@tabler/icons-vue";

defineProps({
    strategiesCount: Number,
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.strategy')">
        <div class="flex flex-col gap-5 items-center self-stretch">
            <!-- Add Strategy -->
            <div class="flex justify-end items-center w-full">
                <Button
                    type="button"
                    class="flex gap-3 items-center"
                    as="a"
                    :href="route('strategy.create')"
                >
                    <IconPlus size="20" stroke-width="1.5" />
                    {{ $t('public.new_strategy') }}
                </Button>
            </div>

            <!-- Overview -->
            <StrategyOverview />

            <StrategyListView
                :strategiesCount="strategiesCount"
            />
        </div>
    </AuthenticatedLayout>
</template>
