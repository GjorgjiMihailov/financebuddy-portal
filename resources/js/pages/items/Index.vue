<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Артикли', href: '/items' }],
    },
});

type Company = { id: number; name: string };
type Item = {
    id: number; code: string; name: string; unit: string;
    vat_category: string; price_without_vat: string; is_active: boolean;
    company: Company;
};
type Paginated = { data: Item[]; total: number; last_page: number; links: { url: string | null; label: string; active: boolean }[] };

const props = defineProps<{ items: Paginated; companies: Company[]; filters: { company_id?: string } }>();

const companyFilter = ref(props.filters.company_id ?? '');

function applyFilter() {
    router.get('/items', companyFilter.value ? { company_id: companyFilter.value } : {}, { replace: true });
}

function formatPrice(val: string): string {
    return Number(val).toLocaleString('mk-MK', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head title="Артикли" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Артикли</h1>
                <p class="text-sm text-muted-foreground">{{ items.total }} вкупно</p>
            </div>
            <Button as-child>
                <Link href="/items/create">
                    <Plus class="mr-2 size-4" />
                    Нов артикл
                </Link>
            </Button>
        </div>

        <div class="flex gap-3">
            <Select v-model="companyFilter" @update:model-value="applyFilter">
                <SelectTrigger class="w-64"><SelectValue placeholder="Сите компании" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Сите компании</SelectItem>
                    <SelectItem v-for="c in companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Шифра</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Назив</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">ЕМ</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">ДДВ %</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Цена без ДДВ</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Компанија</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="items.data.length === 0">
                        <td colspan="8" class="py-12 text-center text-muted-foreground">Нема артикли</td>
                    </tr>
                    <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono">{{ item.code }}</td>
                        <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ item.unit }}</td>
                        <td class="px-4 py-3">{{ item.vat_category }}%</td>
                        <td class="px-4 py-3 text-right font-mono">{{ formatPrice(item.price_without_vat) }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ item.company.name }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="item.is_active ? 'secondary' : 'outline'">{{ item.is_active ? 'Активен' : 'Неактивен' }}</Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button variant="ghost" size="sm" as-child>
                                <Link :href="`/items/${item.id}/edit`">Уреди</Link>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="items.last_page > 1" class="flex justify-center gap-1">
            <Button v-for="link in items.links" :key="link.label" :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" v-html="link.label" @click="link.url && router.visit(link.url)" />
        </div>
    </div>
</template>
