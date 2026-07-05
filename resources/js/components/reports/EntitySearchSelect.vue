<script setup lang="ts">
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';

type Item = Record<string, any>;

const props = withDefaults(defineProps<{
    endpoint: string;
    labelKey?: string;
    codeKey?: string;
    placeholder?: string;
    initialLabel?: string;
}>(), {
    labelKey: 'name',
    codeKey: 'code',
    placeholder: '',
    initialLabel: '',
});

const emit = defineEmits<{ (e: 'select', item: Item | null): void }>();

const query = ref(props.initialLabel);
const results = ref<Item[]>([]);
const show = ref(false);
let timer: ReturnType<typeof setTimeout> | null = null;

watch(() => props.initialLabel, (v) => { query.value = v; });

function onQueryUpdate(value: string | number) {
    query.value = String(value);
    emit('select', null);

    if (timer) clearTimeout(timer);
    if (!query.value) { results.value = []; show.value = false; return; }

    timer = setTimeout(async () => {
        const res = await fetch(`${props.endpoint}?q=${encodeURIComponent(query.value)}`);
        if (!res.ok) return;
        results.value = await res.json();
        show.value = results.value.length > 0;
    }, 180);
}

function pick(item: Item) {
    query.value = item[props.codeKey] ? `${item[props.codeKey]} — ${item[props.labelKey]}` : item[props.labelKey];
    show.value = false;
    emit('select', item);
}

function onBlur() {
    setTimeout(() => { show.value = false; }, 150);
}
</script>

<template>
    <div class="relative">
        <Input
            :model-value="query"
            :placeholder="placeholder"
            autocomplete="off"
            @update:model-value="onQueryUpdate"
            @focus="() => (show = results.length > 0)"
            @blur="onBlur"
        />
        <div
            v-if="show"
            class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-md border bg-popover shadow-md"
        >
            <button
                v-for="(item, i) in results"
                :key="i"
                type="button"
                class="block w-full px-3 py-1.5 text-left text-sm hover:bg-accent"
                @mousedown.prevent="pick(item)"
            >
                <span v-if="item[codeKey]" class="mr-1 font-mono text-xs text-muted-foreground">{{ item[codeKey] }}</span>
                {{ item[labelKey] }}
            </button>
        </div>
    </div>
</template>
