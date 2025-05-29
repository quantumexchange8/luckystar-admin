<script setup>
import { onMounted, ref, watch, watchEffect } from "vue";
import {
    InputText,
    RadioButton,
    Popover,
    DataTable,
    Column,
    Select,
    Avatar,
    Button,
    ProgressSpinner,
    Card,
    Tag,
    MultiSelect, DatePicker
} from "primevue";
import {
    IconSearch,
    IconCircleXFilled,
    IconAdjustments,
    IconAlertCircleFilled,
    IconCloudDownload, IconXboxX,
} from '@tabler/icons-vue';
import MemberTableActions from "@/Pages/Member/Listing/Partials/MemberTableActions.vue";
import { generalFormat } from "@/Composables/format.js";
import Empty from "@/Components/Empty.vue";
import debounce from "lodash/debounce.js";
import { useLangObserver } from "@/Composables/localeObserver.js";
import {FilterMatchMode} from "@primevue/core/api";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    groups: Array,
})

const isLoading = ref(false);
const dt = ref(null);
const users = ref([]);
const { formatRgbaColor, formatAmount, formatNameLabel } = generalFormat();
const totalRecords = ref(0);
const first = ref(0);
const totalUsers = ref();
const usersTrend = ref();
const verifiedUsers = ref();
const unverifiedUsers = ref();
const { locale } = useLangObserver();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    group_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    referrers: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.CONTAINS },
    kyc_status: { value: null, matchMode: FilterMatchMode.CONTAINS },
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
                lazyEvent: JSON.stringify(lazyParams.value),
            };

            const url = route('member.getMemberListingData', params);
            const response = await fetch(url);
            const results = await response.json();

            users.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            totalUsers.value = results?.totalUsers;
            usersTrend.value = results?.usersTrend;
            verifiedUsers.value = results?.verifiedUsers;
            unverifiedUsers.value = results?.unverifiedUsers;
            isLoading.value = false;
        }, 100);
    } catch (red) {
        users.value = [];
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

const user_statuses = [
    'active',
    'inactive',
];

const kyc_statuses = [
    'verified',
    'unverified',
];

watch([filters.value['status'], filters.value['group_id'], filters.value['referrers'], filters.value['kyc_status']], () => {
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
    filters.value['status'].value = null;
    filters.value['kyc_status'].value = null;

    selectedDate.value = [];
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const emit = defineEmits(['update-totals']);

watch([totalUsers, usersTrend, verifiedUsers, unverifiedUsers], () => {
    emit('update-totals', {
        totalUsers: totalUsers.value,
        usersTrend: usersTrend.value,
        verifiedUsers: verifiedUsers.value,
        unverifiedUsers: unverifiedUsers.value,
    });
});

const exportStatus = ref(false);

// Optimized exportMember function
const exportMember = async () => {
    exportStatus.value = true;
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };
    lazyParams.value.filters = filters.value;

    const params = {
        page: JSON.stringify(event?.page + 1),
        sortField: event?.sortField,
        sortOrder: event?.sortOrder,
        include: [],
        lazyEvent: JSON.stringify(lazyParams.value),
        exportStatus: true,
    };
    const url = route('member.getMemberListingData', params);

    try {
        window.location.href = url;
    } catch (e) {
        console.red('Error occured during export:', e);
    } finally {
        isLoading.value = false;
        exportStatus.value = false;
    }
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});
</script>

<template>
    <!-- data table -->
    <Card class="w-full">
        <template #content>
            <DataTable
                :value="users"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                :paginator="users?.length > 0"
                lazy
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
                        <div class="grid grid-cols-2 w-full gap-3">
                            <Button
                                type="button"
                                severity="contrast"
                                @click="toggle"
                                class="flex w-fit gap-3 items-center"
                            >
                                <IconAdjustments size="20" stroke-width="1.5" class="grow-0 shrink-0" />
                                {{ $t('public.filter') }}
                            </Button>
                            <div class="w-full flex justify-end">
                                <Button
                                    severity="secondary"
                                    @click="exportMember"
                                    class="w-full md:w-auto"
                                >
                                    <IconCloudDownload size="20" stroke-width="1.5" />
                                    {{ $t('public.export') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </template>
                <template #empty><Empty :title="$t('public.empty_user_title')" :message="$t('public.empty_user_message')" /></template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <ProgressSpinner strokeWidth="4" />
                        <span class="text-sm text-surface-700 dark:text-white">{{ $t('public.loading_users_caption') }}</span>
                    </div>
                </template>
                <template v-if="users?.length > 0">
                    <Column
                        field="id_number"
                        sortable
                        class="hidden md:table-cell"
                        :header="$t('public.id')"
                    >
                        <template #body="{data}">
                            {{ data.id_number }}
                        </template>
                    </Column>
                    <Column
                        field="full_name"
                        sortable
                        :header="$t('public.name')"
                        class="hidden md:table-cell"
                    >
                        <template #body="{data}">
                            <div class="flex items-center gap-3 max-w-60">
                                <Avatar
                                    v-if="data.profile_photo"
                                    :image="data.profile_photo"
                                    shape="circle"
                                    class="w-7 h-7 rounded-full overflow-hidden grow-0 shrink-0"
                                />
                                <Avatar
                                    v-else
                                    :label="formatNameLabel(data.full_name)"
                                    shape="circle"
                                    class="w-7 h-7 rounded-full overflow-hidden grow-0 shrink-0 text-xs"
                                />
                                <div class="flex flex-col items-start truncate">
                                    <div class="font-medium">
                                        {{ data.full_name }}
                                    </div>
                                    <div class="text-surface-500 text-xs max-w-48 truncate">
                                        {{ data.email }}
                                    </div>
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
                                <div
                                    v-if="data.group_id"
                                    class="flex items-center gap-2 rounded justify-center py-1 px-2 text-nowrap"
                                    :style="{ backgroundColor: formatRgbaColor(data.group_color, 0.1) }"
                                >
                                    <div
                                        class="w-1.5 h-1.5 grow-0 shrink-0 rounded-full"
                                        :style="{ backgroundColor: `#${data.group_color}` }"
                                    ></div>
                                    <div
                                        class="text-xs font-semibold"
                                        :style="{ color: `#${data.group_color}` }"
                                    >
                                        {{ data.group_name }}
                                    </div>
                                </div>
                                <div v-else>
                                    -
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column
                        field="upline_name"
                        :header="$t('public.referrer')"
                        class="hidden md:table-cell"
                    >
                        <template #body="{data}">
                            <div v-if="data.upline_id" class="flex items-center gap-3 max-w-60">
                                <Avatar
                                    v-if="data.upline_profile_photo"
                                    :image="data.upline_profile_photo"
                                    shape="circle"
                                    class="w-7 h-7 rounded-full overflow-hidden grow-0 shrink-0"
                                />
                                <Avatar
                                    v-else
                                    :label="formatNameLabel(data.upline_name)"
                                    shape="circle"
                                    class="w-7 h-7 rounded-full overflow-hidden grow-0 shrink-0 text-sm"
                                />
                                <div class="flex flex-col items-start">
                                    <div class="font-medium">
                                        {{ data.upline_name }}
                                    </div>
                                    <div class="text-surface-500 text-xs max-w-48 truncate">
                                        {{ data.upline_email }}
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                -
                            </div>
                        </template>
                    </Column>
                    <Column
                        field="country_id"
                        :header="$t('public.country')"
                        class="hidden md:table-cell"
                    >
                        <template #body="{data}">
                            <div class="flex items-center gap-1">
                                <img
                                    v-if="data.country_iso2"
                                    :src="`https://flagcdn.com/w40/${data.country_iso2.toLowerCase()}.png`"
                                    :alt="data.country_iso2"
                                    width="18"
                                    height="12"
                                />
                                <div>
                                    {{
                                        data.country_translations
                                            ? (JSON.parse(data.country_translations)[locale] || data.country_name || '-')
                                            : (data.country_name || '-')
                                    }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column
                        field="rank_id"
                        :header="$t('public.rank')"
                        class="hidden md:table-cell"
                    >
                        <template #body="{data}">
                            {{ $t(`public.${data.rank_name}`) }}
                        </template>
                    </Column>
                    <Column
                        field="active_capital"
                        sortable
                        :header="$t('public.capital')"
                        class="hidden md:table-cell"
                    >
                        <template #body="{data}">
                            {{ formatAmount(data.active_capital ?? 0) }}
                        </template>
                    </Column>
                    <Column
                        field="action"
                        header=""
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            <MemberTableActions
                                :member="slotProps.data"
                            />
                        </template>
                    </Column>
                    <Column class="md:hidden" headerClass="hidden">
                        <template #body="slotProps">
                            <div class="flex flex-col items-start gap-1 self-stretch">
                                <div class="flex items-center gap-2 self-stretch w-full">
                                    <div class="flex items-center gap-3 w-full">
                                        <Avatar
                                            v-if="slotProps.data.profile_photo"
                                            :image="slotProps.data.profile_photo"
                                            shape="circle"
                                            class="w-7 h-7 rounded-full overflow-hidden grow-0 shrink-0"
                                        />
                                        <Avatar
                                            v-else
                                            :label="formatNameLabel(slotProps.data.full_name)"
                                            shape="circle"
                                            class="w-7 h-7 rounded-full overflow-hidden grow-0 shrink-0 text-sm"
                                        />

                                        <div class="flex flex-col items-start">
                                            <div class="font-medium flex items-center justify-between max-w-[120px] xxs:max-w-[140px] min-[390px]:max-w-[180px] xs:max-w-[220px] truncate">
                                                <span class="truncate">{{ slotProps.data.full_name }}</span>
                                                <IconAlertCircleFilled
                                                    :size="20"
                                                    stroke-width="1.25"
                                                    class="text-red-500 flex-shrink-0 ml-2"
                                                    v-if="slotProps.data.kyc_status == 'pending'"
                                                />
                                                <!-- <IconAlertCircleFilled
                                                    :size="20"
                                                    stroke-width="1.25"
                                                    class="text-red-500 flex-shrink-0 ml-2"
                                                    v-if="slotProps.data.kyc_status == 'pending'"
                                                    v-tooltip.top="$t('public.trading_account_inactive_warning')"
                                                /> -->
                                            </div>
                                            <div class="text-surface-500 text-xs max-w-[120px] xxs:max-w-[140px] min-[390px]:max-w-[180px] xs:max-w-[220px] truncate">
                                                {{ slotProps.data.email }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-end">
                                        <MemberTableActions
                                            :member="slotProps.data"
                                        />
                                    </div>
                                </div>
                            </div>
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

            <!-- Filter Group-->
            <div class="flex flex-col gap-2 items-center self-stretch text-surface-950 dark:text-white">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_group') }}
                </div>
                <Select
                    v-model="filters['group_id'].value"
                    :options="groups"
                    filter
                    optionLabel="name"
                    :placeholder="$t('public.select_group')"
                    class="w-full"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
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
                        <span v-else class="text-surface-400 dark:text-surface-700">{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
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
                    <template #value="{value, placeholder}">
                        <div v-if="value && value.length > 0" class="flex items-center gap-1">
                            <div>
                                {{ value.slice(0, 2).map(data => data.user.full_name).join(', ') }}
                                <span v-if="value.length > 2">, ..</span>
                            </div>
                        </div>
                        <span v-else class="text-surface-400 dark:text-surface-700">{{ placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        {{ slotProps.option.user.full_name }}
                    </template>
                </MultiSelect>
            </div>

            <!-- Filter Status-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_status') }}
                </div>
                <div class="flex flex-col items-start w-full gap-1">
                    <div
                        v-for="option in user_statuses"
                        class="flex items-center gap-2 text-sm"
                    >
                        <RadioButton
                            v-model="filters['status'].value"
                            :inputId="option"
                            :name="option"
                            :value="option"
                        />
                        <label :for="option">{{ $t(`public.${option}`) }}</label>
                    </div>
                </div>
            </div>

            <!-- Filter kyc-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_kyc_status') }}
                </div>
                <div class="flex flex-col items-start w-full gap-1">
                    <div
                        v-for="option in kyc_statuses"
                        class="flex items-center gap-2 text-sm"
                    >
                        <RadioButton
                            v-model="filters['kyc_status'].value"
                            :inputId="option"
                            :name="option"
                            :value="option"
                        />
                        <label :for="option">{{ $t(`public.${option}`) }}</label>
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
                :disabled="isLoading"
            />
        </div>
    </Popover>
</template>
