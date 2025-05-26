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
    ProgressSpinner
} from "primevue";
import Empty from "@/Components/Empty.vue";
import { onMounted, ref, computed, watch, watchEffect } from "vue";
import { generalFormat } from "@/Composables/format.js";
import dayjs from "dayjs";
import {
    IconAdjustments,
    IconCircleXFilled,
    IconSearch,
    IconXboxX,
} from "@tabler/icons-vue";
import PendingKycAction from "@/Pages/Member/Kyc/PendingKycAction.vue";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";

const isLoading = ref(false);
const dt = ref(null);
const pendingKycs = ref([]);
const { formatAmount, formatRgbaColor } = generalFormat();
const totalRecords = ref(0);
const first = ref(0);

const filters = ref({
    global: null,
    start_date: null,
    end_date: null,
    group_id: null,
    category: null,
    proof_type: null,
});

const groups = ref([]);
const selectedGroup = ref([]);
const loadingGroups = ref(false);

const getGroups = async () => {
    loadingGroups.value = true;
    try {
        const response = await axios.get('/getGroups');
        groups.value = response.data.groups;
    } catch (error) {
        console.error('Error fetching users:', error);
    } finally {
        loadingGroups.value = false;
    }
};

watch(selectedGroup, (newGroup) => {
    if (newGroup) {
        filters.value.group_id = newGroup.id;
    }
});

const categories = ['proof_of_identity', 'proof_of_residency'];

const allProofTypes = {
    proof_of_identity: ['photo_id', 'passport'],
    proof_of_residency: ['utility_bill', 'bank_statement', 'residence_certificate'],
};

