<script setup>
import {Button, Dialog, Tag, Textarea} from "primevue";
import {ref} from "vue";
import {generalFormat} from "@/Composables/format.js";
import dayjs from "dayjs";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    investment: Object,
})

const visible = ref(false);
const {formatAmount} = generalFormat();
const approvalAction = ref('');

const openDialog = () => {
    visible.value = true;
}


const handleApproval = (action) => {
    approvalAction.value = action;
}

const form = useForm({
    investment_id: props.investment.id,
    action: '',
    remarks: '',
});

const submitForm = () => {
    form.action = approvalAction.value;
    form.post(route('pending.pendingInvestmentApproval'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        }
    })
}

const closeDialog = () => {
    visible.value = false;
    approvalAction.value = '';
}
</script>

<template>
    <Button
        type="button"
        severity="secondary"
        size="small"
        :label="$t('public.action')"
        @click="openDialog"
    />

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.investment_request')"
        class="dialog-xs md:dialog-md"
    >
        <div
            class="flex flex-col-reverse md:flex-row md:items-center gap-3 self-stretch w-full pb-4 border-b dark:border-surface-600"
        >
            <div class="flex flex-col items-start w-full">
                <span class="text-sm font-medium">{{ investment.user.full_name }}</span>
                <span class="text-surface-500 text-xs">{{ investment.user.email }}</span>
            </div>
            <div class="min-w-[180px] font-semibold text-xl md:text-right">
                {{ formatAmount(investment.subscription_amount) }}
            </div>
        </div>

        <div class="flex flex-col items-center gap-4 self-stretch">
            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.requested_date') }}
                    </div>
                    <div class="text-surface-950 dark:text-white text-sm font-medium">
                        {{ dayjs(investment.created_at).format('YYYY/MM/DD HH:mm:ss') }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.account') }}
                    </div>
                    <div class="text-surface-950 dark:text-white text-sm font-medium">
                        {{ investment.meta_login }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.strategy') }}
                    </div>
                    <div class="text-surface-950 dark:text-white text-sm font-medium">
                        {{ investment.trading_master.master_name }} - {{ investment.master_meta_login }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.account_type') }}
                    </div>
                    <Tag
                        severity="secondary"
                        class="text-xs"
                        :value="investment.trading_master.account_type.name"
                    />
                </div>
                <div v-if="approvalAction" class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.action') }}
                    </div>
                    <Tag
                        :severity="approvalAction === 'approve' ? 'success' : 'danger'"
                        class="text-xs"
                        :value="$t(`public.${approvalAction}`)"
                    />
                </div>
            </div>

            <template v-if="!approvalAction">
                <div class="flex justify-end items-center pt-5 gap-4 self-stretch sm:pt-7">
                    <Button
                        type="button"
                        severity="danger"
                        class="w-full md:w-[120px]"
                        @click="handleApproval('reject')"
                    >
                        {{ $t('public.reject') }}
                    </Button>
                    <Button
                        type="button"
                        severity="success"
                        class="w-full md:w-[120px]"
                        @click="handleApproval('approve')"
                    >
                        {{ $t('public.approve') }}
                    </Button>
                </div>
            </template>

            <template v-else>
                <div class="flex flex-col items-start gap-1 self-stretch pt-3 border-t dark:border-surface-600">
                    <InputLabel v-if="approvalAction === 'approve'" for="remarks">{{ $t('public.remarks') }}</InputLabel>
                    <InputLabel
                        v-else
                        for="remarks"
                        :value="$t('public.remarks')"
                    />
                    <Textarea
                        id="remarks"
                        type="text"
                        class="flex flex-1 self-stretch"
                        v-model="form.remarks"
                        :placeholder="$t('public.enter_remarks')"
                        :invalid="!!form.errors.remarks"
                        rows="5"
                        cols="30"
                    />
                    <InputError :message="form.errors.remarks" />
                </div>

                <div class="flex justify-end items-center pt-5 gap-4 self-stretch sm:pt-7">
                    <Button
                        type="button"
                        severity="secondary"
                        text
                        class="w-full md:w-fit"
                        :label="$t('public.cancel')"
                        @click="closeDialog"
                    />
                    <Button
                        type="submit"
                        class="w-full md:w-fit"
                        :label="$t('public.confirm')"
                        @click="submitForm"
                    />
                </div>
            </template>
        </div>
    </Dialog>
</template>
