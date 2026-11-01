<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ProfitMasterFeed from '@/Components/Common/ProfitMasterFeed.vue';

defineProps({
    initialSearch: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <AdminLayout
        title="Materi Promosi"
        subtitle="Kelola materi promosi dan rekap profit Master Trader untuk kebutuhan promosi para member & afiliator."
        :breadcrumbs="[{ label: 'Program Kemitraan' }, { label: 'Materi Promosi' }]"
    >
        <div class="py-2">
            <ProfitMasterFeed
                :can-manage="true"
                feed-url="/admin/profit-master/feed"
                action-url="/admin/profit-master"
                :initial-search="initialSearch"
            />
        </div>
    </AdminLayout>
</template>
