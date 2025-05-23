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
    MultiSelect,
    ProgressSpinner
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
} from "@tabler/icons-vue";
import PendingKycAction from "@/Pages/Member/Kyc/Partials/PendingKycAction.vue";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";

const isLoading = ref(false);
const dt = ref(null);
const investments = ref([]);
const {formatAmount, formatRgbaColor} = generalFormat();
const totalRecords = ref(0);
const first = ref(0);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    group_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    referrers: { value: null, matchMode: FilterMatchMode.CONTAINS },
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

            const url = route('pending.getPendingInvestmentsData', params);
            const response = await fetch(url);
            const results = await response.json();

            investments.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        investments.value = [];
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
    getGroupLeaders();
}

const groups = ref([]);
const loadingGroups = ref(false);

const getGroupLeaders = async () => {
    loadingGroups.value = true;
    try {
        const response = await axios.get('/getGroupLeaders');
        groups.value = response.data;
    } catch (error) {
        console.error('Error fetching groups:', error);
    } finally {
        loadingGroups.value = false;
    }
};

const referrers = ref([]);
const loadingReferrers = ref(false);

const getGroupMembers = async () => {
    loadingReferrers.value = true;
    try {
        const response = await axios.get(`/getGroupMembers?group_id=${filters.value['group_id'].value.id}`);
        referrers.value = response.data;
    } catch (error) {
        console.error('Error fetching referrers:', error);
    } finally {
        loadingReferrers.value = false;
    }
};

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

watch([filters.value['group_id'], filters.value['referrers']], () => {
    if (filters.value['group_id'].value !== null) {
        getGroupMembers();
    }
    loadLazyData()
});

