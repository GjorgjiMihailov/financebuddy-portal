<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import type { JournalGroup } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Подесувања', href: '/settings/profile' },
            { title: 'Групи налози', href: '/settings/journal-groups' },
        ],
    },
});

defineProps<{ groups: JournalGroup[] }>();

const showDialog = ref(false);

const form = useForm({
    code: '' as string | number,
    name: '',
    description: '',
});

function openDialog() {
    form.reset();
    form.clearErrors();
    showDialog.value = true;
}

function submit() {
    form.post('/settings/journal-groups', {
        onSuccess: () => { showDialog.value = false; },
    });
}

function destroy(code: number) {
    if (!confirm(`Избриши група ${code}?`)) return;
    router.delete(`/settings/journal-groups/${code}`);
}
</script>

<template>
    <Head title="Групи налози" />

    <div class="flex flex-col gap-6 p-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Групи на книговодствени налози</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ groups.length }} групи</p>
            </div>
            <Button size="sm" @click="openDialog">
                <Plus class="mr-1.5 size-4" />
                Нова група
            </Button>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground w-20">Код</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Назив</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground hidden sm:table-cell">Опис</th>
                        <th class="px-3 py-1.5 w-12"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="g in groups"
                        :key="g.code"
                        class="border-b last:border-0 hover:bg-muted/30"
                    >
                        <td class="px-3 py-1.5 font-mono font-semibold">{{ String(g.code).padStart(2, '0') }}</td>
                        <td class="px-3 py-1.5">{{ g.name }}</td>
                        <td class="px-3 py-1.5 text-muted-foreground hidden sm:table-cell">{{ g.description ?? '---' }}</td>
                        <td class="px-3 py-1.5">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 text-muted-foreground hover:text-destructive"
                                @click="destroy(g.code)"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog v-model:open="showDialog">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Нова група на налози</DialogTitle>
                </DialogHeader>
                <form class="grid gap-4 py-2" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="code">Код (0-99) <span class="text-destructive">*</span></Label>
                        <Input
                            id="code"
                            v-model="form.code"
                            type="number"
                            min="0"
                            max="99"
                            placeholder="пр. 11"
                            :class="{ 'border-destructive': form.errors.code }"
                        />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="name">Назив <span class="text-destructive">*</span></Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="пр. Девизни изводи"
                            :class="{ 'border-destructive': form.errors.name }"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="description">Опис (опционален)</Label>
                        <Input
                            id="description"
                            v-model="form.description"
                            placeholder="дополнителен опис"
                        />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showDialog = false">Откажи</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Се зачувува...' : 'Зачувај' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    </div>
</template>