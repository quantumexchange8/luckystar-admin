<script setup>
import {Button, Card, InputText, InputNumber, MultiSelect, Select, Tag, useToast} from "primevue";
import {IconArrowNarrowLeft, IconAlertSquareRounded, IconFileCheck, IconPhotoPlus} from "@tabler/icons-vue";
import {ref, watch} from "vue";
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import SelectChipGroup from "@/Components/SelectChipGroup.vue";
import ManagementFeeSetting from "@/Pages/Strategy/ManagementFeeSetting.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TipTapEditor from "@/Components/TipTapEditor.vue";

const props = defineProps({
    accountTypes: Array,
});

const form = useForm({
    strategy_name: '',
    trader_name: '',
    category: '',
    strategy_account_type: '',
    account_type_id: '',
    account_number: '',
    estimated_lot: '',
    estimated_monthly_return: '',
    max_drawdown: '',
    cut_loss: '',
    additional_capital: null,
    additional_investors: '',
    strategy_image: '',
    description: '',
    visible_to: null,
    leverage: null,
    minimum_investment: null,
    investment_period: null,
    investment_period_type: '',
    can_top_up: '',
    can_terminate: '',
    sharing_profit: null,
    market_profit: null,
    company_profit: null,
    settlement_period: null,
    settlement_period_type: '',
    management_fee: ''
});

const categories = [
    'pamm',
];

const selectedCategory = ref();
const selectedStrategyAccountType = ref('');

watch(selectedStrategyAccountType, (val) => {
    if (val === 'existing') {
        selectedAccountType.value = null;
    } else {
        form.account_number = '';
    }
});

const selectedAccountType = ref();

const leverages = ref([]);
const loadingLeverages = ref(false);
const selectedLeverage = ref();

watch(selectedAccountType, () => {
    if (selectedAccountType.value) {
        getLeverages();
    } else {
        leverages.value = [];
    }
})

const getLeverages = async () => {
    loadingLeverages.value = true;
    try {
        const response = await axios.get(route('getLeverages', selectedAccountType.value));
        leverages.value = response.data.leverages;
    } catch (error) {
        console.error('Error fetching leverages:', error);
    } finally {
        loadingLeverages.value = false;
    }
}

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

getGroups();

const estimated_lot_min = ref();
const estimated_lot_max = ref();

const estimated_monthly_return_min = ref();
const estimated_monthly_return_max = ref();

const toast = useToast();

// Front Identity
const frontFile = ref(null);
const isDraggingFront = ref(false);

const dragOverFront = () => {
    isDraggingFront.value = true;
};

const dragLeaveFront = () => {
    isDraggingFront.value = false;
};

const handleDropFront = (event) => {
    isDraggingFront.value = false;
    form.errors.strategy_image = null;
    const droppedFiles = event.dataTransfer.files;
    if (droppedFiles.length > 0) {
        validateFile(droppedFiles[0], 'strategy_image');
    }
};

const handleFrontFileSelect = (event) => {
    form.errors.strategy_image = null;
    const selectedFile = event.target.files[0];
    validateFile(selectedFile, 'strategy_image');
};

const validateFile = (fileInput, identity_type) => {
    if (fileInput.type.startsWith("image/")) {
        if (identity_type === "strategy_image") {
            frontFile.value = fileInput;
            form.strategy_image = frontFile.value;
        } else if (identity_type === "back_identity") {
            backFile.value = fileInput;
            form.back_identity = backFile.value;
        } else {
            passportFile.value = fileInput;
            form.passport_identity = passportFile.value;
        }
    } else {
        toast.add({
            severity: 'error',
            summary: trans('public.error'),
            detail: trans('public.toast_file_type_error'),
            life: 5000,
        });
    }
};

const investmentPeriodTypes = [
    'day',
    'month',
    'year',
];

const settlementPeriodTypes = [
    'day',
    'week',
    'month',
];

const booleanOptions = [
    {label: 'yes', value: 1},
    {label: 'no', value: 0},
]

const managementFee = ref();

