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
    employment_status: '',
    occupation_id: '',
    industry_id: '',
    source_of_income: '',
    annual_income: '',
    net_worth: '',
})

const loadingOccupations = ref(false);
const loadingIndustries = ref(false);
const visible = ref(false);
const employmentStatuses = ref(['individual', 'managed']);
const incomeSources = ref(['live', 'virtual']);
const selectedOccupation = ref();
const occupations = ref();
const selectedIndustry = ref();
const industries = ref();

watch(() => props.background, () => {
    form.user_id = props.background.user_id
    form.employment_status = props.background.employment_status
    form.occupation_id = props.background.occupation_id
    form.industry_id = props.background.industry_id
    form.source_of_income = props.background.source_of_income
    form.annual_income = props.background.annual_income
    form.net_worth = props.background.net_worth
});

watch(occupations, () => {
    selectedOccupation.value = occupations.value.find(occupation => occupation.id === props.background?.occupation_id);
});

watch(industries, () => {
    selectedIndustry.value = industries.value.find(industry => industry.id === props.background?.industry_id);
});

const openDialog = () => {
    visible.value = true;
    form.reset();
    // getOccupations();
    // getIndustries();
}

const getOccupations = async () => {
    loadingOccupations.value = true;
    try {
        const response = await axios.get('/getOccupations');
        occupations.value = response.data;

        if (props.background?.occupation_id) {
            selectedOccupation.value = response.data.find(occupation => occupation.id === props.background.occupation_id);
        }
    } catch (error) {
        console.error('Error fetching occupations:', error);
    } finally {
        loadingOccupations.value = false;
    }
};

const getIndustries = async () => {
    loadingIndustries.value = true;
    try {
        const response = await axios.get('/getIndustries');
        industries.value = response.data;

        if (props.background?.industry_id) {
            selectedIndustry.value = response.data.find(industry => industry.id === props.background.industry_id);
        }
    } catch (error) {
        console.error('Error fetching users:', error);
    } finally {
        loadingIndustries.value = false;
    }
};

const submitForm = () => {
    if (selectedOccupation.value) {
        form.occupation_id = selectedOccupation.value.id;
    }
    if (selectedIndustry.value) {
        form.industry_id = selectedIndustry.value.id;
    }

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
            <!-- <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-1">
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
                <div class="flex flex-col gap-1">
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
                <div class="flex flex-col gap-1">
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
                <div class="flex flex-col gap-1">
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
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel for="phone" :value="$t('public.phone_number')" />
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