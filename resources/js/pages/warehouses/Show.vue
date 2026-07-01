<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Plus, ArrowLeft, PackageOpen } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
            { title: 'Материјално', href: '/warehouses' },
            { title: 'Магацини', href: '/warehouses' },
            { title: 'Лагер листа', href: '#' },
        ],
    },
});

type Item = { id: number; code: string; name: string; unit: string; price_without_vat: string };
type InventoryRow = {
    item_id: number;
    total_in: string;
    total_out: string;
    current_stock: string;
    avg_price: string | null;
    item: { id: number; code: string; name: string; unit: string };
};
type Movement = {
    id: number;
    movement_type: 'in' | 'out' | 'adjustment' | 'initial';
    quantity: string;
    unit_price: string | null;
    note: string | null;
    movement_date: string;
    item: { id: number; code: string; name: string; unit: string };
    creator: { id: number; name: string };
};
type Warehouse = {
    id: number;
    name: string;
    location: string | null;
    company_id: number;
    company: { id: number; name: string };
};

const props = defineProps<{
    warehouse: Warehouse;
    inventory: InventoryRow[];
    movements: Movement[];
    items: Item[];
}>();

const MOVEMENT_LABELS: Record<string, string> = {
    in:         'Примка (влез)',
    out:        'Требување (излез)',
    adjustment: 'Корекција (попис)',
    initial:    'Почетна залиха',
};

const MOVEMENT_VARIANT: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    in:         'default',
    out:        'destructive',
    adjustment: 'secondary',
    initial:    'outline',
};

