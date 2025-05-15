<script setup>
import { computed, onMounted, ref, watch, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { generalFormat } from "@/Composables/format.js";
import debounce from "lodash/debounce.js";
import {
    IconSearch,
    IconCircleXFilled,
    IconAdjustments,
} from '@tabler/icons-vue';
import {
    Card,
    InputText,
    MultiSelect,
    RadioButton,
    Popover,
    Select,
    Paginator,
    Skeleton,
} from "primevue";
import Button from '@/Components/Button.vue';
import Badge from '@/Components/Badge.vue';
import Empty from '@/Components/Empty.vue';
import Loader from "@/Components/Loader.vue";
import AccountTypeAction from '@/Pages/AccountType/Partials/AccountTypeAction.vue';

const props = defineProps({
    accountTypesCount: Number,
    leverageOptions: Array,
})

// Define sort options
const sortOptions = ref([
    { name: 'latest', value: 'latest' },
    { name: 'oldest', value: 'oldest' },
    { name: 'popular', value: 'popular' },
    { name: 'least_popular', value: 'least_popular' },
]);

// Initialize sortType with the default value
const sortType = ref(sortOptions.value[0]);
const leverageOptions = ref(props.leverageOptions);
const selectedLeverages = ref([]);

const isLoading = ref(false);
const dt = ref();
const accountTypes = ref();
const totalRecords = ref(0);
const first = ref(0);
const rows = ref(10);
const page = ref(0);
const sortField = ref(null);  
const sortOrder = ref(null);  // (1 for ascending, -1 for descending)
const { formatRgbaColor, formatAmount } = generalFormat();

const filters = ref({
    global: '',
    category: '',
    type: '',
    leverages: [],
    status: '',
});

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

// Watch for changes on the sortType object and debounce the API call
watch(sortType, () => {
    page.value = 0;
    loadLazyData();
});

watch(selectedLeverages, (newVal) => {
    filters.value.leverages = Array.isArray(newVal) ? newVal.map(item => item.id) : [];
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
                sortField: sortType.value.value,
                // sortField: event?.sortField,
                // sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value),
            };

            const url = route('account_type.getAccountTypes', params);
            const response = await fetch(url);

            const results = await response.json();
            const accountTypesList = results?.data?.data || [];

            accountTypes.value = accountTypesList;
            totalRecords.value = results?.data?.total;

            isLoading.value = false;

        }, 100);
    } catch (error) {
        accountTypes.value = [];
        totalRecords.value = 0;
        isLoading.value = false;
    }
};

const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};

// const onSort = (event) => {
//     lazyParams.value = event;
//     loadLazyData(event);
// };

// const onFilter = (event) => {
//     lazyParams.value.fitlers = filters.value;
//     loadLazyData(event);
// };

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
const filterCount = ref(0);

const toggle = (event) => {
    op.value.toggle(event);
}

