<script setup>
import {Button, Dialog, InputText, Select} from "primevue";
import {IconCirclePlus} from "@tabler/icons-vue";
import {ref, watch} from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import {useForm} from "@inertiajs/vue3";
import SelectChipGroup from "@/Components/SelectChipGroup.vue";
import InputError from "@/Components/InputError.vue";

const visible = ref(false);

const openDialog = () => {
    visible.value = true;
    getCountries();
}

const types = [
    'bank_transfer',
    'crypto_payment',
]

const selectedType = ref('bank_transfer');

watch(selectedType, () => {
    form.reset();
    form.errors = [];
})

const countries = ref([]);
const selectedCountry = ref();
const loadingCountries = ref(false);

const getCountries = async () => {
    loadingCountries.value = true;
    try {
        const response = await axios.get('/get_countries');
        countries.value = response.data.countries;
    } catch (error) {
        console.error('Error fetching countries:', error);
    } finally {
        loadingCountries.value = false;
    }
}

watch(selectedCountry, (country) => {
    form.country_id = country['id'];
    form.currency = country['currency'];
});

const platforms = [
    'ttpay',
]

const selectedPlatform = ref('ttpay');

const form = useForm({
    type: '',
    name: '',
    platform: '',
    payment_app_name: '',
    payment_url: '',
    account_number: '',
    swift_code: '',
    bank_address: '',
    secret_key: '',
    secondary_key: '',
    country_id: '',
    currency: '',
});

