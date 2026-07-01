<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Влезни фактури', href: '/purchase-invoices' },
            { title: 'Нова фактура', href: '#' },
        ],
    },
});

type Company = { id: number; name: string };
defineProps<{ companies: Company[] }>();

const form = useForm({
    company_id: '',
    supplier_name: '',
    invoice_number: '',
    date: '',
    total_amount: '',
});

function submit() { form.post('/purchase-invoices'); }
</script>

<template>
    <Head title="Нова влезна фактура" />
    <div class="p-6">
        <Card class="max-w-lg">
            <CardHeader><CardTitle>Нова влезна фактура</CardTitle></CardHeader>
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
                    <div class="grid gap-1.5">
                        <Label>Добавувач *</Label>
                        <Input v-model="form.supplier_name" placeholder="Назив на добавувач" />
                        <p v-if="form.errors.supplier_name" class="text-xs text-destructive">{{ form.errors.supplier_name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-1.5">
                            <Label>Број на фактура *</Label>
                            <Input v-model="form.invoice_number" placeholder="npr. 2024/001" />
                            <p v-if="form.errors.invoice_number" class="text-xs text-destructive">{{ form.errors.invoice_number }}</p>
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Датум *</Label>
                            <Input v-model="form.date" type="date" />
                            <p v-if="form.errors.date" class="text-xs text-destructive">{{ form.errors.date }}</p>
                        </div>
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Вкупен износ (ден.) *</Label>
                        <Input v-model="form.total_amount" type="number" step="0.01" min="0" placeholder="0.00" />
                        <p v-if="form.errors.total_amount" class="text-xs text-destructive">{{ form.errors.total_amount }}</p>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Зачувување…' : 'Зачувај' }}</Button>
                        <Button type="button" variant="outline" @click="$inertia.visit('/purchase-invoices')">Откажи</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
