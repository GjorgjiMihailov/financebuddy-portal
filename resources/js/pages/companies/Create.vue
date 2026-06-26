<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import CompanyForm from '@/components/company/CompanyForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Компании', href: '/companies' },
            { title: 'Нова компанија', href: '/companies/create' },
        ],
    },
});

const form = useForm({
    name: '',
    tax_id: '',
    is_vat_registered: false,
    vat_number: '',
    address: '',
    email: '',
    phone: '',
});

function submit() {
    form.post('/companies');
}
</script>

<template>
    <Head title="Нова компанија" />

    <div class="mx-auto max-w-2xl p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Нова компанија</h1>
            <p class="text-sm text-muted-foreground">Додај нов клиент во системот</p>
        </div>

        <CompanyForm :form="form" @submit="submit" />
    </div>
</template>
