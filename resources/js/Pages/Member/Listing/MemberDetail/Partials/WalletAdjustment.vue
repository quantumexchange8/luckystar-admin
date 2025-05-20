<script setup>
import {ref} from "vue";
import { CreditCardEdit01Icon } from '@/Components/Icons/outline';
import {
    Button,
    Dialog,
    InputNumber,
    RadioButton,
    Chip,
    Textarea,
    Avatar
} from "primevue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useForm } from '@inertiajs/vue3';
import { generalFormat } from "@/Composables/format.js";

const props = defineProps({
    wallet: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    id: '',
    action: '',
    amount: 0,
    remarks: '',
});

const { formatAmount } = generalFormat();

const visible = ref(false);
const actionOptions = ref([]);

const openDialog = () => {
    visible.value = true
    setActionOptions();
};

const closeDialog = () => {
    visible.value = false;
    form.reset();
};

const setActionOptions = () => {
    const type = props.wallet?.type;

    if (type === 'cash_wallet') {
        actionOptions.value = [
            { value: 'cash_in', label: 'cash_in' },
            { value: 'cash_out', label: 'cash_out' },
        ];
    } else if (type === 'bonus_wallet') {
        actionOptions.value = [
            { value: 'bonus_in', label: 'bonus_in' },
            { value: 'bonus_out', label: 'bonus_out' },
        ];
    } else {
        actionOptions.value = [];
    }

    form.action = actionOptions.value.length ? actionOptions.value[0].value : '';
};

const submit = () => {
    if (form.remarks === '') {
        form.remarks = 'Wallet balance adjustment.'
    }

    if (props.wallet) {
        form.id = props.wallet.id;
    }
    form.post(route('member.walletAdjustment'), {
        onSuccess: () => {
            closeDialog();
        },
    });
};

const chips = ref([
    { label: 'Fix wallet balance' },
    { label: '修改錢包餘額' },
]);

const handleChipClick = (label) => {
    form.remarks = label;
};
</script>

<template>
    <Button
        type="button"
        severity="secondary"
        icon="CreditCardEdit01Icon"
        size="small"
        rounded
        @click="openDialog"
    >
        <CreditCardEdit01Icon class="w-4 h-4" />
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.wallet_adjustment')"
        class="dialog-xs md:dialog-sm"
    >
        <form>
            <div class="flex flex-col gap-5">
                <div 
                    class="flex flex-col justify-center items-center px-8 py-4 gap-2 self-stretch"
                    :class="[
                        wallet.type === 'cash_wallet' ? 'bg-primary-500' : 'bg-blue-500'
                    ]"
                >
                    <div class="text-surface-100 text-center text-xs font-medium">{{ $t('public.' + wallet.type + '_balance') }}</div>
                    <div class="text-white text-center text-xl font-semibold">{{ formatAmount(wallet.balance) }}</div>
                </div>

                <!-- action -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="action" :value="$t('public.action')" />
                    <div class="flex items-center gap-5">
                        <div
                            v-for="option in actionOptions"
                            :key="option.value"
                            class="flex items-center gap-2 text-sm text-surface-950 dark:text-white"
                        >
                            <div class="flex p-2 justify-center items-center">
                                <RadioButton
                                    v-model="form.action"
                                    :inputId="option.value"
                                    :name="$t(`public.${option.value}`)"
                                    :value="option.value"
                                    class="w-4 h-4"
                                />
                            </div>
                            <label :for="option.value">{{ $t(`public.${option.value}`) }}</label>
                        </div>
                    </div>
                </div>

                <!-- amount -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="amount" :value="$t('public.amount')" />
                    <InputNumber
                        v-model="form.amount"
                        inputId="currency-us"
                        prefix="$ "
                        class="w-full"
                        inputClass="py-3 px-4"
                        :min="0"
                        :step="100"
                        :minFractionDigits="2"
                        fluid
                        autofocus
                        :invalid="!!form.errors.amount"
                    />
                    <InputError :message="form.errors.amount" />
                </div>

                <!-- remarks -->
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <InputLabel for="remarks">{{ $t('public.remarks_optional') }}</InputLabel>
                    <div class="flex items-center content-center gap-2 self-stretch flex-wrap">
                        <div v-for="(chip, index) in chips" :key="index">
                            <Chip
                                :label="chip.label"
                                class="text-surface-950"
                                :class="{
                                    'border-primary-300 bg-primary-50 text-primary-500 hover:bg-primary-50': form.remarks === chip.label,
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
                        :placeholder="$t('public.wallet_balance_adjustment')"
                        :invalid="!!form.errors.remarks"
                        rows="5"
                        cols="30"
                    />
                </div>
            </div>
            <div class="flex justify-end items-center pt-10 md:pt-7 gap-3 md:gap-4 self-stretch">
                <Button
                    type="button"
                    severity="secondary"
                    class="flex flex-1 md:flex-none md:w-[120px]"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click.prevent="closeDialog"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    class="flex flex-1 md:flex-none md:w-[120px]"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
