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
    beneficiary: Object,
    isLoading: Boolean,
})

const form = useForm({
    user_id: '',
    beneficiary_name: '',
    beneficiary_relationship: '',
    beneficiary_identity_number: '',
    beneficiary_email: '',
    beneficiary_phone: '',
})

watch(() => props.beneficiary, () => {
    form.user_id = props.beneficiary.user_id
    form.beneficiary_name = props.beneficiary.beneficiary_name
    form.beneficiary_relationship = props.beneficiary.beneficiary_relationship
    form.beneficiary_identity_number = props.beneficiary.beneficiary_identity_number
    form.beneficiary_email = props.beneficiary.beneficiary_email
    form.beneficiary_phone = props.beneficiary.beneficiary_phone
});

const countries = ref([]);
const selectedCountry = ref();
const loadingCountries = ref(false);

const getCountries = async () => {
    loadingCountries.value = true;
    try {
        const response = await axios.get('/get_countries');
        countries.value = response.data.countries;
        selectedCountry.value = countries.value.find(country => country.phone_code === props.beneficiary?.dial_code);
    } catch (error) {
        console.error('Error fetching countries:', error);
    } finally {
        loadingCountries.value = false;
    }
}

const openDialog = () => {
    visible.value = true;
    form.reset();
    getCountries();
}

const submitForm = () => {
    if (selectedCountry.value) {
        form.beneficiary_phone = selectedCountry.value.phone_code + form.beneficiary_phone;
    }

    form.post(route('member.updateUserBeneficiary'), {
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
                <div class="w-full flex flex-col items-start gap-1">
                    <InputLabel for="beneficiary_name" :value="$t('public.name')" />
                    <InputText
                        id="beneficiary_name"
                        type="text"
                        class="block w-full"
                        v-model="form.beneficiary_name"
                        :placeholder="$t('public.enter_name')"
                        :invalid="!!form.errors.beneficiary_name"
                        autocomplete="beneficiary_name"
                    />
                    <InputError :message="form.errors.beneficiary_name" />
                </div>
                <div class="w-full flex flex-col items-start gap-1">
                    <InputLabel for="beneficiary_relationship" :value="$t('public.relationship')" />
                    <InputText
                        id="beneficiary_relationship"
                        type="beneficiary_relationship"
                        class="block w-full"
                        v-model="form.beneficiary_relationship"
                        :placeholder="$t('public.enter_beneficiary_relationship')"
                        :invalid="!!form.errors.beneficiary_relationship"
                        autocomplete="beneficiary_relationship"
                    />
                    <InputError :message="form.errors.beneficiary_relationship" />
                </div>
                <div class="w-full flex flex-col items-start gap-1">
                    <InputLabel for="beneficiary_identity_number" :value="$t('public.ic_passport_no')" />
                    <InputText
                        id="beneficiary_identity_number"
                        type="text"
                        class="block w-full"
                        v-model="form.beneficiary_identity_number"
                        :placeholder="$t('public.enter_name')"
                        :invalid="!!form.errors.beneficiary_identity_number"
                        autocomplete="beneficiary_identity_number"
                    />
                    <InputError :message="form.errors.beneficiary_identity_number" />
                </div>
                <div class="w-full flex flex-col items-start gap-1">
                    <InputLabel for="beneficiary_email" :value="$t('public.email')" />
                    <InputText
                        id="beneficiary_email"
                        type="text"
                        class="block w-full"
                        v-model="form.beneficiary_email"
                        :placeholder="$t('public.enter_name')"
                        :invalid="!!form.errors.beneficiary_email"
                        autocomplete="beneficiary_email"
                    />
                    <InputError :message="form.errors.beneficiary_email" />
                </div>
                <div class="w-full flex flex-col items-start gap-1 items-start self-stretch">
                    <InputLabel for="beneficiary_phone" :value="$t('public.phone_number')" />
                    <div class="flex gap-2 items-center self-stretch relative">
                        <Select
                            v-model="selectedCountry"
                            :options="countries"
                            filter
                            :filterFields="['name', 'phone_code']"
                            optionLabel="name"
                            :placeholder="$t('public.phone_code')"
                            class="w-[100px]"
                            scroll-height="236px"
                            :invalid="!!form.errors.beneficiary_phone"
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
                            id="beneficiary_phone"
                            type="text"
                            class="block w-full"
                            v-model="form.beneficiary_phone"
                            :placeholder="$t('public.phone_number')"
                            :invalid="!!form.errors.beneficiary_phone"
                        />
                    </div>
                    <InputError :message="form.errors.beneficiary_phone" />
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