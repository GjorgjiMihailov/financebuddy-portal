<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Building2, FileText, Pencil } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Company } from '@/types';

const props = defineProps<{
    company: Company;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Компании', href: '/companies' },
            { title: 'Детали', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="company.name" />

    <div class="mx-auto max-w-2xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/companies">
                    <ArrowLeft class="mr-2 size-4" />
                    Назад
                </Link>
            </Button>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="`/documents?company_id=${company.id}`">
                        <FileText class="mr-2 size-4" />
                        Документи
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="`/companies/${company.id}/edit`">
                        <Pencil class="mr-2 size-4" />
                        Уреди
                    </Link>
                </Button>
            </div>
        </div>

        <Card>
            <CardHeader>
                <div class="flex items-center gap-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-primary/10">
                        <Building2 class="size-6 text-primary" />
                    </div>
                    <div>
                        <CardTitle class="text-xl">{{ company.name }}</CardTitle>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="font-mono text-sm text-muted-foreground">{{ company.tax_id }}</span>
                            <Badge v-if="company.is_vat_registered" variant="secondary">ДДВ обврзник</Badge>
                        </div>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <dl class="grid gap-3 text-sm">
                    <div v-if="company.vat_number" class="flex justify-between border-b pb-3">
                        <dt class="text-muted-foreground">ДДВ број</dt>
                        <dd class="font-mono">{{ company.vat_number }}</dd>
                    </div>
                    <div v-if="company.address" class="flex justify-between border-b pb-3">
                        <dt class="text-muted-foreground">Адреса</dt>
                        <dd>{{ company.address }}</dd>
                    </div>
                    <div v-if="company.email" class="flex justify-between border-b pb-3">
                        <dt class="text-muted-foreground">Е-пошта</dt>
                        <dd>
                            <a :href="`mailto:${company.email}`" class="text-primary hover:underline">
                                {{ company.email }}
                            </a>
                        </dd>
                    </div>
                    <div v-if="company.phone" class="flex justify-between border-b pb-3">
                        <dt class="text-muted-foreground">Телефон</dt>
                        <dd>
                            <a :href="`tel:${company.phone}`">{{ company.phone }}</a>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Додадено од</dt>
                        <dd>{{ company.creator?.name ?? '—' }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    </div>
</template>
