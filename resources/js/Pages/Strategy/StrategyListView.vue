<script setup>
import {InputText, Button, Select} from "primevue";
import {IconSearch, IconCircleXFilled, IconAdjustments} from "@tabler/icons-vue";
</script>

<template>
    <div class="flex flex-col gap-5 items-center self-stretch">
        <!-- Toolbar -->
        <div class="flex flex-col md:flex-row gap-3 items-center self-stretch">
            <div class="relative w-full md:w-60">
                <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                    <IconSearch size="20" stroke-width="1.25" />
                </div>
                <InputText v-model="search" :placeholder="$t('public.keyword_search')" class="font-normal pl-12 w-full md:w-60" />
                <div
                    v-if="search"
                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                    @click="clearSearch"
                >
                    <IconCircleXFilled size="16" />
                </div>
            </div>
            <div class="w-full flex justify-between items-center self-stretch gap-3">
                <Button
                    type="button"
                    severity="secondary"
                    outlined
                    class="flex gap-3 items-center"
                >
                    <IconAdjustments size="20" stroke-width="1.5" />
                    {{ $t('public.filter') }}
                </Button>
                <Button>

                </Button>
            </div>
        </div>
    </div>
</template>
