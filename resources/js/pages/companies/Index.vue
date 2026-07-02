<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Company, PaginatedCompanies } from '@/types';

defineProps<{
    companies: PaginatedCompanies;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Компании', href: '/companies' }],
    },
});

const deleteTarget = ref<Company | null>(null);

function doDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/companies/${deleteTarget.value.id}`, {
        onFinish: () => { deleteTarget.value = null; },
    });
}
</script>

<template>
    <Head title="Компании" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Компании</h1>
                <p class="text-sm text-muted-foreground">
                    {{ companies.total }}
                    {{ companies.total === 1 ? 'компанија' : 'компании' }}
                </p>
            </div>
            <Button as-child>
                <Link href="/companies/create">
                    <Plus class="mr-2 size-4" />
                    Нова компанија
                </Link>
            </Button>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Назив</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">ЕДБ</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">ДДВ</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Е-пошта</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Телефон</th>
                        <th class="w-24 px-3 py-1.5" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="companies.data.length === 0">
                        <td colspan="6" class="py-16 text-center text-muted-foreground">
                            <Building2 class="mx-auto mb-3 size-10 opacity-30" />
                            <p class="font-medium">Нема компании</p>
                            <p class="text-xs">Додај ја првата компанија со копчето горе</p>
                        </td>
                    </tr>
                    <tr
                        v-for="company in companies.data"
                        :key="company.id"
                        class="cursor-pointer border-b last:border-0 hover:bg-muted/30 transition-colors"
                        @click="router.visit(`/companies/${company.id}`)"
                    >
                        <td class="px-3 py-1.5 font-medium">{{ company.name }}</td>
                        <td class="px-3 py-1.5 font-mono text-xs text-muted-foreground">{{ company.tax_id }}</td>
                        <td class="px-3 py-1.5">
                            <Badge v-if="company.is_vat_registered" variant="secondary">ДДВ</Badge>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-3 py-1.5 text-muted-foreground">{{ company.email ?? '—' }}</td>
                        <td class="px-3 py-1.5 text-muted-foreground">{{ company.phone ?? '—' }}</td>
                        <td class="px-3 py-1.5" @click.stop>
                            <div class="flex items-center justify-end gap-1">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="`/companies/${company.id}/edit`">
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive hover:text-destructive"
                                    @click="deleteTarget = company"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="companies.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in companies.links"
                :key="link.label"
                :variant="link.active ? 'default' : 'outline'"
                size="sm"
                :disabled="!link.url"
                v-html="link.label"
                @click="link.url && router.visit(link.url, { preserveScroll: true })"
            />
        </div>
    </div>

    <Dialog :open="!!deleteTarget" @update:open="(v) => { if (!v) deleteTarget = null }">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Избриши компанија</DialogTitle>
                <DialogDescription>
                    Дали сте сигурни дека сакате да ја избришете компанијата
                    <strong>{{ deleteTarget?.name }}</strong>?
                    Документите поврзани со неа нема да се избришат.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="deleteTarget = null">Откажи</Button>
                <Button variant="destructive" @click="doDelete">Избриши</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
