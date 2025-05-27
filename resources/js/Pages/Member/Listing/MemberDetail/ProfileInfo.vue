<script setup>
import {
    ToggleSwitch,
    Dialog,
    Select,
    InputText,
    Button,
    useConfirm,
    Avatar,
    Tag,
    Card,
    Divider,
    Skeleton
} from "primevue";
import { Edit01Icon } from "@/Components/Icons/outline.jsx";
import { IconCircleCheckFilled } from "@tabler/icons-vue";
import { ref, watch } from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import { useForm } from "@inertiajs/vue3";
import { generalFormat } from "@/Composables/format.js";
import { trans } from "laravel-vue-i18n";
import { router } from "@inertiajs/vue3";
import { useLangObserver } from "@/Composables/localeObserver.js";
import SelectChipGroup from "@/Components/SelectChipGroup.vue";

const props = defineProps({
    userDetail: Object,
})

const checked = ref(false)
const visible = ref(false)
const countries = ref()
const selectedPhoneCode = ref();
const selectedCountry = ref();
const loadingCountries = ref(false);
const { formatRgbaColor, formatNameLabel, formatSeverity } = generalFormat();
const { locale } = useLangObserver();
const genders = [
    'male',
    'female',
]

watch(() => props.userDetail, (user) => {
    checked.value = user.status === 'active';
    form.user_id = props.userDetail.id
    form.username = props.userDetail.username
    form.first_name = props.userDetail.first_name
    form.last_name = props.userDetail.last_name
    form.email = props.userDetail.email
    form.phone = props.userDetail.phone
    form.country_id = props.userDetail.country_id
    form.gender = props.userDetail.gender
    form.address = props.userDetail.address
});

const openDialog = () => {
    visible.value = true
    getCountries();
}

const form = useForm({
    user_id: '',
    username: '',
    first_name: '',
    last_name: '',
    email: '',
    dial_code: '',
    phone: '',
    phone_number: '',
    country_id: '',
    gender: '',
    address: '',
});

const getCountries = async () => {
    loadingCountries.value = true;
    try {
        const response = await axios.get('/get_countries');
        countries.value = response.data.countries;

        selectedCountry.value = countries.value.find(
            (country) => country.id === props.userDetail.country_id
        ) || null;

        selectedPhoneCode.value = countries.value.find(
            (country) => country.phone_code === props.userDetail.dial_code
        ) || null;
    } catch (error) {
        console.error('Error fetching selectedCountry:', error);
    } finally {
        loadingCountries.value = false;
    }
}

const submitForm = () => {
    form.dial_code = selectedPhoneCode.value.phone_code;

    if (selectedPhoneCode.value) {
        form.phone_number = selectedPhoneCode.value.phone_code + form.phone;
    }

    form.country_id = selectedCountry.value?.id;

    form.post(route('member.updateProfileInfo'), {
        onSuccess: () => {
            visible.value = false;
        },
    });
};

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        activate_member: {
            group: 'headless-surface',
            header: trans('public.deactivate_member'),
            text: trans('public.deactivate_member_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.post(route('member.updateMemberStatus', props.userDetail.id), {
                    id: props.userDetail.id,
                })

                checked.value = !checked.value;
            }
        },
        deactivate_member: {
            group: 'headless-primary',
            header: trans('public.activate_member'),
            text: trans('public.activate_member_caption'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.confirm'),
            action: () => {
                router.post(route('member.updateMemberStatus', props.userDetail.id), {
                    id: props.userDetail.id,
                })

                checked.value = !checked.value;
            }
        },
    };

    const { group, header, text, dynamicText, suffix, actionType, cancelButton, acceptButton, action } = messages[action_type];

    confirm.require({
        group,
        header,
        actionType,
        text,
        dynamicText,
        suffix,
        cancelButton,
        acceptButton,
        accept: action
    });
};

const handleMemberStatus = () => {
    if (props.userDetail.status === 'active') {
        requireConfirmation('activate_member')
    } else {
        requireConfirmation('deactivate_member')
    }
}

</script>

