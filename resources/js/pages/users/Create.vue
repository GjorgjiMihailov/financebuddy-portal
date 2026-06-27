<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

defineProps<{
    companies: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Корисници', href: '/users' },
            { title: 'Нов корисник', href: '/users/create' },
        ],
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '' as 'admin' | 'accountant' | 'company_admin' | '',
    company_ids: [] as number[],
});

function toggleCompany(id: number) {
    const idx = form.company_ids.indexOf(id);
    if (idx === -1) {
        form.company_ids.push(id);
    } else {
        form.company_ids.splice(idx, 1);
    }
}

function submit() {
    form.post('/users');
}
</script>

<template>
    <Head title="Нов корисник" />

    <div class="mx-auto max-w-2xl p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Нов корисник</h1>
            <p class="text-sm text-muted-foreground">Создај нова корисничка сметка</p>
        </div>

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="name">Ime и презиме <span class="text-destructive">*</span></Label>
                <Input
                    id="name"
                    v-model="form.name"
                    placeholder="пр. Марко Марковски"
                    :class="{ 'border-destructive': form.errors.name }"
                    required
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Е-пошта <span class="text-destructive">*</span></Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="korisnik@kompanija.mk"
                    :class="{ 'border-destructive': form.errors.email }"
                    required
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Лозинка <span class="text-destructive">*</span></Label>
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    placeholder="мин. 8 знаци"
                    :class="{ 'border-destructive': form.errors.password }"
                    required
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Потврди лозинка <span class="text-destructive">*</span></Label>
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    placeholder="повтори ја лозинката"
                    required
                />
            </div>

            <div class="grid gap-2">
                <Label>Улога <span class="text-destructive">*</span></Label>
                <Select
                    :model-value="form.role"
                    @update:model-value="(v) => (form.role = v as typeof form.role)"
                >
                    <SelectTrigger :class="{ 'border-destructive': form.errors.role }">
                        <SelectValue placeholder="Изберете улога" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="admin">Администратор</SelectItem>
                        <SelectItem value="accountant">Сметководител</SelectItem>
                        <SelectItem value="company_admin">Клиент (company_admin)</SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.role" />
            </div>

            <div v-if="form.role === 'company_admin'" class="grid gap-3">
                <Label>Компании</Label>
                <div class="rounded-lg border divide-y">
                    <p v-if="companies.length === 0" class="px-4 py-3 text-sm text-muted-foreground">
                        Нема додадени компании.
                    </p>
                    <div
                        v-for="company in companies"
                        :key="company.id"
                        class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-muted/30 transition-colors"
                        @click="toggleCompany(company.id)"
                    >
                        <Checkbox
                            :id="`company-${company.id}`"
                            :checked="form.company_ids.includes(company.id)"
                            @update:checked="toggleCompany(company.id)"
                            @click.stop
                        />
                        <Label :for="`company-${company.id}`" class="cursor-pointer font-normal">
                            {{ company.name }}
                        </Label>
                    </div>
                </div>
                <InputError :message="form.errors.company_ids" />
            </div>

            <div class="flex items-center justify-end gap-3 border-t pt-4">
                <Button type="button" variant="outline" as-child>
                    <Link href="/users">Откажи</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Се создава...' : 'Создај корисник' }}
                </Button>
            </div>
        </form>
    </div>
</template>
