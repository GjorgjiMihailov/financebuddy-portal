<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Construction } from '@lucide/vue';

defineProps<{
    title: string;
    note?: string;
}>();
</script>

<template>
    <Head :title="title" />
    <div class="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center">
        <Construction class="size-10 text-muted-foreground" />
        <h1 class="text-2xl font-semibold">{{ title }}</h1>
        <p class="max-w-md text-muted-foreground">
            {{ note ?? 'Овој модул доаѓа во Фаза 1. Засега е тука само за навигацијата.' }}
        </p>
    </div>
</template>