<template>
    <Card class="w-full h-full">
        <template #content>
            <div class="flex flex-col">
                <div class="flex flex-col items-start gap-4 self-stretch">
                    <div class="flex justify-between items-start self-stretch">
                        <template v-if="userDetail">
                            <Avatar
                                v-if="userDetail.profile_photo"
                                :image="userDetail.profile_photo"
                                shape="circle"
                                class="w-20 h-20 grow-0 shrink-0 rounded-full overflow-hidden dark:text-white"
                            />
                            <Avatar
                                v-else
                                :label="formatNameLabel(userDetail.name)"
                                shape="circle"
                                size="xlarge"
                                class="w-20 h-20 grow-0 shrink-0 rounded-full overflow-hidden dark:text-white"
                            />
                        </template>
                        <template v-else class="animate-pulse">
                            <Avatar
                                shape="circle"
                                class="w-20 h-20 grow-0 shrink-0 rounded-full overflow-hidden dark:text-white"
                            />
                        </template>

                        <div class="flex gap-2 items-center">
                            <div class="p-2.5 flex items-center hover:bg-surface-100 dark:hover:bg-surface-600 rounded-full">
                                <ToggleSwitch
                                    v-model="checked"
                                    readonly
                                    @click="handleMemberStatus"
                                />
                            </div>
                            <Button
                                type="button"
                                severity="secondary"
                                text
                                icon="Edit01Icon"
                                size="small"
                                rounded
                                @click="openDialog()"
                                :disabled="!userDetail"
                            >
                                <Edit01Icon class="w-4 h-4 text-surface-500 dark:text-surface-300"/>
                            </Button>
                        </div>
                    </div>
                    <div v-if="userDetail" class="flex flex-col items-start gap-1.5 self-stretch">
                        <div class="flex items-center gap-3 self-stretch">
                            <div class="truncate text-surface-950 dark:text-white md:text-lg font-semibold">
                                {{ userDetail.name }}
                            </div>
                            <IconCircleCheckFilled v-if="userDetail.kyc_status === 'approved'" size="20" stroke-width="1.25" class="text-green-700 grow-0 shrink-0" />
                            <Tag :severity="formatSeverity('primary')" :value="$t('public.' + userDetail.rank_name)"/>
                        </div>
                        <div class="text-surface-700 dark:text-surface-300 text-sm md:text-base">{{ userDetail.id_number }}</div>
                    </div>
                    <div v-else class="animate-pulse flex flex-col items-start gap-1.5 self-stretch">
                        <Skeleton
                            width="12rem"
                            height="1.25rem"
                            class="my-1.5"
                            borderRadius="2rem"
                        />
                        <Skeleton
                            width="8rem"
                            class="my-0.5"
                            borderRadius="2rem"
                        />
                    </div>
                </div>

                <Divider />

                <div v-if="userDetail" class="grid grid-cols-2 gap-5 w-full">
                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.username') }}</div>
                        <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.username }}</div>
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.email_address') }}</div>
                        <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.email }}</div>
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.phone_number') }}</div>
                        <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.dial_code }} {{ userDetail.phone }}</div>
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.country') }}</div>
                        <div class="flex items-center gap-1">
                            <img
                                v-if="userDetail.country_iso2"
                                :src="`https://flagcdn.com/w40/${userDetail.country_iso2.toLowerCase()}.png`"
                                :alt="userDetail.country_iso2"
                                width="18"
                                height="12"
                            />
                            <div class="truncate text-surface-950 dark:text-white text-sm font-medium">
                                {{
                                    userDetail.country_translations
                                        ? (JSON.parse(userDetail.country_translations)[locale] || userDetail.country_name || '-')
                                        : (userDetail.country_name || '-')
                                }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.ic_passport_no') }}</div>
                        <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.identity_number ?? '-' }}</div>
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.gender') }}</div>
                        <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.gender ? $t('public.' + userDetail.gender) : '-' }}</div>
                    </div>

                    <div class="flex flex-col items-start gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.group') }}</div>
                        <div v-if="userDetail.group_name" class="flex items-center gap-2 py-1 px-2 rounded"
                            :style="{ backgroundColor: formatRgbaColor(userDetail.group_color, 0.1) }">
                            <div class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: `#${userDetail.group_color}` }"></div>
                            <div class="text-xs font-semibold" :style="{ color: `#${userDetail.group_color}` }">
                                {{ userDetail.group_name }}
                            </div>
                        </div>
                        <div v-else>-</div>
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.upline') }}</div>
                        <div class="flex items-center gap-2">
                            <Avatar
                                v-if="userDetail.upline_profile_photo"
                                :image="userDetail.upline_profile_photo"
                                shape="circle"
                                class="w-[26px] h-[26px] rounded-full overflow-hidden grow-0 shrink-0 dark:text-white"
                            />
                            <Avatar
                                v-else
                                :label="formatNameLabel(userDetail.upline_name)"
                                shape="circle"
                                class="w-[26px] h-[26px] rounded-full overflow-hidden grow-0 shrink-0 dark:text-white text-sm"
                            />
                            <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.upline_name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="col-span-2 flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.address') }}</div>
                        <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.address ?? '-' }}</div>
                    </div>

                </div>
                <div v-else class="grid grid-cols-2 gap-5 w-full animate-pulse">
                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.username') }}</div>
                        <Skeleton
                            width="8rem"
                            class="my-0.5"
                            borderRadius="2rem"
                        />
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.email_address') }}</div>
                        <Skeleton
                            width="8rem"
                            class="my-0.5"
                            borderRadius="2rem"
                        />
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.phone_number') }}</div>
                        <Skeleton
                            width="8rem"
                            class="my-0.5"
                            borderRadius="2rem"
                        />
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.country') }}</div>
                        <Skeleton
                            width="8rem"
                            class="my-0.5"
                            borderRadius="2rem"
                        />
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.ic_passport_no') }}</div>
                        <Skeleton
                            width="8rem"
                            class="my-0.5"
                            borderRadius="2rem"
                        />
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.gender') }}</div>
                        <Skeleton
                            width="8rem"
                            class="my-0.5"
                            borderRadius="2rem"
                        />
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.group') }}</div>
                        <Skeleton
                            width="8rem"
                            class="my-0.5"
                            height="1.5rem"
                            borderRadius="2rem"
                        />
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.upline') }}</div>
                        <Skeleton
                            width="8rem"
                            class="my-0.5"
                            height="1.5rem"
                            borderRadius="2rem"
                        />
                    </div>

                    <div class="flex flex-col gap-2 w-full">
                        <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.address') }}</div>
                        <Skeleton
                            width="10rem"
                            class="my-0.5"
                            borderRadius="2rem"
                        />
                    </div>
                </div>
            </div>
        </template>
    </Card>

    <!-- edit contact info -->
    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.profile_information')"
        class="dialog-xs md:dialog-lg"
    >
        <form>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="flex flex-col gap-1">
                    <InputLabel for="first_name" :value="$t('public.first_name')" :invalid="!!form.errors.first_name" />
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
                    <InputLabel for="last_name" :value="$t('public.last_name')" :invalid="!!form.errors.last_name" />
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

                <div class="flex flex-col gap-1">
                    <InputLabel for="username" :value="$t('public.username')" :invalid="!!form.errors.username" />
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
                    <InputLabel for="email" :value="$t('public.email')" :invalid="!!form.errors.email" />
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

                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel for="phone" :value="$t('public.phone_number')" :invalid="!!form.errors.phone" />
                    <div class="flex gap-2 items-center self-stretch relative">
                        <Select
                            v-model="selectedPhoneCode"
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
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel for="country" :value="$t('public.nationality')" :invalid="!!form.errors.country_id"/>
                    <Select
                        v-model="selectedCountry"
                        :options="countries"
                        :loading="loadingCountries"
                        optionLabel="name"
                        :placeholder="$t('public.select_nationality')"
                        class="w-full"
                        :invalid="!!form.errors.country_id"
                        filter
                        :filter-fields="['name', 'iso2']"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center">
                                <div class="leading-tight w-full">{{ JSON.parse(slotProps.value.translations)[locale] || slotProps.value.name }}</div>
                            </div>
                            <span v-else class="text-surface-400 dark:text-surface-700">{{ slotProps.placeholder }}</span>
                        </template>
                        <template #option="slotProps">
                            <div class="flex items-center gap-1">
                                <img
                                    v-if="slotProps.option.iso2"
                                    :src="`https://flagcdn.com/w40/${slotProps.option.iso2.toLowerCase()}.png`"
                                    :alt="slotProps.option.iso2"
                                    width="18"
                                    height="12"
                                />
                                <div class="max-w-[200px] truncate">{{ JSON.parse(slotProps.option.translations)[locale] || slotProps.option.name }}</div>
                            </div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.country_id" />
                </div>

                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        for="gender"
                        :value="$t('public.gender')"
                        :invalid="!!form.errors.gender"
                    />
                    <SelectChipGroup
                        :items="genders"
                        v-model="form.gender"
                    />
                    <InputError :message="form.errors.gender" />
                </div>

                <div class="col-span-2 flex flex-col gap-1">
                    <InputLabel for="address" :value="$t('public.address')" :invalid="!!form.errors.address" />
                    <InputText
                        id="address"
                        type="text"
                        class="block w-full"
                        v-model="form.address"
                        :placeholder="$t('public.enter_name')"
                        :invalid="!!form.errors.address"
                        autocomplete="address"
                    />
                    <InputError :message="form.errors.address" />
                </div>

            </div>
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

