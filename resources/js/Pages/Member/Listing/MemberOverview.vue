<script setup>
import { generalFormat } from "@/Composables/format.js"
import { Card } from "primevue";
import {IconUsers, IconTrendingDown, IconTrendingUp, IconUserCheck, IconUserX} from "@tabler/icons-vue";

const props = defineProps({
    totalUsers: Number,
    usersTrend: Number,
    verifiedUsers: Number,
    unverifiedUsers: Number,
})

const { formatAmount } = generalFormat()
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 w-full gap-3 md:gap-5">
        <Card class="w-full">
            <template #content>
                <div class="flex gap-3 items-center">
                    <div class="flex flex-col w-full">
                        <span class="text-sm w-full text-surface-400 dark:text-surface-500">{{ $t('public.total_user') }}</span>
                        <div
                            v-if="totalUsers !== null"
                            class="flex gap-2 items-end"
                        >
                            <span class="text-xl font-medium">{{ formatAmount(totalUsers, 0, '') }}</span>
                            <div
                                v-if="usersTrend !== 0"
                                :class="[
                                    'flex gap-1 items-center',
                                    {
                                        'text-green-500': usersTrend > 0,
                                        'text-red-500': usersTrend < 0,
                                    }
                                ]">
                                <IconTrendingUp
                                    v-if="usersTrend > 0"
                                    size="18"
                                    stroke-width="1.5"
                                />
                                <IconTrendingDown
                                    v-else-if="usersTrend < 0"
                                    size="18"
                                    stroke-width="1.5"
                                />
                                {{ formatAmount(usersTrend, 0, '') }} <span class="text-xs text-surface-500">{{ $t('public.compare_last_month') }}</span>
                            </div>
                        </div>
                        <div v-else class="text-xl">
                            {{ $t('public.calculating') }}...
                        </div>
                    </div>
                    <div class="bg-cyan-100 dark:bg-cyan-900/30 rounded-md text-cyan-500 p-2">
                        <IconUsers size="28" stroke-width="1.5" />
                    </div>
                </div>
            </template>
        </Card>

        <Card class="w-full">
            <template #content>
                <div class="flex gap-3 items-center">
                    <div class="flex flex-col w-full">
                        <span class="text-sm w-full text-surface-400 dark:text-surface-500">{{ $t('public.verified') }}</span>
                        <div
                            v-if="verifiedUsers !== null"
                            class="flex gap-2 items-end"
                        >
                            <span class="text-xl font-medium">{{ formatAmount(verifiedUsers, 0, '') }}</span>
                        </div>
                        <div v-else class="text-xl">
                            {{ $t('public.calculating') }}...
                        </div>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900/30 rounded-md text-green-500 p-2">
                        <IconUserCheck size="28" stroke-width="1.5" />
                    </div>
                </div>
            </template>
        </Card>

        <Card class="w-full">
            <template #content>
                <div class="flex gap-3 items-center">
                    <div class="flex flex-col w-full">
                        <span class="text-sm w-full text-surface-400 dark:text-surface-500">{{ $t('public.unverified') }}</span>
                        <div
                            v-if="unverifiedUsers !== null"
                            class="flex gap-2 items-end"
                        >
                            <span class="text-xl font-medium">{{ formatAmount(unverifiedUsers, 0, '') }}</span>
                        </div>
                        <div v-else class="text-xl">
                            {{ $t('public.calculating') }}...
                        </div>
                    </div>
                    <div class="bg-red-100 dark:bg-red-900/30 rounded-md text-red-500 p-2">
                        <IconUserX size="28" stroke-width="1.5" />
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
