<script setup>
import { ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import AddMember from "@/Pages/Member/Listing/Partials/AddMember.vue";
import MemberOverview from "@/Pages/Member/Listing/MemberOverview.vue";
import MemberListView from "@/Pages/Member/Listing/MemberListView.vue";

const props = defineProps({
    groups: Array,
    countries: Array,
})

const totalVerified = ref(null);
const totalUnverified = ref(null);
const totalUsers = ref(null);

const handleUpdateTotals = (data) => {
    totalVerified.value = data.totalVerified;
    totalUnverified.value = data.totalUnverified;
    totalUsers.value = data.totalUsers;
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.member_listing')">
        <div class="flex flex-col gap-5 items-center">
            <div class="flex justify-end w-full">
                <AddMember
                    :groups="groups"
                    :countries="countries"
                />
            </div>

            <!-- data overview -->
            <MemberOverview 
                :totalVerified="totalVerified"
                :totalUnverified="totalUnverified"
                :totalUsers="totalUsers"
            />

            <!-- data table -->
            <MemberListView
                :groups="groups"
                @update-totals="handleUpdateTotals"
            />
        </div>
    </AuthenticatedLayout>
</template>
