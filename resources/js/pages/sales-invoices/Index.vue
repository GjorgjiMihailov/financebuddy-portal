<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Излезни фактури', href: '/sales-invoices' }],
    },
});

type Company = { id: number; name: string };
type Invoice = {
    id: number; client_name: string; invoice_number: string;
    date: string; total_amount: string; status: string;
    company: Company;
};
type Paginated = { data: Invoice[]; total: number; last_page: number; links: { url: string | null; label: string; active: boolean }[] };

const props = defineProps<{ invoices: Paginated; companies: Company[]; filters: { company_id?: string; status?: string } }>();

const companyFilter = ref(props.filters.company_id ?? '');
const statusFilter = ref(props.filters.status ?? '');

function applyFilter() {
    const params: Record<string, string> = {};
    if (companyFilter.value) params.company_id = companyFilter.value;
    if (statusFilter.value) params.status = statusFilter.value;
    router.get('/sales-invoices', params, { replace: true });
}

const STATUS_LABELS: Record<string, string> = { draft: 'Нацрт', sent: 'Испратена', booked: 'Прокнижена' };
const STATUS_VARIANT: Record<string, 'outline' | 'secondary' | 'default'> = { draft: 'outline', sent: 'secondary', booked: 'default' };

function formatAmount(val: string): string {
    return Number(val).toLocaleString('mk-MK', { minimumFractionDigits: 2 });
}

function formatDate(d: string): string {
    return new Date(d).toLocaleDateString('mk-MK', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head title="Излезни фактури" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Излезни фактури</h1>
                <p class="text-sm text-muted-foreground">{{ invoices.total }} вкупно</p>
            </div>
            <Button as-child>
                <Link href="/sales-invoices/create">
                    <Plus class="mr-2 size-4" />
                    Нова фактура
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap gap-3">
            <Select v-model="companyFilter" @update:model-value="applyFilter">
                <SelectTrigger class="w-56"><SelectValue placeholder="Сите компании" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Сите компании</SelectItem>
                    <SelectItem v-for="c in companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="statusFilter" @update:model-value="applyFilter">
                <SelectTrigger class="w-44"><SelectValue placeholder="Сите статуси" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Сите</SelectItem>
                    <SelectItem value="draft">Нацрт</SelectItem>
                    <SelectItem value="sent">Испратена</SelectItem>
                    <SelectItem value="booked">Прокнижена</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Клиент</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Број фактура</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Датум</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Износ</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Компанија</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="6" class="py-12 text-center text-muted-foreground">Нема излезни фактури</td>
                    </tr>
                    <tr v-for="inv in invoices.data" :key="inv.id" class="border-b last:border-0 hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium">{{ inv.client_name }}</td>
                        <td class="px-4 py-3 font-mono text-muted-foreground">{{ inv.invoice_number }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ formatDate(inv.date) }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ formatAmount(inv.total_amount) }} ден.</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ inv.company.name }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="STATUS_VARIANT[inv.status] ?? 'outline'">{{ STATUS_LABELS[inv.status] ?? inv.status }}</Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="invoices.last_page > 1" class="flex justify-center gap-1">
            <Button v-for="link in invoices.links" :key="link.label" :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" v-html="link.label" @click="link.url && router.visit(link.url)" />
        </div>
    </div>
</template>
