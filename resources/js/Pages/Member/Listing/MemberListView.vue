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
} from "primevue";
import { usePage } from '@inertiajs/vue3';
import {
    IconSearch,
    IconCircleXFilled,
    IconAdjustments,
    IconAlertCircleFilled,
} from '@tabler/icons-vue';
import MemberTableActions from "@/Pages/Member/Listing/Partials/MemberTableActions.vue";
import { generalFormat } from "@/Composables/format.js";
import Empty from "@/Components/Empty.vue";
import debounce from "lodash/debounce.js";
import { useLangObserver } from "@/Composables/localeObserver.js";

const props = defineProps({
    groups: Array,
})

const totalVerified = ref(null);
const totalUnverified = ref(null);
const totalUsers = ref(null);
const exportStatus = ref(false);
const isLoading = ref(false);
const dt = ref();
const users = ref();
const totalRecords = ref(0);
const first = ref(0);
const rows = ref(10);
const page = ref(0);
const sortField = ref(null);  
const sortOrder = ref(null);  // (1 for ascending, -1 for descending)
const { formatRgbaColor, formatAmount, formatNameLabel } = generalFormat();
const { locale } = useLangObserver();

const group_id = ref(null)
const upline_id = ref(null)

const groups = ref();
const uplines = ref();

// Watch for changes in props
watch(() => [props.groups], ([newGroups]) => {
    groups.value = newGroups;
}, { immediate: true });

const filters = ref({
    global: '',
    group_id: null,
    upline_id: null,
    status: [],
    kyc_status: [],
});

const clearFilter = () => {
    filters.value = {
        global: '',
        upline_id: null,
        group_id: null,
        status: [],
        kyc_status: [],
    };

    upline_id.value = null;
    group_id.value = null;

};

const clearFilterGlobal = () => {
    filters.value.global = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});

// Watch for changes on the entire 'filters' object and debounce the API call
watch(filters, debounce(() => {
    page.value = 0; // Reset to first page when filters change
    loadLazyData(); // Call loadLazyData function to fetch the data
}, 1000), { deep: true });