const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;
    filters.value['group_id'].value = null;
    filters.value['referrers'].value = null;

    selectedDate.value = [];
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

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
                :value="investments"
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
                        :title="$t('public.empty_pending_data_title')"
                        :message="$t('public.empty_pending_data_message')"
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <ProgressSpinner
                            strokeWidth="4"
                        />
                        <span class="text-sm text-surface-700 dark:text-surface-300">{{ $t('public.loading_pending_data') }}</span>
                    </div>
                </template>
                <template v-if="investments?.length > 0">
                    <Column
                        field="created_at"
                        sortable
                        class="table-cell min-w-36"
                        :header="$t('public.requested')"
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
                        :header="$t('public.investors')"
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
                        field="group"
                        class="hidden md:table-cell"
                        :header="$t('public.group')"
                    >
                        <template #body="{data}">
                            <div class="flex items-center">
                                <Tag
                                    v-if="data.user.group"
                                    class="flex items-center gap-2 rounded"
                                    :style="{ background: formatRgbaColor(data.user.group.group.color, 0.1) }"
                                >
                                    <div
                                        class="w-1.5 h-1.5 grow-0 shrink-0 rounded-full"
                                        :style="{ backgroundColor: `#${data.user.group.group.color}` }"
                                    ></div>
                                    <div
                                        class="text-xxs font-semibold text-nowrap"
                                        :style="{ color: `#${data.user.group.group.color}` }"
                                    >
                                        {{ data.user.group.group.name }}
                                    </div>
                                </Tag>
                                <div v-else>
                                    -
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column
                        field="referer"
                        class="table-cell min-w-36"
                        :header="$t('public.referrer')"
                    >
                        <template #body="{data}">
                            <div class="flex items-center gap-2">
                                <div v-if="data.user" class="flex flex-col">
                                    <span class="font-semibold">{{ data.user.upline.full_name }}</span>
                                    <span class="text-xs text-surface-500">{{ data.user.upline.email }}</span>
                                </div>
                                <div v-else class="h-[39px] flex items-center self-stretch">
                                    -
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column
                        field="meta_login"
                        sortable
                        class="table-cell"
                        :header="$t('public.account')"
                    >
                        <template #body="{data}">
                            {{ data.meta_login }}
                        </template>
                    </Column>
                    <Column
                        field="strategy"
                        class="table-cell"
                        :header="$t('public.strategy')"
                    >
                        <template #body="{data}">
                            <div class="flex flex-col">
                                <span class="font-semibold">{{ data.trading_master.master_name }}</span>
                                <span class="text-xs text-surface-500">{{ data.master_meta_login }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column
                        field="account_type"
                        class="table-cell"
                        :header="$t('public.account_type')"
                    >
                        <template #body="{data}">
                            <Tag
                                severity="secondary"
                                class="text-xs"
                                :value="data.trading_master.account_type.name"
                            />
                        </template>
                    </Column>
                    <Column
                        field="investment_amount"
                        class="table-cell"
                        sortable
                        :header="$t('public.fund_size')"
                    >
                        <template #body="{data}">
                            <span class="font-medium">{{ formatAmount(data.subscription_amount) }}</span>
                        </template>
                    </Column>
                    <Column
                        field="action"
                        class="table-cell"
                    >
                        <template #body="{data}">
                            <PendingKycAction
                                :investment="data"
                            />
                        </template>
                    </Column>
                </template>
            </DataTable>
        </template>
    </Card>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Request Date -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-surface-950 dark:text-white font-semibold">
                    {{ $t('public.filter_date') }}
                </div>
                <div class="relative w-full">
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
            </div>

            <!-- Filter Group -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-surface-950 dark:text-white font-semibold">
                    {{ $t('public.filter_group') }}
                </div>
                <Select
                    v-model="filters['group_id'].value"
                    :options="groups"
                    filter
                    optionLabel="name"
                    :placeholder="$t('public.select_group')"
                    class="w-full"
                    :loading="loadingGroups"
                >
                    <template #value="slotProps">
                        <div
                            v-if="slotProps.value"
                            class="flex gap-2 items-center"
                        >
                            <div class="flex items-center">
                                <Tag
                                    v-if="slotProps.value"
                                    class="flex items-center gap-2 rounded"
                                    :style="{ background: formatRgbaColor(slotProps.value.color, 0.1) }"
                                >
                                    <div
                                        class="w-1.5 h-1.5 grow-0 shrink-0 rounded-full"
                                        :style="{ backgroundColor: `#${slotProps.value.color}` }"
                                    ></div>
                                    <div
                                        class="text-xxs font-semibold text-nowrap"
                                        :style="{ color: `#${slotProps.value.color}` }"
                                    >
                                        {{ slotProps.value.name }}
                                    </div>
                                </Tag>
                                <div v-else>
                                    -
                                </div>
                            </div>
                            <div class="text-xs truncate">
                                {{ slotProps.value.group_leader.full_name }}
                            </div>
                        </div>
                        <span v-else class="text-surface-400 dark:text-surface-700">{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex gap-2 items-center">
                            <div class="flex items-center">
                                <Tag
                                    v-if="slotProps.option"
                                    class="flex items-center gap-2 rounded"
                                    :style="{ background: formatRgbaColor(slotProps.option.color, 0.1) }"
                                >
                                    <div
                                        class="w-1.5 h-1.5 grow-0 shrink-0 rounded-full"
                                        :style="{ backgroundColor: `#${slotProps.option.color}` }"
                                    ></div>
                                    <div
                                        class="text-xxs font-semibold text-nowrap"
                                        :style="{ color: `#${slotProps.option.color}` }"
                                    >
                                        {{ slotProps.option.name }}
                                    </div>
                                </Tag>
                                <div v-else>
                                    -
                                </div>
                            </div>
                            <div class="text-xs truncate">
                                {{ slotProps.option.group_leader.full_name }}
                            </div>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Filter Referrer -->
            <div
                v-if="filters['group_id'].value !== null"
                class="flex flex-col gap-2 items-center self-stretch"
            >
                <div class="flex self-stretch text-xs text-surface-950 dark:text-white font-semibold">
                    {{ $t('public.filter_referrer') }}
                </div>
                <MultiSelect
                    v-model="filters['referrers'].value"
                    :options="referrers"
                    :placeholder="$t('public.select_referrer')"
                    :maxSelectedLabels="3"
                    class="w-full"
                    :loading="loadingReferrers"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value && slotProps.value.length > 0" class="flex gap-1 items-center">
                            <div v-for="data in slotProps.value">
                                {{ data.user.full_name }},
                            </div>
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        {{ slotProps.option.user.full_name }}
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
