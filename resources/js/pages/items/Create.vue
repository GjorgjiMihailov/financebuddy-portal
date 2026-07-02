<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Артикли', href: '/items' },
            { title: 'Нов артикл', href: '#' },
        ],
    },
});

type Company   = { id: number; name: string };
type Warehouse = { id: number; company_id: number; name: string };

const props = defineProps<{
    companies:  Company[];
    warehouses: Warehouse[];
}>();

const form = useForm({
    company_id:           '',
    code:                 '',
    name:                 '',
    unit:                 'бр',
    vat_category:         '18',
    price_without_vat:    '',
    is_active:            true,
    is_service:           false,
    is_macedonian:        false,
    initial_warehouse_id: '',
    initial_stock:        '',
    initial_date:         new Date().toISOString().split('T')[0],
});

const availableWarehouses = computed(() =>
    props.warehouses.filter(w => !form.company_id || w.company_id === Number(form.company_id))
);

function submit() {
    form.post('/items');
}
</script>

<template>
    <Head title="Нов артикл" />
    <div class="p-6">
        <Card class="max-w-lg">
            <CardHeader><CardTitle>Нов артикл</CardTitle></CardHeader>
            <CardContent>
                <form class="grid gap-4" @submit.prevent="submit">

                    <div class="grid gap-1.5">
                        <Label>Компанија *</Label>
                        <Select v-model="form.company_id">
                            <SelectTrigger><SelectValue placeholder="Избери компанија" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.company_id" class="text-xs text-destructive">{{ form.errors.company_id }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-1.5">
                            <Label>Шифра *</Label>
                            <Input v-model="form.code" placeholder="npr. ART001" />
                            <p v-if="form.errors.code" class="text-xs text-destructive">{{ form.errors.code }}</p>
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Единица мерка *</Label>
                            <Input v-model="form.unit" placeholder="бр, кг, л…" />
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <Label>Назив *</Label>
                        <Input v-model="form.name" placeholder="Назив на артиклот" />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-1.5">
                            <Label>ДДВ категорија *</Label>
                            <Select v-model="form.vat_category">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="18">18% (стандардна)</SelectItem>
                                    <SelectItem value="5">5% (намалена)</SelectItem>
                                    <SelectItem value="0">0% (ослободена)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Цена без ДДВ *</Label>
                            <Input v-model="form.price_without_vat" type="number" step="0.01" min="0" placeholder="0.00" />
                            <p v-if="form.errors.price_without_vat" class="text-xs text-destructive">{{ form.errors.price_without_vat }}</p>
                        </div>
                    </div>

                    <!-- Карактеристики -->
                    <div class="grid gap-2.5">
                        <div class="flex items-center gap-2">
                            <Checkbox v-model:checked="form.is_active" />
                            <Label>Активен</Label>
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model:checked="form.is_service" />
                            <Label>Услуга <span class="text-xs text-muted-foreground">(не се следи залиха)</span></Label>
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model:checked="form.is_macedonian" />
                            <Label>Македонски производ</Label>
                        </div>
                    </div>

                    <!-- Почетна залиха (само за производи) -->
                    <div v-if="!form.is_service" class="rounded-lg border border-dashed p-4">
                        <p class="mb-3 text-sm font-medium">Почетна залиха (опционално)</p>
                        <div class="grid gap-3">
                            <div class="grid gap-1.5">
                                <Label>Магацин</Label>
                                <Select v-model="form.initial_warehouse_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Избери магацин" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">— Без почетна залиха —</SelectItem>
                                        <SelectItem
                                            v-for="w in availableWarehouses"
                                            :key="w.id"
                                            :value="String(w.id)"
                                        >
                                            {{ w.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div v-if="form.initial_warehouse_id" class="grid grid-cols-2 gap-3">
                                <div class="grid gap-1.5">
                                    <Label>Количина</Label>
                                    <Input v-model="form.initial_stock" type="number" step="0.001" min="0" placeholder="0.000" />
                                    <p v-if="form.errors.initial_stock" class="text-xs text-destructive">{{ form.errors.initial_stock }}</p>
                                </div>
                                <div class="grid gap-1.5">
                                    <Label>Датум</Label>
                                    <Input v-model="form.initial_date" type="date" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Зачувување…' : 'Зачувај' }}</Button>
                        <Button type="button" variant="outline" @click="$inertia.visit('/items')">Откажи</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
