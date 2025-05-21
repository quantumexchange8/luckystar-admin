<script setup>
import {
    Button,
    Dialog,
    TieredMenu
} from "primevue";
import {IconDotsVertical, IconListSearch, IconPencilMinus} from "@tabler/icons-vue";
import ToggleStrategyStatus from "@/Pages/Strategy/Partials/ToggleStrategyStatus.vue";
import {h, ref} from "vue";
import InvestmentReport from "@/Pages/Strategy/Partials/InvestmentReport.vue";

const props = defineProps({
    strategy: Object,
});

const menu = ref();
const visible = ref(false)
const dialogType = ref('')

const items = ref([
    {
        label: 'investment_report',
        icon: h(IconListSearch),
        command: () => {
            visible.value = true;
            dialogType.value = 'investment_report';
        },
    },
    {
        label: 'edit',
        icon: h(IconPencilMinus),
        command: () => {
            visible.value = true;
            dialogType.value = 'edit';
        },
    },
]);

const toggle = (event) => {
    menu.value.toggle(event);
};
</script>

<template>
    <ToggleStrategyStatus
        :strategy="strategy"
    />
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

    <TieredMenu ref="menu" id="overlay_tmenu" :model="items" popup>
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
        :class="{
            'dialog-xs md:dialog-md': dialogType !== 'investment_report',
            'dialog-xs md:dialog-lg': dialogType === 'investment_report'
        }"
    >
        <template #header>
            <div class="font-semibold text-lg">
                {{ $t(`public.${dialogType}`) }}<span v-if="dialogType === 'investment_report'"> - {{ strategy.master_name }}</span>
            </div>
        </template>
        <template v-if="dialogType === 'investment_report'">
            <InvestmentReport
                :strategy="strategy"
                @update:visible="visible = false"
            />
        </template>
    </Dialog>
</template>