// Watch for individual changes in upline_id and group_id and apply them to filters
watch([upline_id, group_id], ([newUplineId, newGroupId], [oldUplineId, oldGroupId]) => {
    // Handle group_id change
    if (newGroupId !== oldGroupId) {
        if (newGroupId !== null) {
            filters.value['group_id'] = newGroupId.id;

            // Reset upline selection when group changes
            upline_id.value = null;
            filters.value.upline_id = null;

            axios.get('/get_group_uplines', {
                params: { group_id: newGroupId.id }
            }).then((response) => {
                uplines.value = response.data.uplines;
            }).catch((red) => {
                console.red('Failed to fetch uplines:', red);
                uplines.value = [];
            });
        } else {
            filters.value.group_id = null;
            upline_id.value = null;
            filters.value.upline_id = null;
            uplines.value = [];
        }
    }

    // Handle upline_id change
    if (newUplineId !== oldUplineId) {
        if (newUplineId !== null) {
            filters.value.upline_id = newUplineId.id;
        } else {
            filters.value.upline_id = null;
        }
    }
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
            const usersList = results?.data?.data || [];

            users.value = usersList;
            totalRecords.value = results?.data?.total;

            totalVerified.value = usersList.filter(user => user.kyc_status === 'verified').length;
            totalUnverified.value = usersList.filter(user => user.kyc_status === 'unverified').length;
            totalUsers.value = results?.data?.total;

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

// overlay panel
const op = ref();

const toggle = (event) => {
    op.value.toggle(event);
}

const emit = defineEmits(['update-totals']);

watch([totalVerified, totalUnverified, totalUsers], () => {
    emit('update-totals', {
        totalVerified: totalVerified.value,
        totalUnverified: totalUnverified.value,
        totalUsers: totalUsers.value,
    });
});

</script>

<template>
    <!-- data table -->
    <div class="py-6 px-4 md:p-6 flex flex-col items-center justify-center self-stretch gap-6 border border-surface-200 dark:border-surface-600 bg-white dark:bg-surface-900 shadow-table rounded-2xl">
        <DataTable
            :value="users"
            :paginator="users?.length > 0"
            lazy
            removableSort
            :first="first"
            :page="page"
            :rows="10"
            :rowsPerPageOptions="[10, 20, 50, 100]"
            tableStyle="md:min-width: 50rem"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            :currentPageReportTemplate="$t('public.paginator_caption')"
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
                            <IconSearch size="20" stroke-width="1.25" />
                        </div>
                        <InputText v-model="filters['global']" :placeholder="$t('public.keyword_search')" class="font-normal pl-12 w-full md:w-60" />
                        <div
                            v-if="filters['global'] !== null && filters['global'] !== ''"
                            class="absolute top-2/4 -mt-2 right-4 text-surface-300 hover:text-surface-400 select-none cursor-pointer"
                            @click="clearFilterGlobal"
                        >
                            <IconCircleXFilled size="16" />
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
                                outlined
                                @click="exportMember"
                                class="w-full md:w-auto"
                            >
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
                >
                    <template #header>
                        <span class="hidden md:block">{{ $t('public.id') }}</span>
                    </template>
                    <template #body="slotProps">
                        <div class="text-surface-950 dark:text-white text-sm flex flex-wrap gap-1 items-center truncate">
                            {{ slotProps.data.id_number }}
                            <IconAlertCircleFilled  
                                :size="20" 
                                stroke-width="1.25" 
                                class="text-red-500 grow-0 shrink-0" 
                                v-if="slotProps.data.kyc_status == 'pending'"
                            />
                            <!-- <IconAlertCircleFilled  
                                :size="20" 
                                stroke-width="1.25" 
                                class="text-red-500 grow-0 shrink-0" 
                                v-if="slotProps.data.kyc_status == 'pending'"
                                v-tooltip.top="$t('public.trading_account_inactive_warning')"
                            /> -->
                        </div>
                    </template>
                </Column>
                <Column
                    field="full_name"
                    sortable
                    :header="$t('public.name')"
                    class="hidden md:table-cell"
                >
                    <template #body="slotProps">
                        <div class="flex items-center gap-3">
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
                                <div class="font-medium">
                                    {{ slotProps.data.full_name }}
                                </div>
                                <div class="text-surface-500 text-xs">
                                    {{ slotProps.data.email }}
                                </div>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column
                    field="group"
                    class="hidden md:table-cell"
                >
                    <template #header>
                        <span class="hidden md:block items-center justify-center w-full text-center">{{ $t('public.group') }}</span>
                    </template>
                    <template #body="slotProps">
                        <div class="flex items-center justify-center">
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
                    field="upline_name"
                    :header="$t('public.referrer')"
                    class="hidden md:table-cell"
                >
                    <template #body="slotProps">
                        <div v-if="slotProps.data.upline_id" class="flex items-center gap-3">
                            <Avatar
                                v-if="slotProps.data.upline_profile_photo"
                                :image="slotProps.data.upline_profile_photo"
                                shape="circle"
                                class="w-7 h-7 rounded-full overflow-hidden grow-0 shrink-0"
                            />
                            <Avatar
                                v-else
                                :label="formatNameLabel(slotProps.data.upline_name)"
                                shape="circle"
                                class="w-7 h-7 rounded-full overflow-hidden grow-0 shrink-0 text-sm"
                            />
                            <div class="flex flex-col items-start">
                                <div class="font-medium">
                                    {{ slotProps.data.upline_name }}
                                </div>
                                <div class="text-surface-500 text-xs">
                                    {{ slotProps.data.upline_email }}
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
                    <template #body="slotProps">
                        {{
                            slotProps.data.country_translations
                                ? (JSON.parse(slotProps.data.country_translations)[locale] || slotProps.data.country_name || '-')
                                : (slotProps.data.country_name || '-')
                        }}
                    </template>
                </Column>
                <Column
                    field="rank_id"
                    :header="$t('public.rank')"
                    class="hidden md:table-cell"
                >
                    <template #body="slotProps">
                        {{ $t('public.' + slotProps.data.rank_name) }}
                    </template>
                </Column>
                <Column
                    field="capital"
                    sortable
                    :header="$t('public.capital')"
                    class="hidden md:table-cell"
                >
                    <template #body="slotProps">
                        {{ formatAmount(slotProps.data.capital ?? 0) }}
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
    </div>

    <Popover ref="op">
        <div class="flex flex-col gap-8 w-60 py-5 px-4">
            <!-- Filter Group-->
            <div class="flex flex-col gap-2 items-center self-stretch text-surface-950 dark:text-white">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_group') }}
                </div>
                <Select
                    v-model="group_id"
                    :options="groups"
                    filter
                    :filterFields="['name']"
                    optionLabel="name"
                    :placeholder="$t('public.group_placeholder')"
                    class="w-full"
                    scroll-height="236px"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full overflow-hidden grow-0 shrink-0" :style="{ backgroundColor: `#${slotProps.value.color}` }"></div>
                                <div>{{ slotProps.value.name }}</div>
                            </div>
                        </div>
                        <span v-else class="text-surface-400">{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded-full overflow-hidden grow-0 shrink-0" :style="{ backgroundColor: `#${slotProps.option.color}` }"></div>
                            <div>{{ slotProps.option.name }}</div>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Filter Upline-->
            <div v-if="group_id" class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_upline') }}
                </div>
                <Select
                    v-model="upline_id"
                    :options="uplines"
                    filter
                    :filterFields="['name']"
                    optionLabel="name"
                    :placeholder="$t('public.select_upline')"
                    class="w-full"
                    scroll-height="236px"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <Avatar
                                    v-if="slotProps.value.profile_photo"
                                    :image="slotProps.value.profile_photo"
                                    shape="circle"
                                    class="w-5 h-5 text-xs rounded-full overflow-hidden grow-0 shrink-0"
                                />
                                <Avatar
                                    v-else
                                    :label="formatNameLabel(slotProps.value.full_name)"
                                    shape="circle"
                                    class="w-5 h-5 text-xs rounded-full overflow-hidden grow-0 shrink-0"
                                />
                                <div>{{ slotProps.value.full_name }}</div>
                            </div>
                        </div>
                        <span v-else class="text-surface-400">{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center gap-2">
                            <Avatar
                                v-if="slotProps.option.profile_photo"
                                :image="slotProps.option.profile_photo"
                                shape="circle"
                                class="w-5 h-5 text-xs rounded-full overflow-hidden grow-0 shrink-0"
                            />
                            <Avatar
                                v-else
                                :label="formatNameLabel(slotProps.option.full_name)"
                                shape="circle"
                                class="w-5 h-5 text-xs rounded-full overflow-hidden grow-0 shrink-0"
                            />
                            <div>{{ slotProps.option.full_name }}</div>
                        </div>
                    </template>
                </Select>
            </div>

            <!-- Filter Status-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_status') }}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm">
                        <RadioButton v-model="filters['status']" inputId="status_active" value="active" class="w-4 h-4" />
                        <label for="status_active">{{ $t('public.active') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <RadioButton v-model="filters['status']" inputId="status_inactive" value="inactive" class="w-4 h-4" />
                        <label for="status_inactive">{{ $t('public.inactive') }}</label>
                    </div>
                </div>
            </div>

            <!-- Filter kyc-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_kyc_status') }}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm">
                        <RadioButton v-model="filters['kyc_status']" inputId="kyc_status_unverified" value="unverified" class="w-4 h-4" />
                        <label for="kyc_status_unverified">{{ $t('public.unverified') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <RadioButton v-model="filters['kyc_status']" inputId="kyc_status_verified" value="verified" class="w-4 h-4" />
                        <label for="kyc_status_verified">{{ $t('public.verified') }}</label>
                    </div>
                </div>
            </div>

            <div class="flex w-full">
                <Button
                    type="button"
                    outlined
                    class="flex justify-center w-full"
                    @click="clearFilter()"
                >
                    {{ $t('public.clear_all') }}
                </Button>
            </div>
        </div>
    </Popover>

</template>
