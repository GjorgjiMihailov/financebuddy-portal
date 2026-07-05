<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({ layout: null })

const props = defineProps<{
    auth?: { user?: { name: string } }
}>()

const isLoggedIn = computed(() => !!props.auth?.user)
</script>

<template>
    <Head title="FinanceBuddy — Сметководствен портал за македонски компании" />

    <div class="min-h-screen bg-[#0f0f18] text-white font-sans">

        <!-- NAV -->
        <nav class="fixed top-0 inset-x-0 z-50 bg-[#0f0f18]/80 backdrop-blur border-b border-white/5">
            <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#ff6600] flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <span class="font-bold text-lg tracking-tight">FinanceBuddy</span>
                </div>

                <div class="flex items-center gap-3">
                    <template v-if="isLoggedIn">
                        <Link :href="route('dashboard')" class="px-4 py-2 rounded-lg bg-[#ff6600] hover:bg-[#e65c00] text-white text-sm font-medium transition-colors">
                            Оди на портал →
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-sm text-white/70 hover:text-white transition-colors px-3 py-2">
                            Најава
                        </Link>
                        <Link :href="route('register')" class="px-4 py-2 rounded-lg bg-[#ff6600] hover:bg-[#e65c00] text-white text-sm font-medium transition-colors">
                            Регистрирај се
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- HERO -->
        <section class="relative pt-40 pb-32 px-6 overflow-hidden">
            <!-- Background glows -->
            <div class="absolute top-20 left-1/4 w-96 h-96 bg-[#ff6600]/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-40 right-1/4 w-80 h-80 bg-[#ff6600]/5 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Grid dot pattern -->
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 32px 32px;"></div>

            <div class="relative max-w-4xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-[#ff6600]/30 bg-[#ff6600]/10 text-[#ff6600] text-xs font-medium mb-8">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#ff6600] animate-pulse"></span>
                    Сметководствен портал за Македонија
                </div>

                <h1 class="text-5xl lg:text-7xl font-bold leading-[1.08] tracking-tight mb-6">
                    Сметководство<br>
                    <span class="text-[#ff6600]">со AI</span>, без хаос
                </h1>

                <p class="text-xl text-white/50 max-w-2xl mx-auto mb-10 leading-relaxed">
                    Скенирај документи, книжи автоматски, управувај со фактури и залихи — сè на едно место, усогласено со македонскиот контен план.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link :href="route('register')" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-[#ff6600] hover:bg-[#e65c00] text-white font-semibold text-base transition-all hover:shadow-[0_0_30px_rgba(255,102,0,0.4)]">
                        Започни бесплатно →
                    </Link>
                    <Link :href="route('login')" class="w-full sm:w-auto px-8 py-4 rounded-xl border border-white/15 hover:border-white/30 text-white/80 hover:text-white font-medium text-base transition-colors">
                        Влези во порталот
                    </Link>
                </div>
            </div>

            <!-- Dashboard preview -->
            <div class="relative max-w-5xl mx-auto mt-20">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0f0f18] via-transparent to-transparent z-10 pointer-events-none" style="top: 60%;"></div>
                <div class="rounded-2xl border border-white/10 bg-[#1a1a28] overflow-hidden shadow-[0_40px_80px_rgba(0,0,0,0.6)]">
                    <!-- Fake browser bar -->
                    <div class="flex items-center gap-2 px-4 py-3 bg-[#14141f] border-b border-white/5">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-white/10"></div>
                            <div class="w-3 h-3 rounded-full bg-white/10"></div>
                            <div class="w-3 h-3 rounded-full bg-white/10"></div>
                        </div>
                        <div class="flex-1 mx-4">
                            <div class="mx-auto max-w-xs h-5 rounded bg-white/5 flex items-center justify-center">
                                <span class="text-[10px] text-white/25">portal.financebuddy.mk</span>
                            </div>
                        </div>
                    </div>
                    <!-- Dashboard mock -->
                    <div class="p-6 grid grid-cols-3 gap-4">
                        <div class="col-span-3 grid grid-cols-4 gap-3">
                            <div v-for="(stat, i) in [
                                { label: 'Вкупно приходи', value: '2,840,500 ден', color: 'text-emerald-400' },
                                { label: 'Вкупно расходи', value: '1,620,300 ден', color: 'text-red-400' },
                                { label: 'Отворени фактури', value: '14', color: 'text-amber-400' },
                                { label: 'Документи', value: '87', color: 'text-blue-400' },
                            ]" :key="i" class="p-4 rounded-xl bg-white/5 border border-white/5">
                                <p class="text-xs text-white/40 mb-1">{{ stat.label }}</p>
                                <p class="font-bold text-sm" :class="stat.color">{{ stat.value }}</p>
                            </div>
                        </div>
                        <div class="col-span-2 rounded-xl bg-white/5 border border-white/5 p-4 h-40 flex flex-col gap-2">
                            <p class="text-xs text-white/40 font-medium">Последни налози</p>
                            <div v-for="j in 4" :key="j" class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full" :class="['bg-emerald-400','bg-amber-400','bg-emerald-400','bg-blue-400'][j-1]"></div>
                                <div class="flex-1 h-2 rounded bg-white/5"></div>
                                <div class="w-16 h-2 rounded bg-white/5"></div>
                            </div>
                        </div>
                        <div class="rounded-xl bg-white/5 border border-white/5 p-4 h-40 flex flex-col gap-2">
                            <p class="text-xs text-white/40 font-medium">OCR Документи</p>
                            <div v-for="j in 3" :key="j" class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded bg-[#ff6600]/20 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-[#ff6600]" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                                </div>
                                <div class="flex-1 h-2 rounded bg-white/5"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="py-24 px-6">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-bold mb-4">Сè што ти треба на едно место</h2>
                    <p class="text-white/50 text-lg">Дизајниран за македонски сметководители и бизниси</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="feature in features" :key="feature.title"
                        class="group p-6 rounded-2xl border border-white/8 bg-white/3 hover:bg-white/6 hover:border-white/15 transition-all duration-300">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110"
                            :style="{ background: `${feature.color}18` }">
                            <component :is="'svg'" class="w-6 h-6" :style="{ color: feature.color }"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                                v-html="feature.icon">
                            </component>
                        </div>
                        <h3 class="font-semibold text-base mb-2">{{ feature.title }}</h3>
                        <p class="text-sm text-white/45 leading-relaxed">{{ feature.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ACCOUNT PLAN HIGHLIGHT -->
        <section class="py-20 px-6">
            <div class="max-w-6xl mx-auto">
                <div class="rounded-3xl border border-[#ff6600]/20 bg-gradient-to-br from-[#ff6600]/8 to-[#ff6600]/3 p-10 lg:p-16 flex flex-col lg:flex-row items-center gap-10">
                    <div class="flex-1">
                        <div class="inline-block px-3 py-1 rounded-full bg-[#ff6600]/15 text-[#ff6600] text-xs font-medium mb-4">
                            Официјален контен план
                        </div>
                        <h2 class="text-3xl lg:text-4xl font-bold mb-4">
                            Усогласен со<br>Правилник 174/2011
                        </h2>
                        <p class="text-white/50 text-base leading-relaxed mb-6">
                            Целиот контен план согласно УЈП — сите класи 0-9, со точни трицифрени сметки. Автоматско предлагање на сметки при книжење.
                        </p>
                        <Link :href="route('register')" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#ff6600] hover:bg-[#e65c00] text-white font-medium text-sm transition-colors">
                            Пробај сега →
                        </Link>
                    </div>
                    <div class="flex-shrink-0 w-full lg:w-80">
                        <div class="rounded-xl border border-white/10 bg-[#0f0f18]/80 overflow-hidden text-xs font-mono">
                            <div class="px-4 py-2 border-b border-white/5 text-white/30 text-[10px]">Контен план — извадок</div>
                            <div class="p-4 space-y-1.5">
                                <div v-for="row in chartSample" :key="row.code" class="flex items-center gap-3">
                                    <span class="text-[#ff6600] w-8 flex-shrink-0">{{ row.code }}</span>
                                    <span class="text-white/60 truncate">{{ row.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-24 px-6">
            <div class="max-w-2xl mx-auto text-center">
                <h2 class="text-4xl lg:text-5xl font-bold mb-6 leading-tight">
                    Готов да го<br>дигитализираш<br>
                    <span class="text-[#ff6600]">своето сметководство?</span>
                </h2>
                <p class="text-white/50 text-lg mb-10">
                    Регистрирај се и почни да го користиш FinanceBuddy портал веднаш.
                </p>
                <Link :href="route('register')" class="inline-flex items-center gap-2 px-10 py-4 rounded-xl bg-[#ff6600] hover:bg-[#e65c00] text-white font-semibold text-lg transition-all hover:shadow-[0_0_40px_rgba(255,102,0,0.4)]">
                    Регистрирај се бесплатно →
                </Link>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="border-t border-white/5 py-10 px-6">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-white/40 text-sm">
                    <div class="w-5 h-5 rounded bg-[#ff6600] flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    FinanceBuddy.mk &copy; {{ new Date().getFullYear() }}
                </div>
                <div class="flex items-center gap-6 text-sm text-white/30">
                    <Link :href="route('login')" class="hover:text-white/60 transition-colors">Најава</Link>
                    <Link :href="route('register')" class="hover:text-white/60 transition-colors">Регистрација</Link>
                </div>
            </div>
        </footer>

    </div>
</template>

<script lang="ts">
export default {
    data() {
        return {
            features: [
                {
                    title: 'AI OCR Скенирање',
                    desc: 'Прикачи PDF фактура или банкарски извод — AI автоматски ги чита суми, добавувач, ДДВ и предлага сметки.',
                    color: '#ff6600',
                    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 001.5 2.122m-6 0a2.25 2.25 0 00-1.5 2.122M14.25 3.104c.251.023.501.05.75.082M19.5 14.5l-4.091-4.091M5 14.5l4.091-4.091M5 14.5v4.5m14.5-4.5v4.5m-14.5 0h14.5" />',
                },
                {
                    title: 'Книговодствени налози',
                    desc: 'Создавај налози GG-NNNN со автоматска нумерација, должи/побарува ставки и директно прокнижување.',
                    color: '#3b82f6',
                    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25" />',
                },
                {
                    title: 'Влезни и излезни фактури',
                    desc: 'Управувај со фактури со ДДВ по стапка, поврзани со контрагенти, и автоматско книжење во налог.',
                    color: '#10b981',
                    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
                },
                {
                    title: 'Официјален контен план',
                    desc: 'Правилник 174/2011 — класи 0-9, над 200 сметки, со точни типови (средство/обврска/приход/расход).',
                    color: '#8b5cf6',
                    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />',
                },
                {
                    title: 'Магацинско работење',
                    desc: 'Прими и издај стока, следи залихи по магацин, автоматски движења при книжење на фактури.',
                    color: '#f59e0b',
                    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />',
                },
                {
                    title: 'Вработени и плати',
                    desc: 'Евиденција на вработени, управување со договори и подготовка за пресметка на плата.',
                    color: '#ec4899',
                    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />',
                },
            ],
            chartSample: [
                { code: '100', name: 'Парични средства на трансакциски сметки' },
                { code: '120', name: 'Побарувања од купувачи во земјата' },
                { code: '130', name: 'ДДВ побарување' },
                { code: '220', name: 'Обврски спрема добавувачи во земјата' },
                { code: '230', name: 'Обврски за ДДВ' },
                { code: '240', name: 'Обврски за плата и надоместоци' },
                { code: '449', name: 'Останати трошоци на работењето' },
                { code: '740', name: 'Приходи за продадени производи' },
                { code: '741', name: 'Приходи за продадени стоки' },
            ],
        }
    },
}
</script>