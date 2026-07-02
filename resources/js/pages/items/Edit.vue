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
            { title: 'Артикли', href: '/items' },
            { title: 'Уреди артикл', href: '#' },
        ],
    },
});

type Item = {
    id: number;
    code: string;
    name: string;
    unit: string;
    vat_category: string;
    price_without_vat: string;
    is_active: boolean;
    is_service: boolean;
    is_macedonian: boolean;
};

const props = defineProps<{ item: Item }>();

const form = useForm({
    name:              props.item.name,
    unit:              props.item.unit,
    vat_category:      props.item.vat_category,
    price_without_vat: props.item.price_without_vat,
    is_active:         props.item.is_active,
    is_service:        props.item.is_service,
    is_macedonian:     props.item.is_macedonian,
});

function submit() {
    form.put(`/items/${props.item.id}`);
}
</script>

<template>
    <Head :title="`Уреди: ${item.name}`" />
    <div class="p-6">
        <Card class="max-w-lg">
            <CardHeader>
                <CardTitle>{{ item.code }} — {{ item.name }}</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="grid gap-4" @submit.prevent="submit">

                    <div class="grid gap-1.5">
                        <Label>Назив *</Label>
                        <Input v-model="form.name" placeholder="Назив на артиклот" />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-1.5">
                            <Label>Единица мерка *</Label>
                            <Input v-model="form.unit" placeholder="бр, кг, л…" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Цена без ДДВ *</Label>
                            <Input v-model="form.price_without_vat" type="number" step="0.01" min="0" placeholder="0.00" />
                            <p v-if="form.errors.price_without_vat" class="text-xs text-destructive">{{ form.errors.price_without_vat }}</p>
                        </div>
                    </div>

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

                    <div class="flex gap-3 pt-2">
                        <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Зачувување…' : 'Зачувај промени' }}</Button>
                        <Button type="button" variant="outline" @click="$inertia.visit('/items')">Откажи</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