const proofTypes = computed(() => {
    return allProofTypes[filters.value.category] || [];
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

            const url = route('member.getPendingKycRequest', params);
            const response = await fetch(url);
            const results = await response.json();

            pendingKycs.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        pendingKycs.value = [];
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
    getGroups();
}

const selectedDate = ref([]);

const clearDate = () => {
    selectedDate.value = [];
}

watch(selectedDate, (newDateRange) => {
  if (Array.isArray(newDateRange)) {
    const [startDate, endDate] = newDateRange;

    if (startDate && !endDate) {
      // Only start date present, set both to startDate
      filters.value.start_date = startDate;
      filters.value.end_date = startDate;
    } else if (!startDate && endDate) {
      // Only end date present, set both to endDate
      filters.value.start_date = endDate;
      filters.value.end_date = endDate;
    } else {
      // Both present or both null
      filters.value.start_date = startDate;
      filters.value.end_date = endDate;
    }

    // if (startDate !== null && endDate !== null) {
    //     loadLazyData();
    // }

  } else {
    console.warn('Invalid date range format:', newDateRange);
  }
});

watch(filters, debounce(() => {
    loadLazyData(); // Call loadLazyData function to fetch the data
}, 1000), { deep: true });

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

watch(() => filters.value.global, debounce(() => {
    loadLazyData();
}, 300));

const clearAll = () => {
    filters.value = {
        global: null,
        start_date: null,
        end_date: null,
        group_id: null,
        category: null,
        proof_type: null,
    };

    selectedDate.value = [];
    selectedGroup.value = null;
};

const clearFilterGlobal = () => {
    filters.value.global = null;
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
                :value="pendingKycs"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                lazy
                :paginator="pendingKycs?.length > 0"
                removableSort
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :first="first"
                :rows="10"
                ref="dt"
                dataKey="id"
                :totalRecords="totalRecords"
                :loading="isLoading"
                @page="onPage($event)"
                @sort="onSort($event)"
                @filter="onFilter($event)"
            >
                <template #header>
                    <div class="flex flex-col md:flex-row gap-3 items-center self-stretch">
                        <div class="relative w-full md:w-60">
                            <div class="absolute top-2/4 -mt-[9px] left-4 text-surface-400">
                                <IconSearch size="20" stroke-width="1.5"/>
                            </div>
                            <InputText
                                v-model="filters['global']"
                                :placeholder="$t('public.keyword_search')"
                                class="font-normal pl-12 w-full md:w-60"
                            />
                            <div
                                v-if="filters['global'] !== null && filters['global'] !== ''"
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
                <template v-if="pendingKycs?.length > 0">
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
                        :header="$t('public.name')"
                    >
                        <template #body="{data}">
                            <div class="flex items-center gap-2">
                                <div v-if="data" class="flex flex-col">
                                    <span class="font-semibold">{{ data.full_name }}</span>
                                    <span class="text-xs text-surface-500">{{ data.email }}</span>
                                </div>
                                <div v-else class="h-[39px] flex items-center self-stretch">
                                    -
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column
                        field="group"
                        class="table-cell"
                        :header="$t('public.group')"
                    >
                        <template #body="slotProps">
                            <div class="flex items-center">
                                <div
                                    v-if="slotProps.data.group_id"
                                    class="flex items-center gap-2 rounded justify-center py-1 px-2 text-nowrap"
                                    :style="{ backgroundColor: formatRgbaColor(slotProps.data.group_color, 0.1) }"
                                >
                                    <div
                                        class="w-1.5 h-1.5 grow-0 shrink-0 rounded-full"
                                        :style="{ backgroundColor: `#${slotProps.data.group_color}` }"
                                    ></div>
                                    <div
                                        class="text-xs font-semibold"
                                        :style="{ color: `#${slotProps.data.group_color}` }"
                                    >
                                        {{ slotProps.data.group_name }}
                                    </div>
                                </div>
                                <div v-else>
                                    -
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column
                        field="category"
                        class="table-cell"
                        :header="$t('public.category')"
                    >
                        <template #body="{data}">
                            {{ $t('public.' + data.category) }}
                        </template>
                    </Column>
                    <Column
                        field="proof_type"
                        class="table-cell"
                        :header="$t('public.proof_type')"
                    >
                        <template #body="{data}">
                            {{ $t('public.' + data.proof_type) }}
                        </template>
                    </Column>
                    <Column
                        field="action"
                        class="table-cell"
                    >
                        <template #body="{data}">
                            <PendingKycAction
                                :kyc="data"
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
                    v-model="selectedGroup"
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
                                        class="text-xs font-semibold text-nowrap"
                                        :style="{ color: `#${slotProps.value.color}` }"
                                    >
                                        {{ slotProps.value.name }}
                                    </div>
                                </Tag>
                                <div v-else>
                                    -
                                </div>
                            </div>
                        </div>
                        <span v-else class="text-surface-400">{{ slotProps.placeholder }}</span>
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
                                        class="text-xs font-semibold text-nowrap"
                                        :style="{ color: `#${slotProps.option.color}` }"
                                    >
                                        {{ slotProps.option.name }}
                                    </div>
                                </Tag>
                                <div v-else>
                                    -
                                </div>
                            </div>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Filter Category -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-surface-950 dark:text-white font-semibold">
                    {{ $t('public.category') }}
                </div>
                <Select
                    v-model="filters.category"
                    :options="categories"
                    filter
                    :placeholder="$t('public.select_category')"
                    class="w-full"
                    @change="filters.proof_type = null"
                >
                    <template #option="slotProps">
                        <span>{{ $t('public.' + slotProps.option) }}</span>
                    </template>
                    <template #value="slotProps">
                        <span v-if="slotProps.value">{{ $t('public.' + slotProps.value) }}</span>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                </Select>
            </div>

            <!-- Filter Proof Type -->
            <div v-if="filters.category" class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-surface-950 dark:text-white font-semibold">
                    {{ $t('public.proof_type') }}
                </div>
                <Select
                    v-model="filters.proof_type"
                    :options="proofTypes"
                    filter
                    :placeholder="$t('public.select_proof_type')"
                    class="w-full"
                >
                    <template #option="slotProps">
                        <span>{{ $t('public.' + slotProps.option) }}</span>
                    </template>
                    <template #value="slotProps">
                        <span v-if="slotProps.value">{{ $t('public.' + slotProps.value) }}</span>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                </Select>
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
