<script setup>
import { Button, Card } from "primevue";
import { IconAlertSquareRoundedFilled, IconEdit } from "@tabler/icons-vue";
import { ref, watchEffect } from "vue";
import { generalFormat } from "@/Composables/format.js";
import { usePage } from "@inertiajs/vue3";
import Loader from "@/Components/Loader.vue";
import Empty from "@/Components/Empty.vue";
import EditBeneficiaryInformation from "@/Pages/Member/Listing/MemberDetail/Partials/EditBeneficiaryInformation.vue";

const props = defineProps({
    user_id: Number
})

const { formatAmount, formatDateTime } = generalFormat();
const beneficiaryInfo = ref([]);
const isLoading = ref(false);

const getBeneficiaryInfoData = async () => {
    isLoading.value = true;

    try {
        const response = await axios.get(`/member/getBeneficiaryInfoData?id=${props.user_id}`);
        beneficiaryInfo.value = response.data;
    } catch (error) {
        console.error('Error fetching adjustment history data:', error);
    } finally {
        isLoading.value = false;
    }
}

// getBeneficiaryInfoData();

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getBeneficiaryInfoData();
    }
});
</script>

<template>
    <div class="w-full flex flex-col items-center justify-center gap-5">
        <Loader v-if="isLoading" />

        <template v-else>
            <Card class="w-full">
                <template #title>
                    <div class="flex gap-5 items-center justify-between">
                        {{ $t('public.beneficiary_information') }}
                        <EditBeneficiaryInformation :beneficiary="beneficiaryInfo" :isLoading="isLoading" />
                    </div>
                </template>

                <template #content>
                    <div class="flex flex-col gap-3 items-center">
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.full_name') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">Lucky Star</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.relationship') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">Boss</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.ic_passport_no') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">709854123124</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.email') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">test@test.com</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="text-surface-500 dark:text-surface-300">{{ $t('public.phone_number') }}</div>
                            <span class="text-surface-950 dark:text-white font-medium">60 121412412</span>
                        </div>
                    </div>
                </template>
            </Card>
        </template>
    </div>
</template>