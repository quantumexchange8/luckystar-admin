<script setup>
import {
    Dialog,
    InputText,
    Select,
    Password,
    FileUpload,
    Avatar,
    Button,
} from "primevue";
import {ref, watch, watchEffect} from "vue";
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import {useForm, usePage} from '@inertiajs/vue3';
import { generalFormat } from "@/Composables/format.js";

const { formatRgbaColor, formatNameLabel } = generalFormat();

const props = defineProps({
    groups: Array,
    countries: Array,
})

const countries = ref()
const selectedCountry = ref();
const groups = ref();
const selectedGroup = ref();
const uplines = ref();
const selectedUpline = ref();

// Watch for changes in props
watch(() => [props.groups, props.countries], ([newGroups, newCountries]) => {
    groups.value = newGroups;
    countries.value = newCountries;
}, { immediate: true });

const visible = ref(false)

const form = useForm({
    first_name: '',
    last_name: '',
    username: '',
    email: '',
    dial_code: '',
    phone: '',
    phone_number: '',
    group_id: '',
    upline_id: '',
    // kyc_verification: '',
    password: '',
    password_confirmation: '',
});

// Watch for individual changes in upline_id and group_id
watch([selectedGroup, selectedUpline], ([newGroup, newUpline], [oldGroup]) => {
    // Handle selectedGroup changes
    if (newGroup !== oldGroup) {
        if (newGroup) {
            form.group_id = newGroup.id;

            // Reset upline selection when group changes
            selectedUpline.value = null;
            form.upline_id = null;

            axios.get('/get_group_uplines', {
                params: { group_id: newGroup.id }
            }).then((response) => {
                uplines.value = response.data.uplines;
            }).catch((error) => {
                console.error('Failed to fetch uplines:', error);
                uplines.value = [];
            });
        } else {
            form.group_id = null;
            selectedUpline.value = null;
            form.upline_id = null;
            uplines.value = [];
        }
    }

    // Handle selectedUpline changes
    if (newUpline) {
        form.upline_id = newUpline.id;
    } else {
        form.upline_id = null;
    }
});

const submitForm = () => {
    form.dial_code = selectedCountry.value;

    if (selectedCountry.value) {
        form.phone_number = selectedCountry.value.phone_code + form.phone;
    }

    form.post(route('member.addNewMember'), {
        onSuccess: () => {
            visible.value = false;
            form.reset();
        },
    });
};

const openDialog = () => {
    visible.value = true;
    form.reset();
    selectedCountry.value = null;
    selectedGroup.value = null;
    selectedUpline.value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});
</script>

