<script setup>
import { Button, Dialog, Image, Tag, Textarea } from "primevue";
import { IconCloudDownload } from "@tabler/icons-vue";
import { ref } from "vue";
import { generalFormat } from "@/Composables/format.js";
import dayjs from "dayjs";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    kyc: Object,
})

const visible = ref(false);
const approvalAction = ref('');

const openDialog = () => {
    visible.value = true;
    approvalAction.value = '';
    form.reset();
}


const handleApproval = (action) => {
    approvalAction.value = action;
}

const form = useForm({
    kyc_id: props.kyc.id,
    action: '',
    remarks: '',
});

const submitForm = () => {
    form.action = approvalAction.value;
    form.post(route('member.pendingKycApproval'), {
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
        :header="$t('public.kyc_request')"
        class="dialog-xs md:dialog-md"
    >
        <div
            class="flex flex-col-reverse md:flex-row md:items-center gap-3 self-stretch w-full pb-4 border-b dark:border-surface-600"
        >
            <div class="flex flex-col items-start w-full">
                <span class="text-sm font-medium">{{ kyc.full_name }}</span>
                <span class="text-surface-500 text-sm">{{ kyc.email }}</span>
            </div>
            <Tag
                severity="contrast"
                class="w-fit text-nowrap"
                :value="$t('public.' + kyc.proof_type)"
            />
        </div>

        <div class="flex flex-col items-center gap-4 self-stretch">
            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.requested_date') }}
                    </div>
                    <div class="text-surface-950 dark:text-white text-sm font-medium">
                        {{ dayjs(kyc.created_at).format('YYYY/MM/DD HH:mm:ss') }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.category') }}
                    </div>
                    <div class="text-surface-950 dark:text-white text-sm font-medium">
                        {{ $t('public.' + kyc.category) }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.ic_passport_no') }}
                    </div>
                    <div class="text-surface-950 dark:text-white text-sm font-medium">
                        {{ kyc.identity_number }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row items-start gap-1 self-stretch">
                    <div class="w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.address') }}
                    </div>
                    <div class="text-surface-950 dark:text-white text-sm font-medium">
                        {{ kyc.address }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-start gap-1 self-stretch">
                    <div class="min-w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.file') }}
                    </div>
                    <div class="flex flex-col gap-2 overflow-hidden w-full">
                        <template v-for="file in kyc.media" :key="file.id">
                            <div
                                v-if="file.mime_type.startsWith('image/')"
                                class="flex flex-col justify-center gap-3 items-center self-stretch p-3 rounded-md border-2 border-dashed transition-colors duration-150 bg-surface-50 dark:bg-surface-950 border-surface-300 dark:border-surface-600"
                            >
                                <Image
                                    :src="file.original_url"
                                    :alt="file.file_name"
                                    preview
                                    imageClass="w-full h-[120px] object-contain"
                                />
                            </div>

                            <div
                                v-else
                                class="flex flex-col justify-center gap-3 items-center self-stretch p-3 rounded-md border-2 border-dashed transition-colors duration-150 bg-surface-50 dark:bg-surface-950 border-surface-300 dark:border-surface-600"
                            >
                                <div class="w-full flex flex-col items-center gap-3 justify-center h-[100px] text-sm">
                                    <span class="font-semibold">{{ file.file_name }}</span>
                                    <Button
                                        type="button"
                                        as="a"
                                        :label="$t('public.view_file')"
                                        :href="file.original_url"
                                        target="_blank"
                                        size="small"
                                        severity="secondary"
                                    />
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div v-if="approvalAction" class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-surface-500 text-xs font-medium">
                        {{ $t('public.action') }}
                    </div>
                    <Tag
                        :severity="approvalAction === 'approve' ? 'success' : 'danger'"
                        class="text-xs w-fit"
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
