<script setup>
import { computed } from 'vue'
import { generalFormat } from "@/Composables/format.js"
import { MemberIcon, IBIcon, UserIcon } from '@/Components/Icons/solid';
import { trans, wTrans } from "laravel-vue-i18n";

const props = defineProps({
  totalVerified: Number,
  totalUnverified: Number,
  totalUsers: Number,
})

const { formatAmount } = generalFormat()

const dataOverviews = computed(() => [
  {
    icon: MemberIcon,
    total: props.totalVerified,
    label: trans('public.total_verified'),
  },
  {
    icon: IBIcon,
    total: props.totalUnverified,
    label: trans('public.total_unverified'),
  },
  {
    icon: UserIcon,
    total: props.totalUsers,
    label: trans('public.total_user'),
  },
])
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-3 w-full gap-3 md:gap-5">
    <div
      v-for="(item, index) in dataOverviews"
      :key="index"
      class="flex justify-center items-center px-6 py-4 md:p-6 gap-5 self-stretch rounded-2xl bg-white dark:bg-gray-700 shadow-toast"
    >
      <component :is="item.icon" class="w-12 h-12 grow-0 shrink-0" />
      <div class="flex flex-col items-start gap-1 w-full">
        <div class="text-gray-950 dark:text-white text-lg md:text-2xl font-semibold">
          {{ formatAmount(item.total, 0, '') }}
        </div>
        <span class="text-gray-500 dark:text-white text-xs md:text-sm">{{ item.label }}</span>
      </div>
    </div>
  </div>
</template>
