<script setup>
import {
    Card,
    DataTable,
    Column,
    InputText,
    Button,
    Tag,
    Popover,
    DatePicker,
    Select,
    ProgressSpinner,
    RadioButton,
} from "primevue";

import Empty from "@/Components/Empty.vue";
import {onMounted, ref, watch, watchEffect} from "vue";
import {generalFormat} from "@/Composables/format.js";
import {FilterMatchMode} from "@primevue/core/api";
import dayjs from "dayjs";
import {
    IconAdjustments,
    IconCircleXFilled,
    IconSearch,
    IconXboxX,
    IconX,
} from "@tabler/icons-vue";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    type: String, // 'cash_in' or 'cash_out'
});

const isLoading = ref(false);
const dt = ref(null);
const adjustments = ref([]);
const {formatAmount} = generalFormat();
const totalRecords = ref(0);
const first = ref(0);
const totalAdjustmentAmount = ref();
const adjustmentCounts = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const lazyParams = ref({}); 

const loadLazyData = (event) => { // event will retrieve from the datatable attribute
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value, type: props.type };
    lazyParams.value.filters = filters.value;

    try {
        setTimeout(async () => {
            const params = { 
                page: JSON.stringify(event?.page + 1), 
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                type: props.type,
                lazyEvent: JSON.stringify(lazyParams.value), 
            };

            const url = route('report.transaction.get_adjustment_listing_data', params);
            const response = await fetch(url);

            const results = await response.json();
            adjustments.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            totalAdjustmentAmount.value = results?.totalAdjustmentAmount;
            adjustmentCounts.value = results?.adjustmentCounts;
            isLoading.value = false;
        }, 100);
    } catch (e) {
        adjustments.value = [];
        totalRecords.value = 0;
        isLoading.value = false;
    }
};

const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};

const onSort = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};

const onFilter = (event) => {
    lazyParams.value.fitlers = filters.value;
    loadLazyData(event);
};

const emit = defineEmits(['updateAdjustment']);

// Emit the totals whenever they change
watch([totalAdjustmentAmount, adjustmentCounts], () => {
    emit('updateAdjustment', {
        totalAdjustmentAmount: totalAdjustmentAmount.value,
        adjustmentCounts: adjustmentCounts.value,
    });
});

//Date Filter
const selectedDate = ref([]);

const clearDate = () => {
    selectedDate.value = [];
};

watch(selectedDate, (newDateRange) => {
    if(Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange; 
        filters.value['start_date'].value = startDate;
        filters.value['end_date'].value = endDate;

        if(startDate !== null && endDate !== null){
            loadLazyData();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
});

//filter toggle
const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
};

onMounted(() => {
    lazyParams.value = {
        first: dt.value.first,
        rows: dt.value.rows,
        sortField: null,
        sortOrder: null,
        type: props.type,
        filters: filters.value
    };
    loadLazyData();
});

watch(() => props.type, () => {
    loadLazyData(); // Re-fetch the data
});

watch(
    filters.value['global'],
    debounce(() => {
        loadLazyData();
    }, 300)
)

//clear all selected filter
const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;
    selectedDate.value = [];
};

//clear global filter
const clearFilterGlobal = () => {
    filters.value['global'].value = null;
};

//status severity
const getSeverity = (status) => {
    switch (status) {
        case 'success':
            return 'success';
    }
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});
</script>

