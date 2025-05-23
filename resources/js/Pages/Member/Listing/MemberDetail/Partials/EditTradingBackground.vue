<script setup>
import { 
    Button,
    Dialog,
    Select,
    InputText,
} from "primevue";
import { IconEdit } from "@tabler/icons-vue";
import { ref, watch, watchEffect } from "vue";
import { generalFormat } from "@/Composables/format.js";
import { usePage, useForm } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";

const props = defineProps({
    background: Object,
    isLoading: Boolean,
})

const form = useForm({
    user_id: '',
    trading_experience: '',
    traded_product_type: '',
    trade_per_month: '',
    trade_concept: '',
    trade_platform_experience: '',
})

const visible = ref(false);

watch(() => props.background, () => {
    form.user_id = props.background.user_id
    form.trading_experience = props.background.trading_experience
    form.traded_product_type = props.background.traded_product_type
    form.trade_per_month = props.background.trade_per_month
    form.trade_concept = props.background.trade_concept
    form.trade_platform_experience = props.background.trade_platform_experience
});

const openDialog = () => {
    visible.value = true;
    form.reset();
}

const submitForm = () => {
    form.post(route('member.updateUserBackground'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        }
    })
}

const closeDialog = () => {
    visible.value = false;
    form.reset();
}

</script>

<template>
    <div class="flex items-center justify-center gap-2">
        <Button
            type="button"
            icon="IconEdit"
            severity="secondary"
            rounded
            text
            :disable="props.isLoading"
        >
            <template #icon>
                <IconEdit size="16" stroke-width="1.5" />
            </template>
        </Button>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.work_information')"
        class="dialog-xs md:dialog-md"
    >
        <form>
            <!-- <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                <div class="flex flex-col items-start gap-0.5 w-full">
                    <InputLabel for="username" :value="$t('public.username')" />
                    <InputText
                        id="username"
                        type="text"
                        class="block w-full"
                        v-model="form.username"
                        :placeholder="$t('public.enter_name')"
                        :invalid="!!form.errors.username"
                        autocomplete="username"
                    />
                    <InputError :message="form.errors.username" />
                </div>
                <div class="flex flex-col items-start gap-0.5 w-full">
                    <InputLabel for="email" :value="$t('public.email')" />
                    <InputText
                        id="email"
                        type="email"
                        class="block w-full"
                        v-model="form.email"
                        :placeholder="$t('public.enter_email')"
                        :invalid="!!form.errors.email"
                        autocomplete="email"
                    />
                    <InputError :message="form.errors.email" />
                </div>
                <div class="flex flex-col items-start gap-0.5 w-full">
                    <InputLabel for="first_name" :value="$t('public.first_name')" />
                    <InputText
                        id="first_name"
                        type="text"
                        class="block w-full"
                        v-model="form.first_name"
                        :placeholder="$t('public.enter_name')"
                        :invalid="!!form.errors.first_name"
                        autocomplete="first_name"
                    />
                    <InputError :message="form.errors.first_name" />
                </div>
                <div class="flex flex-col items-start gap-0.5 w-full">
                    <InputLabel for="last_name" :value="$t('public.last_name')" />
                    <InputText
                        id="last_name"
                        type="text"
                        class="block w-full"
                        v-model="form.last_name"
                        :placeholder="$t('public.enter_name')"
                        :invalid="!!form.errors.last_name"
                        autocomplete="last_name"
                    />
                    <InputError :message="form.errors.last_name" />
                </div>
                <div class="flex flex-col items-start gap-0.5 w-full">
                    <InputLabel for="phone" :value="$t('public.phone_number')" />
                    <div class="flex gap-2 items-center w-full relative">
                        <Select
                            v-model="selectedCountry"
                            :options="countries"
                            filter
                            :filterFields="['name', 'phone_code']"
                            optionLabel="name"
                            :placeholder="$t('public.phone_code')"
                            class="w-[100px]"
                            scroll-height="236px"
                            :invalid="!!form.errors.phone"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div>{{ slotProps.value.phone_code }}</div>
                                </div>
                                <span v-else>
                                    {{ slotProps.placeholder }}
                                </span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center w-[262px] md:max-w-[236px]">
                                    <div>{{ slotProps.option.name }} <span class="text-surface-500 dark:text-surface-300">{{ slotProps.option.phone_code }}</span></div>
                                </div>
                            </template>
                        </Select>

                        <InputText
                            id="phone"
                            type="text"
                            class="block w-full"
                            v-model="form.phone"
                            :placeholder="$t('public.phone_number')"
                            :invalid="!!form.errors.phone"
                        />
                    </div>
                    <InputError :message="form.errors.phone" />
                </div>
            </div> -->
            <div class="flex justify-end items-center pt-10 md:pt-7 gap-4 self-stretch">
                <Button
                    type="button"
                    severity="secondary"
                    raised
                    class="w-full md:w-[120px]"
                    :disabled="form.processing"
                    @click.prevent="visible = false"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    class="w-full md:w-[120px]"
                    :disabled="form.processing"
                    @click="submitForm"
                >
                    {{ $t('public.save') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>