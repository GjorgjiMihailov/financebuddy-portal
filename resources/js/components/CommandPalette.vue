<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Dialog, DialogContent } from '@/components/ui/dialog';

type Command = {
    title: string;
    href: string;
    group: string;
};

const commands: Command[] = [
    { title: 'Контролна табла', href: '/dashboard', group: 'Главно' },
    { title: 'Клиенти', href: '/companies', group: 'Материјално' },
    { title: 'Магацини', href: '/warehouses', group: 'Материјално' },
    { title: 'Артикли', href: '/items', group: 'Материјално' },
    { title: 'Профил', href: '/settings/profile', group: 'Поставки' },
    { title: 'Безбедност', href: '/settings/security', group: 'Поставки' },
    { title: 'Изглед', href: '/settings/appearance', group: 'Поставки' },
];

const open = ref(false);
const query = ref('');
const selected = ref(0);
const inputRef = ref<HTMLInputElement | null>(null);

const filtered = computed<Command[]>(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) {
        return commands;
    }
    return commands.filter((c) => c.title.toLowerCase().includes(q));
});

watch(filtered, () => {
    selected.value = 0;
});

watch(open, async (isOpen) => {
    if (isOpen) {
        query.value = '';
        selected.value = 0;
        await nextTick();
        inputRef.value?.focus();
    }
});

function onKeydown(e: KeyboardEvent) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        open.value = !open.value;
    }
}

function move(delta: number) {
    const n = filtered.value.length;
    if (n === 0) {
        return;
    }
    selected.value = (selected.value + delta + n) % n;
}

function go(cmd?: Command) {
    const target = cmd ?? filtered.value[selected.value];
    if (!target) {
        return;
    }
    open.value = false;
    router.visit(target.href);
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="gap-0 overflow-hidden p-0 sm:max-w-lg">
            <div class="flex items-center border-b px-3">
                <Search class="size-4 shrink-0 text-muted-foreground" />
                <input
                    ref="inputRef"
                    v-model="query"
                    placeholder="Барај команда или модул..."
                    class="h-11 w-full bg-transparent px-2 text-sm outline-none placeholder:text-muted-foreground"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter.prevent="go()"
                />
            </div>
            <ul class="max-h-72 overflow-y-auto p-1">
                <li
                    v-if="filtered.length === 0"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    Нема резултати.
                </li>
                <li
                    v-for="(cmd, i) in filtered"
                    :key="cmd.href"
                    :class="[
                        'flex cursor-pointer items-center justify-between rounded-md px-3 py-2 text-sm',
                        i === selected ? 'bg-accent text-accent-foreground' : '',
                    ]"
                    @click="go(cmd)"
                    @mousemove="selected = i"
                >
                    <span>{{ cmd.title }}</span>
                    <span class="text-xs text-muted-foreground">{{ cmd.group }}</span>
                </li>
            </ul>
        </DialogContent>
    </Dialog>
</template>
