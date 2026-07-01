<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BarChart2 } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Извештаи', href: '/reports' }],
    },
});
</script>

<template>
    <Head title="Извештаи" />
    <div class="flex flex-col items-center justify-center gap-4 p-12 text-center">
        <div class="flex size-16 items-center justify-center rounded-full bg-muted">
            <BarChart2 class="size-8 text-muted-foreground" />
        </div>
        <h1 class="text-2xl font-semibold">Извештаи</h1>
        <p class="max-w-sm text-muted-foreground">
            Бруто биланс, аналитичка и синтетичка картичка по конто, картичка по клиент — со избор на период (датум од / до).
        </p>
        <p class="text-sm font-medium text-primary">Наскоро достапно</p>
    </div>
</template>