<template>
    <Button
        type="button"
        class='w-full md:w-auto'
        @click="openDialog"
    >
        {{ $t('public.add_member') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.new_member')"
        class="dialog-xs md:dialog-md"
    >
        <form @submit.prevent="submitForm()">
            <div class="flex flex-col items-center gap-8 self-stretch">

                <!-- Basic Information -->
                <div class="flex flex-col gap-3 items-center self-stretch">
                    <div class="text-gray-950 font-semibold text-sm self-stretch">
                        {{ $t('public.basic_information') }}
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                        <div class="flex flex-col items-start gap-0.5 self-stretch">
                            <InputLabel for="first_name" :value="$t('public.first_name')" />
                            <InputText
                                id="first_name"
                                type="text"
                                class="block w-full"
                                v-model="form.first_name"
                                :placeholder="$t('public.first_name_placeholder')"
                                :invalid="!!form.errors.first_name"
                                autofocus
                            />
                            <InputError :message="form.errors.first_name" />
                        </div>
                        <div class="flex flex-col items-start gap-0.5 self-stretch">
                            <InputLabel for="last_name" :value="$t('public.last_name')" />
                            <InputText
                                id="last_name"
                                type="text"
                                class="block w-full"
                                v-model="form.last_name"
                                :placeholder="$t('public.last_name_placeholder')"
                                :invalid="!!form.errors.last_name"
                                autofocus
                            />
                            <InputError :message="form.errors.last_name" />
                        </div>
                        <div class="flex flex-col items-start gap-0.5 self-stretch">
                            <InputLabel for="username" :value="$t('public.username')" />
                            <InputText
                                id="username"
                                type="text"
                                class="block w-full"
                                v-model="form.username"
                                :placeholder="$t('public.username_placeholder')"
                                :invalid="!!form.errors.username"
                                autofocus
                            />
                            <InputError :message="form.errors.username" />
                        </div>
                        <div class="flex flex-col items-start gap-0.5 self-stretch">
                            <InputLabel for="email" :value="$t('public.email')" />
                            <InputText
                                id="email"
                                type="email"
                                class="block w-full"
                                v-model="form.email"
                                :placeholder="$t('public.enter_email')"
                                :invalid="!!form.errors.email"
                            />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div class="flex flex-col items-start gap-0.5 self-stretch">
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
                                            <div>{{ slotProps.option.name }} <span class="text-gray-500">{{ slotProps.option.phone_code }}</span></div>
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
                        <div class="flex flex-col items-start gap-0.5 self-stretch">
                            <InputLabel for="group_id" :value="$t('public.group')" />
                            <Select
                                v-model="selectedGroup"
                                :options="groups"
                                filter
                                :filterFields="['name', 'phone_code']"
                                optionLabel="name"
                                :placeholder="$t('public.group_placeholder')"
                                class="w-full"
                                scroll-height="236px"
                                :invalid="!!form.errors.group_id"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-4 h-4 rounded-full overflow-hidden grow-0 shrink-0" :style="{ backgroundColor: `#${slotProps.value.color}` }"></div>
                                            <div>{{ slotProps.value.name }}</div>
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">
                                        {{ slotProps.placeholder }}
                                    </span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <div class="w-4 h-4 rounded-full overflow-hidden grow-0 shrink-0" :style="{ backgroundColor: `#${slotProps.option.color}` }"></div>
                                        <div>{{ slotProps.option.name }}</div>
                                    </div>
                                </template>
                            </Select>
                            <InputError :message="form.errors.group_id" />
                        </div>

                        <div v-if="selectedGroup" class="flex flex-col items-start gap-0.5 self-stretch">
                            <InputLabel for="upline_id" :value="$t('public.upline')" />
                            <Select
                                v-model="selectedUpline"
                                :options="uplines"
                                filter
                                :filterFields="['full_name', 'phone_code']"
                                optionLabel="full_name"
                                :placeholder="$t('public.upline_placeholder')"
                                class="w-full"
                                scroll-height="236px"
                                :invalid="!!form.errors.upline_id"
                            >
                                <template #value="slotProps">
                                    <div v-if="slotProps.value" class="flex items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <Avatar
                                                v-if="slotProps.value.profile_photo"
                                                :image="slotProps.value.profile_photo"
                                                shape="circle"
                                                class="w-5 h-5 text-xs rounded-full overflow-hidden grow-0 shrink-0"
                                            />
                                            <Avatar
                                                v-else
                                                :label="formatNameLabel(slotProps.value.full_name)"
                                                shape="circle"
                                                class="w-5 h-5 text-xs rounded-full overflow-hidden grow-0 shrink-0"
                                            />
                                            <div>{{ slotProps.value.full_name }}</div>
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">
                                            {{ slotProps.placeholder }}
                                    </span>
                                </template>
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <Avatar
                                            v-if="slotProps.option.profile_photo"
                                            :image="slotProps.option.profile_photo"
                                            shape="circle"
                                            class="w-5 h-5 text-xs rounded-full overflow-hidden grow-0 shrink-0"
                                        />
                                        <Avatar
                                            v-else
                                            :label="formatNameLabel(slotProps.option.full_name)"
                                            shape="circle"
                                            class="w-5 h-5 text-xs rounded-full overflow-hidden grow-0 shrink-0"
                                        />
                                        <div>{{ slotProps.option.full_name }}</div>
                                    </div>
                                </template>
                            </Select>
                            <InputError :message="form.errors.upline_id" />
                        </div>
                    </div>
                </div>

                <!-- Kyc Verification -->
                <!-- <div class="flex flex-col gap-3 items-center self-stretch">
                    <div class="text-gray-950 font-semibold text-sm self-stretch">
                        {{ $t('public.kyc_verification') }}
                    </div>
                    <div class="flex flex-col gap-3 items-start self-stretch">
                        <span class="text-xs text-gray-500">{{ $t('public.kyc_caption') }}</span>
                        <FileUpload
                            class="w-full"
                            name="kyc_verification"
                            url="/member/uploadKyc"
                            accept="image/*"
                            :maxFileSize="10485760"
                            auto
                        >
                            <template #header="{ chooseCallback }">
                                <div class="flex flex-wrap justify-between items-center flex-1 gap-2">
                                    <div class="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="primary-tonal"
                                            @click="chooseCallback()"
                                        >
                                            {{ $t('public.browse') }}
                                        </Button>
                                    </div>
                                </div>
                            </template>
                        </FileUpload>
                    </div>
                </div> -->

                <!-- Create Password -->
                <div class="flex flex-col gap-3 items-center self-stretch">
                    <div class="text-gray-950 font-semibold text-sm self-stretch">
                        {{ $t('public.create_password') }}
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                        <div class="flex flex-col items-start gap-0.5 self-stretch">
                            <InputLabel for="password" :value="$t('public.password')" />
                            <Password
                                v-model="form.password"
                                toggleMask
                                :invalid="!!form.errors.password"
                            />
                            <InputError :message="form.errors.password" />
                        </div>
                        <div class="flex flex-col items-start gap-0.5 self-stretch">
                            <InputLabel for="password_confirmation" :value="$t('public.confirm_password')" />
                            <Password
                                v-model="form.password_confirmation"
                                toggleMask
                                :invalid="!!form.errors.password"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-end pt-5 self-stretch">
                <Button
                    @click="submitForm"
                    :disabled="form.processing"
                >
                    {{ $t('public.create') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
