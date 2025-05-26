<script setup>
import { computed, onMounted, ref } from "vue";
import { generalFormat } from "@/Composables/format.js";
import {
    RadioButton,
    InputNumber,
    Textarea,
    Chip,
    Button,
    Skeleton,
} from "primevue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { useForm } from "@inertiajs/vue3";
import { IconAlertCircle } from "@tabler/icons-vue"

const props = defineProps({
    account: Object,
    dialogType: String,
})

const isLoading = ref(false);
const data = ref([]);
const { formatAmount } = generalFormat();
const emit = defineEmits(['update:visible']);

const getTradingAccountData = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('member.getTradingAccountData', {
            meta_login: props.account.meta_login,
            type: props.account.account_type.type,
        }));
        data.value = response.data.data;
    } catch (error) {
        console.error('Error update account:', error);
    } finally {
        isLoading.value = false;
    }
}

onMounted(getTradingAccountData);

const form = useForm({
    meta_login: props.account.meta_login,
    action: '',
    amount: null,
    remarks: '',
    type: props.dialogType,
})

const radioOptions = computed(() => {
    const options = {
        account_balance: [
            { label: 'balance_in', value: 'balance_in' },
            { label: 'balance_out', value: 'balance_out' }
        ],
        account_credit: [
            { label: 'credit_in', value: 'credit_in' },
            { label: 'credit_out', value: 'credit_out' }
        ]
    };

    // Return the correct options based on dialogType
    return options[props.dialogType] || [];
});

// Computed Property for Chips
const chips = computed(() => {
    const chipsMapping = {
        account_balance: [
            { label: 'Fix account balance' },
            { label: '修改账户余额' },
        ],
        account_credit: [
            { label: 'Fix account credit' },
            { label: '修改信用余额' },
        ],
    };

    return chipsMapping[props.dialogType] || [];
});

// Computed Property for Placeholder
const placeholderText = computed(() => {
    const placeholderMapping = {
        account_balance: 'Account balance adjustment',
        account_credit: 'Account credit adjustment',
    };

    return placeholderMapping[props.dialogType] || 'Enter remarks here';
});

const handleChipClick = (label) => {
    form.remarks = label;
};

const closeDialog = () => {
    emit('update:visible', false);
}

const submitForm = () => {
    if (form.remarks === '') {
        form.remarks = placeholderText.value;
    }

    form.post(route('member.accountAdjustment'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        },
    });
}
</script>

<template>
    <form>
        <div class="flex flex-col gap-5 items-center self-stretch">
            <div class="flex flex-col justify-center items-center px-8 py-4 gap-2 self-stretch bg-surface-200 dark:bg-surface-800">
                <div class="text-surface-500 text-center text-xs font-medium">
                    #{{ account.meta_login }} - {{ dialogType === 'account_balance' ? $t('public.available_account_balance') : $t('public.available_account_credit') }}
                </div>
                <div v-if="isLoading" class="text-center text-xl font-semibold">
                    {{ $t('public.loading') }}..
                </div>
                <div v-else class="text-center text-xl font-semibold">
                    <span v-if="dialogType === 'account_balance'">{{ formatAmount(data.balance) }}</span>
                    <span v-else-if="dialogType === 'account_credit'">{{ formatAmount(data.credit) }}</span>
                </div>
            </div>

            <!-- action -->
            <div class="flex flex-col items-start gap-1 self-stretch">
                <InputLabel
                    for="action"
                    :value="$t('public.action')"
                    :invalid="!!form.errors.action"
                />
                <div class="flex items-center gap-5">
                    <div
                        v-for="action in radioOptions"
                        class="flex items-center gap-2 text-sm"
                    >
                        <RadioButton
                            v-model="form.action"
                            :inputId="action.value"
                            :name="action.value"
                            :value="action.value"
                        />
                        <label :for="action.value">{{ $t(`public.${action.label}`) }}</label>
                    </div>
                </div>
                <InputError :message="form.errors.action" />
            </div>

            <!-- amount -->
            <div class="flex flex-col items-start gap-1 self-stretch">
                <InputLabel
                    for="amount"
                    :value="$t('public.amount')"
                    :invalid="!!form.errors.amount"
                />
                <InputNumber
                    v-model="form.amount"
                    inputId="amount"
                    mode="currency"
                    currency="USD"
                    locale="en-US"
                    fluid
                    placeholder="$0.00"
                    :invalid="!!form.errors.amount"
                />
                <InputError :message="form.errors.amount" />
            </div>

            <!-- remarks -->
            <div class="flex flex-col items-start gap-3 self-stretch">
                <InputLabel for="remarks">
                    {{ $t('public.remarks_optional')}}
                </InputLabel>
                <div class="flex items-center content-center gap-2 self-stretch flex-wrap">
                    <div v-for="(chip, index) in chips" :key="index">
                        <Chip
                            :label="chip.label"
                            class="text-xs transition-all duration-200 border"
                            :class="{
                                'border-primary-300 bg-primary-50 text-primary-600 dark:bg-primary-950 dark:border-primary-900': form.remarks === chip.label,
                                'border-transparent hover:bg-surface-200 dark:hover:bg-surface-700': form.remarks !== chip.label,
                            }"
                            @click="handleChipClick(chip.label)"
                        />
                    </div>
                </div>
                <Textarea
                    id="remarks"
                    type="text"
                    class="flex flex-1 self-stretch"
                    v-model="form.remarks"
                    :placeholder="placeholderText"
                    :invalid="!!form.errors.remarks"
                    rows="5"
                    cols="30"
                />
                <InputError :message="form.errors.remarks" />
            </div>
        </div>

        <div class="flex justify-end items-center pt-10 md:pt-7 gap-3 md:gap-4 self-stretch">
            <Button
                severity="secondary"
                class="flex flex-1 md:flex-none md:w-[120px]"
                :disabled="form.processing"
                @click.prevent="closeDialog"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                class="flex flex-1 md:flex-none md:w-[120px]"
                :disabled="form.processing || isLoading"
                @click.prevent="submitForm"
            >
                {{ $t('public.confirm') }}
            </Button>
        </div>
    </form>
</template>
