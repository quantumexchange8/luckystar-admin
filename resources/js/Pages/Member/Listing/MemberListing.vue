<script setup>
import {computed, onMounted, ref, watch, watchEffect} from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import AddMember from "@/Pages/Member/Listing/Partials/AddMember.vue";
import { MemberIcon, IBIcon, UserIcon, } from '@/Components/Icons/solid';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import Button from '@/Components/Button.vue';
import {usePage} from '@inertiajs/vue3';
import Popover from 'primevue/popover';
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import Loader from "@/Components/Loader.vue";
import Select from 'primevue/select';
import {
    IconSearch,
    IconCircleXFilled,
    IconAdjustments,
    IconAlertCircleFilled,
    IconDotsVertical,
    IconReportSearch,
    IconPencilMinus,
    IconTrash
} from '@tabler/icons-vue';
import Badge from '@/Components/Badge.vue';
import MemberTableActions from "@/Pages/Member/Listing/Partials/MemberTableActions.vue";
import { trans, wTrans } from "laravel-vue-i18n";
import { generalFormat } from "@/Composables/format.js";
import StatusBadge from "@/Components/StatusBadge.vue";
import Empty from "@/Components/Empty.vue";
import debounce from "lodash/debounce.js";

const total_unverified = ref(0);
const total_verified = ref(0);
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
const { formatRgbaColor, formatAmount } = generalFormat();

// data overview
const dataOverviews = computed(() => [
    {
        icon: MemberIcon,
        total: total_verified.value,
        label: trans('public.total_verified'),
    },
    {
        icon: IBIcon,
        total: total_unverified.value,
        label: trans('public.total_unverified'),
    },
    {
        icon: UserIcon,
        total: totalRecords.value,
        label: trans('public.total_users'),
    },
]);

const filters = ref({
    global: '',
    group_id: null,
    upline_id: null,
    role: [],
    status: [],
    kyc_status: [],
});

const group_id = ref(null)
const upline_id = ref(null)

// Watch for changes on the entire 'filters' object and debounce the API call
watch(filters, debounce(() => {
    // Count active filters, excluding null, undefined, empty strings, and empty arrays
    filterCount.value = Object.values(filters.value).filter(filter => {
        if (Array.isArray(filter)) {
            return filter.length > 0;  // Check if the array is not empty
        }
        return filter !== null && filter !== '';  // Check if the value is not null or an empty string
    }).length;

    page.value = 0; // Reset to first page when filters change
    loadLazyData(); // Call loadLazyData function to fetch the data
}, 1000), { deep: true });

