<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({ layout: null });

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Најава — FinanceBuddy.mk" />

    <!-- Full-page dark futuristic background -->
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#020817]">

        <!-- Ambient gradient blobs -->
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-60 -top-60 h-[700px] w-[700px] rounded-full bg-violet-700/20 blur-[140px]" />
            <div class="absolute -bottom-60 -right-60 h-[700px] w-[700px] rounded-full bg-sky-500/15 blur-[140px]" />
            <div class="absolute left-1/2 top-1/2 h-[400px] w-[400px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-700/10 blur-[100px]" />
        </div>

        <!-- Dot-grid overlay -->
        <div
            class="pointer-events-none absolute inset-0 opacity-40"
            style="background-image: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px); background-size: 32px 32px;"
        />

        <!-- Horizontal scanline accent -->
        <div class="pointer-events-none absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-gradient-to-r from-transparent via-violet-500/30 to-transparent" />

        <!-- Login card -->
        <div class="relative z-10 w-full max-w-md px-4 py-8">
            <div class="overflow-hidden rounded-2xl border border-white/[0.08] bg-white/[0.04] shadow-[0_32px_80px_-12px_rgba(0,0,0,0.8)] backdrop-blur-2xl">

                <!-- Top accent line -->
                <div class="h-px w-full bg-gradient-to-r from-transparent via-violet-500/60 to-transparent" />

                <div class="p-8">
                    <!-- Logo & brand -->
                    <div class="mb-10 flex flex-col items-center gap-4">
                        <div class="relative">
                            <div class="absolute inset-0 rounded-full bg-violet-500/20 blur-xl" />
                            <img
                                src="https://i.imgur.com/fEiVbXj.png"
                                alt="FinanceBuddy.mk"
                                class="relative h-14 w-auto drop-shadow-[0_0_20px_rgba(139,92,246,0.5)]"
                            />
                        </div>
                        <div class="text-center">
                            <h1 class="text-2xl font-bold tracking-tight text-white">
                                Finance<span class="bg-gradient-to-r from-violet-400 to-sky-400 bg-clip-text text-transparent">Buddy</span>.mk
                            </h1>
                            <p class="mt-1.5 text-[13px] text-slate-500">Управување со финансии · Сметководство</p>
                        </div>
                    </div>

                    <!-- Status message -->
                    <div
                        v-if="status"
                        class="mb-5 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-2.5 text-center text-sm text-emerald-400"
                    >
                        {{ status }}
                    </div>

                    <!-- Form -->
                    <Form
                        v-bind="store.form()"
                        :reset-on-success="['password']"
                        v-slot="{ errors, processing }"
                        class="flex flex-col gap-5"
                    >
                        <!-- Email -->
                        <div class="grid gap-1.5">
                            <Label for="email" class="text-xs font-medium uppercase tracking-widest text-slate-500">
                                Е-маил
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="vase@kompanija.mk"
                                class="h-11 border-white/10 bg-white/[0.06] text-white placeholder:text-slate-600 focus-visible:border-violet-500/60 focus-visible:ring-2 focus-visible:ring-violet-500/20"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <!-- Password -->
                        <div class="grid gap-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="password" class="text-xs font-medium uppercase tracking-widest text-slate-500">
                                    Лозинка
                                </Label>
                                <a
                                    v-if="canResetPassword"
                                    :href="request()"
                                    :tabindex="5"
                                    class="text-xs text-violet-500 transition-colors hover:text-violet-300"
                                >
                                    Заборавена лозинка?
                                </a>
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="h-11 border-white/10 bg-white/[0.06] text-white placeholder:text-slate-600 focus-visible:border-violet-500/60 focus-visible:ring-2 focus-visible:ring-violet-500/20"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <!-- Remember -->
                        <Label
                            for="remember"
                            class="flex cursor-pointer items-center gap-3 text-sm text-slate-400"
                        >
                            <Checkbox
                                id="remember"
                                name="remember"
                                :tabindex="3"
                                class="border-white/20 data-[state=checked]:bg-violet-600 data-[state=checked]:border-violet-600"
                            />
                            Запомни ме
                        </Label>

                        <!-- Submit -->
                        <Button
                            type="submit"
                            :tabindex="4"
                            :disabled="processing"
                            class="relative mt-2 h-11 w-full overflow-hidden rounded-lg bg-gradient-to-r from-violet-600 to-blue-600 text-sm font-semibold text-white shadow-lg shadow-violet-900/40 transition-all hover:from-violet-500 hover:to-blue-500 hover:shadow-violet-800/50 disabled:opacity-70"
                        >
                            <span class="relative z-10 flex items-center justify-center gap-2">
                                <Spinner v-if="processing" class="size-4" />
                                {{ processing ? 'Најавување…' : 'Најава' }}
                            </span>
                        </Button>
                    </Form>
                </div>

                <!-- Bottom accent line -->
                <div class="h-px w-full bg-gradient-to-r from-transparent via-sky-500/30 to-transparent" />
            </div>

            <!-- Footer -->
            <p class="mt-6 text-center text-[11px] text-slate-700">
                © {{ new Date().getFullYear() }} FinanceBuddy.mk · Сите права задржани
            </p>
        </div>
    </div>
</template>