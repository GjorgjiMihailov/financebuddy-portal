<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Магацини', href: '/warehouses' },
            { title: 'Нов магацин', href: '#' },
        ],
    },
});

type Company = { id: number; name: string };

defineProps<{ companies: Company[] }>();

const form = useForm({
    company_id: '',
    name: '',
    location: '',
    is_active: true,
});

function submit() {
    form.post('/warehouses');
}
</script>

<template>
    <Head title="Нов магацин" />

    <div class="p-6">
        <Card class="max-w-lg">
            <CardHeader><CardTitle>Нов магацин</CardTitle></CardHeader>
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
                        <Label>Назив *</Label>
                        <Input v-model="form.name" placeholder="npr. Централен магацин" />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Локација</Label>
                        <Input v-model="form.location" placeholder="npr. ул. Партизанска 12, Скопје" />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox v-model:checked="form.is_active" />
                        <Label>Активен</Label>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Зачувување…' : 'Зачувај' }}
                        </Button>
                        <Button type="button" variant="outline" @click="$inertia.visit('/warehouses')">Откажи</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
