<script setup>
import { IconCircleCheckFilled } from "@tabler/icons-vue";

const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
    selectedItem: {
        type: String
    },
});

const emit = defineEmits(['update:selected']);

const selectItem = (item) => {
    emit('update:selected', item);
};
</script>

<template>
    <div class="flex items-start overflow-x-auto gap-3 self-stretch">
        <div
            v-for="item in items"
            :key="item"
            @click="selectItem(item)"
            class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full relative"
            :class="{
            'bg-primary-50 dark:bg-primary-800/40 border-primary': selectedItem === item,
            'bg-white dark:bg-surface-950 border-surface-300 dark:border-surface-700 hover:bg-primary-50 hover:border-primary': selectedItem !== item,
          }"
        >
            <div class="flex items-center self-stretch">
                <div
                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary"
                    :class="{
                        'text-primary-700 dark:text-primary-200': selectedItem === item,
                        'text-surface-950 dark:text-white': selectedItem !== item
                    }"
                >
                    <div class="text-xs uppercase">{{ $t(`public.${item}`) }}</div>
                </div>
                <div
                    v-if="selectedItem === item"
                    class="absolute right-2"
                >
                    <IconCircleCheckFilled
                        size="16"
                        stroke-width="1.5"
                        color="#34d399"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
