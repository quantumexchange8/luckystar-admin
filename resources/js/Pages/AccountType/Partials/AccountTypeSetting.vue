<script setup>
import Button from '@/Components/Button.vue';
import { IconAdjustmentsHorizontal } from '@tabler/icons-vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import { onMounted, ref, watch } from 'vue';
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useForm } from '@inertiajs/vue3';
import ColorPicker from 'primevue/colorpicker';
import InputNumber from 'primevue/inputnumber';
import MultiSelect from 'primevue/multiselect';

const props = defineProps({
    accountType: Object,
    leverageOptions: Array,
    users: Array,
    loading: Boolean,
})

const isLoading = ref(false);
const visible = ref(false);
const categories = ref(['individual', 'managed']);
const types = ref(['live', 'virtual']);
const leverageOptions = ref(props.leverageOptions);
const selectedLeverages = ref([]);
// const selectedUser = ref();

const openDialog = () => {
    visible.value = true;
    form.reset();
    // selectedUser.value = null;
    // getAccountTypeUsers();
    const leverageOptionsMap = leverageOptions.value;

    // Check if leverage options and form leverages are available
    if (Array.isArray(form.leverages) && Array.isArray(leverageOptionsMap)) {
        // Map form leverages to their respective leverage options based on IDs
        selectedLeverages.value = form.leverages
            .map(id => leverageOptionsMap.find(option => option.id === id)) // Match IDs to options
            .filter(option => option !== undefined); // Remove undefined options
    }

}

const closeDialog = () => {
    visible.value = false;
}

const form = useForm({
    id: props.accountType.id,
    name: props.accountType.name,
    category: props.accountType.category,
    type: props.accountType.type,
    minimum_deposit: Number(props.accountType.minimum_deposit ?? 0),
    leverages: props.accountType.leverages,
    maximum_account_number: props.accountType.maximum_account_number,
    color: props.accountType.color,
    allow_trade: props.accountType.allow_trade,
    // user_access: [],
})

watch([() => form.category, () => form.type], ([category, type]) => {
    if (category === 'managed' || type === 'virtual') {
        form.allow_trade = false;
    }
});

// const getAccountTypeUsers = async () => {
//     isLoading.value = true;

//     try {
//         const response = await axios.get(`/account_type/getAccountTypeUsers?account_type_id=${props.accountType.id}`);
//         selectedUser.value = response.data.users;
//     } catch (error) {
//         console.error('Error getting account type users:', error);
//     } finally {
//         isLoading.value = false;
//     }
// }

const submitForm = () => {
    // if (selectedUser?.value?.length) {
    //     form.user_access = selectedUser.value.map(user => user.value);
    // }
    form.leverages = selectedLeverages.value.map(item => item.id);
    
    form.post(route('account_type.update'), {
        preserveScroll: true,
        onSuccess: () => {
            closeDialog();
            emit('detailsVisible', false);
        },
        onError: (e) => {
            console.log('Error submit form:', e);
        }
    })
}


const emit = defineEmits(['detailsVisible']);
</script>

