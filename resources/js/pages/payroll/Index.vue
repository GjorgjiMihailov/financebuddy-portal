<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { DollarSign } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Плати', href: '/payroll' }],
    },
});
</script>

<template>
    <Head title="Плати" />
    <div class="flex flex-col items-center justify-center gap-4 p-12 text-center">
        <div class="flex size-16 items-center justify-center rounded-full bg-muted">
            <DollarSign class="size-8 text-muted-foreground" />
        </div>
        <h1 class="text-2xl font-semibold">Плати</h1>
        <p class="max-w-sm text-muted-foreground">
            Автоматско генерирање на плати, пресметка на придонеси и извозирање на налози за плаќање.
        </p>
        <p class="text-sm font-medium text-primary">Наскоро достапно</p>
    </div>
</template>