const submitForm = () => {
    if (estimated_lot_min.value && estimated_lot_max.value) {
        form.estimated_lot = estimated_lot_min.value + '-' + estimated_lot_max.value;
    } else {
        form.estimated_lot = '';
    }

    if (estimated_monthly_return_min.value && estimated_monthly_return_max.value) {
        form.estimated_monthly_return = estimated_monthly_return_min.value + '-' + estimated_monthly_return_max.value;
    } else {
        form.estimated_monthly_return = '';
    }

    if (selectedAccountType.value) {
        form.account_type_id = selectedAccountType.value;
    } else {
        form.account_type_id = '';
    }

    if (selectedLeverage.value) {
        form.leverage = selectedLeverage.value;
    } else {
        form.leverage = null;
    }

    form.category = selectedCategory.value;
    form.strategy_account_type = selectedStrategyAccountType.value;
    form.visible_to = selectedGroup.value;
    form.management_fee = managementFee.value;
    form.post(route('strategy.addStrategy'), {
        onSuccess: () => {
            form.reset();
        }
    });
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.new_strategy')">
        <form class="grid xl:grid-cols-6 gap-5 self-stretch">
            <!-- Left -->
            <div class="flex flex-col items-center gap-5 xl:col-span-4 self-stretch">
                <!-- Basic Information -->
                <Card class="w-full">
                    <template #content>
                        <div class="flex flex-col items-center gap-3 self-stretch">
                            <span class="self-stretch text-sm font-bold">{{ $t('public.basic_information') }}</span>
                            <div class="w-full grid grid-cols-1 gap-3 md:gap-5 md:grid-cols-2">
                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="strategy_name"
                                        :value="$t('public.strategy_name')"
                                        :invalid="!!form.errors.strategy_name"
                                    />
                                    <InputText
                                        id="strategy_name"
                                        type="text"
                                        class="block w-full"
                                        v-model="form.strategy_name"
                                        :placeholder="$t('public.strategy_name_placeholder')"
                                        :invalid="!!form.errors.strategy_name"
                                        autofocus
                                    />
                                    <InputError :message="form.errors.strategy_name" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="category"
                                        :value="$t('public.category')"
                                        :invalid="!!form.errors.category"
                                    />
                                    <SelectChipGroup
                                        :items="categories"
                                        v-model="selectedCategory"
                                    />
                                    <InputError :message="form.errors.category" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="strategy_account"
                                        :value="$t('public.strategy_account')"
                                        :invalid="!!form.errors.strategy_account_type"
                                    />
                                    <SelectChipGroup
                                        v-model="selectedStrategyAccountType"
                                        :items="['existing', 'create_new']"
                                    />
                                    <InputError :message="form.errors.strategy_account_type" />
                                </div>

                                <div
                                    v-if="selectedStrategyAccountType === 'existing'"
                                    class="flex flex-col items-start gap-1 self-stretch"
                                >
                                    <InputLabel
                                        for="public.account_number"
                                        :value="$t('public.account_number')"
                                        :invalid="!!form.errors.account_number"
                                    />
                                    <InputNumber
                                        v-model="form.account_number"
                                        inputId="account_number"
                                        fluid
                                        :useGrouping="false"
                                        :placeholder="$t('public.enter_account_number')"
                                        :invalid="!!form.errors.account_number"
                                    />
                                    <InputError :message="form.errors.account_number" />
                                </div>

                                <div
                                   v-else
                                    class="flex flex-col items-start gap-1 self-stretch"
                                >
                                    <InputLabel
                                        for="account_type"
                                        :value="$t('public.account_type')"
                                        :invalid="!!form.errors.account_type_id"
                                    />
                                    <SelectChipGroup
                                        v-model="selectedAccountType"
                                        :items="accountTypes"
                                        value-key="id"
                                    >
                                        <template #option="{ item }">
                                            {{ item.name }}
                                        </template>
                                    </SelectChipGroup>
                                    <InputError :message="form.errors.account_type_id" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="estimated_lot"
                                        :value="$t('public.estimated_lot') + ' (Ł)'"
                                        :invalid="!!form.errors.estimated_lot"
                                    />
                                    <div class="flex items-center gap-2 w-full">
                                        <InputNumber
                                            v-model="estimated_lot_min"
                                            inputId="estimated_lot"
                                            fluid
                                            :maxFractionDigits="2"
                                            placeholder="0"
                                            :invalid="!!form.errors.estimated_lot"
                                        />
                                        -
                                        <InputNumber
                                            v-model="estimated_lot_max"
                                            inputId="estimated_lot_max"
                                            fluid
                                            :maxFractionDigits="2"
                                            placeholder="5"
                                            :invalid="!!form.errors.estimated_lot"
                                        />
                                    </div>
                                    <InputError :message="form.errors.estimated_lot" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="estimated_monthly_return"
                                        :value="$t('public.estimated_monthly_return') + ' (%)'"
                                        :invalid="!!form.errors.estimated_monthly_return"
                                    />
                                    <div class="flex items-center gap-2 w-full">
                                        <InputNumber
                                            v-model="estimated_monthly_return_min"
                                            inputId="estimated_monthly_return"
                                            fluid
                                            :maxFractionDigits="2"
                                            placeholder="10"
                                            :invalid="!!form.errors.estimated_monthly_return"
                                        />
                                        -
                                        <InputNumber
                                            v-model="estimated_monthly_return_max"
                                            inputId="estimated_monthly_return_max"
                                            fluid
                                            :maxFractionDigits="2"
                                            placeholder="20"
                                            :invalid="!!form.errors.estimated_monthly_return"
                                        />
                                    </div>
                                    <InputError :message="form.errors.estimated_monthly_return" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="trader_name"
                                        :value="$t('public.trader_name')"
                                        :invalid="!!form.errors.trader_name"
                                    />
                                    <InputText
                                        id="trader_name"
                                        type="text"
                                        class="block w-full"
                                        v-model="form.trader_name"
                                        :placeholder="$t('public.trader_name_placeholder')"
                                        :invalid="!!form.errors.trader_name"
                                    />
                                    <InputError :message="form.errors.trader_name" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="max_drawdown"
                                        :value="$t('public.max_drawdown')"
                                        :invalid="!!form.errors.max_drawdown"
                                    />
                                    <InputText
                                        id="max_drawdown"
                                        type="text"
                                        class="block w-full"
                                        v-model="form.max_drawdown"
                                        :placeholder="$t('public.max_drawdown_placeholder')"
                                        :invalid="!!form.errors.max_drawdown"
                                    />
                                    <InputError :message="form.errors.max_drawdown" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="cut_loss"
                                        :value="$t('public.cut_loss')"
                                        :invalid="!!form.errors.cut_loss"
                                    />
                                    <InputText
                                        id="cut_loss"
                                        type="text"
                                        class="block w-full"
                                        v-model="form.cut_loss"
                                        :placeholder="$t('public.cut_loss_placeholder')"
                                        :invalid="!!form.errors.cut_loss"
                                    />
                                    <InputError :message="form.errors.cut_loss" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="leverage"
                                        :invalid="!!form.errors.leverage"
                                    >
                                        {{ $t('public.leverage') }}
                                    </InputLabel>
                                    <Select
                                        v-model="selectedLeverage"
                                        id="leverage"
                                        :options="leverages"
                                        class="w-full"
                                        :placeholder="$t('public.select_leverage')"
                                        :invalid="!!form.errors.leverage"
                                        :loading="loadingLeverages"
                                        :disabled="!leverages.length"
                                    >
                                        <template #value="{ value }">
                                            <div v-if="value" class="flex items-center gap-3">
                                                <div class="flex items-center gap-2">
                                                    <div>{{ value.setting_leverage.label }}</div>
                                                </div>
                                            </div>
                                        </template>
                                        <template #option="{ option }">
                                            <div class="flex items-center gap-2">
                                                <div>{{ option.setting_leverage.label }}</div>
                                            </div>
                                        </template>
                                    </Select>
                                    <InputError :message="form.errors.leverage" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="additional_capital"
                                        :invalid="!!form.errors.additional_capital"
                                    >
                                        {{ $t('public.additional_capital') }}
                                    </InputLabel>
                                    <InputNumber
                                        v-model="form.additional_capital"
                                        inputId="additional_capital"
                                        fluid
                                        mode="currency"
                                        currency="USD"
                                        locale="en-US"
                                        placeholder="$0.00"
                                        :invalid="!!form.errors.additional_capital"
                                    />
                                    <InputError :message="form.errors.additional_capital" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="additional_investors"
                                        :invalid="!!form.errors.additional_investors"
                                    >
                                        {{ $t('public.additional_investors') }}
                                    </InputLabel>
                                    <InputNumber
                                        v-model="form.additional_investors"
                                        inputId="additional_investors"
                                        fluid
                                        placeholder="0"
                                        :invalid="!!form.errors.additional_investors"
                                    />
                                    <InputError :message="form.errors.additional_investors" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="w-full">
                    <template #content>
                        <div class="flex flex-col items-center gap-3 self-stretch">
                            <span class="self-stretch text-sm font-bold">{{ $t('public.advanced_information') }}</span>
                            <div class="w-full flex flex-col gap-3 md:gap-5">
                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="upload_image"
                                        :invalid="!!form.errors.upload_image"
                                    >
                                        {{ $t('public.upload_image') }}
                                    </InputLabel>
                                    <div
                                        :class="[
                                            'flex flex-col gap-3 items-center self-stretch px-5 py-8 rounded-md border-2 border-dashed transition-colors duration-150',
                                            {
                                                'border-blue-500 dark:text-blue-400 bg-blue-200/50 dark:bg-blue-800/10': isDraggingFront,
                                                'bg-surface-50 dark:bg-surface-950 border-surface-300 dark:border-surface-600': !isDraggingFront,
                                            }
                                        ]"
                                        @dragover.prevent="dragOverFront"
                                        @dragleave.prevent="dragLeaveFront"
                                        @drop.prevent="handleDropFront"
                                    >
                                        <div
                                            v-if="form.errors.strategy_image"
                                            class="rounded-lg w-12 h-12 shrink-0 grow-0 border border-red-300 dark:border-red-600 flex items-center justify-center text-red-500 dark:text-red-400"
                                        >
                                            <IconAlertSquareRounded size="28" stroke-width="1.5" />
                                        </div>
                                        <div
                                            v-else-if="frontFile"
                                            class="rounded-lg w-12 h-12 shrink-0 grow-0 border border-green-300 dark:border-green-600 flex items-center justify-center text-green-500 dark:text-green-400"
                                        >
                                            <IconFileCheck size="28" stroke-width="1.5" />
                                        </div>
                                        <div
                                            v-else
                                            :class="[
                                                'rounded-lg w-12 h-12 shrink-0 grow-0 border border-surface-300 dark:border-surface-600 flex items-center justify-center',
                                                {
                                                    'text-blue-500 dark:text-blue-400': isDraggingFront,
                                                    'text-surface-600 dark:text-surface-400': !isDraggingFront,
                                                }
                                            ]"
                                        >
                                            <IconPhotoPlus size="28" stroke-width="1.5" />
                                        </div>
                                        <div
                                            v-if="frontFile"
                                            class="flex flex-col items-center justify-center self-stretch"
                                        >
                                            <span class="text-xs text-surface-600 dark:text-surface-400">{{ frontFile.name }}</span>
                                            <label
                                                for="fileInputFront"
                                                class="text-xs text-blue-500 cursor-pointer underline select-none hover:text-blue-600"
                                            >
                                                {{ $t('public.replace_file') }}
                                            </label>
                                        </div>
                                        <div v-else class="flex flex-col items-center text-surface-500 dark:text-surface-400 text-xs text-center">
                                            {{ $t('public.drag_and_drop') }} <label for="fileInputFront" class="text-blue-500 cursor-pointer underline select-none hover:text-blue-600">{{ $t('public.choose_file') }}</label>
                                        </div>
                                        <input type="file" id="fileInputFront" class="hidden" @change="handleFrontFileSelect" accept="image/*" />
                                        <InputError :message="form.errors.strategy_image" class="text-center" />
                                        <div class="flex items-center gap-2 self-stretch justify-center w-full">
                                            <Tag severity="secondary">
                                                <span class="dark:text-surface-500">PNG</span>
                                            </Tag>
                                            <Tag severity="secondary">
                                                <span class="dark:text-surface-500">JPG</span>
                                            </Tag>
                                            <Tag severity="secondary">
                                                <span class="dark:text-surface-500">JPEG</span>
                                            </Tag>
                                        </div>
                                    </div>
                                    <div class="text-xs text-right w-full text-surface-500 dark:text-surface-400">
                                        {{ $t('public.max_size') }}: 8MB
                                    </div>
                                </div>

                                <div class="flex flex-col gap-1 items-start self-stretch">
                                    <InputLabel
                                        for="description"
                                        :value="$t('public.description')"
                                        :invalid="!!form.errors.description"
                                    />
                                    <TipTapEditor
                                        v-model="form.description"
                                    />
                                    <InputError :message="form.errors.description" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Right -->
            <div class="flex flex-col items-center gap-8 xl:col-span-2 self-stretch">
                <!-- Joining Conditions -->
                <Card class="w-full">
                    <template #content>
                        <div class="flex flex-col items-center gap-3 self-stretch">
                            <span class="self-stretch text-sm font-bold">{{ $t('public.joining_conditions') }}</span>
                            <div class="w-full grid grid-cols-1 gap-3 md:gap-5 md:grid-cols-2">
                                <div class="flex flex-col gap-1 items-start self-stretch md:col-span-2">
                                    <InputLabel
                                        for="minimum_investment"
                                        :value="$t('public.minimum_investment')"
                                        :invalid="!!form.errors.minimum_investment"
                                    />
                                    <InputNumber
                                        v-model="form.minimum_investment"
                                        inputId="minimum_investment"
                                        fluid
                                        mode="currency"
                                        currency="USD"
                                        locale="en-US"
                                        placeholder="$0.00"
                                        :invalid="!!form.errors.minimum_investment"
                                    />
                                    <InputError :message="form.errors.minimum_investment" />
                                </div>

                                <div class="flex flex-col gap-1 items-start self-stretch md:col-span-2">
                                    <InputLabel
                                        for="investment_period"
                                        :value="$t('public.investment_period')"
                                        :invalid="!!form.errors.investment_period"
                                    />
                                    <div class="flex items-center gap-3 w-full">
                                        <InputNumber
                                            v-model="form.investment_period"
                                            inputId="investment_period"
                                            fluid
                                            placeholder="10"
                                            class="w-full"
                                            :invalid="!!form.errors.investment_period"
                                        />
                                        <Select
                                            v-model="form.investment_period_type"
                                            id="category"
                                            :options="investmentPeriodTypes"
                                            class="w-full"
                                            :invalid="!!form.errors.investment_period_type"
                                        >
                                            <template #value="{ value }">
                                                <div v-if="value" class="flex items-center gap-3">
                                                    <div class="flex items-center gap-2">
                                                        <div>{{ $t(`public.${value}`) }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template #option="{ option }">
                                                <div class="flex items-center gap-2">
                                                    <div>{{ $t(`public.${option}`) }}</div>
                                                </div>
                                            </template>
                                        </Select>
                                    </div>
                                    <InputError :message="form.errors.investment_period" />
                                </div>

                                <div class="flex flex-col gap-1 items-start self-stretch">
                                    <InputLabel
                                        for="can_top_up"
                                        :value="$t('public.top_up_strategy')"
                                        :invalid="!!form.errors.can_top_up"
                                    />
                                    <SelectChipGroup
                                        v-model="form.can_top_up"
                                        :items="booleanOptions"
                                        value-key="value"
                                    >
                                        <template #option="{ item }">
                                            {{ $t(`public.${item.label}`) }}
                                        </template>
                                    </SelectChipGroup>
                                    <InputError :message="form.errors.can_top_up" />
                                </div>

                                <div class="flex flex-col gap-1 items-start self-stretch">
                                    <InputLabel
                                        for="can_terminate"
                                        :value="$t('public.terminate_strategy')"
                                        :invalid="!!form.errors.can_terminate"
                                    />
                                    <SelectChipGroup
                                        v-model="form.can_terminate"
                                        :items="booleanOptions"
                                        value-key="value"
                                    >
                                        <template #option="{ item }">
                                            {{ $t(`public.${item.label}`) }}
                                        </template>
                                    </SelectChipGroup>
                                    <InputError :message="form.errors.can_terminate" />
                                </div>

                                <div class="flex flex-col gap-1 items-start self-stretch md:col-span-2">
                                    <InputLabel
                                        for="visible_to"
                                        :value="$t('public.visible_to')"
                                        :invalid="!!form.errors.visible_to"
                                    />
                                    <MultiSelect
                                        v-model="selectedGroup"
                                        :options="groups"
                                        optionLabel="name"
                                        :placeholder="$t('public.group_placeholder')"
                                        :maxSelectedLabels="3"
                                        class="w-full"
                                        :invalid="!!form.errors.visible_to"
                                        :loading="loadingGroups"
                                    />
                                    <InputError :message="form.errors.visible_to" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Profit Information -->
                <Card class="w-full">
                    <template #content>
                        <div class="flex flex-col items-center gap-3 self-stretch">
                            <span class="self-stretch text-sm font-bold">{{ $t('public.profit_information') }}</span>
                            <div class="w-full grid grid-cols-1 gap-3 md:gap-5 md:grid-cols-2">
                                <div class="flex flex-col gap-1 items-start self-stretch md:col-span-2">
                                    <InputLabel
                                        for="sharing_profit"
                                        :value="$t('public.profit_distribution')"
                                        :invalid="!!form.errors.sharing_profit"
                                    />
                                    <div class="flex items-center gap-3 w-full">
                                        <InputNumber
                                            v-model="form.sharing_profit"
                                            inputId="sharing_profit"
                                            fluid
                                            placeholder="60%"
                                            class="w-full"
                                            suffix="%"
                                            :maxFractionDigits="2"
                                            :invalid="!!form.errors.sharing_profit"
                                        />
                                        <InputNumber
                                            v-model="form.market_profit"
                                            inputId="market_profit"
                                            fluid
                                            placeholder="20%"
                                            class="w-full"
                                            suffix="%"
                                            :maxFractionDigits="2"
                                            :invalid="!!form.errors.market_profit"
                                        />
                                        <InputNumber
                                            v-model="form.company_profit"
                                            inputId="company_profit"
                                            fluid
                                            placeholder="20%"
                                            class="w-full"
                                            suffix="%"
                                            :maxFractionDigits="2"
                                            :invalid="!!form.errors.company_profit"
                                        />
                                    </div>
                                    <InputError :message="form.errors.sharing_profit" />
                                </div>

                                <div class="flex flex-col gap-1 items-start self-stretch md:col-span-2">
                                    <InputLabel
                                        for="settlement_period"
                                        :value="$t('public.settlement_period')"
                                        :invalid="!!form.errors.settlement_period"
                                    />
                                    <div class="flex items-center gap-3 w-full">
                                        <InputNumber
                                            v-model="form.settlement_period"
                                            inputId="settlement_period"
                                            fluid
                                            placeholder="10"
                                            class="w-full"
                                            :invalid="!!form.errors.settlement_period"
                                        />
                                        <Select
                                            v-model="form.settlement_period_type"
                                            id="category"
                                            :options="settlementPeriodTypes"
                                            class="w-full"
                                            :invalid="!!form.errors.settlement_period_type"
                                        >
                                            <template #value="{ value }">
                                                <div v-if="value" class="flex items-center gap-3">
                                                    <div class="flex items-center gap-2">
                                                        <div>{{ $t(`public.${value}`) }}</div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template #option="{ option }">
                                                <div class="flex items-center gap-2">
                                                    <div>{{ $t(`public.${option}`) }}</div>
                                                </div>
                                            </template>
                                        </Select>
                                    </div>
                                    <InputError :message="form.errors.settlement_period" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="w-full">
                    <template #content>
                        <div class="flex flex-col items-center gap-8 self-stretch">
                            <div class="flex flex-col items-center gap-3 self-stretch">
                                <span class="self-stretch text-sm font-bold">{{ $t('public.management_fee_setting') }}</span>
                                <ManagementFeeSetting
                                    @get:management_fee="managementFee = $event"
                                />
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="w-full">
                    <template #content>
                        <div class="flex self-stretch justify-between items-center">
                            <Button
                                type="button"
                                severity="secondary"
                                :disabled="form.processing"
                                as="a"
                                :href="route('strategy.listing')"
                            >
                                <IconArrowNarrowLeft size="20" stroke-witdth="1.5" />
                                {{ $t('public.cancel') }}
                            </Button>

                            <Button
                                type="submit"
                                :disabled="form.processing"
                                :label="$t('public.create')"
                                @click="submitForm"
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
