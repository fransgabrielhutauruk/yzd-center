<script setup>
import MemberLayout from '@/Layouts/MemberLayout.vue';
import ProfitMasterFeed from '@/Components/Common/ProfitMasterFeed.vue';
import AppPageHeader from '@/Components/Common/AppPageHeader.vue';

defineProps({
    initialSearch: {
        type: String,
        default: '',
    },
    initialStartDate: {
        type: String,
        default: '',
    },
    initialEndDate: {
        type: String,
        default: '',
    },
    todayDate: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <MemberLayout title="Materi Promosi">
        <div class="space-y-6">
            <AppPageHeader
                title="Materi Promosi"
                subtitle="Bahan promosi dan rekap bukti profit Master Trader. Siap salin dan bagikan ke calon mitra Anda."
                :breadcrumbs="[
                    { label: 'Program Kemitraan' },
                    { label: 'Materi Promosi' },
                ]"
            />

            <ProfitMasterFeed
                :can-manage="false"
                feed-url="/member/profit-master/feed"
                :initial-search="initialSearch"
                :initial-start-date="initialStartDate"
                :initial-end-date="initialEndDate"
                :today-date="todayDate"
            />
        </div>
    </MemberLayout>
</template>
