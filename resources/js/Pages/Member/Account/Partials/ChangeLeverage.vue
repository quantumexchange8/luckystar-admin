<script setup>
import { useForm } from "@inertiajs/vue3";
import {
    Button,
    Select,
} from "primevue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import { ref, watch } from "vue";

const props = defineProps({
    account: Object,
})

const isLoading = ref(false);
const leverages = ref([]);
const selectedLeverage = ref();
const emit = defineEmits(['update:visible'])

const form = useForm({
    meta_login: props.account.meta_login,
    leverage: props.account.margin_leverage,
})

const getOptions = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get(route('getLeverages', props.account.account_type_id));
        leverages.value = response.data.leverages;
        selectedLeverage.value = leverages.value.find(leverage => leverage.setting_leverage?.value === props.account.margin_leverage) || null;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        isLoading.value = false;
    }
};

getOptions();

const submitForm = () => {
    form.leverage = selectedLeverage.value.setting_leverage.value;
    form.post(route('member.updateLeverage'), {
        onSuccess: () => {
            closeDialog();
        }
    });
}

const closeDialog = () => {
    emit('update:visible', false)
}
</script>

<template>
    <form>
        <div class="flex flex-col items-center gap-8 self-stretch md:gap-10">
            <div class="flex flex-col items-center gap-5 self-stretch">
                <div class="flex flex-col justify-center items-center py-4 px-8 gap-2 self-stretch bg-surface-200">
                    <span class="w-full text-surface-500 text-center text-xs font-medium">#{{ account.meta_login }} - {{ $t('public.available_account_balance') }}</span>
                    <span class="w-full text-surface-950 text-center text-xl font-semibold">$ {{ account.balance }}</span>
                </div>

                <!-- input fields -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="leverage" :value="$t('public.leverage')" />
                    <Select
                        v-model="selectedLeverage"
                        id="leverage"
                        :options="leverages"
                        class="w-full"
                        :placeholder="$t('public.select_leverage')"
                        :invalid="!!form.errors.leverage"
                        :loading="isLoading"
                        :disabled="!leverages.length"
                    >
                        <template #value="{ value }">
                            <div v-if="value" class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <div>{{ value.setting_leverage.label }}</div>
                                </div>
                            </div>
                        </template>
                        <template #option="{ option }">
                            <div class="flex items-center gap-2">
                                <div>{{ option.setting_leverage.label }}</div>
                            </div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.leverage" />
                </div>
            </div>
        </div>
        <div class="flex justify-end items-center pt-5 gap-4 self-stretch sm:pt-7">
            <Button
                type="button"
                severity="secondary"
                class="w-full md:w-[120px]"
                @click.prevent="closeDialog()"
                :disabled="form.processing"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                class="w-full md:w-[120px]"
                @click.prevent="submitForm"
                :disabled="form.processing"
            >
                {{ $t('public.confirm') }}
            </Button>
        </div>
    </form>
</template>
