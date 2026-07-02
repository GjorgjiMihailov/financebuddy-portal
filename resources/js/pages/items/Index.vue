<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, X } from '@lucide/vue';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Артикли', href: '/items' }],
    },
});

type Company = { id: number; name: string };
type Item = {
    id: number;
    code: string;
    name: string;
    unit: string;
    vat_category: string;
    price_without_vat: string;
    is_active: boolean;
    current_stock: string | null;
    company: Company;
};
type Paginated = {
    data: Item[];
    total: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
};

const props = defineProps<{
    items: Paginated;
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');

function applyFilters() {
    router.get('/items', { ...(search.value ? { search: search.value } : {}) }, { replace: true });
}

function clearFilters() {
    search.value = '';
    router.get('/items', {}, { replace: true });
}

const hasFilters = computed(() => !!search.value);

function formatPrice(val: string): string {
    return Number(val).toLocaleString('mk-MK', { minimumFractionDigits: 2 });
}

function formatStock(val: string | null): string {
    if (val === null || val === undefined) return '—';
    const n = parseFloat(val);
    if (isNaN(n)) return '—';
    return n.toLocaleString('mk-MK', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
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

        <!-- Filters -->
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex min-w-56 flex-1 items-center gap-2 rounded-md border px-3">
                <Search class="size-4 shrink-0 text-muted-foreground" />
                <Input
                    v-model="search"
                    placeholder="Пребарај по шифра или назив…"
                    class="border-0 bg-transparent px-0 focus-visible:ring-0"
                    @keyup.enter="applyFilters"
                />
            </div>

            <Button variant="outline" @click="applyFilters">
                <Search class="mr-2 size-4" />
                Пребарај
            </Button>

            <Button v-if="hasFilters" variant="ghost" size="icon" @click="clearFilters" title="Исчисти">
                <X class="size-4" />
            </Button>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Шифра</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Назив</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">ЈМ</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">ДДВ %</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Цена без ДДВ</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Залихи</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Компанија</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="items.data.length === 0">
                        <td colspan="9" class="py-12 text-center text-muted-foreground">Нема артикли</td>
                    </tr>
                    <tr v-for="item in items.data" :key="item.id" class="border-b last:border-0 hover:bg-muted/30" :class="{ 'opacity-50': !item.is_active }">
                        <td class="px-4 py-3 font-mono text-xs">{{ item.code }}</td>
                        <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ item.unit }}</td>
                        <td class="px-4 py-3">{{ item.vat_category }}%</td>
                        <td class="px-4 py-3 text-right font-mono">{{ formatPrice(item.price_without_vat) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span :class="parseFloat(item.current_stock ?? '0') > 0 ? 'font-semibold text-green-700' : 'text-muted-foreground'">
                                {{ formatStock(item.current_stock) }}
                            </span>
                        </td>
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
            <Button
                v-for="link in items.links"
                :key="link.label"
                :variant="link.active ? 'default' : 'outline'"
                size="sm"
                :disabled="!link.url"
                v-html="link.label"
                @click="link.url && router.visit(link.url)"
            />
        </div>
    </div>
</template>
