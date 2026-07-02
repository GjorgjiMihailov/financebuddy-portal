<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Search, Pencil, X } from '@lucide/vue';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Подесувања', href: '/settings/profile' },
            { title: 'Конта', href: '/settings/accounts' },
        ],
    },
});

type Account = {
    code: string;
    name: string;
    class: number;
    account_type: string;
    parent_code: string | null;
    allows_posting: boolean;
    is_active: boolean;
};

type PaginatedAccounts = {
    data: Account[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

const props = defineProps<{
    accounts: PaginatedAccounts;
    filters: { search?: string; class?: string };
}>();

const CLASS_NAMES: Record<number, string> = {
    0: 'Кл.0 — Нетековни средства',
    1: 'Кл.1 — Залихи',
    2: 'Кл.2 — Побарувања и пари',
    3: 'Кл.3 — Главнина',
    4: 'Кл.4 — Обврски',
    5: 'Кл.5 — Трошоци',
    6: 'Кл.6 — Приходи',
    7: 'Кл.7 — Набавна вредност',
    8: 'Кл.8 — Заклучни',
    9: 'Кл.9 — Вонбилансни',
};

const TYPE_LABELS: Record<string, string> = {
    asset: 'Средство',
    liability: 'Обврска',
    equity: 'Капитал',
    revenue: 'Приход',
    expense: 'Расход',
    cost_center: 'Трошк. центар',
    bank_only: 'Банка',
};

const TYPE_VARIANT: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    asset: 'default',
    liability: 'destructive',
    equity: 'secondary',
    revenue: 'default',
    expense: 'outline',
    cost_center: 'outline',
    bank_only: 'secondary',
};

// ─── Filters ─────────────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '');
const classFilter = ref(props.filters.class ?? '');

function applyFilters() {
    router.get('/settings/accounts', {
        ...(search.value ? { search: search.value } : {}),
        ...(classFilter.value !== '' ? { class: classFilter.value } : {}),
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    classFilter.value = '';
    router.get('/settings/accounts', {}, { preserveState: true, replace: true });
}

// ─── Create dialog ───────────────────────────────────────────────────────────
const showCreate = ref(false);

const createForm = useForm({
    code: '',
    name: '',
    class: '',
    account_type: 'asset',
    parent_code: '',
    allows_posting: true,
    is_active: true,
});

function submitCreate() {
    createForm.post('/settings/accounts', {
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
    });
}

// ─── Edit dialog ─────────────────────────────────────────────────────────────
const showEdit = ref(false);
const editTarget = ref<Account | null>(null);

const editForm = useForm({
    name: '',
    allows_posting: true,
    is_active: true,
});

function openEdit(account: Account) {
    editTarget.value = account;
    editForm.name = account.name;
    editForm.allows_posting = account.allows_posting;
    editForm.is_active = account.is_active;
    showEdit.value = true;
}

function submitEdit() {
    if (!editTarget.value) return;
    editForm.put(`/settings/accounts/${editTarget.value.code}`, {
        onSuccess: () => {
            showEdit.value = false;
            editTarget.value = null;
        },
    });
}

const hasFilters = computed(() => search.value || classFilter.value !== '');
</script>

<template>
    <Head title="Сметковен план" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Сметковен план</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {{ accounts.total }} конта — Правилник бр. 174 на УЈП (2024/2025)
                </p>
            </div>
            <Button @click="showCreate = true">
                <Plus class="mr-2 size-4" />
                Додај конто
            </Button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex min-w-60 flex-1 items-center gap-2 rounded-md border px-3">
                <Search class="size-4 shrink-0 text-muted-foreground" />
                <Input
                    v-model="search"
                    placeholder="Пребарај по шифра или назив…"
                    class="border-0 bg-transparent px-0 focus-visible:ring-0"
                    @keyup.enter="applyFilters"
                />
            </div>

            <Select v-model="classFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-56">
                    <SelectValue placeholder="Сите класи" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Сите класи</SelectItem>
                    <SelectItem v-for="(name, cls) in CLASS_NAMES" :key="cls" :value="String(cls)">
                        {{ name }}
                    </SelectItem>
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
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Шифра</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Назив</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Класа</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Тип</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Книжење</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-3 py-1.5"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="accounts.data.length === 0">
                        <td colspan="7" class="py-12 text-center text-muted-foreground">
                            Нема конта според пребарувањето
                        </td>
                    </tr>
                    <tr
                        v-for="account in accounts.data"
                        :key="account.code"
                        class="border-b last:border-0 hover:bg-muted/30"
                        :class="{ 'opacity-50': !account.is_active }"
                    >
                        <td class="px-3 py-1.5 font-mono font-medium">{{ account.code }}</td>
                        <td class="px-3 py-1.5">
                            <span :class="account.code.length === 2 ? 'font-semibold' : ''">
                                {{ account.name }}
                            </span>
                            <span v-if="account.parent_code" class="ml-2 font-mono text-xs text-muted-foreground">
                                → {{ account.parent_code }}
                            </span>
                        </td>
                        <td class="px-3 py-1.5 text-muted-foreground">
                            {{ account.class }}
                        </td>
                        <td class="px-3 py-1.5">
                            <Badge :variant="TYPE_VARIANT[account.account_type] ?? 'outline'" class="text-xs">
                                {{ TYPE_LABELS[account.account_type] ?? account.account_type }}
                            </Badge>
                        </td>
                        <td class="px-3 py-1.5">
                            <Badge v-if="account.allows_posting" variant="secondary" class="text-xs">Да</Badge>
                            <span v-else class="text-xs text-muted-foreground">Не</span>
                        </td>
                        <td class="px-3 py-1.5">
                            <Badge v-if="account.is_active" variant="secondary" class="text-xs">Активно</Badge>
                            <Badge v-else variant="outline" class="text-xs">Неактивно</Badge>
                        </td>
                        <td class="px-3 py-1.5 text-right">
                            <Button variant="ghost" size="icon" @click="openEdit(account)" title="Уреди">
                                <Pencil class="size-4" />
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="accounts.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in accounts.links"
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
                <DialogTitle>Ново конто</DialogTitle>
            </DialogHeader>

            <form class="grid gap-4 py-2" @submit.prevent="submitCreate">
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label for="c-code">Шифра *</Label>
                        <Input id="c-code" v-model="createForm.code" placeholder="npr. 2011" maxlength="10" />
                        <p v-if="createForm.errors.code" class="text-xs text-destructive">{{ createForm.errors.code }}</p>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="c-class">Класа *</Label>
                        <Select v-model="createForm.class">
                            <SelectTrigger id="c-class">
                                <SelectValue placeholder="Избери класа" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="i in [0,1,2,3,4,5,6,7,8,9]" :key="i" :value="String(i)">
                                    Класа {{ i }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="createForm.errors.class" class="text-xs text-destructive">{{ createForm.errors.class }}</p>
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="c-name">Назив *</Label>
                    <Input id="c-name" v-model="createForm.name" placeholder="Назив на контото" />
                    <p v-if="createForm.errors.name" class="text-xs text-destructive">{{ createForm.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label for="c-type">Тип *</Label>
                        <Select v-model="createForm.account_type">
                            <SelectTrigger id="c-type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="asset">Средство</SelectItem>
                                <SelectItem value="liability">Обврска</SelectItem>
                                <SelectItem value="equity">Капитал</SelectItem>
                                <SelectItem value="revenue">Приход</SelectItem>
                                <SelectItem value="expense">Расход</SelectItem>
                                <SelectItem value="cost_center">Трошк. центар</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="c-parent">Матично конто</Label>
                        <Input id="c-parent" v-model="createForm.parent_code" placeholder="npr. 20" maxlength="10" />
                        <p v-if="createForm.errors.parent_code" class="text-xs text-destructive">{{ createForm.errors.parent_code }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <Checkbox id="c-posting" v-model:checked="createForm.allows_posting" />
                        <Label for="c-posting">Дозволува книжење</Label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox id="c-active" v-model:checked="createForm.is_active" />
                        <Label for="c-active">Активно</Label>
                    </div>
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="showCreate = false">Откажи</Button>
                <Button :disabled="createForm.processing" @click="submitCreate">
                    {{ createForm.processing ? 'Зачувување…' : 'Додај конто' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ─── Edit Dialog ────────────────────────────────────────────────────── -->
    <Dialog v-model:open="showEdit">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>
                    Уреди конто
                    <span v-if="editTarget" class="ml-2 font-mono text-base text-muted-foreground">
                        {{ editTarget.code }}
                    </span>
                </DialogTitle>
            </DialogHeader>

            <form class="grid gap-4 py-2" @submit.prevent="submitEdit">
                <div class="grid gap-1.5">
                    <Label for="e-name">Назив *</Label>
                    <Input id="e-name" v-model="editForm.name" />
                    <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <Checkbox id="e-posting" v-model:checked="editForm.allows_posting" />
                        <Label for="e-posting">Дозволува книжење</Label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox id="e-active" v-model:checked="editForm.is_active" />
                        <Label for="e-active">Активно</Label>
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
