<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Вработени', href: '/employees' },
            { title: 'Уреди', href: '#' },
        ],
    },
});

type Employee = {
    id: number; first_name: string; last_name: string;
    embg: string | null; position: string | null;
    net_salary: string; bank_account: string | null;
    is_active: boolean; hired_at: string | null;
};

const props = defineProps<{ employee: Employee }>();

const form = useForm({
    first_name:   props.employee.first_name,
    last_name:    props.employee.last_name,
    embg:         props.employee.embg ?? '',
    position:     props.employee.position ?? '',
    net_salary:   props.employee.net_salary,
    bank_account: props.employee.bank_account ?? '',
    is_active:    props.employee.is_active,
    hired_at:     props.employee.hired_at ?? '',
});

function submit() {
    form.put(`/employees/${props.employee.id}`);
}
</script>

<template>
    <Head :title="`${employee.first_name} ${employee.last_name}`" />
    <div class="p-6">
        <Card class="max-w-lg">
            <CardHeader>
                <CardTitle>Уреди вработен — {{ employee.first_name }} {{ employee.last_name }}</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="grid gap-4" @submit.prevent="submit">
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
                            <Input v-model="form.embg" maxlength="13" />
                            <p v-if="form.errors.embg" class="text-xs text-destructive">{{ form.errors.embg }}</p>
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Позиција</Label>
                            <Input v-model="form.position" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-1.5">
                            <Label>Нето плата (ден.)</Label>
                            <Input v-model="form.net_salary" type="number" step="0.01" min="0" />
                            <p v-if="form.errors.net_salary" class="text-xs text-destructive">{{ form.errors.net_salary }}</p>
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Трансакциска сметка</Label>
                            <Input v-model="form.bank_account" />
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
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Зачувување…' : 'Зачувај промени' }}</Button>
                        <Button type="button" variant="outline" @click="$inertia.visit('/employees')">Откажи</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
