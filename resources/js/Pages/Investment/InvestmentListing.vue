<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InvestmentOverview from "@/Pages/Investment/InvestmentOverview.vue";
import InvestmentTable from "@/Pages/Investment/InvestmentTable.vue";
import {Tab, TabList, Tabs, Tag} from "primevue";
import InvestmentReportTable from "@/Pages/Strategy/Partials/InvestmentReportTable.vue";
import {ref, watch} from "vue";
import {generalFormat} from "@/Composables/format.js";

const activeTab = ref('0');
const {formatAmount} = generalFormat();

const tabs = ref([
    {
        title: 'active',
        value: '0',
        load: ref(false),
    },
    {
        title: 'completed',
        value: '1',
        load: ref(false),
    },
    {
        title: 'revoked',
        value: '2',
        load: ref(false),
    },
]);

const selectedStatus = ref('active');
const activeIndex = ref('0');

watch(activeIndex, (newIndex) => {
    const activeTab = tabs.value.find(tab => tab.value === newIndex);
    if (activeTab) {
        selectedStatus.value = activeTab.title;
    }
});

watch(activeTab, (newVal) => {
    const tab = tabs.value.find((t) => t.value === newVal);
    if (tab && tab.load) tab.load = true;
});

const investorsCount = ref(null);
const investorsTrend = ref(null);
const fundSize = ref(null);
const fundSizeTrend = ref(null);

const handleUpdateTotals = (data) => {
    investorsCount.value = data.investorsCount;
    investorsTrend.value = data.investorsTrend;
    fundSize.value = data.fundSize;
    fundSizeTrend.value = data.fundSizeTrend;
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.investment')">
        <div class="flex flex-col gap-5 items-center self-stretch">
            <InvestmentOverview
                :investorsCount="investorsCount"
                :investorsTrend="investorsTrend"
                :fundSize="fundSize"
                :fundSizeTrend="fundSizeTrend"
            />

            <div class="flex flex-col gap-5 items-center self-stretch">
                <Tabs v-model:value="activeIndex" class="w-full">
                    <TabList>
                        <Tab
                            v-for="tab in tabs"
                            :key="tab.value"
                            :value="tab.value"
                        >
                            {{ $t(`public.${tab.title}`) }}
                        </Tab>
                    </TabList>
                </Tabs>

                <InvestmentTable
                    :status="selectedStatus"
                    @update-totals="handleUpdateTotals"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
