<script setup>
import { ref, watch } from 'vue'
import { Card, Button, Dialog, Image, Tag, Avatar, Chip, Textarea } from 'primevue'
import InputLabel from '@/Components/InputLabel.vue'
import { generalFormat } from "@/Composables/format.js";
import { useForm, usePage } from "@inertiajs/vue3";
import axios from 'axios';

const props = defineProps({
    userDetail: Object,
    kycIdentity: Object,
    kycResidency: Object,
    isLoading: Boolean,
})
const visible = ref(false);
const { formatSeverity } = generalFormat();

const approvalAction = ref('');
const kycIdentity = ref(null);
const kycResidency = ref(null);
const selectedId = ref(null);

watch(() => [props.kycIdentity, props.kycResidency], ([newKycIdentity, newKycResidency]) => {
    kycIdentity.value = newKycIdentity;
    kycResidency.value = newKycResidency;
  },{ immediate: true }
);

const openDialog = (action, kycId) => {
    form.reset();
    approvalAction.value = '';
    selectedId.value = kycId;
    if (action) {
        visible.value = true;
        approvalAction.value = action;
    }
}

const closeDialog = () => {
    visible.value = false;
    approvalAction.value = '';
    form.reset();
}

const chips = ref({
    approve: [
        { label: 'KYC approved' },
        { label: 'KYC已獲批准' },
    ],
    reject: [
        { label: 'KYC rejected' },
        { label: 'KYC已被拒絕' },
    ]
});

const handleChipClick = (label) => {
    form.remarks = label;
};

const form = useForm({
    id: '',
    action: '',
    remarks: '',
})

const submit = (kycId) => {
    if (form.remarks === '') {
        form.remarks = approvalAction.value === 'approve' ? 'KYC approved ' : 'KYC rejected. Please submit again.'
    }

    form.id = kycId;
    form.action = approvalAction.value;

    form.post(route('member.updateKycStatus'), {
        onSuccess: () => {
            closeDialog();
        },
    });
};

</script>

