<script setup>
import {
    DataTable,
    Column,
    Button,
    Popover,
    DatePicker,
    MultiSelect,
    ProgressSpinner
} from "primevue";
import Empty from "@/Components/Empty.vue";
import {onMounted, ref, watch} from "vue";
import {generalFormat} from "@/Composables/format.js";
import {FilterMatchMode} from "@primevue/core/api";
import dayjs from "dayjs";
import {
    IconAdjustments,
    IconXboxX,
} from "@tabler/icons-vue";
import debounce from "lodash/debounce.js";

const props = defineProps({
    account: Object,
})

const isLoading = ref(false);
const dt = ref(null);
const transactions = ref([]);
const {formatAmount, formatRgbaColor} = generalFormat();
const totalRecords = ref(0);
const first = ref(0);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    meta_login: { value: props.account.meta_login, matchMode: FilterMatchMode.EQUALS },
    types: { value: null, matchMode: FilterMatchMode.EQUALS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const lazyParams = ref({});

const loadLazyData = (event) => {
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };
    lazyParams.value.filters = filters.value;
    try {
        setTimeout(async () => {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value)
            };

            const url = route('account.getAccountTransaction', params);
            const response = await fetch(url);
            const results = await response.json();

            transactions.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        transactions.value = [];
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
    lazyParams.value.filters = filters.value ;
    loadLazyData(event);
};

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
}

const selectedDate = ref([]);

const clearDate = () => {
    selectedDate.value = [];
}

watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        filters.value['start_date'].value = startDate;
        filters.value['end_date'].value = endDate;

        if (startDate !== null && endDate !== null) {
            loadLazyData();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
})

const types = [
    'deposit',
    'withdraw',
    'transfer',
    'balance_in',
    'balance_out',
    'credit_in',
    'credit_out',
    'invest_capital',
    'top_up_capital',
];

onMounted(() => {
    lazyParams.value = {
        first: dt.value.first,
        rows: dt.value.rows,
        sortField: null,
        sortOrder: null,
        filters: filters.value
    };

    loadLazyData();
});

watch(
    filters.value['global'],
    debounce(() => {
        loadLazyData();
    }, 300)
);

watch([filters.value['types']], () => {
    loadLazyData()
});

const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;
    filters.value['types'].value = null;

    selectedDate.value = [];
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}
</script>

<template>
    <div
        class="w-full"
    >
        <DataTable
            :value="transactions"
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
                    <div class="relative w-full md:max-w-60">
                        <DatePicker
                            v-model="selectedDate"
                            dateFormat="yy-mm-dd"
                            class="w-full"
                            selectionMode="range"
                            placeholder="YYYY-MM-DD - YYYY-MM-DD"
                        />
                        <div
                            v-if="selectedDate && selectedDate.length > 0"
                            class="absolute top-2/4 -mt-1.5 right-2 text-surface-400 select-none cursor-pointer bg-transparent"
                            @click="clearDate"
                        >
                            <IconXboxX size="12" stoke-width="1.5" />
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
                    :title="$t('public.empty_transaction_title')"
                    :message="$t('public.empty_transaction_message')"
                />
            </template>
            <template #loading>
                <div class="flex flex-col gap-2 items-center justify-center">
                    <ProgressSpinner
                        strokeWidth="4"
                    />
                    <span class="text-sm text-surface-700 dark:text-surface-300">{{ $t('public.loading_transaction_caption') }}</span>
                </div>
            </template>
            <template v-if="transactions?.length > 0">
                <Column
                    field="created_at"
                    sortable
                    class="table-cell"
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
                    field="transaction_type"
                    class="table-cell"
                    :header="$t('public.type')"
                >
                    <template #body="{data}">
                        <span class="font-medium">{{ $t(`public.${data.transaction_type}`) }}</span>
                    </template>
                </Column>
                <Column
                    field="amount"
                    sortable
                    class="table-cell"
                    :header="$t('public.amount')"
                >
                    <template #body="{data}">
                        <div v-if="data.from_meta_login" class="text-red-500">
                            {{ formatAmount(data.amount) }}
                        </div>
                        <div v-else class="text-green-500">
                            {{ formatAmount(data.amount) }}
                        </div>
                    </template>
                </Column>
            </template>
        </DataTable>
    </div>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Request Date -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-surface-950 dark:text-white font-semibold">
                    {{ $t('public.filter_type') }}
                </div>
                <MultiSelect
                    v-model="filters['types'].value"
                    :options="types"
                    :placeholder="$t('public.select_type')"
                    :maxSelectedLabels="3"
                    class="w-full"
                >
                    <template #value="{value, placeholder}">
                        <div v-if="value && value.length > 0" class="flex items-center gap-1">
                            <div>
                                {{ value.slice(0, 2).map(data => $t(`public.${data}`)).join(', ') }}
                                <span v-if="value.length > 2">, ..</span>
                            </div>
                        </div>
                        <span v-else class="text-surface-400 dark:text-surface-700">{{ placeholder }}</span>
                    </template>
                    <template #option="{option}">
                        {{ $t(`public.${option}`) }}
                    </template>
                </MultiSelect>

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
