<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Company {
    id: number;
    name: string;
    tax_id: string | null;
    pending_count: number;
}

defineProps<{
    companies: Company[];
}>();

function select(companyId: number) {
    router.post('/select-company', { company_id: companyId });
}
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center bg-background p-6">
        <div class="w-full max-w-3xl">
            <div class="flex flex-col items-center gap-10">
                <div class="flex flex-col items-center gap-3 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-foreground/5">
                        <AppLogoIcon class="size-8 fill-current text-foreground dark:text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">Изберете обврзник</h1>
                        <p class="mt-1 text-sm text-muted-foreground">За кого работите денес?</p>
                    </div>
                </div>

                <div
                    v-if="companies.length === 0"
                    class="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
                >
                    Нема регистрирани компании.
                </div>

                <div v-else class="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <button
                        v-for="company in companies"
                        :key="company.id"
                        type="button"
                        @click="select(company.id)"
                        class="flex flex-col gap-4 rounded-xl border bg-card p-5 text-left shadow-sm transition-all hover:border-foreground/30 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-foreground/5">
                                <Building2 class="size-5 text-muted-foreground" />
                            </div>
                            <span
                                v-if="company.pending_count > 0"
                                class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                            >
                                {{ company.pending_count }} на чекање
                            </span>
                        </div>

                        <div>
                            <div class="font-medium leading-tight">{{ company.name }}</div>
                            <div v-if="company.tax_id" class="mt-0.5 text-xs text-muted-foreground">
                                ЕДБ: {{ company.tax_id }}
                            </div>
                        </div>

                        <div class="text-xs font-medium text-primary">Работи →</div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
