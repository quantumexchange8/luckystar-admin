<script setup>
import {Button, Dialog, InputText, InputNumber, MultiSelect, Select} from "primevue";
import {IconArrowNarrowLeft, IconArrowNarrowRight, IconPlus} from "@tabler/icons-vue";
import {ref, watch} from "vue";
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import SelectChipGroup from "@/Components/SelectChipGroup.vue";
import ManagementFeeSetting from "@/Pages/Strategy/ManagementFeeSetting.vue";

const props = defineProps({
    accountTypes: Array,
});

const visible = ref(false);

const openDialog = () => {
    visible.value = true;
    getGroups();
}

const form = useForm({
    step: 1,
    strategy_name: '',
    trader_name: '',
    category: '',
    account_type_id: '',
    estimated_lot: '',
    estimated_monthly_return: '',
    max_drawdown: '',
    cut_loss: '',
    additional_capital: null,
    additional_investors: '',
    strategy_image: '',
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
const selectedAccountType = ref();

const leverages = ref([]);
const loadingLeverages = ref(false);
const selectedLeverage = ref();

watch(selectedAccountType, () => {
    getLeverages();
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

const estimated_lot_min = ref();
const estimated_lot_max = ref();

const estimated_monthly_return_min = ref();
const estimated_monthly_return_max = ref();

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

const previousStep = () => {
    if (form.step > 1) {
        form.step -= 1;
    }
};

const handleContinue = () => {
    form.category = selectedCategory.value;
    form.account_type_id = selectedAccountType.value;
    form.estimated_lot = estimated_lot_min.value + '-' + estimated_lot_max.value;
    form.estimated_monthly_return = estimated_monthly_return_min.value + '-' + estimated_monthly_return_max.value;
    form.visible_to = selectedGroup.value;
    form.leverage = selectedLeverage.value;

    form.management_fee = managementFee.value;
    form.post(route('strategy.addStrategy'), {
        onSuccess: () => {
            if (form.step === 3) {
                closeDialog();
                form.reset();
                form.step = 1;
            } else {
                form.step += 1;
            }
        }
    });
};

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <div class="flex justify-end items-center w-full">
        <Button
            type="button"
            class="flex gap-3 items-center"
            @click="openDialog"
        >
            <IconPlus size="20" stroke-width="1.5" />
            {{ $t('public.new_strategy') }}
        </Button>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.new_strategy')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col items-center gap-8 self-stretch">
            <!-- Stepper -->
            <div class="flex justify-center items-center gap-2 self-stretch">
                <div
                    v-for="step in [1, 2, 3]"
                    :key="step"
                    class="min-h-1 flex-grow rounded-full relative overflow-hidden"
                    :class="{
                        'bg-primary': form.step >= step,
                        'bg-surface-200 dark:bg-surface-600': form.step < step
                    }"
                >
                    <!-- Highlight the current step -->
                    <template v-if="form.step === step">
                        <div class="absolute inset-0 w-1/2 bg-primary"></div>
                        <div class="absolute inset-0 left-1/2 w-1/2 bg-surface-200 dark:bg-surface-600"></div>
                    </template>
                </div>
            </div>

            <!-- Step 1 -->
            <div v-if="form.step === 1" class="flex flex-col items-center gap-8 self-stretch">
                <!-- Basic Information -->
                <div class="flex flex-col items-center gap-3 self-stretch">
                    <span class="self-stretch text-sm font-bold">{{ $t('public.basic_information') }}</span>
                    <div class="w-full grid grid-cols-1 gap-3 md:gap-5 md:grid-cols-2">
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="strategy_name"
                                :value="$t('public.strategy_name')"
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
                                for="trader_name"
                                :value="$t('public.trader_name')"
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
                                for="category"
                                :value="$t('public.category')"
                            />
                            <SelectChipGroup
                                :items="categories"
                                v-model="selectedCategory"
                            />
                            <InputError :message="form.errors.category" />
                        </div>

                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="account_type"
                                :value="$t('public.account_type')"
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
                            />
                            <div class="flex items-center gap-2">
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
                            />
                            <div class="flex items-center gap-2">
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
                                for="max_drawdown"
                                :value="$t('public.max_drawdown')"
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
                                for="additional_capital"
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

                <div class="flex flex-col items-center gap-3 self-stretch">
                    <span class="self-stretch text-sm font-bold">{{ $t('public.advanced_information') }}</span>
                    <div class="w-full grid grid-cols-1 gap-3 md:gap-5 md:grid-cols-2">
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="upload_image"
                            >
                                {{ $t('public.upload_image') }}
                            </InputLabel>
                        </div>

                        <div class="flex flex-col gap-1 items-start self-stretch">
                            <InputLabel
                                for="visible_to"
                                :value="$t('public.visible_to')"
                                :invalid="!!form.errors.visible_to"
                            />
                            <MultiSelect
                                v-model="selectedGroup"
                                :options="groups"
                                optionLabel="name"
                                :placeholder="$t('public.select_group_placeholder')"
                                :maxSelectedLabels="3"
                                class="w-full"
                                :invalid="!!form.errors.visible_to"
                                :loading="loadingGroups"
                            />
                            <InputError :message="form.errors.visible_to" />
                        </div>

                        <div class="flex flex-col gap-1 items-start self-stretch">
                            <InputLabel
                                for="leverage"
                                :value="$t('public.leverage')"
                                :invalid="!!form.errors.leverage"
                            />
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
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div v-if="form.step === 2" class="flex flex-col items-center gap-8 self-stretch">
                <!-- Joining Conditions -->
                <div class="flex flex-col items-center gap-3 self-stretch">
                    <span class="self-stretch text-sm font-bold">{{ $t('public.joining_conditions') }}</span>
                    <div class="w-full grid grid-cols-1 gap-3 md:gap-5 md:grid-cols-2">
                        <div class="flex flex-col gap-1 items-start self-stretch">
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

                        <div class="flex flex-col gap-1 items-start self-stretch">
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
                                    class="w-full md:w-40"
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
                    </div>
                </div>

                <!-- Profit Information -->
                <div class="flex flex-col items-center gap-3 self-stretch">
                    <span class="self-stretch text-sm font-bold">{{ $t('public.profit_information') }}</span>
                    <div class="w-full grid grid-cols-1 gap-3 md:gap-5 md:grid-cols-2">
                        <div class="flex flex-col gap-1 items-start self-stretch">
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

                        <div class="flex flex-col gap-1 items-start self-stretch">
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
                                    class="w-full md:w-40"
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
            </div>

            <!-- Step 3 -->
            <div v-if="form.step === 3" class="flex flex-col items-center gap-8 self-stretch">
                <div class="flex flex-col items-center gap-3 self-stretch">
                    <span class="self-stretch text-sm font-bold">{{ $t('public.management_fee_setting') }}</span>
                    <ManagementFeeSetting
                        @get:management_fee="managementFee = $event"
                    />
                </div>
            </div>

            <div class="flex self-stretch"
                 :class="{
                    'flex-col items-end': form.step === 1,
                    'justify-between items-center': form.step !== 1,
                }"
            >
                <Button
                    v-if="form.step !== 1"
                    type="button"
                    severity="secondary"
                    :disabled="form.processing"
                    @click="previousStep"
                >
                    <IconArrowNarrowLeft size="20" stroke-witdth="1.5" />
                    {{ $t('public.back') }}
                </Button>

                <Button
                    type="button"
                    :disabled="form.processing"
                    @click="handleContinue"
                >
                    {{ form.step === 3 ? $t('public.create') : $t('public.next') }}
                    <IconArrowNarrowRight
                        v-if="form.step !== 3"
                        size="20"
                        stroke-witdth="1.5"
                    />
                </Button>
            </div>
        </div>
    </Dialog>
</template>
