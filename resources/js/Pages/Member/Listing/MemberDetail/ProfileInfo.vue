<script setup>
import {
    ToggleSwitch,
    Dialog,
    Select,
    InputText,
    Button,
    useConfirm,
    Avatar
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

const props = defineProps({
    userDetail: Object,
})

const checked = ref(false)
const visible = ref(false)
const countries = ref()
const selectedCountry = ref();
const { formatRgbaColor, formatNameLabel } = generalFormat();

watch(() => props.userDetail, (user) => {
    checked.value = user.status === 'active';
    form.user_id = props.userDetail.id
    form.username = props.userDetail.username
    form.first_name = props.userDetail.first_name
    form.last_name = props.userDetail.last_name
    form.email = props.userDetail.email
    form.phone = props.userDetail.phone
});

watch(countries, () => {
    selectedCountry.value = countries.value.find(country => country.phone_code === props.userDetail?.dial_code);
});

const openDialog = () => {
    visible.value = true
}

const form = useForm({
    user_id: '',
    name: '',
    email: '',
    dial_code: '',
    phone: '',
    phone_number: '',
});

const getResults = async () => {
    try {
        const response = await axios.get('/get_countries');
        countries.value = response.data.countries;
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};

getResults();

const submitForm = () => {
    form.dial_code = selectedCountry.value;

    if (selectedCountry.value) {
        form.phone_number = selectedCountry.value.phone_code + form.phone;
    }

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
    <div class="bg-white dark:bg-surface-800 w-full xl:min-w-[540px] flex flex-col gap-6 md:gap-5 xl:gap-8 p-4 md:py-6 md:px-8 rounded-2xl shadow-toast self-stretch">
        <div class="flex flex-col pb-6 md:pb-5 xl:pb-8 items-start gap-4 self-stretch border-b border-surface-200 dark:border-surface-500">
            <div class="flex justify-between items-start self-stretch">
                <div v-if="userDetail">
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
                        class="w-20 h-20 grow-0 shrink-0 rounded-full overflow-hidden dark:text-white"
                    />
                </div>
                <div v-else class="animate-pulse">
                    <Avatar
                        shape="circle"
                        class="w-20 h-20 grow-0 shrink-0 rounded-full overflow-hidden dark:text-white"
                    />
                </div>

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
                    <!-- <Button
                        type="button"
                        severity="secondary"
                        text
                        icon="Edit01Icon"
                        size="small"
                        rounded
                        :disabled="!userDetail"
                    > -->
                        <Edit01Icon class="w-4 h-4 text-surface-500 dark:text-surface-300"/>
                    </Button>
                </div>
            </div>
            <div v-if="userDetail" class="flex flex-col items-start gap-1.5 self-stretch">
                <div class="flex items-center gap-3 self-stretch">
                    <div class="truncate text-surface-950 dark:text-white md:text-lg font-semibold">
                        {{ userDetail.name }}
                    </div>
                    <IconCircleCheckFilled v-if="userDetail.kyc_status == 'approved'" size="20" stroke-width="1.25" class="text-green-700 grow-0 shrink-0" />
                    <!-- <StatusBadge :variant="userDetail.role" :value="$t('public.' + userDetail.role)"/> -->
                </div>
                <div class="text-surface-700 dark:text-surface-300 text-sm md:text-base">{{ userDetail.id_number }}</div>
            </div>
            <div v-else class="animate-pulse flex flex-col items-start gap-1.5 self-stretch">
                <div class="h-4 bg-surface-200 dark:bg-surface-500 rounded-full w-48 my-2 md:my-3"></div>
                <div class="h-2 bg-surface-200 dark:bg-surface-500 rounded-full w-20 mb-1"></div>
            </div>
        </div>
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
                <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.country ?? '-' }}</div>
            </div>

            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.nationality') }}</div>
                <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.nationality ?? '-' }}</div>
            </div>

            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.group') }}</div>
                <div v-if="userDetail.group_id" class="flex items-center gap-2 py-1 px-2 rounded"
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
                        class="w-[26px] h-[26px] rounded-full overflow-hidden grow-0 shrink-0 dark:text-white"
                    />
                    <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.upline_name ?? '-' }}</div>
                </div>
            </div>

            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.total_referred_member') }}</div>
                <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.total_direct_member }}</div>
            </div>

            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs truncate">{{ $t('public.total_referred_ib') }}</div>
                <div class="truncate text-surface-950 dark:text-white text-sm font-medium">{{ userDetail.total_direct_ib }}</div>
            </div>
        </div>
        <div v-else class="grid grid-cols-2 gap-5 w-full animate-pulse">
            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.username') }}</div>
                <div class="truncate text-surface-950 dark:text-white text-sm font-medium w-full">
                    <div class="h-2 bg-surface-200 dark:bg-surface-500 rounded-full w-48 my-2"></div>
                </div>
            </div>

            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.email_address') }}</div>
                <div class="truncate text-surface-950 dark:text-white text-sm font-medium w-full">
                    <div class="h-2 bg-surface-200 dark:bg-surface-500 rounded-full w-48 my-2"></div>
                </div>
            </div>

            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.phone_number') }}</div>
                <div class="h-2 bg-surface-200 dark:bg-surface-500 rounded-full w-36 my-2"></div>
            </div>

            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.group') }}</div>
                <div class="h-3 bg-surface-200 dark:bg-surface-500 rounded-full w-20 mt-1 mb-1.5"></div>
            </div>

            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.upline') }}</div>
                <div class="h-3 bg-surface-200 dark:bg-surface-500 rounded-full w-36 mt-1 mb-1.5"></div>
            </div>
            
            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.total_referred_member') }}</div>
                <div class="h-2 bg-surface-200 dark:bg-surface-500 rounded-full w-36 mt-2 mb-1"></div>
            </div>

            <div class="flex flex-col gap-2 w-full">
                <div class="text-surface-500 dark:text-surface-300 text-xs w-full truncate">{{ $t('public.total_referred_ib') }}</div>
                <div class="h-2 bg-surface-200 dark:bg-surface-500 rounded-full w-36 mt-2 mb-1"></div>
            </div>
        </div>
    </div>

    <!-- edit contact info -->
    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.profile_information')"
        class="dialog-xs md:dialog-sm"
    >
        <form>
            <div class="flex flex-col gap-5">
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

