<script setup>
import Button from '@/Components/Button.vue';
import { IconRefresh } from '@tabler/icons-vue';
import DataTable from 'primevue/datatable';
import { onMounted, ref, watchEffect } from 'vue';
import Column from 'primevue/column';
import Empty from '@/Components/Empty.vue';
import Loader from "@/Components/Loader.vue";
import { usePage } from '@inertiajs/vue3';
import AccountTypeAction from '@/Pages/AccountType/Partials/AccountTypeAction.vue';

const props = defineProps({
    leverageOptions: Array,
})

const accountTypes = ref();
const loading = ref(false);

const getAccountTypes = async () => {
    loading.value = true;

    try {
        const response = await axios.get(route('account_type.getAccountTypes'));
        accountTypes.value = response.data.accountTypes;
    } catch (error) {
        console.error('Error getting account types:', error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    getAccountTypes();
})

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getAccountTypes();
    }
});

const visibleDetails = ref(false);
const selected_row = ref();
const rowClicked = (data) => {
    if (window.innerWidth < 768) {
        visibleDetails.value = true;
        selected_row.value = data;
    }
}
</script>

<template>
    <div class="w-full flex flex-col items-center">
        <div
            class="py-6 px-4 md:p-6 flex flex-col justify-center items-center gap-6 self-stretch rounded-2xl border border-solid border-gray-200 dark:border-gray-600 bg-white dark:bg-surface-900 shadow-table">
            <DataTable
                :value="accountTypes"
                removableSort
                :loading="loading"
                @row-click="rowClicked($event.data)"
                :paginator="accountTypes?.length > 0"
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
            >
                <template #empty>
                    <Empty :title="$t('public.no_account_type_header')"
                            :message="$t('public.no_account_type_caption')"/>
                </template>

                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700 dark:text-white">{{ $t('public.loading_account_type') }}</span>
                    </div>
                </template>

                <template v-if="accountTypes?.length > 0">
                    <Column field="name" sortable class="hidden md:table-cell">
                        <template #header>
                            <span class="w-10 truncate sm:w-auto">{{ $t('public.name') }}</span>
                        </template>
                        <template #body="slotProps">
                            <span class="w-20 truncate inline-block sm:w-auto">{{ slotProps.data.name }}</span>
                        </template>
                    </Column>
                    <Column field="max_acc" class="hidden md:table-cell">
                        <template #header>
                            <span>{{ $t('public.max_account') }}</span>
                        </template>
                        <template #body="slotProps">
                            {{ slotProps.data.maximum_account_number }}
                        </template>
                    </Column>
                    <Column field="category" class="hidden md:table-cell">
                        <template #header>
                            <span>{{ $t('public.category') }}</span>
                        </template>
                        <template #body="slotProps">
                            {{ $t('public.' + slotProps.data.category) }}
                        </template>
                    </Column>
                    <Column field="type" class="hidden md:table-cell">
                        <template #header>
                            <span>{{ $t('public.type') }}</span>
                        </template>
                        <template #body="slotProps">
                            {{ $t('public.' + slotProps.data.type) }}
                        </template>
                    </Column>
                    <Column field="total_acc" sortable bodyClass="text-center md:text-left" class="hidden md:table-cell">
                        <template #header>
                            <span class="w-14 truncate sm:w-auto">{{ $t('public.total_account') }}</span>
                        </template>
                        <template #body="slotProps">
                            {{ slotProps.data.total_account }}
                        </template>
                    </Column>
                    <Column field="action" class="hidden md:table-cell">
                        <template #body="slotProps">
                            <div class="py-2 px-3 flex justify-center items-center gap-2 flex-1">
                                <AccountTypeAction 
                                    :accountType="slotProps.data" 
                                    :leverageOptions="props.leverageOptions"
                                    :users="props.users" 
                                    :loading="loading"
                                />
                            </div>
                        </template>
                    </Column>
                    <Column class="md:hidden" headerClass="hidden">
                        <template #body="slotProps">
                            <div class="flex flex-col items-start gap-1 self-stretch">
                                <div class="flex items-center gap-2 self-stretch w-full">
                                    <div class="flex items-center w-full">
                                        <div class="flex flex-col items-start">
                                            <span class="font-semibold">{{ slotProps.data.name }}</span>
                                            <div class="text-sm">
                                                {{ `${$t('public.total_account')}:&nbsp;${slotProps.data.total_account}` }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-end">
                                        <AccountTypeAction 
                                            :accountType="slotProps.data" 
                                            :leverageOptions="props.leverageOptions"
                                            :users="props.users" 
                                            :loading="loading"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>
    </div>
</template>
