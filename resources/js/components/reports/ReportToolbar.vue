<script setup lang="ts">
import { Printer } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{ from: string; to: string }>();
const emit = defineEmits<{
    (e: 'update:from', value: string): void;
    (e: 'update:to', value: string): void;
    (e: 'submit'): void;
}>();

function printPage() { window.print(); }
</script>

<template>
    <div class="mb-4 flex flex-wrap items-end gap-3 print:hidden">
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">Од</label>
            <input
                type="date"
                :value="from"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                @input="emit('update:from', ($event.target as HTMLInputElement).value)"
            >
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-muted-foreground">До</label>
            <input
                type="date"
                :value="to"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                @input="emit('update:to', ($event.target as HTMLInputElement).value)"
            >
        </div>

        <slot />

        <Button @click="emit('submit')">Прикажи</Button>
        <Button variant="outline" class="ml-auto" @click="printPage">
            <Printer class="mr-2 size-4" />Печати / PDF
        </Button>
    </div>
</template>
