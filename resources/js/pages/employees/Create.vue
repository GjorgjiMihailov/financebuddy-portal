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
            { title: 'Вработени', href: '/employees' },
            { title: 'Нов вработен', href: '#' },
        ],
    },
});

type Company = { id: number; name: string };
defineProps<{ companies: Company[] }>();

const form = useForm({
    company_id: '',
    first_name: '',
    last_name: '',
    embg: '',
    position: '',
    net_salary: '',
    bank_account: '',
    is_active: true,
    hired_at: '',
});

function submit() { form.post('/employees'); }
</script>

<template>
    <Head title="Нов вработен" />
    <div class="p-6">
        <Card class="max-w-lg">
            <CardHeader><CardTitle>Нов вработен</CardTitle></CardHeader>
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
                            <Label>Име *</Label>
                            <Input v-model="form.first_name" />
                            <p v-if="form.errors.first_name" class="text-xs text-destructive">{{ form.errors.first_name }}</p>
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Презиме *</Label>
                            <Input v-model="form.last_name" />
                            <p v-if="form.errors.last_name" class="text-xs text-destructive">{{ form.errors.last_name }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-1.5">
                            <Label>ЕМБГ</Label>
                            <Input v-model="form.embg" maxlength="13" placeholder="1234567890123" />
                            <p v-if="form.errors.embg" class="text-xs text-destructive">{{ form.errors.embg }}</p>
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Позиција</Label>
                            <Input v-model="form.position" placeholder="npr. Сметководител" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-1.5">
                            <Label>Нето плата (ден.)</Label>
                            <Input v-model="form.net_salary" type="number" step="0.01" min="0" placeholder="0.00" />
                            <p v-if="form.errors.net_salary" class="text-xs text-destructive">{{ form.errors.net_salary }}</p>
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Трансакциска сметка</Label>
                            <Input v-model="form.bank_account" placeholder="200000000000000" />
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <Label>Датум на вработување</Label>
                        <Input v-model="form.hired_at" type="date" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Switch v-model:checked="form.is_active" />
                        <Label>Активен вработен</Label>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Зачувување…' : 'Зачувај' }}</Button>
                        <Button type="button" variant="outline" @click="$inertia.visit('/employees')">Откажи</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
