<script setup>
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
import SidebarCollapsible from '@/Components/Sidebar/SidebarCollapsible.vue'
import SidebarCollapsibleItem from '@/Components/Sidebar/SidebarCollapsibleItem.vue'
import { sidebarState } from '@/Composables'
import {onMounted, ref, watchEffect} from "vue";
import {usePage} from "@inertiajs/vue3";
import {
    IconLayoutDashboard,
    IconUsers,
    IconUsersGroup,
    IconTemplate,
    IconCategory,
    IconCoinMonero,
    IconHistory,
    IconClockDollar,
    IconAdjustmentsDollar,
    IconTag,
    IconClipboardData,
    IconPhotoCog,
    IconChartPie,
    IconId,
    IconServerCog,
    IconSettingsDollar
} from '@tabler/icons-vue';
import SidebarCategoryLabel from "@/Components/Sidebar/SidebarCategoryLabel.vue";
//
// const page = usePage();
// const pendingDepositCounts = ref(page.props.getPendingDepositCount);
// const pendingWithdrawalCounts = ref(page.props.pendingWithdrawalCounts);
//
// const getPendingCounts = async () => {
//     try {
//         const response = await axios.get(route('dashboard.getPendingCounts'));
//         pendingDepositCounts.value = response.data.pendingDepositCounts;
//         pendingWithdrawalCounts.value = response.data.pendingWithdrawalCounts;
//     } catch (error) {
//         console.error('Error pending counts:', error);
//     }
// };
//
// watchEffect(() => {
//     if (usePage().props.toast !== null) {
//         getPendingCounts();
//     }
// });
</script>

<template>
    <nav
        class="relative flex flex-col flex-1 max-h-full gap-1 items-center"
        :class="{
            'p-3': sidebarState.isOpen || sidebarState.isHovered,
            'px-5 py-3': !sidebarState.isOpen && !sidebarState.isHovered,
        }"
    >
        <!-- Dashboard -->
        <SidebarLink
            :title="$t('public.dashboard')"
            :href="route('dashboard')"
            :active="route().current('dashboard')"
        >
            <template #icon>
                <IconLayoutDashboard :size="20" stroke-width="1.5" />
            </template>
        </SidebarLink>

        <SidebarCategoryLabel
            :title="$t('public.configuration')"
        />

        <!-- Account Type -->
        <SidebarLink
            :title="$t('public.account_type')"
            :href="route('account_type.index')"
            :active="route().current('account_type.index')"
        >
            <template #icon>
                <IconServerCog :size="20" stroke-width="1.5" />
            </template>
        </SidebarLink>

        <!-- Account Type -->
        <SidebarLink
            :title="$t('public.top_up_profile')"
            :href="route('configuration.top_up_profile')"
            :active="route().current('configuration.top_up_profile')"
        >
            <template #icon>
                <IconSettingsDollar :size="20" stroke-width="1.5" />
            </template>
        </SidebarLink>

<!--        <SidebarLink-->
<!--            :title="'Accounts'"-->
<!--            :href="route('account')"-->
<!--            :active="route().current('account')"-->
<!--        >-->
<!--            <template #icon>-->
<!--                <IconId :size="20" stroke-width="1.5" />-->
<!--            </template>-->
<!--        </SidebarLink>-->

<!--        <SidebarLink-->
<!--            :title="'Master'"-->
<!--          -->
<!--        >-->
<!--            <template #icon>-->
<!--                <IconCoinMonero :size="20" stroke-width="1.5" />-->
<!--            </template>-->
<!--        </SidebarLink>-->

<!--        <SidebarLink-->
<!--            :title="'Structure'"-->
<!--          -->
<!--        >-->
<!--            <template #icon>-->
<!--                <IconUsersGroup :size="20" stroke-width="1.5" />-->
<!--            </template>-->
<!--        </SidebarLink>-->

<!--        <SidebarCategoryLabel-->
<!--            :title="'Transaction'"-->
<!--        />-->

<!--        <SidebarLink-->
<!--            :title="'History'"-->
<!--          -->
<!--        >-->
<!--            <template #icon>-->
<!--                <IconClockDollar :size="20" stroke-width="1.5" />-->
<!--            </template>-->
<!--        </SidebarLink>-->
    </nav>
</template>
