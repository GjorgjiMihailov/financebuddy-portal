<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarRange } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineProps<{
    years: number[];
    currentYear: number;
}>();

function select(year: number) {
    router.post('/select-year', { year });
}
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center bg-background p-6">
        <div class="w-full max-w-2xl">
            <div class="flex flex-col items-center gap-10">
                <div class="flex flex-col items-center gap-3 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-foreground/5">
                        <AppLogoIcon class="size-8 fill-current text-foreground dark:text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">Изберете работна година</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Во која година работите сега?</p>
                    </div>
                </div>

                <div class="grid w-full grid-cols-2 gap-4 sm:grid-cols-3">
                    <button
                        v-for="year in years"
                        :key="year"
                        type="button"
                        @click="select(year)"
                        class="flex flex-col items-center gap-3 rounded-xl border bg-card p-6 text-center shadow-sm transition-all hover:border-foreground/30 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :class="{ 'border-primary/50 bg-primary/5': year === currentYear }"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-foreground/5">
                            <CalendarRange class="size-5 text-muted-foreground" />
                        </div>
                        <div class="text-lg font-semibold">{{ year }}</div>
                        <div v-if="year === currentYear" class="text-xs font-medium text-primary">Тековна година</div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
