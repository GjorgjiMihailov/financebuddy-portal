<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Вработени', href: '/employees' }],
    },
});

type Company = { id: number; name: string };
type Employee = {
    id: number; first_name: string; last_name: string;
    embg: string | null; position: string | null;
    net_salary: string; bank_account: string | null;
    is_active: boolean; hired_at: string | null;
    company: Company;
};
type Paginated = { data: Employee[]; total: number; last_page: number; links: { url: string | null; label: string; active: boolean }[] };

const props = defineProps<{ employees: Paginated; companies: Company[]; filters: { company_id?: string; search?: string } }>();

const companyFilter = ref(props.filters.company_id ?? '');
const searchFilter = ref(props.filters.search ?? '');

function applyFilter() {
    const params: Record<string, string> = {};
    if (companyFilter.value) params.company_id = companyFilter.value;
    if (searchFilter.value) params.search = searchFilter.value;
    router.get('/employees', params, { replace: true });
}

function deleteEmployee(e: Employee) {
    if (!confirm(`Избриши вработен "${e.first_name} ${e.last_name}"?`)) return;
    router.delete(`/employees/${e.id}`);
}

function formatSalary(val: string): string {
    return Number(val).toLocaleString('mk-MK', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head title="Вработени" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Вработени</h1>
                <p class="text-sm text-muted-foreground">{{ employees.total }} вкупно</p>
            </div>
            <Button as-child>
                <Link href="/employees/create">
                    <Plus class="mr-2 size-4" />
                    Нов вработен
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap gap-3">
            <Input
                v-model="searchFilter"
                placeholder="Пребарај по име или ЕМБГ…"
                class="w-64"
                @keyup.enter="applyFilter"
            />
            <Select v-model="companyFilter" @update:model-value="applyFilter">
                <SelectTrigger class="w-56"><SelectValue placeholder="Сите компании" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Сите компании</SelectItem>
                    <SelectItem v-for="c in companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                </SelectContent>
            </Select>
            <Button variant="outline" @click="applyFilter">Пребарај</Button>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Вработен</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">ЕМБГ</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Позиција</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Нето плата</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Компанија</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="employees.data.length === 0">
                        <td colspan="7" class="py-12 text-center text-muted-foreground">Нема вработени</td>
                    </tr>
                    <tr v-for="emp in employees.data" :key="emp.id" class="border-b last:border-0 hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium">{{ emp.first_name }} {{ emp.last_name }}</td>
                        <td class="px-4 py-3 font-mono text-muted-foreground">{{ emp.embg ?? '—' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ emp.position ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ formatSalary(emp.net_salary) }} ден.</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ emp.company.name }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="emp.is_active ? 'secondary' : 'outline'">
                                {{ emp.is_active ? 'Активен' : 'Неактивен' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="`/employees/${emp.id}/edit`"><Pencil class="size-4" /></Link>
                                </Button>
                                <Button variant="ghost" size="icon" @click="deleteEmployee(emp)">
                                    <Trash2 class="size-4 text-destructive" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="employees.last_page > 1" class="flex justify-center gap-1">
            <Button v-for="link in employees.links" :key="link.label" :variant="link.active ? 'default' : 'outline'" size="sm" :disabled="!link.url" v-html="link.label" @click="link.url && router.visit(link.url)" />
        </div>
    </div>
</template>