<template>

     <Card class="w-full">
        <template #content>
            <DataTable
                :value="adjustments"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                lazy
                paginator
                removableSort
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :first="first"
                :rows="10"
                v-model:filters="filters"
                ref="dt"
                dataKey="id"
                :totalRecords="totalRecords"
                :loading="isLoading"
                @page="onPage($event)"
                @sort="onSort($event)"
                @filter="onFilter($event)"
                :globalFilterFields="['name', 'email', 'username']"
            >
                <template #header>
                    <div class="flex flex-col md:flex-row gap-3 items-center self-stretch">
                        <div class="relative w-full md:w-60">
                            <div class="absolute top-2/4 -mt-[9px] left-4 text-surface-400">
                                <IconSearch size="20" stroke-width="1.5"/>
                            </div>
                            <InputText
                                v-model="filters['global'].value"
                                :placeholder="$t('public.keyword_search')"
                                class="font-normal pl-12 w-full md:w-60"
                            />
                            <div
                                v-if="filters['global'].value !== null"
                                class="absolute top-2/4 -mt-2 right-4 text-surface-300 hover:text-surface-400 dark:text-surface-500 dark:hover:text-surface-400 select-none cursor-pointer"
                                @click="clearFilterGlobal"
                            >
                                <IconCircleXFilled size="16"/>
                            </div>
                        </div>
                        <div class="flex justify-between items-center w-full gap-3">
                            <Button
                                type="button"
                                severity="contrast"
                                class="flex gap-3 items-center"
                                @click="toggle"
                            >
                                <IconAdjustments size="20" stroke-width="1.5" />
                                {{ $t('public.filter') }}
                            </Button>
                        </div>
                    </div>
                </template>
                <template #empty>
                    <Empty
                        v-if="!isLoading"
                        :title="$t('public.empty_top_up_title')"
                        :message="$t('public.empty_top_up_caption')"
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <ProgressSpinner
                            strokeWidth="4"
                        />
                        <span class="text-sm text-surface-700 dark:text-surface-300">{{ $t('public.loading_top_up_data') }}</span>
                    </div>
                </template>
                <template v-if="adjustments?.length > 0">
                    <Column
                        field="created_at"
                        sortable
                        class="table-cell min-w-36"
                        :header="$t('public.date')"
                    >
                        <template #body="{data}">
                            {{ dayjs(data.created_at).format('YYYY-MM-DD') }}
                            <div class="text-xs text-surface-500">
                                {{ dayjs(data.created_at).format('HH:mm:ss') }}
                            </div>
                        </template>
                    </Column>

                    <Column
                        field="user"
                        class="table-cell"
                        :header="$t('public.name')"
                    >
                        <template #body="{data}">
                            <div class="flex items-center gap-2">
                                <div v-if="data.user" class="flex flex-col">
                                    <span class="font-semibold">{{ data.user.full_name }}</span>
                                    <span class="text-xs text-surface-500">{{ data.user.email }}</span>
                                </div>
                                <div v-else class="h-[39px] flex items-center self-stretch">
                                    -
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column
                        field="from_wallet_id"
                        :header="$t('public.from')"
                    >
                        <template #body="{ data }">
                            <div v-if="data.from_wallet_id">
                                {{ $t(`public.${data.from_wallet.type}`) }}
                            </div>
                            <div v-else-if="data.from_meta_login">
                                {{ data.from_meta_login }}
                            </div>
                            <div v-else>
                                -
                            </div>
                        </template>
                    </Column>

                    <Column
                        field="to_wallet_id"
                        :header="$t('public.to')"
                    >
                        <template #body="{ data }">
                            <div v-if="data.to_wallet_id">
                                {{ $t(`public.${data.to_wallet.type}`) }}
                            </div>
                            <div v-else-if="data.to_meta_login">
                                {{ data.to_meta_login }}
                            </div>
                            <div v-else>
                                -
                            </div>
                        </template>
                    </Column>

                    <Column
                        field="transaction_number"
                        sortable
                        class="table-cell"
                        :header="$t('public.transaction_number')"
                    >
                        <template #body="{data}">
                            {{ data.transaction_number }}
                        </template>
                    </Column>
                    <Column
                        field="amount"
                        class="table-cell"
                        sortable
                        :header="$t('public.amount')"
                    >
                        <template #body="{data}">
                            <span class="font-medium">{{ formatAmount(data.amount ?? 0, 2) }}</span>
                        </template>
                    </Column>

                    <Column
                        field="status"
                        :header="$t('public.status')"
                    >
                        <template #body="{ data }">
                            <Tag
                                :value="$t(`public.${data.status}`)" :severity="getSeverity(data.status)"
                            />
                        </template>
                    </Column>
                </template>
            </DataTable>
        </template>
    </Card>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Date -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-sm text-surface-950 dark:text-white">
                    {{ $t('public.filter_by_date') }}
                </div>
                <div class="relative w-full">
                    <DatePicker
                        v-model="selectedDate"
                        dateFormat="dd/mm/yy"
                        selectionMode="range"
                        placeholder="dd/mm/yyyy - dd/mm/yyyy"
                        class="w-full"
                    />
                    <div
                        v-if="selectedDate && selectedDate.length > 0"
                        class="absolute top-2/4 -mt-2 right-2 text-surface-400 select-none cursor-pointer bg-transparent"
                        @click="clearDate"
                    >
                        <IconX :size="15" strokeWidth="1.5"/>
                    </div>
                </div>
            </div>

            <Button
                type="button"
                severity="info"
                class="w-full"
                outlined
                @click="clearAll"
                :label="$t('public.clear_all')"
            />
        </div>
    </Popover>
</template>