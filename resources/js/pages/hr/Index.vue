<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { UserCheck } from '@lucide/vue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Човечки ресурси', href: '/hr' }],
    },
});
</script>

<template>
    <Head title="Човечки ресурси" />
    <div class="flex flex-col items-center justify-center gap-4 p-12 text-center">
        <div class="flex size-16 items-center justify-center rounded-full bg-muted">
            <UserCheck class="size-8 text-muted-foreground" />
        </div>
        <h1 class="text-2xl font-semibold">Човечки ресурси</h1>
        <p class="max-w-sm text-muted-foreground">
            Управување со одсуства, болничко, годишни одмори и друга HR евиденција.
        </p>
        <p class="text-sm font-medium text-primary">Наскоро достапно</p>
    </div>
</template>