function fmtQty(v: string) {
    return parseFloat(v).toLocaleString('mk-MK', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
}
function fmtPrice(v: string | null) {
    if (!v) return '—';
    return parseFloat(v).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ─── Add movement dialog ──────────────────────────────────────────────────────
const showAdd = ref(false);

const addForm = useForm({
    warehouse_id:  String(props.warehouse.id),
    item_id:       '',
    movement_type: 'in',
    quantity:      '',
    unit_price:    '',
    note:          '',
    movement_date: new Date().toISOString().split('T')[0],
});

function submitAdd() {
    addForm.post('/warehouse-movements', {
        onSuccess: () => {
            showAdd.value = false;
            addForm.item_id = '';
            addForm.movement_type = 'in';
            addForm.quantity = '';
            addForm.unit_price = '';
            addForm.note = '';
            addForm.movement_date = new Date().toISOString().split('T')[0];
            router.reload({ only: ['inventory', 'movements'] });
        },
    });
}

function selectedItemUnit(): string {
    const item = props.items.find(i => String(i.id) === addForm.item_id);
    return item?.unit ?? '';
}
</script>

<template>
    <Head :title="`Лагер листа — ${warehouse.name}`" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Button variant="ghost" size="icon" as-child>
                    <Link href="/warehouses"><ArrowLeft class="size-4" /></Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-semibold">{{ warehouse.name }}</h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ warehouse.company.name }}
                        <span v-if="warehouse.location"> · {{ warehouse.location }}</span>
                    </p>
                </div>
            </div>
            <Button @click="showAdd = true">
                <Plus class="mr-2 size-4" />
                Ново движење
            </Button>
        </div>

        <!-- Лагер листа -->
        <div>
            <h2 class="mb-3 text-base font-semibold">Тековни залихи</h2>
            <div class="rounded-lg border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Шифра</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Артикл</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Влез</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Излез</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">На рака</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">ЈМ</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Ср. цена</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="inventory.length === 0">
                            <td colspan="7" class="py-16 text-center text-muted-foreground">
                                <PackageOpen class="mx-auto mb-3 size-10 opacity-30" />
                                Нема залихи во магацинот
                            </td>
                        </tr>
                        <tr
                            v-for="row in inventory"
                            :key="row.item_id"
                            class="border-b last:border-0 hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ row.item.code }}</td>
                            <td class="px-4 py-3 font-medium">{{ row.item.name }}</td>
                            <td class="px-4 py-3 text-right text-green-700">+{{ fmtQty(row.total_in) }}</td>
                            <td class="px-4 py-3 text-right text-red-600">-{{ fmtQty(row.total_out) }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ fmtQty(row.current_stock) }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ row.item.unit }}</td>
                            <td class="px-4 py-3 text-right text-muted-foreground">{{ fmtPrice(row.avg_price) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Историја на движења -->
        <div>
            <h2 class="mb-3 text-base font-semibold">Последни движења (50)</h2>
            <div class="rounded-lg border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Датум</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Тип</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Артикл</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Количина</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Цена</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Белешка</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Корисник</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="movements.length === 0">
                            <td colspan="7" class="py-8 text-center text-muted-foreground">
                                Нема движења
                            </td>
                        </tr>
                        <tr
                            v-for="m in movements"
                            :key="m.id"
                            class="border-b last:border-0 hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 text-muted-foreground">{{ m.movement_date }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="MOVEMENT_VARIANT[m.movement_type]" class="text-xs">
                                    {{ MOVEMENT_LABELS[m.movement_type] }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-muted-foreground">{{ m.item.code }}</span>
                                {{ m.item.name }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span :class="['out'].includes(m.movement_type) ? 'text-red-600' : 'text-green-700'">
                                    {{ ['out'].includes(m.movement_type) ? '-' : '+' }}{{ fmtQty(m.quantity) }}
                                    {{ m.item.unit }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-muted-foreground">{{ fmtPrice(m.unit_price) }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ m.note ?? '—' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ m.creator.name }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ─── Add Movement Dialog ───────────────────────────────────────────── -->
    <Dialog v-model:open="showAdd">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Ново движење — {{ warehouse.name }}</DialogTitle>
            </DialogHeader>

            <form class="grid gap-4 py-2" @submit.prevent="submitAdd">
                <div class="grid gap-1.5">
                    <Label for="m-type">Тип на движење *</Label>
                    <Select v-model="addForm.movement_type">
                        <SelectTrigger id="m-type">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="in">Примка (влез)</SelectItem>
                            <SelectItem value="out">Требување (излез)</SelectItem>
                            <SelectItem value="adjustment">Корекција (попис)</SelectItem>
                            <SelectItem value="initial">Почетна залиха</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="grid gap-1.5">
                    <Label for="m-item">Артикл *</Label>
                    <Select v-model="addForm.item_id">
                        <SelectTrigger id="m-item">
                            <SelectValue placeholder="Избери артикл" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="item in items" :key="item.id" :value="String(item.id)">
                                {{ item.code }} — {{ item.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="addForm.errors.item_id" class="text-xs text-destructive">{{ addForm.errors.item_id }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label for="m-qty">Количина * <span v-if="selectedItemUnit()" class="text-muted-foreground">({{ selectedItemUnit() }})</span></Label>
                        <Input id="m-qty" v-model="addForm.quantity" type="number" step="0.001" min="0.001" placeholder="0.000" />
                        <p v-if="addForm.errors.quantity" class="text-xs text-destructive">{{ addForm.errors.quantity }}</p>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="m-price">Цена по ЈМ (без ДДВ)</Label>
                        <Input id="m-price" v-model="addForm.unit_price" type="number" step="0.01" min="0" placeholder="0.00" />
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="m-date">Датум *</Label>
                    <Input id="m-date" v-model="addForm.movement_date" type="date" />
                    <p v-if="addForm.errors.movement_date" class="text-xs text-destructive">{{ addForm.errors.movement_date }}</p>
                </div>

                <div class="grid gap-1.5">
                    <Label for="m-note">Белешка</Label>
                    <Input id="m-note" v-model="addForm.note" placeholder="Опционална белешка…" />
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="showAdd = false">Откажи</Button>
                <Button :disabled="addForm.processing" @click="submitAdd">
                    {{ addForm.processing ? 'Зачувување…' : 'Зачувај движење' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
