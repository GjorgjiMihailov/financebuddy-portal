<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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

    <div class="flex min-h-screen font-sans">

        <!-- ════════════════════════════════════════
             LEFT PANEL  (hidden on mobile)
        ════════════════════════════════════════ -->
        <div class="hidden lg:flex lg:w-[48%] relative flex-col justify-between overflow-hidden bg-[#1a1a24] p-12">

            <!-- ── Grid mesh background ── -->
            <svg class="pointer-events-none absolute inset-0 h-full w-full" viewBox="0 0 640 960" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="fb-grid" width="64" height="64" patternUnits="userSpaceOnUse">
                        <path d="M64 0L0 0 0 64" fill="none" stroke="rgba(255,102,0,0.07)" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#fb-grid)"/>

                <!-- Radial fade mask -->
                <defs>
                    <radialGradient id="fade" cx="50%" cy="60%" r="70%">
                        <stop offset="0%" stop-color="transparent"/>
                        <stop offset="100%" stop-color="#1a1a24"/>
                    </radialGradient>
                </defs>
                <rect width="100%" height="100%" fill="url(#fade)"/>

                <!-- Financial growth polyline -->
                <polyline
                    points="0,760 80,700 180,650 300,530 400,420 500,300 600,190 640,160"
                    fill="none" stroke="rgba(255,102,0,0.35)" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round"/>
                <!-- Area fill under line -->
                <polygon
                    points="0,760 80,700 180,650 300,530 400,420 500,300 600,190 640,160 640,960 0,960"
                    fill="rgba(255,102,0,0.04)"/>
                <!-- Data-point dots -->
                <circle cx="80"  cy="700" r="3.5" fill="rgba(255,102,0,0.4)"/>
                <circle cx="180" cy="650" r="3.5" fill="rgba(255,102,0,0.4)"/>
                <circle cx="300" cy="530" r="5"   fill="rgba(255,102,0,0.6)"/>
                <circle cx="400" cy="420" r="5"   fill="rgba(255,102,0,0.6)"/>
                <circle cx="500" cy="300" r="7"   fill="rgba(255,102,0,0.8)"/>
                <circle cx="600" cy="190" r="9"   fill="#ff6600"/>
                <!-- Pulse ring on last point -->
                <circle cx="600" cy="190" r="20"  fill="none" stroke="rgba(255,102,0,0.25)" stroke-width="1"/>

                <!-- Decorative rings top-right -->
                <circle cx="590" cy="90"  r="140" fill="none" stroke="rgba(255,102,0,0.06)" stroke-width="1"/>
                <circle cx="590" cy="90"  r="90"  fill="none" stroke="rgba(255,102,0,0.04)" stroke-width="1"/>

                <!-- Decorative triangle bottom-left -->
                <polygon points="30,880 130,720 230,880" fill="none" stroke="rgba(255,102,0,0.07)" stroke-width="1"/>
            </svg>

            <!-- ── Ambient glows ── -->
            <div class="pointer-events-none absolute bottom-0 left-0 h-72 w-72 rounded-full bg-[#ff6600]/10 blur-[90px]"/>
            <div class="pointer-events-none absolute right-0 top-20 h-52 w-52 rounded-full bg-[#ff6600]/8 blur-[70px]"/>

            <!-- ── Top: Logo ── -->
            <div class="relative z-10 flex items-center gap-3">
                <img src="https://i.imgur.com/fEiVbXj.png" alt="FinanceBuddy.mk" class="h-9 w-auto"/>
                <span class="text-lg font-bold tracking-tight text-white">
                    Finance<span class="text-[#ff6600]">Buddy</span>.mk
                </span>
            </div>

            <!-- ── Middle: Headline + features ── -->
            <div class="relative z-10 space-y-8">
                <div class="space-y-4">
                    <h1 class="text-[2.6rem] font-extrabold leading-[1.15] tracking-tight text-white">
                        Паметно<br/>сметководство<br/>за раст на<br/>
                        <span class="text-[#ff6600]">вашиот бизнис.</span>
                    </h1>
                    <p class="max-w-xs text-[15px] leading-relaxed text-slate-400">
                        Управувајте со финансиите, издавајте фактури и водете книговодство — сè на едно место.
                    </p>
                </div>

                <!-- Feature list -->
                <ul class="space-y-3">
                    <li v-for="feat in [
                        'Автоматско книговодство и налози',
                        'Управување со влезни и излезни фактури',
                        'Читање банкарски извод и скенирање документи',
                    ]" :key="feat" class="flex items-center gap-3 text-sm text-slate-300">
                        <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-[#ff6600]/15">
                            <span class="h-2 w-2 rounded-full bg-[#ff6600]"/>
                        </span>
                        {{ feat }}
                    </li>
                </ul>
            </div>

            <!-- ── Bottom: Copyright ── -->
            <p class="relative z-10 text-xs text-slate-700">
                © {{ new Date().getFullYear() }} FinanceBuddy.mk · Сите права задржани
            </p>
        </div>

        <!-- ════════════════════════════════════════
             RIGHT PANEL  (Form)
        ════════════════════════════════════════ -->
        <div class="flex flex-1 flex-col items-center justify-center bg-white px-6 py-14 sm:px-12">

            <!-- Mobile logo (only on sm/md) -->
            <div class="mb-8 flex flex-col items-center gap-2 lg:hidden">
                <img src="https://i.imgur.com/fEiVbXj.png" alt="FinanceBuddy.mk" class="h-12 w-auto"/>
                <span class="text-xl font-bold text-gray-900">
                    Finance<span class="text-[#ff6600]">Buddy</span>.mk
                </span>
            </div>

            <div class="w-full max-w-[380px]">

                <!-- Heading -->
                <div class="mb-8">
                    <h2 class="text-[1.75rem] font-bold tracking-tight text-gray-900">Најавете се</h2>
                    <p class="mt-1.5 text-sm text-gray-500">Внесете ги вашите податоци за пристап</p>
                </div>

                <!-- Status -->
                <div v-if="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ status }}
                </div>

                <!-- ── Form ── -->
                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="space-y-5"
                >
                    <!-- Email -->
                    <div class="space-y-1.5">
                        <Label for="email" class="text-[13px] font-semibold text-gray-700">Е-маил адреса</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="vase@kompanija.mk"
                            class="h-11 rounded-lg border-gray-200 bg-gray-50 text-gray-900 placeholder:text-gray-400 transition-all duration-200 focus-visible:border-[#ff6600] focus-visible:bg-white focus-visible:ring-2 focus-visible:ring-[#ff6600]/20"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-[13px] font-semibold text-gray-700">Лозинка</Label>
                            <a
                                v-if="canResetPassword"
                                :href="request()"
                                :tabindex="5"
                                class="text-xs text-gray-400 transition-colors hover:text-[#ff6600]"
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
                            class="h-11 rounded-lg border-gray-200 bg-gray-50 text-gray-900 placeholder:text-gray-400 transition-all duration-200 focus-visible:border-[#ff6600] focus-visible:bg-white focus-visible:ring-2 focus-visible:ring-[#ff6600]/20"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <!-- Remember me -->
                    <Label for="remember" class="flex cursor-pointer select-none items-center gap-3 text-sm text-gray-600">
                        <Checkbox
                            id="remember"
                            name="remember"
                            :tabindex="3"
                            class="border-gray-300 data-[state=checked]:border-[#ff6600] data-[state=checked]:bg-[#ff6600]"
                        />
                        Запомни ме
                    </Label>

                    <!-- Submit button -->
                    <button
                        type="submit"
                        :tabindex="4"
                        :disabled="processing"
                        class="mt-1 flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#ff6600] text-base font-semibold text-white shadow-sm transition-all duration-200 hover:bg-[#e65c00] hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ff6600]/40 focus-visible:ring-offset-2 disabled:opacity-60"
                    >
                        <Spinner v-if="processing" class="size-4" />
                        {{ processing ? 'Најавување…' : 'Најава' }}
                    </button>
                </Form>
            </div>
        </div>
    </div>
</template>