<script setup>
import {
    Button,
    Dialog,
    InputText,
    InputNumber,
    MultiSelect,
    ToggleSwitch,
    ColorPicker,
} from "primevue";
import {
    IconCirclePlus
} from "@tabler/icons-vue";
import {ref} from "vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import SelectChipGroup from "@/Components/SelectChipGroup.vue";

const props = defineProps({
    leverageOptions: Array
})

const visible = ref(false);

const openDialog = () => {
    visible.value = true;
}

const form = useForm({
    name: '',
    category: '',
    type: '',
    minimum_deposit: null,
    leverages: null,
    allow_trade: true,
    color: '34d399',
    maximum_account_number: null,
});

const categories = [
    'individual',
    'managed'
];

const selectedCategory = ref();

const selectCategory = (category) => {
    selectedCategory.value = category;

    if (category === 'managed') {
        form.allow_trade = false;
    }
}

const types = [
    'live',
    'virtual'
];

const selectedType = ref();

const selectType = (type) => {
    selectedType.value = type;

    if (type === 'virtual') {
        form.allow_trade = false;
    }
}

const selectedLeverages = ref([]);

const submitForm = () => {
    form.category = selectedCategory.value;
    form.type = selectedType.value;
    form.leverages = selectedLeverages.value;
    form.post(route('account_type.addAccountType'), {
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
            :label="$t('public.add_account_type')"
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
        :header="$t('public.add_account_type')"
        class="dialog-xs md:dialog-md"
    >
        <form class="flex flex-col gap-8 items-center self-stretch">
            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-sm w-full text-left">{{ $t('public.account_information') }}</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="name"
                            :value="$t('public.name')"
                            :invalid="!!form.errors.name"
                        />
                        <InputText
                            id="name"
                            type="text"
                            class="block w-full"
                            v-model="form.name"
                            :placeholder="$t('public.enter_name')"
                            :invalid="!!form.errors.name"
                            autofocus
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="minimum_deposit"
                            :value="$t('public.minimum_deposit')"
                            :invalid="!!form.errors.minimum_deposit"
                        />
                        <InputNumber
                            v-model="form.minimum_deposit"
                            inputId="minimum_deposit"
                            mode="currency"
                            currency="USD"
                            locale="en-US"
                            fluid
                            placeholder="$0.00"
                            :invalid="!!form.errors.minimum_deposit"
                        />
                        <InputError :message="form.errors.minimum_deposit" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="category"
                            :value="$t('public.category')"
                            :invalid="!!form.errors.category"
                        />
                        <SelectChipGroup
                            :items="categories"
                            :selected-item="selectedCategory"
                            @update:selected="selectCategory"
                        />
                        <InputError :message="form.errors.category" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="type"
                            :value="$t('public.type')"
                            :invalid="!!form.errors.type"
                        />
                        <SelectChipGroup
                            :items="types"
                            :selected-item="selectedType"
                            @update:selected="selectType"
                        />
                        <InputError :message="form.errors.type" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-sm w-full text-left">{{ $t('public.trading_conditions') }}</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="leverages"
                            :value="$t('public.leverage')"
                            :invalid="!!form.errors.leverages"
                        />
                        <MultiSelect
                            v-model="selectedLeverages"
                            :options="leverageOptions"
                            optionLabel="label"
                            :placeholder="$t('public.select_leverage')"
                            :maxSelectedLabels="3"
                            class="w-full"
                            :invalid="!!form.errors.leverages"
                        />
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
                            :invalid="!!form.errors.allow_trade"
                        />
                        <InputError :message="form.errors.allow_trade" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 items-center self-stretch">
                <span class="font-bold text-sm w-full text-left">{{ $t('public.extra_settings') }}</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="maximum_account_number"
                            :value="$t('public.maximum_account')"
                            :invalid="!!form.errors.maximum_account_number"
                        />
                        <InputNumber
                            v-model="form.maximum_account_number"
                            inputId="maximum_account_number"
                            :placeholder="$t('public.enter_maximum_account')"
                            fluid
                            :invalid="!!form.errors.maximum_account_number"
                        />
                        <InputError :message="form.errors.maximum_account_number" />
                    </div>

                    <div class="flex flex-col gap-1 items-start self-stretch">
                        <InputLabel
                            for="color"
                            :value="$t('public.color')"
                            :invalid="!!form.errors.color"
                        />
                        <ColorPicker
                            v-model="form.color"
                            :invalid="!!form.errors.color"
                        />
                        <InputError :message="form.errors.color" />
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
