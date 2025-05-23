<script setup>
import { computed } from 'vue'
import { generalFormat } from "@/Composables/format.js"
import { MemberIcon, IBIcon, UserIcon } from '@/Components/Icons/solid';
import { trans, wTrans } from "laravel-vue-i18n";
import { Card, ProgressSpinner } from "primevue";

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
    <Card
      v-for="(item, index) in dataOverviews"
      :key="index"
      class="w-full"
    >
      <template #content>
        <div class="flex justify-center items-center gap-5 self-stretch">
          <component :is="item.icon" class="w-12 h-12 grow-0 shrink-0" />
          <div class="flex flex-col items-start gap-1 w-full">
            <div class="text-surface-950 dark:text-white text-lg md:text-2xl font-semibold">
              {{ formatAmount(item.total, 0, '') }}
            </div>
            <span class="text-surface-500 dark:text-white text-xs md:text-sm">{{ item.label }}</span>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>
