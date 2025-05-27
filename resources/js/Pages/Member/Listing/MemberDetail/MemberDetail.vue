<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { IconChevronRight } from '@tabler/icons-vue';
import {ref, h, watch, watchEffect} from "vue";
import { Tab, TabList, TabPanel, TabPanels, Tabs, Button } from "primevue";
import {usePage} from '@inertiajs/vue3';
import FinancialInfo from "@/Pages/Member/Listing/MemberDetail/FinancialInfo.vue";
import TradingAccount from "@/Pages/Member/Listing/MemberDetail/TradingAccount.vue";
import ProfileInfo from "@/Pages/Member/Listing/MemberDetail/ProfileInfo.vue";
import KycVerification from "@/Pages/Member/Listing/MemberDetail/KycVerification.vue";
import PaymentAccount from "@/Pages/Member/Listing/MemberDetail/PaymentAccount.vue";
import AdjustmentHistory from "@/Pages/Member/Listing/MemberDetail/AdjustmentHistory.vue";

const props = defineProps({
    user: Object
})

const isLoading = ref(false);
const userDetail = ref();
const paymentAccounts = ref();
const kycIdentity = ref();
const kycResidency = ref();

const getUserData = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/member/getUserData?id=` + props.user.id);

        userDetail.value = response.data.userDetail;
        paymentAccounts.value = response.data.paymentAccounts;
        kycIdentity.value = response.data.proof_of_identity;
        kycResidency.value = response.data.proof_of_residency;
    } catch (error) {
        console.error('Error get network:', error);
    } finally {
        isLoading.value = false;
    }
};

getUserData();

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getUserData();
    }
});

const tabs = ref([
    {
        title: 'financial_info',
        component: h(FinancialInfo, {user_id: props.user.id}),
        value: 0
    },
    {
        title: 'trading_accounts',
        component: h(TradingAccount, {user_id: props.user.id}),
        value: 1
    },
    {
        title: 'payment_account',
        component: h(PaymentAccount, {user_id: props.user.id}),
        value: 2
    },
    // {
    //     title: 'adjustment_history',
    //     component: h(AdjustmentHistory, {user_id: props.user.id}),
    //     value: 2
    // },
]);

const activeIndex = ref(0);
</script>

<template>
    <AuthenticatedLayout :title="$t('public.member_listing')">
        <div class="flex flex-col gap-5">

            <!-- Breadcrumb -->
            <div class="flex flex-wrap md:flex-nowrap items-center gap-2 self-stretch">
                <Button
                    text
                    size="small"
                    as="a"
                    :href="route('member.listing')"
                >
                    {{ $t('public.member_listing') }}
                </Button>
                <IconChevronRight
                    :size="16"
                    stroke-width="1.25"
                    class="dark:text-white"
                />
                <span class="flex px-4 py-2 text-surface-400 dark:text-surface-200 items-center justify-center text-sm font-medium">{{ userDetail?.name }} - {{ $t('public.view_member_details') }}</span>
            </div>

            <!-- Profile Info -->
            <div class="flex flex-col xl:flex-row items-center w-full gap-5 self-stretch">
                <ProfileInfo
                    :userDetail="userDetail"
                />
                <div class="flex flex-col w-full gap-5 self-stretch">
                    <KycVerification
                        :userDetail="userDetail"
                        :kycIdentity="kycIdentity"
                        :kycResidency="kycResidency"
                        :isLoading="isLoading"
                    />
                </div>
            </div>

            <Tabs v-model:value="activeIndex">
                <TabList>
                    <Tab
                        v-for="tab in tabs"
                        :key="tab.title"
                        :value="tab.value"
                    >
                        {{ $t(`public.${tab.title}`) }}
                    </Tab>
                </TabList>
                <TabPanels>
                    <TabPanel
                        v-for="tab in tabs"
                        :key="tab.value"
                        :value="tab.value"
                        class="flex flex-col gap-5"
                    >
                        <component v-if="activeIndex === tab.value" :is="tab.component" />
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
