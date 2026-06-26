<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    form: any;
}>();

const emit = defineEmits<{ submit: [] }>();

watch(
    () => props.form.is_vat_registered,
    (val) => {
        if (!val) props.form.vat_number = '';
    },
);
</script>

<template>
    <form class="flex flex-col gap-5" @submit.prevent="emit('submit')">
        <div class="grid gap-2">
            <Label for="name">Назив <span class="text-destructive">*</span></Label>
            <Input
                id="name"
                v-model="form.name"
                placeholder="пр. ДОО Некоја Компанија"
                :class="{ 'border-destructive': form.errors.name }"
                required
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="tax_id">ЕДБ (Единствен даночен број) <span class="text-destructive">*</span></Label>
            <Input
                id="tax_id"
                v-model="form.tax_id"
                placeholder="4030000000000"
                maxlength="20"
                :class="{ 'border-destructive': form.errors.tax_id }"
                required
            />
            <InputError :message="form.errors.tax_id" />
        </div>

        <div class="flex items-start gap-3 rounded-lg border p-4">
            <Checkbox
                id="is_vat_registered"
                :checked="form.is_vat_registered"
                class="mt-0.5"
                @update:checked="(v: boolean) => (form.is_vat_registered = v)"
            />
            <div>
                <Label for="is_vat_registered" class="cursor-pointer font-medium">ДДВ обврзник</Label>
                <p class="text-sm text-muted-foreground">Компанијата е регистрирана за ДДВ</p>
            </div>
        </div>

        <div v-if="form.is_vat_registered" class="grid gap-2">
            <Label for="vat_number">ДДВ број <span class="text-destructive">*</span></Label>
            <Input
                id="vat_number"
                v-model="form.vat_number"
                placeholder="MK4030000000000"
                maxlength="20"
                :class="{ 'border-destructive': form.errors.vat_number }"
            />
            <InputError :message="form.errors.vat_number" />
        </div>

        <div class="grid gap-2">
            <Label for="address">Адреса</Label>
            <Input
                id="address"
                v-model="form.address"
                placeholder="ул. Македонија бб, Скопје"
                :class="{ 'border-destructive': form.errors.address }"
            />
            <InputError :message="form.errors.address" />
        </div>

        <div class="grid gap-2">
            <Label for="email">Е-пошта</Label>
            <Input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="info@kompanija.mk"
                :class="{ 'border-destructive': form.errors.email }"
            />
            <InputError :message="form.errors.email" />
        </div>

        <div class="grid gap-2">
            <Label for="phone">Телефон</Label>
            <Input
                id="phone"
                v-model="form.phone"
                placeholder="+389 2 123 456"
                :class="{ 'border-destructive': form.errors.phone }"
            />
            <InputError :message="form.errors.phone" />
        </div>

        <div class="flex items-center justify-end gap-3 border-t pt-4">
            <Button type="button" variant="outline" as-child>
                <Link href="/companies">Откажи</Link>
            </Button>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Се зачувува...' : 'Зачувај' }}
            </Button>
        </div>
    </form>
</template>
