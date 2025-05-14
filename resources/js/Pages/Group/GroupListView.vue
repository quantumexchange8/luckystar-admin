<script setup>
import {Card, DatePicker, InputText, Paginator, Skeleton, Avatar} from "primevue";
import {onMounted, ref, watch} from "vue";
import {IconUserFilled, IconXboxX, IconSearch} from "@tabler/icons-vue";
import {generalFormat} from "@/Composables/format.js";
import {FilterMatchMode} from "@primevue/core/api";
import CreateGroup from "@/Pages/Group/CreateGroup.vue";
import debounce from "lodash/debounce.js";
import Empty from "@/Components/Empty.vue";

const props = defineProps({
    groupsCount: Number
})

const isLoading = ref(false);
const first = ref(0);
const rows = ref(6);
const groups = ref([]);
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
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value),
            };

            const url = route('group.getGroupsData', params);
            const response = await fetch(url);

            const results = await response.json();
            groups.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            totalWalletTopUp.value = results?.totalWalletTopUp;
            totalWalletWithdrawal.value = results?.totalWalletWithdrawal;
            totalActiveCapital.value = results?.totalActiveCapital;

            isLoading.value = false;
        })
    } catch (e) {
        groups.value = [];
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
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div class="flex justify-between items-center self-stretch">
                    <div class="flex flex-col md:flex-row items-center self-stretch gap-3">
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

                        <div class="relative w-full md:w-60">
                            <DatePicker
                                v-model="selectedDate"
                                dateFormat="yy-mm-dd"
                                class="w-full"
                                selectionMode="range"
                                placeholder="YYYY-MM-DD - YYYY-MM-DD"
                                :manual-input="false"
                            />
                            <div
                                v-if="selectedDate && selectedDate.length > 0"
                                class="absolute top-2/4 -mt-1.5 right-3 text-surface-400 select-none cursor-pointer bg-transparent"
                                @click="clearDate"
                            >
                                <IconXboxX size="14" stoke-width="1.5" />
                            </div>
                        </div>
                    </div>
                    <CreateGroup />
                </div>

                <div v-if="groupsCount === 0 && !groups.length">
                    <Empty
                        :title="$t('public.no_groups_founded')"
                        :message="$t('public.no_groups_founded_caption')"
                    />
                </div>

                <div
                    v-else
                    class="w-full"
                >
                    <div
                        v-if="isLoading"
                        class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch w-full"
                    >
                        <div
                            v-for="(group, index) in groupsCount > rows ? rows : groupsCount"
                        >
                            <div class="flex flex-col items-center self-stretch w-full shadow-toast">
                                <div
                                    class="py-2 px-4 flex items-center gap-3 self-stretch bg-primary-500"
                                >
                                    <div class="flex-1 text-white font-semibold">
                                        <Skeleton
                                            width="9rem"
                                            class="my-1"
                                            borderRadius="2rem"
                                        ></Skeleton>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <IconUserFilled size="16" stroke-width="1.25" color="white" />
                                    </div>
                                </div>
                                <div class="p-4 flex flex-col items-center gap-2 self-stretch dark:bg-surface-600/20">
                                    <div class="min-w-[240px] pb-3 flex items-center gap-3 self-stretch border-b border-solid border-surface-200 dark:border-surface-700">
                                        <div class="flex items-center gap-3 flex-1">
                                            <Avatar
                                                shape="circle"
                                            />
                                            <div class="flex flex-col items-start flex-1">
                                                <div class="max-w-40 self-stretch overflow-hidden whitespace-nowrap text-surface-950 dark:text-white text-ellipsis text-sm font-medium md:max-w-[500px] xl:max-w-3xl">
                                                    <Skeleton
                                                        width="9rem"
                                                        height="1rem"
                                                        class="my-0.5"
                                                        shape="circle"
                                                    ></Skeleton>
                                                </div>
                                                <div class="max-w-40 self-stretch overflow-hidden whitespace-nowrap text-surface-500 dark:text-surface-500 text-ellipsis text-xs md:max-w-[500px] xl:max-w-3xl">
                                                    <Skeleton width="12rem" height="0.75rem" class="mt-0.5 mb-1" borderRadius="2rem"></Skeleton>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="min-w-[240px] pb-2 grid grid-cols-2 gap-2 self-stretch border-b border-solid border-surface-200 dark:border-surface-700">
                                        <div class="w-full flex flex-col items-start gap-1">
                                            <span class="text-surface-500 dark:text-surface-400 text-xs">{{ $t('public.wallet_top_up') }}:</span>
                                            <Skeleton
                                                width="9rem"
                                                height="0.75rem"
                                                class="my-1"
                                                borderRadius="2rem"
                                            ></Skeleton>
                                        </div>
                                        <div class="w-full flex flex-col items-start gap-1">
                                            <span class="text-surface-500 dark:text-surface-400 text-xs">{{ $t('public.wallet_withdrawal') }}:</span>
                                            <Skeleton
                                                width="9rem"
                                                height="0.75rem"
                                                class="my-1"
                                                borderRadius="2rem"
                                            ></Skeleton>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 self-stretch">
                                        <div class="w-full flex items-center gap-1">
                                            <span class="text-surface-500 dark:text-surface-400 text-xs">{{ $t('public.account_deposit') }}:</span>
                                            <Skeleton
                                                width="9rem"
                                                height="0.75rem"
                                                class="my-1"
                                                borderRadius="2rem"
                                            ></Skeleton>
                                        </div>
                                        <div class="w-full flex items-center gap-1">
                                            <span class="text-surface-500 dark:text-surface-400 text-xs">{{ $t('public.account_withdrawal') }}:</span>
                                            <Skeleton
                                                width="9rem"
                                                height="0.75rem"
                                                class="my-1"
                                                borderRadius="2rem"
                                            ></Skeleton>
                                        </div>
                                        <div class="w-full flex items-center gap-1">
                                            <span class="text-surface-500 dark:text-surface-400 text-xs">{{ $t('public.active_capital') }}:</span>
                                            <Skeleton
                                                width="9rem"
                                                height="0.75rem"
                                                class="my-1"
                                                borderRadius="2rem"
                                            ></Skeleton>
                                        </div>
                                        <div class="w-full flex items-center gap-1">
                                            <span class="text-surface-500 dark:text-surface-400 text-xs">{{ $t('public.profit') }}:</span>
                                            <Skeleton
                                                width="9rem"
                                                height="0.75rem"
                                                class="my-1"
                                                borderRadius="2rem"
                                            ></Skeleton>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="!groups.length">
                        <Empty
                            :title="$t('public.no_groups_founded')"
                            :message="$t('public.no_groups_founded_caption')"
                        />
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch w-full"
                    >
                        <div
                            v-for="(group, index) in groups"
                            :key="index"
                            class="flex flex-col items-center self-stretch w-full shadow-toast"
                        >
                            <div
                                class="py-2 px-4 flex items-center gap-3 self-stretch"
                                :style="{'backgroundColor': `#${group.color}`}"
                            >
                                <div class="flex-1 text-white font-semibold">
                                    {{ group.name }}
                                </div>
                                <div class="flex items-center gap-2">
                                    <IconUserFilled size="16" color="white" stroke-width="1.5" />
                                    <div class="text-white text-right text-sm font-medium">
                                        {{ group.group_has_user_count }}
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 flex flex-col items-center gap-2 self-stretch dark:bg-surface-600/20 transition-all duration-200">
                                <div class="min-w-[240px] pb-3 flex items-center gap-3 self-stretch border-b border-solid border-surface-200 dark:border-surface-700">
                                    <div class="flex items-center gap-3 flex-1">
                                        <Avatar
                                            v-if="group.group_leader?.media"
                                            :image="group.group_leader.profile_photo"
                                            shape="circle"
                                        />
                                        <Avatar
                                            v-else
                                            :label="formatNameLabel(group.group_leader.full_name)"
                                            shape="circle"
                                        />
                                        <div class="flex flex-col items-start flex-1">
                                            <div class="max-w-40 self-stretch overflow-hidden whitespace-nowrap text-surface-950 dark:text-white text-ellipsis text-sm font-medium md:max-w-[500px] xl:max-w-3xl">
                                                {{ group.group_leader.full_name }}
                                            </div>
                                            <div class="max-w-40 self-stretch overflow-hidden whitespace-nowrap text-surface-500 dark:text-surface-400 text-ellipsis text-xs md:max-w-[500px] xl:max-w-3xl">
                                                {{ group.group_leader.email }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="min-w-[240px] pb-2 grid grid-cols-2 gap-2 self-stretch border-b border-solid border-surface-200 dark:border-surface-700">
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="text-surface-500 text-xs">{{ $t('public.wallet_top_up') }}:</span>
                                        <span class="text-surface-950 dark:text-white text-sm font-medium">{{ formatAmount(group.wallet_top_up ?? 0) }}</span>
                                    </div>
                                    <div class="w-full flex flex-col items-start gap-1">
                                        <span class="text-surface-500 text-xs">{{ $t('public.wallet_withdrawal') }}:</span>
                                        <span class="text-surface-950 dark:text-white text-sm font-medium">{{ formatAmount(group.wallet_withdrawal ?? 0) }}</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 self-stretch">
                                    <div class="w-full flex items-center gap-1">
                                        <span class="text-surface-500 text-xs">{{ $t('public.account_deposit') }}:</span>
                                        <span class="text-surface-950 dark:text-white text-sm font-medium">{{ formatAmount(group.account_deposit ?? 0) }}</span>
                                    </div>
                                    <div class="w-full flex items-center gap-1">
                                        <span class="text-surface-500 text-xs">{{ $t('public.account_withdrawal') }}:</span>
                                        <span class="text-surface-950 dark:text-white text-sm font-medium">{{ formatAmount(group.account_withdrawal ?? 0) }}</span>
                                    </div>
                                    <div class="w-full flex items-center gap-1">
                                        <span class="text-surface-500 text-xs">{{ $t('public.active_capital') }}:</span>
                                        <span class="text-surface-950 dark:text-white text-sm font-medium">{{ formatAmount(group.active_capital ?? 0) }}</span>
                                    </div>
                                    <div class="w-full flex items-center gap-1">
                                        <span class="text-surface-500 text-xs">{{ $t('public.profit') }}:</span>
                                        <span class="text-surface-950 dark:text-white text-sm font-medium">{{ formatAmount(0) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <Paginator
                    v-if="totalRecords > 0"
                    :rows="rows"
                    :totalRecords="totalRecords"
                    @page="onPage"
                ></Paginator>
            </div>
        </template>
    </Card>
</template>
