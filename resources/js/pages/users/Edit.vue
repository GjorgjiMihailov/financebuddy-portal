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

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        role: 'admin' | 'accountant' | 'company_admin' | null;
        company_ids: number[];
    };
    companies: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Корисници', href: '/users' },
            { title: 'Уреди корисник', href: '#' },
        ],
    },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role: props.user.role ?? ('' as 'admin' | 'accountant' | 'company_admin' | ''),
    company_ids: [...props.user.company_ids],
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
    form.put(`/users/${props.user.id}`);
}
</script>

<template>
    <Head title="Уреди корисник" />

    <div class="mx-auto max-w-2xl p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Уреди корисник</h1>
            <p class="text-sm text-muted-foreground">{{ user.email }}</p>
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
                <Label for="password">Нова лозинка <span class="text-muted-foreground font-normal">(остави празно за да не се менува)</span></Label>
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    placeholder="мин. 8 знаци"
                    :class="{ 'border-destructive': form.errors.password }"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div v-if="form.password" class="grid gap-2">
                <Label for="password_confirmation">Потврди лозинка <span class="text-destructive">*</span></Label>
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    placeholder="повтори ја новата лозинка"
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
                    {{ form.processing ? 'Се зачувува...' : 'Зачувај' }}
                </Button>
            </div>
        </form>
    </div>
</template>
