<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
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

type Company = { id: number; name: string };
defineProps<{ companies: Company[] }>();

const form = useForm({
    company_id: '',
    code: '',
    name: '',
    unit: 'бр',
    vat_category: '18',
    price_without_vat: '',
    is_active: true,
});

function submit() { form.post('/items'); }
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

                    <div class="flex items-center gap-2">
                        <Switch v-model:checked="form.is_active" />
                        <Label>Активен</Label>
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
