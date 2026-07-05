<script setup lang="ts">
defineProps<{
    title: string;
    company: { name: string } | null;
    from: string;
    to: string;
}>();

function fmtShort(d: string): string {
    if (!d) return '';
    const [y, m, day] = d.split('-');
    return `${day}-${m}-${y.slice(2)}`;
}

function monthSpan(from: string, to: string): string {
    if (!from || !to) return '';
    const fm = Number(from.split('-')[1]);
    const tm = Number(to.split('-')[1]);
    return `m.${fm}-${tm}`;
}

function yearOf(d: string): string {
    return d ? d.split('-')[0] : '';
}
</script>

<template>
    <div class="mb-6 grid grid-cols-3 items-start">
        <div></div>
        <div class="text-center">
            <h1 class="inline-block border-b-2 border-foreground pb-1 text-lg font-semibold">{{ title }}</h1>
            <p class="mt-1 text-xs text-muted-foreground">{{ fmtShort(from) }} {{ fmtShort(to) }} {{ monthSpan(from, to) }}</p>
        </div>
        <div class="text-right text-sm">
            <div class="font-medium">{{ company?.name ?? '—' }}</div>
            <div class="text-muted-foreground">{{ yearOf(to) }}</div>
        </div>
    </div>
</template>