<template>
    <Button
        variant="gray-text"
        type="button"
        size="sm"
        iconOnly
        pill
        @click="openDialog"
        :disabled="props.loading"
    >
        <IconAdjustmentsHorizontal size="16" stroke-width="1.25" color="#667085" />
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.account_type_setting')"
        class="dialog-xs md:dialog-md"
    >
        <form @submit.prevent="submitForm()">
            <div class="flex flex-col items-center gap-8 self-stretch">
                <div class="flex flex-col items-center gap-3 self-stretch">
                    <div class="self-stretch text-gray-950 text-sm font-bold">
                        {{ $t('public.account_information') }}
                    </div>
                    <div class="w-full flex flex-col gap-1">
                        <div class="grid justify-center items-start content-start gap-3 self-stretch flex-wrap grid-cols-1 md:grid-cols-2 md:gap-5">
                            <div class="flex flex-col items-start gap-1 flex-1">
                                <InputLabel for="name" :invalid="!!form.errors.name">
                                    {{ $t('public.name') }}
                                </InputLabel>
                                <InputText
                                    v-model="form.name"
                                    id="name"
                                    type="text"
                                    class="w-full"
                                    disabled
                                />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="flex flex-col items-start gap-1 flex-1">
                                <InputLabel for="minimum_deposit" :invalid="!!form.errors.minimum_deposit">
                                    {{ $t('public.minimum_deposit') }}
                                </InputLabel>
                                <InputNumber
                                    v-model="form.minimum_deposit"
                                    id="minimum_deposit"
                                    :min="0"
                                    :minFractionDigits="2"
                                    prefix="$ "
                                    class="w-full"
                                    inputClass="w-full py-3 px-4"
                                    placeholder="0"
                                />
                                <InputError :message="form.errors.minimum_deposit" />
                            </div>

                            <div class="flex flex-col items-start gap-1 flex-1">
                                <InputLabel for="category" :value="$t('public.category')" :invalid="!!form.errors.category" />
                                <Select
                                    v-model="form.category"
                                    id="category"
                                    :options="categories"
                                    class="w-full"
                                    :disabled="props.loading"
                                >
                                    <template #value="slotProps">
                                        <div v-if="slotProps.value" class="flex items-center gap-3">
                                            <div class="flex items-center gap-2">
                                                <div>{{ $t('public.' + slotProps.value) }}</div>
                                            </div>
                                        </div>
                                    </template>
                                    <template #option="slotProps">
                                        <div class="flex items-center gap-2">
                                            <div>{{ $t('public.' + slotProps.option) }}</div>
                                        </div>
                                    </template>
                                </Select>
                                <InputError :message="form.errors.category" />
                            </div>
                            <div class="flex flex-col items-start gap-1 flex-1">
                                <InputLabel for="type" :value="$t('public.type')" :invalid="!!form.errors.type" />
                                <Select
                                    v-model="form.type"
                                    id="type"
                                    :options="types"
                                    class="w-full"
                                    :disabled="props.loading"
                                >
                                    <template #value="slotProps">
                                        <div v-if="slotProps.value" class="flex items-center gap-3">
                                            <div class="flex items-center gap-2">
                                                <div>{{ $t('public.' + slotProps.value) }}</div>
                                            </div>
                                        </div>
                                    </template>
                                    <template #option="slotProps">
                                        <div class="flex items-center gap-2">
                                            <div>{{ $t('public.' + slotProps.option) }}</div>
                                        </div>
                                    </template>
                                </Select>
                                <InputError :message="form.errors.type" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-center gap-3 self-stretch">
                    <div class="self-stretch text-gray-950 text-sm font-bold">
                        {{ $t('public.trading_conditions') }}
                    </div>
                    <div class="grid justify-center items-start content-start gap-3 self-stretch flex-wrap grid-cols-1 md:grid-cols-2 md:gap-5">
                        <div class="w-full flex flex-col items-start gap-1 flex-1">
                            <InputLabel for="leverages" :value="$t('public.leverage')" :invalid="!!form.errors.leverages" />
                            <MultiSelect
                                v-model="selectedLeverages"
                                :options="leverageOptions"
                                optionLabel="label"
                                :placeholder="$t('public.select_leverage')"
                                :maxSelectedLabels="3"
                                class="w-full"
                                :invalid="!!form.errors.leverages"
                            >
                                <template #option="{option}">
                                    <div class="flex flex-col">
                                        <span>{{ option.label }}</span>
                                    </div>
                                </template>
                            </MultiSelect>
                            <InputError :message="form.errors.leverages" />
                        </div>
                        <div class="flex flex-col gap-1 items-start self-stretch">
                            <InputLabel
                                for="allow_trade"
                                :value="$t('public.allow_trade')"
                                :invalid="!!form.errors.allow_trade"
                            />
                            <ToggleSwitch
                                v-model="form.allow_trade"
                                :disabled="form.category === 'managed' || form.type === 'virtual'" 
                                :invalid="!!form.errors.allow_trade"
                            />
                            <InputError :message="form.errors.allow_trade" />
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-center gap-3 self-stretch">
                    <div class="self-stretch text-gray-950 text-sm font-bold">
                        {{ $t('public.extra_settings') }}
                    </div>
                    <div class="grid justify-center items-start content-start gap-3 self-stretch flex-wrap grid-cols-1 md:grid-cols-2 md:gap-5">
                        <div class="flex flex-col items-start gap-1 flex-1">
                            <InputLabel for="maximum_account_number" :value="$t('public.maximum_account')" :invalid="!!form.errors.maximum_account_number" />
                            <InputText
                                v-model="form.maximum_account_number"
                                id="maximum_account_number"
                                type="number"
                                class="w-full"
                                placeholder="0"
                                :disabled="props.loading"
                            />
                            <InputError :message="form.errors.maximum_account_number" />
                        </div>
                        <!-- <div class="flex flex-col items-start gap-1 flex-1">
                            <InputLabel for="user_access" :value="$t('public.access_to')" :invalid="!!form.errors.user_access"/>
                            <MultiSelect
                                v-model="selectedUser"
                                :options="props.users"
                                :placeholder="$t('public.select_user')"
                                filter
                                :filterFields="['name', 'email', 'id_number']"
                                :maxSelectedLabels="1"
                                :selectedItemsLabel="`${selectedUser?.length} ${$t('public.users_selected')}`"
                                class="w-full md:w-64 font-normal"
                                :disabled="isLoading"
                            >
                                <template #option="{option}">
                                    <div class="flex flex-col">
                                        <span>{{ option.name }}</span>
                                        <span class="text-xs text-gray-400 max-w-52 truncate">{{ option.email }}</span>
                                    </div>
                                </template>
                                <template #value>
                                    <div v-if="selectedUser?.length === 1">
                                        <span>{{ selectedUser[0].name }}</span>
                                    </div>
                                    <span v-else-if="selectedUser?.length > 1">
                                        {{ selectedUser?.length }} {{ $t('public.users_selected') }}
                                    </span>
                                    <span v-else class="text-gray-400">
                                        {{ $t('public.select_user') }}
                                    </span>
                                </template>
                            </MultiSelect>
                            <InputError :message="form.errors.color" />
                        </div> -->
                        <div class="flex flex-col items-start gap-1 flex-1">
                            <InputLabel for="color" :value="$t('public.color')" :invalid="!!form.errors.color"/>
                            <ColorPicker v-model="form.color" id="Color"/>
                            <InputError :message="form.errors.color" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="pt-5 md:pt-7 flex flex-col items-end self-stretch">
                <Button
                    variant="primary-flat"
                    :disabled="form.processing || props.loading || isLoading"
                >
                    {{ $t('public.save') }}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
