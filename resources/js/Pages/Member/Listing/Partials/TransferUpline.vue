<script setup>
import {
    Password,
    Button,
    Select,
    useConfirm,
    Avatar,
} from "primevue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useForm } from "@inertiajs/vue3";
import { h, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { IconUserCheck } from "@tabler/icons-vue";
import { trans, wTrans } from "laravel-vue-i18n";
import { generalFormat } from "@/Composables/format.js";

const { formatRgbaColor, formatNameLabel } = generalFormat();

const props = defineProps({
    member: Object,
})

const emit = defineEmits(['update:visible'])

const form = useForm({
    user_id: props.member.id,
    upline_id: props.member.upline_id,
})

const closeDialog = () => {
    emit('update:visible', false);
}

const uplines = ref(null);
const upline = ref(null);
const isLoading = ref(false);

const getAvailableUplines = async () => {
    isLoading.value = true;

    try {
        const url = `/member/getAvailableUplines?id=${props.member.id}`;

        const response = await axios.get(url);

        // Filter out the current member from the uplines list
        uplines.value = response.data.uplines;
        
    } catch (error) {
        console.error('Error get available uplines:', error);
    } finally {
        isLoading.value = false;
        // Try to match the initial upline_id (which is `form.upline_id`) to one of the available options
        upline.value = uplines.value.find(upline => upline.value === form.upline_id);
    }
};
getAvailableUplines();

const submitForm = () => {
    form.upline_id = upline.value['value'];
    form.post(route('member.transferUpline'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        },
    });
}

</script>

<template>
    <div class="flex flex-col gap-6 items-center self-stretch py-4 md:py-6 md:gap-8">
        <div class="flex flex-col justify-center items-start p-3 self-stretch bg-surface-50">
            <span class="w-full truncate text-surface-950 font-semibold">{{ props.member.name }}</span>
            <span class="w-full truncate text-surface-500 text-sm">{{ props.member.email }}</span>
        </div>
        <div class="flex flex-col items-center gap-3 self-stretch">
            <span class="self-stretch text-surface-950 text-sm font-bold">{{ $t('public.select_new_upline') }}</span>
            <div class="flex flex-col items-start gap-2 self-stretch">
                <InputLabel for="upline" :value="$t('public.upline')" />
                <Select
                    v-model="upline"
                    :options="uplines"
                    filter
                    :filterFields="['name']"
                    optionLabel="name"
                    :placeholder="$t('public.select_upline')"
                    class="w-full font-normal"
                    :disabled="isLoading"
                    scroll-height="236px"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <Avatar
                                    v-if="slotProps.value.profile_photo"
                                    :image="slotProps.value.profile_photo"
                                    shape="circle"
                                    class="w-5 h-5 grow-0 shrink-0 rounded-full overflow-hidden dark:text-white"
                                />
                                <Avatar
                                    v-else
                                    :label="formatNameLabel(slotProps.value.name)"
                                    shape="circle"
                                    class="w-5 h-5 grow-0 shrink-0 rounded-full overflow-hidden dark:text-white text-xs"
                                />
                                <div>{{ slotProps.value.name }}</div>
                            </div>
                        </div>
                        <span v-else class="text-surface-400">{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center gap-2">
                            <Avatar
                                v-if="slotProps.option.profile_photo"
                                :image="slotProps.option.profile_photo"
                                shape="circle"
                                class="w-5 h-5 grow-0 shrink-0 rounded-full overflow-hidden dark:text-white"
                            />
                            <Avatar
                                v-else
                                :label="formatNameLabel(slotProps.option.name)"
                                shape="circle"
                                class="w-5 h-5 grow-0 shrink-0 rounded-full overflow-hidden dark:text-white text-xs"
                            />
                            <div>{{ slotProps.option.name }}</div>
                        </div>
                    </template>
                </Select>
                <InputError :message="form.errors.upline_id" />
            </div>
        </div>
    </div>

    <div class="flex justify-end items-center pt-6 gap-4 self-stretch">
        <Button
            severity="secondary"
            outlined
            class="w-full"
            :disabled="form.processing"
            @click.prevent="closeDialog"
        >
            {{ $t('public.cancel') }}
        </Button>
        <Button
            class="w-full"
            :disabled="form.processing || isLoading"
            @click.prevent="submitForm"
        >
            {{ $t('public.reset') }}
        </Button>
    </div>
</template>