// Watch for individual changes in upline_id and group_id and apply them to filters
watch([upline_id, group_id], ([newUplineId, newGroupId]) => {
    if (newUplineId !== null) {
        filters.value['upline_id'] = newUplineId.value;
    }

    if (newGroupId !== null) {
        filters.value['group_id'] = newGroupId.value;

        axios.get('/get_group_uplines', {
            params: { group_id: newGroupId }
        }).then((response) => {
            uplines.value = response.data.uplines;

            // Reset upline selection
            upline_id.value = null;
            filters.value.upline_id = null;
        }).catch((error) => {
            console.error('Failed to fetch uplines:', error);
            uplines.value = [];
        });
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

            total_verified.value = usersList.filter(user => user.kyc_status === 'verified').length;
            total_unverified.value = usersList.filter(user => user.kyc_status === 'unverified').length;

            isLoading.value = false;

        }, 100);
    } catch (error) {
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

// Optimized exportRebateSummary function
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
        console.error('Error occured during export:', e);
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
const uplines = ref()
const groups = ref()
const filterCount = ref(0);

const toggle = (event) => {
    op.value.toggle(event);
}

const clearFilter = () => {
    filters.value = {
        global: '',
        upline_id: null,
        group_id: null,
        role: [],
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

</script>

<template>
    <AuthenticatedLayout :title="$t('public.member_listing')">
        <div class="flex flex-col gap-5 items-center">
            <div class="flex justify-end w-full">
                <AddMember />
            </div>

            <!-- data overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 w-full gap-3 md:gap-5">
                <div
                    v-for="(item, index) in dataOverviews"
                    :key="index"
                    class="flex justify-center items-center px-6 py-4 md:p-6 gap-5 self-stretch rounded-2xl bg-white dark:bg-gray-700 shadow-toast"
                >
                    <component :is="item.icon" class="w-12 h-12 grow-0 shrink-0" />
                    <div class="flex flex-col items-start gap-1 w-full">
                        <div class="text-gray-950 dark:text-white text-lg md:text-2xl font-semibold">
                            {{ formatAmount(item.total, 0, '') }}
                        </div>
                        <span class="text-gray-500 dark:text-white text-xs md:text-sm">{{ item.label }}</span>
                    </div>
                </div>
            </div>

            <!-- data table -->
            <div class="py-6 px-4 md:p-6 flex flex-col items-center justify-center self-stretch gap-6 border border-gray-200 dark:border-gray-600 bg-white dark:bg-surface-900 shadow-table rounded-2xl">
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
                                <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                                    <IconSearch size="20" stroke-width="1.25" />
                                </div>
                                <InputText v-model="filters['global']" :placeholder="$t('public.keyword_search')" class="font-normal pl-12 w-full md:w-60" />
                                <div
                                    v-if="filters['global'] !== null && filters['global'] !== ''"
                                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                    @click="clearFilterGlobal"
                                >
                                    <IconCircleXFilled size="16" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 w-full gap-3">
                                <Button
                                    variant="gray-outlined"
                                    @click="toggle"
                                    size="sm"
                                    class="flex gap-3 items-center justify-center py-3 w-full md:w-[130px]"
                                >
                                    <IconAdjustments size="20" stroke-width="1.25" />
                                    <div class="text-sm font-medium">
                                        {{ $t('public.filter') }}
                                    </div>
                                    <Badge class="w-5 h-5 text-xs text-white" variant="numberbadge">
                                        {{ filterCount }}
                                    </Badge>
                                </Button>
                                <div class="w-full flex justify-end">
                                    <Button
                                        variant="primary-outlined"
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
                            <Loader />
                            <span class="text-sm text-gray-700">{{ $t('public.loading_users_caption') }}</span>
                        </div>
                    </template>
                    <template v-if="users?.length > 0">
                        <Column
                            field="id_number"
                            sortable
                            style="width: 15%"
                            class="hidden md:table-cell"
                        >
                            <template #header>
                                <span class="hidden md:block">{{ $t('public.id') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="text-gray-950 dark:text-white text-sm flex flex-wrap gap-1 items-center truncate">
                                    {{ slotProps.data.id_number }}
                                    <IconAlertCircleFilled  
                                        :size="20" 
                                        stroke-width="1.25" 
                                        class="text-error-500 grow-0 shrink-0" 
                                        v-if="slotProps.data.kyc_status == 'pending'"
                                    />
                                    <!-- <IconAlertCircleFilled  
                                        :size="20" 
                                        stroke-width="1.25" 
                                        class="text-error-500 grow-0 shrink-0" 
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
                            style="width: 35%"
                            class="hidden md:table-cell"
                        >
                            <template #body="slotProps">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-full overflow-hidden grow-0 shrink-0">
                                        <template v-if="slotProps.data.profile_photo">
                                            <img :src="slotProps.data.profile_photo" alt="profile_photo">
                                        </template>
                                        <template v-else>
                                            <DefaultProfilePhoto />
                                        </template>
                                    </div>
                                    <div class="flex flex-col items-start">
                                        <div class="font-medium">
                                            {{ slotProps.data.full_name }}
                                        </div>
                                        <div class="text-gray-500 text-xs">
                                            {{ slotProps.data.email }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="role"
                            style="width: 15%"
                            class="hidden md:table-cell"
                        >
                            <template #header>
                                <span class="hidden md:block items-center justify-center w-full text-center">{{ $t('public.role') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex items-center justify-center">
                                    <StatusBadge :variant="slotProps.data.role" :value="$t('public.' + slotProps.data.role)"/>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="group"
                            style="width: 20%"
                            class="hidden md:table-cell"
                        >
                            <template #header>
                                <span class="hidden md:block items-center justify-center w-full text-center">{{ $t('public.group') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex items-center justify-center">
                                    <div
                                        v-if="slotProps.data.group_id"
                                        class="flex items-center gap-2 rounded justify-center py-1 px-2"
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
                            field="action"
                            header=""
                            style="width: 15%"
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
                                            <div class="w-7 h-7 rounded-full overflow-hidden grow-0 shrink-0">
                                                <template v-if="slotProps.data.profile_photo">
                                                    <img :src="slotProps.data.profile_photo" alt="profile_photo">
                                                </template>
                                                <template v-else>
                                                    <DefaultProfilePhoto />
                                                </template>
                                            </div>
                                            <div class="flex flex-col items-start">
                                                <div class="font-medium flex items-center justify-between max-w-[120px] xxs:max-w-[140px] min-[390px]:max-w-[180px] xs:max-w-[220px] truncate">
                                                    <span class="truncate">{{ slotProps.data.full_name }}</span>
                                                    <IconAlertCircleFilled  
                                                        :size="20" 
                                                        stroke-width="1.25" 
                                                        class="text-error-500 flex-shrink-0 ml-2" 
                                                        v-if="slotProps.data.kyc_status == 'pending'"
                                                    />
                                                    <!-- <IconAlertCircleFilled  
                                                        :size="20" 
                                                        stroke-width="1.25" 
                                                        class="text-error-500 flex-shrink-0 ml-2" 
                                                        v-if="slotProps.data.kyc_status == 'pending'"
                                                        v-tooltip.top="$t('public.trading_account_inactive_warning')"
                                                    /> -->
                                                </div>
                                                <div class="text-gray-500 text-xs max-w-[120px] xxs:max-w-[140px] min-[390px]:max-w-[180px] xs:max-w-[220px] truncate">
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
                                    <div class="flex items-center gap-1 h-[26px]">
                                        <StatusBadge :variant="slotProps.data.role" :value="$t('public.' + slotProps.data.role)"/>
                                        <div class="flex items-center justify-center">
                                            <div
                                                v-if="slotProps.data.group_id"
                                                class="flex items-center gap-2 rounded justify-center py-1 px-2"
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
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>

    <Popover ref="op">
        <div class="flex flex-col gap-8 w-60 py-5 px-4">
            <!-- Filter Role-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_role') }}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm">
                        <RadioButton v-model="filters['role']" inputId="role_member" value="member" class="w-4 h-4" />
                        <label for="role_member">{{ $t('public.member') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <RadioButton v-model="filters['role']" inputId="role_ib" value="ib" class="w-4 h-4" />
                        <label for="role_ib">{{ $t('public.ib') }}</label>
                    </div>
                </div>
            </div>

            <!-- Filter Group-->
            <div class="flex flex-col gap-2 items-center self-stretch text-gray-950 dark:text-white">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_group') }}
                </div>
                <Select
                    v-model="group_id"
                    :options="groups"
                    filter
                    :filterFields="['name']"
                    optionLabel="name"
                    :placeholder="$t('public.select_group_placeholder')"
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
                        <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
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
                                <div class="w-5 h-5 rounded-full overflow-hidden">
                                    <template v-if="slotProps.value.profile_photo">
                                        <img :src="slotProps.value.profile_photo" alt="profile_picture" />
                                    </template>
                                    <template v-else>
                                        <DefaultProfilePhoto />
                                    </template>
                                </div>
                                <div>{{ slotProps.value.name }}</div>
                            </div>
                        </div>
                        <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded-full overflow-hidden">
                                <template v-if="slotProps.option.profile_photo">
                                    <img :src="slotProps.option.profile_photo" alt="profile_picture" />
                                </template>
                                <template v-else>
                                    <DefaultProfilePhoto />
                                </template>
                            </div>
                            <div>{{ slotProps.option.name }}</div>
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
                    variant="primary-outlined"
                    class="flex justify-center w-full"
                    @click="clearFilter()"
                >
                    {{ $t('public.clear_all') }}
                </Button>
            </div>
        </div>
    </Popover>

</template>
