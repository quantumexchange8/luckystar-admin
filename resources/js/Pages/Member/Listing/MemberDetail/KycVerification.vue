<script setup>
import { ref, watch } from 'vue'
import { Card, Button, Dialog, Image, Tag, Avatar, Chip, Textarea, ProgressSpinner } from 'primevue'
import InputLabel from '@/Components/InputLabel.vue'
import { generalFormat } from "@/Composables/format.js";
import { useForm } from "@inertiajs/vue3";
import {IconCloudDownload, IconImageInPicture} from '@tabler/icons-vue';

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
                    <Tag
                        v-else-if="isLoading"
                        severity="secondary"
                        :value="$t('public.loading')"
                    />
                    <Tag
                        v-else
                        severity="danger"
                        :value="$t('public.unverified')"
                    />
                </div>
            </div>
        </template>

        <template #content>
            <div class="flex flex-col gap-5">
                <!-- Photo ID -->
                <div v-if="props.isLoading || kycIdentity?.proof_type === 'photo_id'" class="grid grid-cols-2 gap-2">
                    <div class="w-full flex flex-col items-start gap-1">
                        <InputLabel for="front_identity">{{ $t('public.front_identity') }}</InputLabel>
                        <div
                            class="flex flex-col justify-center gap-3 items-center self-stretch p-3 rounded-md border-2 border-dashed transition-colors duration-150 bg-surface-50 dark:bg-surface-950 border-surface-300 dark:border-surface-600"
                        >
                            <div v-if="isLoading" class="w-full flex items-center justify-center h-[100px]">
                                <ProgressSpinner />
                            </div>
                            <template v-else>
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
                        <div
                            class="flex flex-col justify-center gap-3 items-center self-stretch p-3 rounded-md border-2 border-dashed transition-colors duration-150 bg-surface-50 dark:bg-surface-950 border-surface-300 dark:border-surface-600"
                        >
                            <div v-if="isLoading" class="w-full flex items-center justify-center h-[100px]">
                                <ProgressSpinner />
                            </div>
                            <template v-else>
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
                <div v-else-if="isLoading || kycIdentity?.proof_type === 'passport'" class="grid grid-cols-1 gap-2">
                    <div class="w-full flex flex-col items-start gap-1">
                        <InputLabel for="passport">{{ $t('public.passport') }}</InputLabel>
                        <div class="w-full flex flex-col gap-3 items-center p-3 rounded-md border-2 border-dashed">
                            <Avatar v-if="isLoading" class="w-full h-[100px] animate-pulse" />
                            <template v-else>
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

                <div v-else class="w-full flex flex-col items-start gap-1">
                    <InputLabel>{{ $t('public.file') }}</InputLabel>
                    <div
                        class="flex flex-col justify-center gap-3 items-center self-stretch p-3 rounded-md border-2 border-dashed transition-colors duration-150 bg-surface-50 dark:bg-surface-950 border-surface-300 dark:border-surface-600"
                    >
                        <div class="w-full flex flex-col items-center gap-3 justify-center h-[100px] text-sm">
                            <div
                                class="rounded-lg w-8 h-8 shrink-0 grow-0 border border-surface-300 dark:border-surface-600 flex items-center justify-center text-surface-500 dark:text-surface-400"
                            >
                                <IconImageInPicture size="20" stroke-width="1.5" />
                            </div>
                            {{ $t('public.kyc_documents_pending') }}
                        </div>
                    </div>
                </div>

                <div v-if="kycIdentity" class="flex items-center justify-end gap-3">
                    <Button
                        type="button"
                        size="small"
                        severity="danger"
                        class="w-[120px] disabled:cursor-not-allowed"
                        :disabled="isLoading || !kycIdentity?.media || kycIdentity?.kyc_status !== 'pending'"
                        @click="openDialog('reject', kycIdentity.id)"
                        :label="$t('public.reject')"
                    />
                    <Button
                        type="button"
                        size="small"
                        severity="success"
                        class="w-[120px] disabled:cursor-not-allowed"
                        :disabled="isLoading || !kycIdentity?.media || kycIdentity?.kyc_status !== 'pending'"
                        @click="openDialog('approve', kycIdentity.id)"
                        :label="$t('public.approve')"
                    />
                </div>
                <div v-else class="flex items-center justify-end gap-3">
                    <Button
                        type="button"
                        size="small"
                        severity="danger"
                        class="w-[120px]"
                        disabled
                        :label="$t('public.reject')"
                    />
                    <Button
                        type="button"
                        size="small"
                        severity="success"
                        class="w-[120px]"
                        disabled
                        :label="$t('public.approve')"
                    />
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
                        <Tag
                            v-else-if="isLoading"
                            severity="secondary"
                            :value="$t('public.loading')"
                        />
                        <Tag
                            v-else
                            severity="danger"
                            :value="$t('public.unverified')"
                        />
                    </div>
                </div>
        </template>

        <template #content>
            <div class="flex flex-col gap-5">
                <div class="grid grid-cols-1 gap-2">
                    <template v-if="props.isLoading || kycResidency">
                        <InputLabel for="residency_image">
                            <template v-if="kycResidency?.proof_type === 'utility_bill'">{{ $t('public.utility_bill') }}</template>
                            <template v-else-if="kycResidency?.proof_type === 'bank_statement'">{{ $t('public.bank_statement') }}</template>
                            <template v-else-if="kycResidency?.proof_type === 'residence_certificate'">{{ $t('public.residence_certificate') }}</template>
                            <template v-else>{{ $t('public.residency_proof') }}</template>
                        </InputLabel>
                        <div
                            v-if="isLoading"
                            class="flex flex-col justify-center gap-3 items-center self-stretch p-3 rounded-md border-2 border-dashed transition-colors duration-150 bg-surface-50 dark:bg-surface-950 border-surface-300 dark:border-surface-600"
                        >
                            <div class="w-full flex items-center justify-center h-[40px]">
                                <ProgressSpinner />
                            </div>
                        </div>
                        <template v-else-if="kycResidency?.media?.length">
                            <div
                                class="flex flex-col justify-center gap-3 items-center self-stretch p-3 rounded-md border-2 border-dashed transition-colors duration-150 bg-surface-50 dark:bg-surface-950 border-surface-300 dark:border-surface-600"
                            >
                                <div class="w-full flex flex-col items-center gap-1 justify-center h-[40px] text-sm">
                                    <span class="text-xs">{{ kycResidency?.media[0].file_name }}</span>
                                    <Button
                                        type="button"
                                        as="a"
                                        :href="`/member/media/download/${kycResidency?.media[0].id}`"
                                        size="small"
                                        severity="info"
                                        class="w-[120px]"
                                    >
                                        <IconCloudDownload size="20" stroke-width="1.5"/>
                                        <span class="text-xs">
                                            {{ $t('public.download') }}
                                        </span>
                                    </Button>
                                </div>
                            </div>
                        </template>
                    </template>

                    <div v-else class="w-full flex flex-col items-start gap-1">
                        <InputLabel>{{ $t('public.file') }}</InputLabel>
                        <div
                            class="flex flex-col justify-center gap-3 items-center self-stretch p-3 rounded-md border-2 border-dashed transition-colors duration-150 bg-surface-50 dark:bg-surface-950 border-surface-300 dark:border-surface-600"
                        >
                            <div class="w-full flex flex-col items-center gap-3 justify-center h-[40px] text-sm">
                                {{ $t('public.kyc_documents_pending') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="kycResidency" class="flex items-center justify-end gap-3">
                    <Button
                        v-if="kycResidency"
                        type="button"
                        size="small"
                        severity="danger"
                        class="w-[120px]"
                        :disabled="props.isLoading || !kycResidency?.media || kycResidency?.kyc_status !== 'pending'"
                        @click="openDialog('reject', kycResidency.id)"
                        :label="$t('public.reject')"
                    />
                    <Button
                        v-if="kycResidency"
                        type="button"
                        size="small"
                        severity="success"
                        class="w-[120px]"
                        :disabled="props.isLoading || !kycResidency?.media || kycResidency?.kyc_status !== 'pending'"
                        @click="openDialog('approve', kycResidency.id)"
                        :label="$t('public.approve')"
                    />
                </div>
                <div v-else class="flex items-center justify-end gap-3">
                    <Button
                        type="button"
                        size="small"
                        severity="danger"
                        class="w-[120px]"
                        disabled
                        :label="$t('public.reject')"
                    />
                    <Button
                        type="button"
                        size="small"
                        severity="success"
                        class="w-[120px]"
                        disabled
                        :label="$t('public.approve')"
                    />
                </div>
            </div>
        </template>
    </Card>

    <!-- Dialog -->
    <Dialog
        v-model:visible="visible"
        modal
        class="dialog-xs md:dialog-md"
    >
        <template #header>
            <div class="flex flex-col gap-1">
                <span class="font-semibold text-lg">{{ approvalAction === 'reject' ? $t('public.reject_kyc_request') : $t('public.approve_kyc_request') }}</span>
                <div class="text-surface-500 dark:text-surface-200 text-sm">
                    {{ $t('public.kyc_request_caption_1') }}
                    <span class="font-semibold lowercase" :class="[approvalAction === 'approve' ? 'text-green-500' : 'text-red-500']">
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
