<script setup>
import {InputText, Button, Select, Card, Tag, Avatar, Skeleton} from "primevue";
import {
    IconSearch,
    IconCircleXFilled,
    IconAdjustments,
    IconXboxX,
    IconUserDollar,
    IconPremiumRights,
    IconScanEye,
} from "@tabler/icons-vue";
import Empty from "@/Components/Empty.vue";
import {onMounted, ref, watch, watchEffect} from "vue";
import {generalFormat} from "@/Composables/format.js";
import {FilterMatchMode} from "@primevue/core/api";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";
import StrategyAction from "@/Pages/Strategy/Partials/StrategyAction.vue";

defineProps({
    strategiesCount: Number,
});

const isLoading = ref(false);
const first = ref(0);
const rows = ref(6);
const strategies = ref([]);
const totalRecords = ref(0);
const {formatAmount, formatNameLabel} = generalFormat();
const totalWalletTopUp = ref(null);
const totalWalletWithdrawal = ref(null);
const totalActiveCapital = ref(null);


const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const lazyParams = ref({});

const selectedDate = ref([]);

const clearDate = () => {
    selectedDate.value = [];
}

watch(
    filters.value['global'],
    debounce(() => {
        loadLazyData();
    }, 300)
);

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const loadLazyData = (event) => {
    isLoading.value = true;

    lazyParams.value = {
        ...lazyParams.value, first: event?.first || first.value, rows: event?.rows || rows.value
    };
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

            const url = route('strategy.getStrategyData', params);
            const response = await fetch(url);

            const results = await response.json();
            strategies.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            totalWalletTopUp.value = results?.totalWalletTopUp;
            totalWalletWithdrawal.value = results?.totalWalletWithdrawal;
            totalActiveCapital.value = results?.totalActiveCapital;

            isLoading.value = false;
        })
    } catch (e) {
        strategies.value = [];
        isLoading.value = false;
    }
}

const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};

onMounted(() => {
    lazyParams.value = {
        first: first.value,
        rows: rows.value,
        sortField: null,
        sortOrder: null,
    };
    loadLazyData({ first: first.value, rows: rows.value });
});

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
});

const emit = defineEmits(['update-totals']);

watch([totalWalletTopUp, totalWalletWithdrawal, totalActiveCapital], () => {
    emit('update-totals', {
        totalWalletTopUp: totalWalletTopUp.value,
        totalWalletWithdrawal: totalWalletWithdrawal.value,
        totalActiveCapital: totalActiveCapital.value,
    });
});

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});
</script>