const submitForm = () => {
    form.type = selectedType.value;
    form.platform = selectedPlatform.value;
    form.post(route('account_type.addTopUpProfile'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        }
    })
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <div class="flex justify-end items-center w-full">
        <Button
            type="button"
            class="w-full md:w-fit"
            :label="$t('public.add_profile')"
            icon="IconCirclePlus"
            @click="openDialog"
        >
            <template #icon>
                <IconCirclePlus size="20" stroke-width="1.5" />
            </template>
        </Button>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.add_profile')"
        class="dialog-xs md:dialog-md"
    >
        <form class="flex flex-col gap-8 items-center self-stretch">
            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-sm w-full text-left">{{ $t('public.payment_type') }}</span>

                <div class="flex flex-col gap-1 items-start self-stretch">
                    <SelectChipGroup
                        :items="types"
                        v-model="selectedType"
                    />
                    <InputError :message="form.errors.type" />
                </div>
            </div>

            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-sm w-full text-left">{{ $t('public.profile_information') }}</span>

                <!-- Bank Transfer -->
                <div
                    v-if="selectedType === 'bank_transfer'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full"
                >
                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="bank_name"
                            :value="$t('public.bank_name')"
                            :invalid="!!form.errors.name"
                        />
                        <InputText
                            id="bank_name"
                            type="text"
                            class="block w-full"
                            v-model="form.name"
                            :placeholder="$t('public.enter_bank_name')"
                            :invalid="!!form.errors.name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="bank_address"
                            :value="$t('public.bank_address')"
                            :invalid="!!form.errors.bank_address"
                        />
                        <InputText
                            id="bank_address"
                            type="text"
                            class="block w-full"
                            v-model="form.bank_address"
                            :placeholder="$t('public.enter_bank_address')"
                            :invalid="!!form.errors.bank_address"
                        />
                        <InputError :message="form.errors.bank_address" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="payment_app_name"
                            :value="$t('public.account_name')"
                            :invalid="!!form.errors.payment_app_name"
                        />
                        <InputText
                            id="payment_app_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_app_name"
                            :placeholder="$t('public.enter_bank_account_name')"
                            :invalid="!!form.errors.payment_app_name"
                        />
                        <InputError :message="form.errors.payment_app_name" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="account_number"
                            :value="$t('public.account_number')"
                            :invalid="!!form.errors.account_number"
                        />
                        <InputText
                            id="account_number"
                            type="text"
                            class="block w-full"
                            v-model="form.account_number"
                            :placeholder="$t('public.enter_bank_account_number')"
                            :invalid="!!form.errors.account_number"
                        />
                        <InputError :message="form.errors.account_number" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="swift_code"
                            :value="$t('public.swift_code')"
                            :invalid="!!form.errors.swift_code"
                        />
                        <InputText
                            id="swift_code"
                            type="text"
                            class="block w-full"
                            v-model="form.swift_code"
                            :placeholder="$t('public.enter_swift_code')"
                            :invalid="!!form.errors.swift_code"
                        />
                        <InputError :message="form.errors.swift_code" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="country"
                            :value="$t('public.country')"
                            :invalid="!!form.errors.country_id"
                        />
                        <div class="grid grid-cols-2 gap-3 w-full">
                            <Select
                                v-model="selectedCountry"
                                :options="countries"
                                class="w-full"
                                filter
                                :filter-fields="['name', 'iso2']"
                                :placeholder="$t('public.select_country')"
                                :loading="loadingCountries"
                                :invalid="!!form.errors.country_id"
                            >
                                <template #value="{ value, placeholder }">
                                    <div v-if="value" class="flex items-center gap-3">
                                        {{ value.name }}
                                    </div>
                                    <div v-else>
                                        {{ placeholder }}
                                    </div>
                                </template>

                                <template #option="{ option }">
                                    <div class="flex items-center gap-2">
                                        <img
                                            v-if="option.iso2"
                                            :src="`https://flagcdn.com/w40/${option.iso2.toLowerCase()}.png`"
                                            :alt="option.iso2"
                                            width="18"
                                            height="12"
                                        />
                                        <div class="max-w-[200px] truncate">{{ option.name }}</div>
                                    </div>
                                </template>
                            </Select>
                            <InputText
                                id="currency"
                                type="text"
                                class="block w-full"
                                v-model="form.currency"
                                :placeholder="$t('public.currency')"
                                disabled
                                :invalid="!!form.errors.currency"
                            />
                        </div>
                        <InputError :message="form.errors.country_id" />
                    </div>
                </div>

                <div
                    v-else-if="selectedType === 'crypto_payment'"
                    class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full"
                >
                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="crypto_payment_name"
                            :value="$t('public.payment_name')"
                            :invalid="!!form.errors.name"
                        />
                        <InputText
                            id="crypto_payment_name"
                            type="text"
                            class="block w-full"
                            v-model="form.name"
                            placeholder="eg. Lucky Star Pay"
                            :invalid="!!form.errors.name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="payment_url"
                            :value="$t('public.payment_url')"
                            :invalid="!!form.errors.payment_url"
                        />
                        <InputText
                            id="payment_url"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_url"
                            placeholder="eg. https://payment_url.com"
                            :invalid="!!form.errors.payment_url"
                        />
                        <InputError :message="form.errors.payment_url" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="platform"
                            :value="$t('public.platform')"
                            :invalid="!!form.errors.platform"
                        />
                        <SelectChipGroup
                            :items="platforms"
                            v-model="selectedPlatform"
                        >
                            <template #option="{ item }">
                                <span class="text-xs uppercase">{{ item }}</span>
                            </template>
                        </SelectChipGroup>
                        <InputError :message="form.errors.platform" />
                    </div>
                </div>
            </div>

            <div
                v-if="selectedType === 'crypto_payment'"
                class="flex flex-col gap-3 items-center self-stretch"
            >
                <span class="font-bold text-sm w-full text-left">{{ $t('public.payment_credentials') }}</span>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 w-full">
                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="payment_app_name"
                            :value="$t('public.payment_app')"
                            :invalid="!!form.errors.payment_app_name"
                        />
                        <InputText
                            id="payment_app_name"
                            type="text"
                            class="block w-full"
                            v-model="form.payment_app_name"
                            placeholder="eg. luckystar-pay"
                            :invalid="!!form.errors.payment_app_name"
                        />
                        <InputError :message="form.errors.payment_app_name" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="secret_key"
                            :value="$t('public.secret_key')"
                            :invalid="!!form.errors.secret_key"
                        />
                        <InputText
                            id="secret_key"
                            type="text"
                            class="block w-full"
                            v-model="form.secret_key"
                            :placeholder="$t('public.enter_secret_key')"
                            :invalid="!!form.errors.secret_key"
                        />
                        <InputError :message="form.errors.secret_key" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="secondary_key"
                            :value="$t('public.secondary_key')"
                            :invalid="!!form.errors.secondary_key"
                        />
                        <InputText
                            id="secondary_key"
                            type="text"
                            class="block w-full"
                            v-model="form.secondary_key"
                            :placeholder="$t('public.enter_secondary_key')"
                            :invalid="!!form.errors.secondary_key"
                        />
                        <InputError :message="form.errors.secondary_key" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center self-stretch gap-3">
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
                    @click.prevent="submitForm"
                />
            </div>
        </form>
    </Dialog>
</template>
