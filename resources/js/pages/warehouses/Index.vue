<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Магацини', href: '/warehouses' }],
    },
});

type Company = { id: number; name: string };
type Warehouse = { id: number; name: string; location: string | null; is_active: boolean; company: Company };
type Paginated = { data: Warehouse[]; total: number; last_page: number; links: { url: string | null; label: string; active: boolean }[] };

const props = defineProps<{
    warehouses: Paginated;
    companies: Company[];
    filters: { company_id?: string; search?: string };
}>();

const companyFilter = ref(props.filters.company_id ?? '');

function applyFilter() {
    router.get('/warehouses', companyFilter.value ? { company_id: companyFilter.value } : {}, { replace: true });
}

// ── Edit inline dialog ────────────────────────────────────────────────────────
const showEdit = ref(false);
const editTarget = ref<Warehouse | null>(null);
const editForm = useForm({ name: '', location: '', is_active: true });

function openEdit(w: Warehouse) {
    editTarget.value = w;
    editForm.name = w.name;
    editForm.location = w.location ?? '';
    editForm.is_active = w.is_active;
    showEdit.value = true;
}

function submitEdit() {
    if (!editTarget.value) return;
    editForm.put(`/warehouses/${editTarget.value.id}`, { onSuccess: () => { showEdit.value = false; } });
}

function deleteWarehouse(w: Warehouse) {
    if (!confirm(`Избриши магацин "${w.name}"?`)) return;
    router.delete(`/warehouses/${w.id}`);
}
</script>

<template>
    <Head title="Магацини" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Магацини</h1>
                <p class="text-sm text-muted-foreground">{{ warehouses.total }} вкупно</p>
            </div>
            <Button as-child>
                <Link href="/warehouses/create">
                    <Plus class="mr-2 size-4" />
                    Нов магацин
                </Link>
            </Button>
        </div>

        <div class="flex gap-3">
            <Select v-model="companyFilter" @update:model-value="applyFilter">
                <SelectTrigger class="w-64">
                    <SelectValue placeholder="Сите компании" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Сите компании</SelectItem>
                    <SelectItem v-for="c in companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Магацин</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Локација</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Компанија</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="warehouses.data.length === 0">
                        <td colspan="5" class="py-12 text-center text-muted-foreground">Нема магацини</td>
                    </tr>
                    <tr v-for="w in warehouses.data" :key="w.id" class="border-b last:border-0 hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium">{{ w.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ w.location ?? '—' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ w.company.name }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="w.is_active ? 'secondary' : 'outline'">
                                {{ w.is_active ? 'Активен' : 'Неактивен' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Button variant="ghost" size="icon" @click="openEdit(w)"><Pencil class="size-4" /></Button>
                                <Button variant="ghost" size="icon" @click="deleteWarehouse(w)"><Trash2 class="size-4 text-destructive" /></Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="warehouses.last_page > 1" class="flex justify-center gap-1">
            <Button v-for="link in warehouses.links" :key="link.label" :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" v-html="link.label" @click="link.url && router.visit(link.url)" />
        </div>
    </div>

    <Dialog v-model:open="showEdit">
        <DialogContent class="max-w-md">
            <DialogHeader><DialogTitle>Уреди магацин</DialogTitle></DialogHeader>
            <div class="grid gap-4 py-2">
                <div class="grid gap-1.5">
                    <Label>Назив *</Label>
                    <Input v-model="editForm.name" />
                </div>
                <div class="grid gap-1.5">
                    <Label>Локација</Label>
                    <Input v-model="editForm.location" />
                </div>
                <div class="flex items-center gap-2">
                    <Switch v-model:checked="editForm.is_active" />
                    <Label>Активен</Label>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showEdit = false">Откажи</Button>
                <Button :disabled="editForm.processing" @click="submitEdit">Зачувај</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
