<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Plus, Search, Pencil, Trash2, X, Building2 } from '@lucide/vue';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import MaterijaliTabs from '@/components/MaterijaliTabs.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Материјално', href: '/kontragenti' }, { title: 'Кооперанти', href: '/kontragenti' }],
    },
});

type Kontragent = {
    id: number;
    company_id: number;
    name: string;
    edb: string;
    embs: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    is_vat_payer: boolean;
    type: 'client' | 'supplier' | 'both';
    is_active: boolean;
    company: { id: number; name: string };
};

type PaginatedKontragenti = {
    data: Kontragent[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

const props = defineProps<{
    kontragenti: PaginatedKontragenti;
    filters: { type?: string; search?: string };
}>();

const page = usePage();
const currentCompany = computed(() => (page.props as any).current_company as { id: number; name: string } | null);

const TYPE_LABELS: Record<string, string> = {
    client:   'Клиент',
    supplier: 'Добавувач',
    both:     'Двете',
};

const TYPE_VARIANT: Record<string, 'default' | 'secondary' | 'outline'> = {
    client:   'default',
    supplier: 'secondary',
    both:     'outline',
};

// ─── Filters ─────────────────────────────────────────────────────────────────
const search     = ref(props.filters.search ?? '');
const typeFilter = ref(props.filters.type ?? '');

function applyFilters() {
    router.get('/kontragenti', {
        ...(search.value ? { search: search.value } : {}),
        ...(typeFilter.value ? { type: typeFilter.value } : {}),
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    typeFilter.value = '';
    router.get('/kontragenti', {}, { preserveState: true, replace: true });
}

const hasFilters = computed(() => search.value || typeFilter.value);

// ─── Create dialog ───────────────────────────────────────────────────────────
const showCreate = ref(false);

const createForm = useForm({
    company_id:   String(currentCompany.value?.id ?? ''),
    name:         '',
    edb:          '',
    embs:         '',
    address:      '',
    phone:        '',
    email:        '',
    is_vat_payer: false,
    type:         'both',
    is_active:    true,
});

function submitCreate() {
    createForm.company_id = String(currentCompany.value?.id ?? '');
    createForm.post('/kontragenti', {
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
            createForm.company_id = String(currentCompany.value?.id ?? '');
        },
    });
}

// ─── Edit dialog ─────────────────────────────────────────────────────────────
const showEdit    = ref(false);
const editTarget  = ref<Kontragent | null>(null);

const editForm = useForm({
    name:         '',
    edb:          '',
    embs:         '',
    address:      '',
    phone:        '',
    email:        '',
    is_vat_payer: false,
    type:         'both' as 'client' | 'supplier' | 'both',
    is_active:    true,
});

function openEdit(k: Kontragent) {
    editTarget.value = k;
    editForm.name         = k.name;
    editForm.edb          = k.edb;
    editForm.embs         = k.embs ?? '';
    editForm.address      = k.address ?? '';
    editForm.phone        = k.phone ?? '';
    editForm.email        = k.email ?? '';
    editForm.is_vat_payer = k.is_vat_payer;
    editForm.type         = k.type;
    editForm.is_active    = k.is_active;
    showEdit.value = true;
}

function submitEdit() {
    if (!editTarget.value) return;
    editForm.put(`/kontragenti/${editTarget.value.id}`, {
        onSuccess: () => {
            showEdit.value = false;
            editTarget.value = null;
        },
    });
}

// ─── Delete ───────────────────────────────────────────────────────────────────
function deleteKontragent(k: Kontragent) {
    if (!confirm(`Избриши контрагент "${k.name}"?`)) return;
    router.delete(`/kontragenti/${k.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Кооперанти" />
    <MaterijaliTabs />

    <div class="flex flex-col gap-6 p-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Кооперанти</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {{ kontragenti.total }} деловни партнери (клиенти / добавувачи)
                </p>
            </div>
            <Button @click="showCreate = true">
                <Plus class="mr-2 size-4" />
                Нов контрагент
            </Button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex min-w-60 flex-1 items-center gap-2 rounded-md border px-3">
                <Search class="size-4 shrink-0 text-muted-foreground" />
                <Input
                    v-model="search"
                    placeholder="Пребарај по назив или ЕДБ…"
                    class="border-0 bg-transparent px-0 focus-visible:ring-0"
                    @keyup.enter="applyFilters"
                />
            </div>

            <Select v-model="typeFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-44">
                    <SelectValue placeholder="Сите типови" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Сите типови</SelectItem>
                    <SelectItem value="client">Клиенти</SelectItem>
                    <SelectItem value="supplier">Добавувачи</SelectItem>
                    <SelectItem value="both">Двете</SelectItem>
                </SelectContent>
            </Select>

            <Button variant="outline" @click="applyFilters">
                <Search class="mr-2 size-4" />
                Пребарај
            </Button>

            <Button v-if="hasFilters" variant="ghost" size="icon" @click="clearFilters" title="Исчисти">
                <X class="size-4" />
            </Button>
        </div>

        <!-- Table -->
        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Назив</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">ЕДБ</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Компанија</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Тип</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">ДДВ</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="kontragenti.data.length === 0">
                        <td colspan="7" class="py-16 text-center text-muted-foreground">
                            <Building2 class="mx-auto mb-3 size-10 opacity-30" />
                            Нема контрагенти
                        </td>
                    </tr>
                    <tr
                        v-for="k in kontragenti.data"
                        :key="k.id"
                        class="border-b last:border-0 hover:bg-muted/30"
                        :class="{ 'opacity-50': !k.is_active }"
                    >
                        <td class="px-4 py-3 font-medium">{{ k.name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ k.edb }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ k.company.name }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="TYPE_VARIANT[k.type]" class="text-xs">
                                {{ TYPE_LABELS[k.type] }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            <Badge v-if="k.is_vat_payer" variant="secondary" class="text-xs">ДДВ</Badge>
                            <span v-else class="text-xs text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <Badge v-if="k.is_active" variant="secondary" class="text-xs">Активен</Badge>
                            <Badge v-else variant="outline" class="text-xs">Неактивен</Badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button variant="ghost" size="icon" @click="openEdit(k)" title="Уреди">
                                    <Pencil class="size-4" />
                                </Button>
                                <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="deleteKontragent(k)" title="Избриши">
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="kontragenti.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in kontragenti.links"
                :key="link.label"
                :variant="link.active ? 'default' : 'outline'"
                size="sm"
                :disabled="!link.url"
                v-html="link.label"
                @click="link.url && router.visit(link.url, { preserveScroll: true })"
            />
        </div>

    </div>

    <!-- ─── Create Dialog ──────────────────────────────────────────────────── -->
    <Dialog v-model:open="showCreate">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>Нов контрагент</DialogTitle>
            </DialogHeader>

            <form class="grid gap-4 py-2" @submit.prevent="submitCreate">
                <div class="grid gap-1.5">
                    <Label for="c-name">Назив *</Label>
                    <Input id="c-name" v-model="createForm.name" placeholder="Назив на контрагентот" />
                    <p v-if="createForm.errors.name" class="text-xs text-destructive">{{ createForm.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label for="c-edb">ЕДБ * (13 цифри)</Label>
                        <Input id="c-edb" v-model="createForm.edb" placeholder="4030000000000" maxlength="13" />
                        <p v-if="createForm.errors.edb" class="text-xs text-destructive">{{ createForm.errors.edb }}</p>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="c-embs">ЕМБС (7 цифри)</Label>
                        <Input id="c-embs" v-model="createForm.embs" placeholder="1234567" maxlength="7" />
                        <p v-if="createForm.errors.embs" class="text-xs text-destructive">{{ createForm.errors.embs }}</p>
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="c-address">Адреса</Label>
                    <Input id="c-address" v-model="createForm.address" placeholder="ул. Мито Хаџивасилев Јасмин 50, Скопје" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label for="c-phone">Телефон</Label>
                        <Input id="c-phone" v-model="createForm.phone" placeholder="+389 2 000 0000" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="c-email">Е-пошта</Label>
                        <Input id="c-email" v-model="createForm.email" type="email" placeholder="info@firma.mk" />
                        <p v-if="createForm.errors.email" class="text-xs text-destructive">{{ createForm.errors.email }}</p>
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="c-type">Тип *</Label>
                    <Select v-model="createForm.type">
                        <SelectTrigger id="c-type">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="client">Клиент</SelectItem>
                            <SelectItem value="supplier">Добавувач</SelectItem>
                            <SelectItem value="both">Клиент и Добавувач</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <Checkbox id="c-vat" v-model:checked="createForm.is_vat_payer" />
                        <Label for="c-vat">ДДВ обврзник</Label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox id="c-active" v-model:checked="createForm.is_active" />
                        <Label for="c-active">Активен</Label>
                    </div>
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="showCreate = false">Откажи</Button>
                <Button :disabled="createForm.processing" @click="submitCreate">
                    {{ createForm.processing ? 'Зачувување…' : 'Додај контрагент' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ─── Edit Dialog ────────────────────────────────────────────────────── -->
    <Dialog v-model:open="showEdit">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    Уреди контрагент
                    <span v-if="editTarget" class="ml-2 text-base font-normal text-muted-foreground">
                        {{ editTarget.name }}
                    </span>
                </DialogTitle>
            </DialogHeader>

            <form class="grid gap-4 py-2" @submit.prevent="submitEdit">
                <div class="grid gap-1.5">
                    <Label for="e-name">Назив *</Label>
                    <Input id="e-name" v-model="editForm.name" />
                    <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label for="e-edb">ЕДБ *</Label>
                        <Input id="e-edb" v-model="editForm.edb" maxlength="13" />
                        <p v-if="editForm.errors.edb" class="text-xs text-destructive">{{ editForm.errors.edb }}</p>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="e-embs">ЕМБС</Label>
                        <Input id="e-embs" v-model="editForm.embs" maxlength="7" />
                        <p v-if="editForm.errors.embs" class="text-xs text-destructive">{{ editForm.errors.embs }}</p>
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="e-address">Адреса</Label>
                    <Input id="e-address" v-model="editForm.address" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label for="e-phone">Телефон</Label>
                        <Input id="e-phone" v-model="editForm.phone" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="e-email">Е-пошта</Label>
                        <Input id="e-email" v-model="editForm.email" type="email" />
                        <p v-if="editForm.errors.email" class="text-xs text-destructive">{{ editForm.errors.email }}</p>
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="e-type">Тип *</Label>
                    <Select v-model="editForm.type">
                        <SelectTrigger id="e-type">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="client">Клиент</SelectItem>
                            <SelectItem value="supplier">Добавувач</SelectItem>
                            <SelectItem value="both">Клиент и Добавувач</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <Checkbox id="e-vat" v-model:checked="editForm.is_vat_payer" />
                        <Label for="e-vat">ДДВ обврзник</Label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox id="e-active" v-model:checked="editForm.is_active" />
                        <Label for="e-active">Активен</Label>
                    </div>
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="showEdit = false">Откажи</Button>
                <Button :disabled="editForm.processing" @click="submitEdit">
                    {{ editForm.processing ? 'Зачувување…' : 'Зачувај' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
