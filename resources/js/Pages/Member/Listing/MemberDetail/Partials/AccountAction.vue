<script setup>
import {
    TieredMenu,
    Dialog,
    useConfirm,
    Button,
} from "primevue";
import {
    IconDots,
    IconTrash,
    IconDatabaseEdit,
    IconSettingsDollar,
    IconScale,
    IconKey,
    IconListSearch
} from "@tabler/icons-vue";
import {computed, h, ref} from "vue";
import AccountAdjustment from "@/Pages/Member/Account/Partials/AccountAdjustment.vue";
import ChangeLeverage from "@/Pages/Member/Account/Partials/ChangeLeverage.vue";
import ChangePassword from "@/Pages/Member/Account/Partials/ChangePassword.vue";
import { trans } from "laravel-vue-i18n";
import { router } from "@inertiajs/vue3";
import AccountDetail from "@/Pages/Account/Partials/AccountDetail.vue";

const props = defineProps({
    account: Object,
})

const menu = ref();
const visible = ref(false);
const dialogType = ref('');
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
    {
        label: 'change_leverage',
        icon: h(IconScale),
        command: () => {
            visible.value = true;
            dialogType.value = 'change_leverage';
        },
    },
    {
        label: 'change_password',
        icon: h(IconKey),
        command: () => {
            visible.value = true;
            dialogType.value = 'change_password';
        },
    },
]);

const filteredItems = computed(() => {
    return items.value.filter(item => {
        if (item.label === 'change_password' && props.account.account_type.type === 'virtual') {
            return false;
        }

        if (['account_balance', 'account_credit', 'change_leverage'].includes(item.label)) {
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

const confirm = useConfirm();

const requireConfirmation = (action_type) => {
    const messages = {
        delete_account: {
            group: 'headless-error',
            header: trans('public.delete_trading_account_header'),
            text: trans('public.delete_trading_account_message'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete_confirm'),
            action: () => {
                router.delete(route('member.accountDelete'), {
                    data: {
                        meta_login: props.account.meta_login,
                    },
                })
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
</script>

<template>
    <div class="flex items-center justify-center gap-2">
        <Button
            type="button"
            severity="secondary"
            text
            size="small"
            icon="IconDots"
            rounded
            @click="toggle"
            aria-haspopup="true"
            aria-controls="overlay_tmenu"
        >
            <IconDots size="16" stroke-width="1.5" />
        </Button>

        <TieredMenu ref="menu" id="overlay_tmenu" :model="filteredItems" popup>
            <template #item="{ item, props, hasSubmenu }">
                <div
                    class="flex items-center gap-3 self-stretch"
                    v-bind="props.action"
                >
                    <component :is="item.icon" size="20" stroke-width="1.5" :color="item.label === 'delete_account' ? '#F04438' : '#71717a'" />
                    <span class="font-medium" :class="{'text-red-500': item.label === 'delete_account'}">{{ $t(`public.${item.label}`) }}</span>
                </div>
            </template>
        </TieredMenu>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="dialogType === 'account_balance' || dialogType === 'account_credit' ? $t(`public.${dialogType + '_adjustment'}`) : $t(`public.${dialogType}`)"
        class="dialog-xs sm:dialog-sm"
    >
        <template v-if="dialogType === 'account_details'">
            <AccountDetail
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
        <template v-if="dialogType === 'change_leverage'">
            <ChangeLeverage
                :account="account"
                @update:visible="visible = false"
            />
        </template>
        <template v-if="dialogType === 'change_password'">
            <ChangePassword
                :account="account"
                @update:visible="visible = false"
            />
        </template>
    </Dialog>
</template>
