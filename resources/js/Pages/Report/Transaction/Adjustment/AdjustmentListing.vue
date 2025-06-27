<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Tab, TabList, TabPanels, Tabs, TabPanel } from 'primevue';
import { onMounted, ref, h, watch } from 'vue';
import AdjustmentOverview from "./AdjustmentOverview.vue";
import AdjustmentTable from "./AdjustmentTable.vue";

const totalAdjustmentAmount = ref(null);
const adjustmentCounts = ref(null);

const handleOverview = (data) => {
    totalAdjustmentAmount.value = Number(data.totalAdjustmentAmount);
    adjustmentCounts.value = data.adjustmentCounts;
}

const tabs = ref([
    {
        title: 'cash_in',
        component: h(AdjustmentTable),
        value: '0'
    },
    {
        title: 'cash_out',
        component: h(AdjustmentTable),
        value: '1'
    },
]);

const selectedType = ref('cash_in');
const activeIndex = ref('0');

onMounted(() => {
    const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });

    // Only set the active tab index from the URL param
    activeIndex.value = params.tab === 'cash_out' ? '1' : '0';
});

// Let the watch control selectedType always
watch(activeIndex, (newIndex) => {
    selectedType.value = newIndex === '1' ? 'cash_out' : 'cash_in';
});

</script>

<template>
    <AuthenticatedLayout :title="$t('public.adjustment')">
        <div class="flex flex-col gap-5">
            <div class="flex flex-col">
                <Tabs v-model:value="activeIndex">
                    <div class="flex flex-col gap-5">
                        <TabList>
                            <Tab v-for="tab in tabs" :key="tab.title" :value="tab.value">
                                {{ $t(`public.${tab.title}`) }}
                            </Tab>
                        </TabList>

                        <AdjustmentOverview 
                            :totalAdjustmentAmount="totalAdjustmentAmount"
                            :adjustmentCounts="adjustmentCounts"
                        />

                        <TabPanels class="pt-0">
                            <TabPanel v-for="tab in tabs" :key="tab.value" :value="tab.value">
                                <component
                                    :is="tabs[activeIndex]?.component"
                                    :type="selectedType"
                                    @updateAdjustment="handleOverview"
                                />
                            </TabPanel>
                        </TabPanels>
                    </div>
                </Tabs>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
