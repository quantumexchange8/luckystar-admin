<script setup>
import {Button,
    Dialog,
    Stepper,
    StepList,
    StepPanels,
    Step,
    StepPanel,
    InputText,
    InputNumber,
    DataTable,
    Column,
    Select,
    ColorPicker
} from "primevue";
import {IconPlus, IconArrowNarrowLeft, IconArrowNarrowRight} from "@tabler/icons-vue";
import {ref, watch} from "vue";
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

const visible = ref(false);
const loadingUsers = ref(false);
const loadingRanks = ref(false);
const users = ref([]);
const selectedUser = ref();
const total_group_members = ref(0);
const colorHex = ref('');
const ranks = ref([]);

const getRandomColorHex = () => {
    const letters = '0123456789ABCDEF';
    let color = '';
    for (let i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
};

const openDialog = () => {
    visible.value = true;
    colorHex.value = getRandomColorHex();
    getAvailableLeader();
    getSettingRanks();
}

const getAvailableLeader = async () => {
    loadingUsers.value = true;
    try {
        const response = await axios.get('/getAvailableLeader');
        users.value = response.data;
    } catch (error) {
        console.error('Error fetching users:', error);
    } finally {
        loadingUsers.value = false;
    }
};

const getSettingRanks = async () => {
    loadingRanks.value = true;
    try {
        const response = await axios.get('/getSettingRanks');
        ranks.value = response.data;
        form.rank_settings = response.data.reduce((acc, rank) => {
            acc[rank.id] = { ...rank };
            return acc;
        }, {});
    } catch (error) {
        console.error('Error fetching users:', error);
    } finally {
        loadingRanks.value = false;
    }
};

const onCellEditComplete = (event) => {
    let { data, newValue, field } = event;

    switch (field) {
        case 'lot_rebate_amount':
            data[field] = newValue;
            break;

        case 'min_group_sales':
            data[field] = newValue;
            break;

        default:
            if (newValue.trim().length > 0) data[field] = newValue;
            else event.preventDefault();
            break;
    }

    form.rank_settings[data.id] = { ...data };
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
}

watch(selectedUser, () => {
    total_group_members.value = selectedUser.value.total_group_members;
})

const form = useForm({
    name: '',
    color: '',
    leader: '',
    rank_settings: {}
})

const submitForm = () => {
    form.color = colorHex.value;
    form.leader = selectedUser.value;
    form.post(route('group.addGroup'), {
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
    <Button
        type="button"
        class="flex gap-3 items-center"
        @click="openDialog"
    >
        <IconPlus size="20" stroke-width="1.5" />
        {{ $t('public.create_group') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.create_group')"
        class="dialog-xs md:dialog-md"
    >
        <Stepper value="1" linear>
            <StepList>
                <Step value="1">{{ $t('public.basics') }}</Step>
                <Step value="2">{{ $t('public.rank_settings') }}</Step>
            </StepList>
            <StepPanels>
                <StepPanel v-slot="{ activateCallback }" value="1">
                    <div class="flex flex-col gap-6 items-center self-stretch">
                        <div class="flex flex-col gap-3 items-center self-stretch">
                            <span class="font-bold text-sm text-surface-950 dark:text-white w-full text-left">{{ $t('public.basics') }}</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                                <div class="flex flex-col items-start gap-1 self-stretch">
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
                                        :placeholder="$t('public.enter_group_name')"
                                        :invalid="!!form.errors.name"
                                        autofocus
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="color"
                                        :value="$t('public.color')"
                                        :invalid="!!form.errors.color"
                                    />
                                    <div class="flex items-center gap-2 w-full">
                                        <ColorPicker v-model="colorHex" id="color" />
                                        <InputText
                                            id="color"
                                            type="text"
                                            class="block w-full uppercase"
                                            v-model="colorHex"
                                            :placeholder="$t('public.hex_color_placeholder')"
                                            :invalid="!!form.errors.color"
                                            autocomplete="off"
                                        />
                                    </div>

                                    <InputError :message="form.errors.color" />
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 items-center self-stretch">
                            <span class="font-bold text-sm text-surface-950 dark:text-white w-full text-left">{{ $t('public.leader') }}</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 w-full">
                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="leader"
                                        :value="$t('public.leader')"
                                        :invalid="!!form.errors.leader"
                                    />
                                    <Select
                                        v-model="selectedUser"
                                        :options="users"
                                        filter
                                        optionLabel="name"
                                        :placeholder="$t('public.select_leader')"
                                        class="w-full"
                                        :loading="loadingUsers"
                                        :invalid="!!form.errors.leader"
                                    >
                                        <template #value="slotProps">
                                            <div v-if="slotProps.value" class="flex items-center">
                                                <div>{{ slotProps.value.full_name }}</div>
                                            </div>
                                            <span v-else>{{ slotProps.placeholder }}</span>
                                        </template>
                                        <template #option="slotProps">
                                            <div class="flex flex-col max-w-[220px] truncate">
                                                <span class="text-xs">{{ slotProps.option.full_name }}</span>
                                                <span class="text-xs text-surface-500">@{{ slotProps.option.username }}</span>
                                            </div>
                                        </template>
                                    </Select>
                                    <InputError :message="form.errors.leader" />
                                </div>

                                <div class="flex flex-col items-start gap-1 self-stretch">
                                    <InputLabel
                                        for="total_group_members"
                                    >
                                        {{ $t('public.total_group_members') }}
                                    </InputLabel>
                                    <InputText
                                        id="name"
                                        type="text"
                                        class="block w-full"
                                        disabled
                                        v-model="total_group_members"
                                        :placeholder="$t('public.total_group_members')"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex pt-6 justify-end">
                        <Button
                            type="button"
                            :disabled="form.processing"
                            @click="activateCallback('2')"
                        >
                            {{ $t('public.next') }}
                            <IconArrowNarrowRight size="20" stroke-witdth="1.5" />
                        </Button>
                    </div>
                </StepPanel>
                <StepPanel v-slot="{ activateCallback }" value="2">
                    <DataTable
                        :value="ranks"
                        editMode="cell"
                        @cell-edit-complete="onCellEditComplete"
                    >
                        <template #header>

                        </template>
                        <Column field="rank_name" :header="$t('public.rank')">
                            <template #body="{data}">
                                {{ $t(`public.${data.rank_name}`) }}
                            </template>
                        </Column>
                        <Column
                            field="lot_rebate_amount"
                            :header="$t('public.rebate')"
                        >
                            <template #body="{ data, field }">
                                {{ formatCurrency(data[field]) }}
                            </template>
                            <template #editor="{ data, field }">
                                <InputNumber
                                    v-model="data[field]"
                                    autofocus
                                    fluid
                                    class="max-w-[100px]"
                                    mode="currency"
                                    currency="USD"
                                    size="sm"
                                />
                            </template>
                        </Column>
                        <Column
                            field="min_group_sales"
                            :header="$t('public.group_sales')"
                        >
                            <template #body="{ data, field }">
                                {{ formatCurrency(data[field]) }}
                            </template>
                            <template #editor="{ data, field }">
                                <InputNumber
                                    v-model="data[field]"
                                    autofocus
                                    fluid
                                    class="max-w-[150px]"
                                    mode="currency"
                                    currency="USD"
                                />
                            </template>
                        </Column>
                    </DataTable>
                    <div class="text-xs mt-1.5 text-surface-500 dark:text-surface-400">
                        {{ $t('public.click_to_edit_note') }}
                    </div>
                    <div class="pt-8 flex justify-between items-center self-stretch w-full">
                        <Button
                            type="button"
                            severity="secondary"
                            :disabled="form.processing"
                            @click="activateCallback('1')"
                        >
                            <IconArrowNarrowLeft size="20" stroke-witdth="1.5" />
                            {{ $t('public.back') }}
                        </Button>

                        <Button
                            type="submit"
                            @click="submitForm"
                            :disabled="form.processing"
                            class="w-full md:w-auto"
                        >
                            {{ $t('public.create') }}
                        </Button>
                    </div>
                </StepPanel>
            </StepPanels>
        </Stepper>
    </Dialog>
</template>
