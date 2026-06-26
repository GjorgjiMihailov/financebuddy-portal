<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import CompanyForm from '@/components/company/CompanyForm.vue';
import type { Company } from '@/types';

const props = defineProps<{
    company: Company;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Компании', href: '/companies' },
            { title: 'Уреди', href: '#' },
        ],
    },
});

const form = useForm({
    name: props.company.name,
    tax_id: props.company.tax_id,
    is_vat_registered: props.company.is_vat_registered,
    vat_number: props.company.vat_number ?? '',
    address: props.company.address ?? '',
    email: props.company.email ?? '',
    phone: props.company.phone ?? '',
});

function submit() {
    form.put(`/companies/${props.company.id}`);
}
</script>

<template>
    <Head :title="`Уреди — ${company.name}`" />

    <div class="mx-auto max-w-2xl p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Уреди компанија</h1>
            <p class="text-sm text-muted-foreground">{{ company.name }}</p>
        </div>

        <CompanyForm :form="form" @submit="submit" />
    </div>
</template>
