<script setup>
import {
    Tabs,
    Tab,
    TabList,
    Tag
} from "primevue";
import {ref, watch} from "vue";
import {generalFormat} from "@/Composables/format.js";
import InvestmentReportTable from "@/Pages/Strategy/Partials/InvestmentReportTable.vue";

const props = defineProps({
    strategy: Object
});

const activeTab = ref('0');
const {formatAmount} = generalFormat();

const tabs = ref([
    {
        title: 'active',
        value: '0',
        count: formatAmount(props.strategy.active_subscriptions_count, 0, ''),
        load: ref(false),
    },
    {
        title: 'completed',
        value: '1',
        count: formatAmount(props.strategy.completed_subscriptions_count, 0, ''),
        load: ref(false),
    },
    {
        title: 'revoked',
        value: '2',
        count: formatAmount(props.strategy.revoked_subscriptions_count, 0, ''),
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

    console.log(tab.load);
});
</script>

<template>
    <Tabs v-model:value="activeIndex" class="w-full">
        <TabList>
            <Tab
                v-for="tab in tabs"
                :key="tab.value"
                :value="tab.value"
            >
                <div class="flex items-center gap-2">
                    {{ $t(`public.${tab.title}`) }}
                    <Tag
                        :severity="tab.value === activeIndex ? '' : 'contrast'"
                        :value="tab.count"
                        class="text-xs"
                    />
                </div>
            </Tab>
        </TabList>
    </Tabs>

    <InvestmentReportTable
        :strategy="strategy"
        :status="selectedStatus"
    />
</template>