<template>
    <Card class="w-full h-full">
        <template #title>
            <div class="flex items-center justify-between">
                <div class="flex flex-wrap gap-x-2 gap-y-1 items-center">
                    <span>{{ $t('public.proof_of_identity') }}</span>
                    <Tag
                        v-if="kycIdentity"
                        :value="$t(`public.${kycIdentity?.kyc_status}`)"
                        :severity="formatSeverity(kycIdentity?.kyc_status)"
                    />
                </div>
            </div>
        </template>

        <template #content>
            <div class="flex flex-col gap-2">
                <!-- Photo ID -->
                <div v-if="props.isLoading || kycIdentity?.proof_type === 'photo_id'" class="grid grid-cols-2 gap-2">
                    <div class="w-full flex flex-col items-start gap-1">
                        <InputLabel for="front_identity">{{ $t('public.front_identity') }}</InputLabel>
                        <div class="w-full flex flex-col gap-3 items-center p-3 rounded-md border-2 border-dashed">
                            <Avatar v-if="props.isLoading" class="w-full h-[100px] animate-pulse" />
                            <template v-else="kycIdentity?.front_image">
                                <Image
                                    :src="kycIdentity?.front_image"
                                    alt="front_identity"
                                    preview
                                    imageClass="w-full h-[100px] object-contain"
                                />
                            </template>
                        </div>
                    </div>
                    <div class="w-full flex flex-col items-start gap-1">
                        <InputLabel for="back_identity">{{ $t('public.back_identity') }}</InputLabel>
                        <div class="w-full flex flex-col gap-3 items-center p-3 rounded-md border-2 border-dashed">
                            <Avatar v-if="props.isLoading" class="w-full h-[100px] animate-pulse" />
                            <template v-else="kycIdentity?.back_image">
                                <Image
                                    :src="kycIdentity?.back_image"
                                    alt="back_identity"
                                    preview
                                    imageClass="w-full h-[100px] object-contain"
                                />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Passport -->
                <div v-else-if="props.isLoading || kycIdentity?.proof_type === 'passport'" class="grid grid-cols-1 gap-2">
                    <div class="w-full flex flex-col items-start gap-1">
                        <InputLabel for="passport">{{ $t('public.passport') }}</InputLabel>
                        <div class="w-full flex flex-col gap-3 items-center p-3 rounded-md border-2 border-dashed">
                            <Avatar v-if="props.isLoading" class="w-full h-[100px] animate-pulse" />
                            <template v-else="kycIdentity?.passport_image">
                                <Image
                                    :src="kycIdentity?.passport_image"
                                    alt="passport"
                                    preview
                                    imageClass="w-full h-[100px] object-contain"
                                />
                            </template>
                        </div>
                    </div>
                </div>

                <div v-else="!kycIdentity" class="grid grid-cols-1 gap-2">
                    <div class="w-full flex flex-col items-start gap-1">
                        {{ $t('public.kyc_documents_pending') }}
                    </div>
                </div>

                <div class="flex items-center justify-end gap-5">
                    <Button
                        v-if="kycIdentity"
                        type="button"
                        severity="danger"
                        class="w-[120px] disabled:cursor-not-allowed"
                        :disabled="props.isLoading || !kycIdentity?.media || kycIdentity?.kyc_status !== 'pending'"
                        @click="openDialog('reject', kycIdentity.id)"
                        >
                        {{ $t('public.reject') }}
                    </Button>
                    <Button
                        v-if="kycIdentity"
                        type="button"
                        severity="primary"
                        class="w-[120px] disabled:cursor-not-allowed"
                        :disabled="props.isLoading || !kycIdentity?.media || kycIdentity?.kyc_status !== 'pending'"
                        @click="openDialog('approve', kycIdentity.id)"
                        >
                        {{ $t('public.approve') }}
                    </Button>
                </div>
            </div>
        </template>
    </Card>

    <Card class="w-full h-full">
        <template #title>
                <div class="flex items-center justify-between">
                    <div class="flex flex-wrap gap-x-2 gap-y-1 items-center">
                        <span>{{ $t('public.proof_of_residency') }}</span>
                        <Tag
                            v-if="kycResidency"
                            :value="$t(`public.${kycResidency?.kyc_status}`)"
                            :severity="formatSeverity(kycResidency?.kyc_status)"
                        />
                    </div>
                </div>
        </template>
        
        <template #content>
            <div class="flex flex-col gap-2">
                <div class="grid grid-cols-1 gap-2">
                    <template v-if="props.isLoading || kycResidency">
                        <InputLabel for="residency_image">
                            <template v-if="kycResidency?.proof_type === 'utility_bill'">{{ $t('public.utility_bill') }}</template>
                            <template v-else-if="kycResidency?.proof_type === 'bank_statement'">{{ $t('public.bank_statement') }}</template>
                            <template v-else-if="kycResidency?.proof_type === 'residence_certificate'">{{ $t('public.residence_certificate') }}</template>
                            <template v-else>{{ $t('public.residency_proof') }}</template>
                        </InputLabel>
                        <Avatar v-if="props.isLoading" class="w-full h-[50px] animate-pulse" />
                        <template v-else-if="kycResidency?.media?.length">
                            <a
                                v-for="file in kycResidency?.media"
                                :key="file.id"
                                :href="`/member/media/download/${file.id}`"
                                class="flex items-center px-4 py-3 gap-3 self-stretch select-none cursor-pointer rounded border border-surface-200 dark:border-surface-500 hover:bg-surface-200 dark:hover:bg-surface-500"
                            >
                                <span class="truncate text-surface-950 dark:text-white font-medium w-full">
                                    {{ file.file_name }}
                                </span>
                            </a>
                        </template>
                    </template>

                    <div v-else="!kycResidency" class="grid grid-cols-1 gap-2">
                        <div class="w-full flex flex-col items-start gap-1">
                            {{ $t('public.kyc_documents_pending') }}
                        </div>
                    </div>

                </div>

                <div class="flex items-center justify-end gap-5">
                    <Button
                        v-if="kycResidency"
                        type="button"
                        severity="danger"
                        class="w-[120px] disabled:cursor-not-allowed"
                        :disabled="props.isLoading || !kycResidency?.media || kycResidency?.kyc_status !== 'pending'"
                        @click="openDialog('reject', kycResidency.id)"
                    >
                        {{ $t('public.reject') }}
                    </Button>
                    <Button
                        v-if="kycResidency"
                        type="button"
                        severity="primary"
                        class="w-[120px] disabled:cursor-not-allowed"
                        :disabled="props.isLoading || !kycResidency?.media || kycResidency?.kyc_status !== 'pending'"
                        @click="openDialog('approve', kycResidency.id)"
                    >
                        {{ $t('public.approve') }}
                    </Button>
                </div>
            </div>
        </template>
    </Card>

    <!-- Dialog -->
    <Dialog 
        v-model:visible="visible"
        modal
        class="dialog-xs md:dialog-lg"
    >
        <template #header>
            <div class="flex flex-col gap-1">
                <span class="font-semibold text-lg">{{ approvalAction === 'reject' ? $t('public.reject_kyc_request') : $t('public.approve_kyc_request') }}</span>
                <div class="text-surface-500 dark:text-surface-200 text-sm">
                    {{ $t('public.kyc_request_caption_1') }}
                    <span class="font-semibold lowercase" :class="[approvalAction === 'approve' ? 'text-success-500' : 'text-error-500']">
                        {{ $t(`public.${approvalAction}`) }}
                    </span>
                    {{ $t('public.kyc_request_caption_2') }}
                </div>
            </div>
        </template>
        
        <div class="flex flex-col gap-5">
            <div class="flex flex-col items-start gap-3 h-40 self-stretch">
                <InputLabel for="remarks">{{ $t('public.remarks') }}</InputLabel>
                <div class="flex items-center gap-2 self-stretch overflow-x-auto">
                    <div v-for="(chip, index) in chips[approvalAction]" :key="index">
                        <Chip
                            :label="chip.label"
                            class="w-full text-gray-950 whitespace-nowrap overflow-hidden"
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
                    :placeholder="approvalAction === 'approve' ? 'KYC approved' : 'KYC rejected. Please submit again.'"
                    :invalid="!!form.errors.remarks"
                    rows="5"
                    cols="30"
                />
            </div>

            <div class="flex items-center justify-end gap-5">
                <Button 
                    type="button"
                    severity="secondary"
                    class="w-[120px]"
                    @click="closeDialog()"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button 
                    type="button"
                    severity="primary"
                    class="w-[120px]"
                    @click="submit(selectedId)"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </Dialog>
</template>