<template>
    <div class="flex flex-col gap-5 items-center self-stretch">
        <!-- Toolbar -->
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
                    <IconXboxX size="16" stroke-width="1.5" />
                </div>
            </div>
            <div class="w-full flex justify-between items-center self-stretch gap-3">
                <Button
                    type="button"
                    severity="contrast"
                    class="flex gap-3 items-center"
                >
                    <IconAdjustments size="20" stroke-width="1.5" />
                    {{ $t('public.filter') }}
                </Button>
                <Button>

                </Button>
            </div>
        </div>

        <div v-if="strategiesCount === 0 && !strategies.length">
            <Empty
                :title="$t('public.no_strategies_created')"
                :message="$t('public.no_strategies_created_caption')"
            />
        </div>

        <div
            v-else
            class="w-full"
        >
            <div
                v-if="isLoading"
                class="grid grid-cols-1 md:grid-cols-2 3xl:grid-cols-3 gap-5 self-stretch"
            >
                <Card
                    v-for="(strategy, index) in strategiesCount > rows ? rows : strategiesCount"
                >
                    <template #content>
                        <div class="flex flex-col items-center gap-4 self-stretch">
                            <!-- Profile Section -->
                            <div class="w-full flex items-center gap-4 self-stretch">
                                <Avatar
                                    shape="circle"
                                    class="min-w-10"
                                />
                                <div class="flex flex-col items-start max-w-[100px] xxs:max-w-[124px] xs:max-w-full 3xl:max-w-[134px]">
                                    <Skeleton
                                        width="9rem"
                                        class="my-1"
                                        borderRadius="2rem"
                                    ></Skeleton>
                                    <Skeleton
                                        width="5rem"
                                        height="0.75rem"
                                        class="my-1"
                                        borderRadius="2rem"
                                    ></Skeleton>
                                </div>
                            </div>

                            <!-- StatusBadge Section -->
                            <div class="flex flex-wrap items-center gap-2 self-stretch mt-[1px] mb-[1px]">
                                <Skeleton
                                    width="4rem"
                                    class="my-1"
                                    borderRadius="2rem"
                                ></Skeleton>
                                <Skeleton
                                    width="4rem"
                                    class="my-1"
                                    borderRadius="2rem"
                                ></Skeleton>
                                <Skeleton
                                    width="4rem"
                                    class="my-1"
                                    borderRadius="2rem"
                                ></Skeleton>
                                <Skeleton
                                    width="4rem"
                                    class="my-1"
                                    borderRadius="2rem"
                                ></Skeleton>
                            </div>

                            <!-- Performance Section -->
                            <div class="py-2 flex justify-center items-center gap-2 self-stretch border-y border-solid border-surface-200 dark:border-surface-700">
                                <div class="w-full flex flex-col items-center">
                                    <Skeleton
                                        width="5rem"
                                        class="my-1"
                                        borderRadius="2rem"
                                    ></Skeleton>
                                    <div class="self-stretch text-surface-500 text-center text-xs">
                                        {{ $t('public.minimum') }}
                                    </div>
                                </div>
                                <div class="w-full flex flex-col items-center">
                                    <Skeleton
                                        width="5rem"
                                        class="my-1"
                                        borderRadius="2rem"
                                    ></Skeleton>
                                    <div class="self-stretch text-surface-500 text-center text-xs">
                                        {{ $t('public.settlement') }}
                                    </div>
                                </div>
                                <div class="w-full flex flex-col items-center">
                                    <Skeleton
                                        width="5rem"
                                        class="my-1"
                                        borderRadius="2rem"
                                    ></Skeleton>
                                    <div class="self-stretch text-surface-500 text-center text-xs">
                                        {{ $t('public.latest') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="flex items-end justify-between self-stretch">
                                <div class="flex flex-col items-center gap-1 self-stretch">
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <IconUserDollar size="20" stroke-width="1.25" />
                                        <Skeleton
                                            width="7rem"
                                            height="0.75rem"
                                            borderRadius="2rem"
                                        ></Skeleton>
                                    </div>
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <IconPremiumRights size="20" stroke-width="1.25" />
                                        <Skeleton
                                            width="7rem"
                                            height="0.75rem"
                                            borderRadius="2rem"
                                        ></Skeleton>
                                    </div>
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <IconScanEye size="20" stroke-width="1.25" />
                                        <Skeleton
                                            width="7rem"
                                            height="0.75rem"
                                            borderRadius="2rem"
                                        ></Skeleton>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <div v-else-if="!strategies.length">
                <Empty
                    :title="$t('public.no_strategies_created')"
                    :message="$t('public.no_strategies_created_caption')"
                />
            </div>

            <div
                v-else
                class="grid grid-cols-1 md:grid-cols-2 3xl:grid-cols-3 gap-5 self-stretch"
            >
                <Card
                    v-for="strategy in strategies"
                    :key="strategy.id"
                >
                    <template #content>
                        <div class="flex flex-col items-center gap-4 self-stretch">
                            <!-- Profile Section -->
                            <div class="w-full flex items-center gap-4 self-stretch">
                                <Avatar
                                    v-if="strategy?.media"
                                    :image="strategy.group_image"
                                    shape="circle"
                                    class="min-w-10"
                                />
                                <Avatar
                                    v-else
                                    :label="formatNameLabel(strategy.master_name)"
                                    shape="circle"
                                    class="min-w-10"
                                />
                                <div class="flex flex-col overflow-hidden w-full">
                                    <div class="self-stretch truncate text-surface-950 dark:text-white font-bold">
                                        {{ strategy.master_name }}
                                    </div>
                                    <div class="self-stretch truncate text-surface-500 text-sm">
                                        {{ strategy.meta_login }}
                                    </div>
                                </div>
                                <div class="flex gap-3 items-center w-fit justify-end">
                                    <StrategyAction
                                        :strategy="strategy"
                                    />
                                </div>
                            </div>

                            <!-- StatusBadge Section -->
                            <div class="flex flex-wrap items-center gap-2 self-stretch">
                                <Tag
                                    class="text-xs"
                                    :value="$t(`public.${strategy.category}`)"
                                />
                                <Tag
                                    severity="secondary"
                                    class="text-xs"
                                    :value="strategy.account_type.name"
                                />
                                <Tag severity="secondary" class="text-xs">
                                    <div v-if="strategy.investment_period !== 0">
                                        {{ strategy.investment_period }}
                                        {{ $t(`public.${strategy.investment_period_type}`) }}
                                    </div>
                                    <div v-else>
                                        {{ $t('public.lock_free') }}
                                    </div>
                                </Tag>
                                <Tag severity="secondary" class="text-xs">
                                    {{ formatAmount(strategy.sharing_profit, 0, '') + '%&nbsp;' + $t('public.profit') }}
                                </Tag>
                            </div>

                            <!-- Performance Section -->
                            <div class="py-2 flex justify-center items-center gap-2 self-stretch border-y border-solid border-surface-200 dark:border-surface-700">
                                <div class="w-full flex flex-col items-center">
                                    <div class="self-stretch text-surface-950 dark:text-white text-center font-semibold">
                                        {{ formatAmount(strategy.minimum_investment ?? 0) }}
                                    </div>
                                    <div class="self-stretch text-surface-500 text-center text-xs">
                                        {{ $t('public.minimum') }}
                                    </div>
                                </div>
                                <div class="w-full flex flex-col items-center">
                                    <div class="self-stretch text-surface-950 dark:text-white text-center font-semibold">
                                        {{ formatAmount(strategy.settlement_period, 0, '') }} {{ $t(`public.${strategy.settlement_period_type}`) }}
                                    </div>
                                    <div class="self-stretch text-surface-500 text-center text-xs">
                                        {{ $t('public.settlement') }}
                                    </div>
                                </div>
                                <div class="w-full flex flex-col items-center">
                                    <div class="self-stretch text-center font-semibold">
                                        <div
                                            v-if="strategy.latest_profit !== 0"
                                            :class="(strategy.latest_profit < 0) ? 'text-red-500' : 'text-green-500'"
                                        >
                                            {{ formatAmount(strategy.latest_profit ?? 0) }}
                                        </div>
                                        <div
                                            v-else
                                            class="text-surface-950 dark:text-white"
                                        >
                                            -
                                        </div>
                                    </div>
                                    <div class="self-stretch text-surface-500 text-center text-xs">
                                        {{ $t('public.latest') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="flex items-end justify-between self-stretch">
                                <div class="flex flex-col items-center gap-1 self-stretch">
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <div class="min-w-5">
                                            <IconUserDollar size="20" stroke-width="1.5" />
                                        </div>
                                        <div class="w-full text-surface-950 dark:text-white text-sm font-medium">
                                            {{ formatAmount(strategy.active_subscriptions_count, 0, '') }} {{ $t('public.investors') }}
                                        </div>
                                    </div>
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <div class="min-w-5">
                                            <IconPremiumRights size="20" stroke-width="1.5" />
                                        </div>
                                        <div class="w-full text-surface-950 dark:text-white text-sm font-medium">
                                            <span class="text-primary-500">{{ formatAmount(strategy.active_subscriptions_sum_real_fund ?? 0) }}</span> {{ $t('public.real_fund') }}
                                        </div>
                                    </div>
                                    <div class="py-1 flex items-center gap-3 self-stretch">
                                        <div class="min-w-5">
                                            <IconScanEye size="20" stroke-width="1.5" />
                                        </div>
                                        <div class="w-full text-surface-950 dark:text-white text-sm font-medium max-w-[128px] xxs:max-w-[180px] xs:max-w-[220px] sm:max-w-full md:max-w-[180px] xl:max-w-md 3xl:max-w-[180px] truncate">
                                            {{ strategy.group_names }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>