const clearFilter = () => {
    filters.value = {
        global: '',
        category: '',
        type: '',
        leverages: [],
        status: '',
    };

    selectedLeverages.value = null;
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
    <!-- toolbar -->
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
        <div class="w-full flex justify-between items-center self-stretch gap-3">
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
            <Select
                v-model="sortType"
                :options="sortOptions"
                optionLabel="name"
                class="w-full md:w-40"
                scroll-height="236px"
            >
                <template #value="slotProps">
                    <div v-if="slotProps.value" class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <div>{{ $t('public.' + slotProps.value.name) }}</div>
                        </div>
                    </div>
                </template>
                <template #option="slotProps">
                    {{ $t('public.' + slotProps.option.name) }}
                </template>
            </Select>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <div v-if="(accountTypesCount === 0 && !accountTypes.length) || (!isLoading && !accountTypes?.length)">
            <Empty
                :title="$t('public.empty_account_type_title')"
                :message="$t('public.empty_account_type_message')"
            />
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 3xl:grid-cols-4 gap-5 self-stretch">
            <Card v-if="isLoading" class="w-full">
                <template #content>
                    <div class="w-full flex flex-col items-start gap-2">
                        <div class="w-full flex items-center justify-between pb-2 gap-3 border-b border-solid border-surface-200 dark:border-surface-700">
                            <Skeleton
                                width="9rem"
                                height="36px"
                                borderRadius="2rem"
                            />

                            <Skeleton
                                width="3rem"
                                height="36px"
                                borderRadius="2rem"
                            />
                        </div>

                        <div class="w-full grid grid-cols-2 pb-2 gap-3 border-b border-solid border-surface-200 dark:border-surface-700">
                            <div class="w-full flex flex-col items-start gap-0.5">
                                <span class="text-surface-500 text-xs">{{ $t('public.category') }}</span>
                                <Skeleton
                                    width="5rem"
                                    height="20px"
                                    borderRadius="2rem"
                                />
                            </div>
                            <div class="w-full flex flex-col items-start gap-0.5">
                                <span class="text-surface-500 text-xs">{{ $t('public.type') }}</span>
                                <Skeleton
                                    width="5rem"
                                    height="20px"
                                    borderRadius="2rem"
                                />
                            </div>
                        </div>

                        <div class="w-full grid grid-cols-2 pb-2 gap-3">
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.minimum_deposit') }}:</span>
                                <Skeleton
                                    width="3rem"
                                    height="20px"
                                    borderRadius="2rem"
                                />
                            </div>
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.currency') }}:</span>
                                <Skeleton
                                    width="3rem"
                                    height="20px"
                                    borderRadius="2rem"
                                />
                            </div>
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.maximum_account') }}:</span>
                                <Skeleton
                                    width="3rem"
                                    height="20px"
                                    borderRadius="2rem"
                                />
                            </div>
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.total_account') }}:</span>
                                <Skeleton
                                    width="3rem"
                                    height="20px"
                                    borderRadius="2rem"
                                />
                            </div>
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.allow_trade') }}:</span>
                                <Skeleton
                                    width="3rem"
                                    height="20px"
                                    borderRadius="2rem"
                                />
                            </div>
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.color') }}:</span>
                                <Skeleton
                                    width="3rem"
                                    height="20px"
                                    borderRadius="2rem"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card v-else v-for="accountType in accountTypes" :key="accountType.id" class="w-full">
                <template #content>
                    <div class="w-full flex flex-col items-start gap-2">
                        <div class="w-full flex items-center justify-between pb-2 gap-3 border-b border-solid border-surface-200 dark:border-surface-700">
                            <span class="text-xl font-semibold">{{ accountType.name }}</span>
                            <AccountTypeAction 
                                :accountType="accountType" 
                                :leverageOptions="props.leverageOptions"
                                :users="props.users" 
                                :loading="isLoading"
                            />
                        </div>

                        <div class="w-full grid grid-cols-2 pb-2 gap-3 border-b border-solid border-surface-200 dark:border-surface-700">
                            <div class="w-full flex flex-col items-start gap-0.5">
                                <span class="text-surface-500 text-xs">{{ $t('public.category') }}</span>
                                <span class="text-surface-950 dark:text-white text-sm">{{ $t('public.' + accountType.category) }}</span>
                            </div>
                            <div class="w-full flex flex-col items-start gap-0.5">
                                <span class="text-surface-500 text-xs">{{ $t('public.type') }}</span>
                                <span class="text-surface-950 dark:text-white text-sm">{{ $t('public.' + accountType.type) }}</span>
                            </div>
                        </div>

                        <div class="w-full grid grid-cols-2 pb-2 gap-3">
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.minimum_deposit') }}:</span>
                                <span class="text-surface-950 dark:text-white text-xs">{{ formatAmount(accountType.minimum_deposit) }}</span>
                            </div>
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.currency') }}:</span>
                                <span class="text-surface-950 dark:text-white text-xs">{{ accountType.currency }}</span>
                            </div>
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.maximum_account') }}:</span>
                                <span class="text-surface-950 dark:text-white text-xs">{{ accountType.maximum_account_number }}</span>
                            </div>
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.total_account') }}:</span>
                                <span class="text-surface-950 dark:text-white text-xs">{{ accountType.total_account }}</span>
                            </div>
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.allow_trade') }}:</span>
                                <span class="text-surface-950 dark:text-white text-xs">{{ accountType.allow_trade ? $t('public.allow') : $t('public.not_allow') }}</span>
                            </div>
                            <div class="w-full flex items-start gap-2">
                                <span class="text-surface-500 text-xs">{{ $t('public.color') }}:</span>
                                <div
                                    class="w-[18px] h-[18px] grow-0 shrink-0"
                                    :style="{ backgroundColor: `#${accountType.color}` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>

    <Paginator 
        v-model:first="first"
        :rows="rows"
        :totalRecords="totalRecords"
        :rowsPerPageOptions="[10, 20, 50, 100]"
        template="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        :currentPageReportTemplate="$t('public.paginator_caption')"
        ref="dt"
        @page="onPage"
    />

    <Popover ref="op">
        <div class="flex flex-col gap-8 w-60 py-5 px-4">
            <!-- Filter Category-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_category') }}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm">
                        <RadioButton v-model="filters['category']" inputId="individual" value="individual" class="w-4 h-4" />
                        <label for="individual">{{ $t('public.individual') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <RadioButton v-model="filters['category']" inputId="managed" value="managed" class="w-4 h-4" />
                        <label for="managed">{{ $t('public.managed') }}</label>
                    </div>
                </div>
            </div>

            <!-- Filter Type-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_type') }}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm">
                        <RadioButton v-model="filters['type']" inputId="live" value="live" class="w-4 h-4" />
                        <label for="live">{{ $t('public.live') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <RadioButton v-model="filters['type']" inputId="virtual" value="virtual" class="w-4 h-4" />
                        <label for="virtual">{{ $t('public.virtual') }}</label>
                    </div>
                </div>
            </div>

            <!-- Filter Leverages-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs font-semibold">
                    {{ $t('public.filter_leverage') }}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <MultiSelect
                        v-model="selectedLeverages"
                        :options="leverageOptions"
                        optionLabel="label"
                        :placeholder="$t('public.select_leverage')"
                        :maxSelectedLabels="3"
                        class="w-full"
                    >
                        <template #option="{option}">
                            <div class="flex flex-col">
                                <span>{{ option.label }}</span>
                            </div>
                        </template>
                    </MultiSelect>
                </div>
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