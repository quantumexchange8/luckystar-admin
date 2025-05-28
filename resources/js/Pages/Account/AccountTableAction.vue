<script setup>
import {Button, Dialog, TieredMenu} from "primevue";
import {
    IconDatabaseEdit,
    IconDotsVertical,
    IconListSearch,
    IconReport,
    IconSettingsDollar
} from "@tabler/icons-vue";
import {computed, h, ref} from "vue";
import AccountDetail from "@/Pages/Account/Partials/AccountDetail.vue";
import AccountAdjustment from "@/Pages/Member/Account/Partials/AccountAdjustment.vue";
import AccountReport from "@/Pages/Account/Partials/AccountReport.vue";

const props = defineProps({
    account: Object,
})

const menu = ref();
const visible = ref(false)
const dialogType = ref('')

const items = ref([
    {
        label: 'account_details',
        icon: h(IconListSearch),
        command: () => {
            visible.value = true;
            dialogType.value = 'account_details';
        },
    },
    {
        label: 'account_report',
        icon: h(IconReport),
        command: () => {
            visible.value = true;
            dialogType.value = 'account_report';
        },
    },
    {
        label: 'account_balance',
        icon: h(IconDatabaseEdit),
        command: () => {
            visible.value = true;
            dialogType.value = 'account_balance';
        },
    },
    {
        label: 'account_credit',
        icon: h(IconSettingsDollar),
        command: () => {
            visible.value = true;
            dialogType.value = 'account_credit';
        },
    },
]);

const filteredItems = computed(() => {
    return items.value.filter(item => {
        if (item.label === 'change_password' && props.account.account_type.type === 'virtual') {
            return false;
        }

        if (['account_balance', 'account_credit'].includes(item.label)) {
            if (props.account.trading_master || props.account.has_active_or_pending_subscriptions) {
                return false;
            }
        }

        return true;
    });
});

const toggle = (event) => {
    menu.value.toggle(event);
};
</script>

<template>
    <Button
        type="button"
        severity="secondary"
        icon="IconDotsVertical"
        size="small"
        rounded
        text
        @click="toggle"
        aria-haspopup="true"
        aria-controls="overlay_tmenu"
    >
        <template #icon>
            <IconDotsVertical size="16" stroke-width="1.5" />
        </template>
    </Button>

    <TieredMenu ref="menu" id="overlay_tmenu" :model="filteredItems" popup>
        <template #item="{ item, props, hasSubmenu }">
            <div
                class="flex items-center gap-3 self-stretch"
                v-bind="props.action"
            >
                <component :is="item.icon" size="20" color="#71717a" stroke-width="1.5" />
                <span class="font-medium">{{ $t(`public.${item.label}`) }}</span>
            </div>
        </template>
    </TieredMenu>

    <Dialog
        v-model:visible="visible"
        modal
        :header="dialogType === 'account_balance' || dialogType === 'account_credit' ? $t(`public.${dialogType + '_adjustment'}`) : `${$t(`public.${dialogType}`)} #${account.meta_login}`"
        :class="{
            'dialog-xs md:dialog-sm': dialogType !== 'account_report',
            'dialog-xs md:dialog-md': dialogType === 'account_report',
        }"
    >
        <template v-if="dialogType === 'account_details'">
            <AccountDetail
                :account="account"
            />
        </template>

        <template v-if="dialogType === 'account_report'">
            <AccountReport
                :account="account"
            />
        </template>

        <template v-if="dialogType === 'account_balance'|| dialogType === 'account_credit' ">
            <AccountAdjustment
                :account="account"
                :dialogType="dialogType"
                @update:visible="visible = $event"
            />
        </template>
    </Dialog>
</template>
