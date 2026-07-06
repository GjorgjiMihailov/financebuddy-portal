<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({ layout: null })

const props = defineProps<{
    auth?: { user?: { name: string } }
}>()

const isLoggedIn = computed(() => !!props.auth?.user)

const features = [
    {
        title: 'AI OCR Скенирање',
        desc: 'Прикачи PDF фактура или банкарски извод — AI автоматски ги чита суми, добавувач, ДДВ и предлага сметки.',
        color: '#ff6600',
        icon: 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714a2.25 2.25 0 001.5 2.122M14.25 3.104c.251.023.501.05.75.082M19.5 14.5l-4.091-4.091M5 14.5l4.091-4.091M5 14.5v4.5m14.5-4.5v4.5m-14.5 0h14.5',
    },
    {
        title: 'Книговодствени налози',
        desc: 'Создавај налози GG-NNNN со автоматска нумерација, должи/побарува ставки и директно прокнижување.',
        color: '#3b82f6',
        icon: 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.966 8.966 0 00-6 2.292m0-14.25v14.25',
    },
    {
        title: 'Влезни и излезни фактури',
        desc: 'Управувај со фактури со ДДВ по стапка, поврзани со кооперанти, и автоматско книжење во налог.',
        color: '#10b981',
        icon: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    },
    {
        title: 'Официјален контен план',
        desc: 'Правилник 174/2011 — класи 0-9, над 200 сметки, со точни типови (средство/обврска/приход/расход).',
        color: '#8b5cf6',
        icon: 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z',
    },
    {
        title: 'Магацинско работење',
        desc: 'Прими и издај стока, следи залихи по магацин, автоматски движења при книжење на фактури.',
        color: '#f59e0b',
        icon: 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
    },
    {
        title: 'Вработени и плати',
        desc: 'Евиденција на вработени, управување со договори и подготовка за пресметка на плата.',
        color: '#ec4899',
        icon: 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
    },
]

const chartSample = [
    { code: '100', name: 'Парични средства на трансакциски сметки' },
    { code: '120', name: 'Побарувања од купувачи во земјата' },
    { code: '130', name: 'ДДВ побарување' },
    { code: '220', name: 'Обврски спрема добавувачи' },
    { code: '230', name: 'Обврски за ДДВ' },
    { code: '240', name: 'Обврски за плата и надоместоци' },
    { code: '449', name: 'Останати трошоци на работењето' },
    { code: '740', name: 'Приходи за продадени производи' },
    { code: '741', name: 'Приходи за продадени стоки' },
]

const year = new Date().getFullYear()
</script>

<template>
    <Head title="FinanceBuddy — Сметководствен портал" />

    <div class="min-h-screen bg-[#0f0f18] text-white" style="font-family:system-ui,-apple-system,sans-serif;">

        <!-- NAV -->
        <nav style="position:fixed;top:0;left:0;right:0;z-index:50;background:rgba(15,15,24,0.88);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,0.07);">
            <div style="max-width:1100px;margin:0 auto;padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:8px;background:#ff6600;display:flex;align-items:center;justify-content:center;">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <span style="font-weight:700;font-size:17px;letter-spacing:-0.3px;">FinanceBuddy</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <Link v-if="isLoggedIn" href="/dashboard" style="padding:9px 20px;border-radius:10px;background:#ff6600;color:white;font-size:14px;font-weight:600;text-decoration:none;">
                        Оди на портал →
                    </Link>
                    <template v-else>
                        <Link href="/login" style="font-size:14px;color:rgba(255,255,255,0.6);text-decoration:none;padding:8px 12px;">
                            Најава
                        </Link>
                        <Link href="/register" style="padding:9px 20px;border-radius:10px;background:#ff6600;color:white;font-size:14px;font-weight:600;text-decoration:none;">
                            Регистрирај се
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- HERO -->
        <section style="position:relative;padding:160px 24px 80px;overflow:hidden;">
            <div style="position:absolute;top:80px;left:20%;width:600px;height:600px;background:rgba(255,102,0,0.07);border-radius:50%;filter:blur(90px);pointer-events:none;"></div>
            <div style="position:absolute;top:200px;right:15%;width:400px;height:400px;background:rgba(255,102,0,0.04);border-radius:50%;filter:blur(80px);pointer-events:none;"></div>

            <div style="position:relative;max-width:860px;margin:0 auto;text-align:center;">
                <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;border:1px solid rgba(255,102,0,0.3);background:rgba(255,102,0,0.09);color:#ff8533;font-size:12px;font-weight:600;margin-bottom:32px;letter-spacing:0.3px;">
                    <span style="width:7px;height:7px;border-radius:50%;background:#ff6600;display:inline-block;"></span>
                    Сметководствен портал за Македонија
                </div>

                <h1 style="font-size:clamp(40px,7vw,70px);font-weight:800;line-height:1.07;letter-spacing:-2px;margin:0 0 24px;color:#ffffff;">
                    Сметководство<br>
                    <span style="color:#ff6600;">со AI</span>, без хаос
                </h1>

                <p style="font-size:18px;color:rgba(255,255,255,0.48);max-width:560px;margin:0 auto 40px;line-height:1.75;">
                    Скенирај документи, книжи автоматски, управувај со фактури и залихи — сè на едно место, усогласено со македонскиот контен план.
                </p>

                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:14px;">
                    <Link href="/register" style="padding:15px 34px;border-radius:12px;background:#ff6600;color:white;font-weight:700;font-size:15px;text-decoration:none;box-shadow:0 0 32px rgba(255,102,0,0.4);">
                        Започни бесплатно →
                    </Link>
                    <Link href="/login" style="padding:15px 34px;border-radius:12px;border:1px solid rgba(255,255,255,0.14);color:rgba(255,255,255,0.72);font-weight:600;font-size:15px;text-decoration:none;">
                        Влези во порталот
                    </Link>
                </div>
            </div>

            <!-- Dashboard mockup -->
            <div style="position:relative;max-width:1000px;margin:56px auto 0;">
                <div style="position:absolute;bottom:0;left:0;right:0;height:180px;background:linear-gradient(to top,#0f0f18,transparent);z-index:2;pointer-events:none;"></div>
                <div style="border-radius:20px;border:1px solid rgba(255,255,255,0.1);background:#1a1a28;overflow:hidden;box-shadow:0 40px 80px rgba(0,0,0,0.55);">
                    <div style="display:flex;align-items:center;gap:8px;padding:12px 16px;background:#14141f;border-bottom:1px solid rgba(255,255,255,0.05);">
                        <div style="width:11px;height:11px;border-radius:50%;background:rgba(255,255,255,0.1);"></div>
                        <div style="width:11px;height:11px;border-radius:50%;background:rgba(255,255,255,0.1);"></div>
                        <div style="width:11px;height:11px;border-radius:50%;background:rgba(255,255,255,0.1);"></div>
                        <div style="flex:1;display:flex;justify-content:center;">
                            <div style="background:rgba(255,255,255,0.05);border-radius:6px;padding:4px 20px;font-size:11px;color:rgba(255,255,255,0.2);">portal.financebuddy.mk/dashboard</div>
                        </div>
                    </div>
                    <div style="padding:20px;display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                        <div v-for="(s,i) in [
                            {label:'Приходи',val:'2,840,500 ден',c:'#10b981'},
                            {label:'Расходи',val:'1,620,300 ден',c:'#f87171'},
                            {label:'Отворени фактури',val:'14',c:'#f59e0b'},
                            {label:'Документи',val:'87',c:'#3b82f6'},
                        ]" :key="i" style="padding:14px;border-radius:12px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);">
                            <div style="font-size:10px;color:rgba(255,255,255,0.33);margin-bottom:6px;">{{ s.label }}</div>
                            <div style="font-size:14px;font-weight:700;" :style="{color:s.c}">{{ s.val }}</div>
                        </div>
                    </div>
                    <div style="padding:0 20px 20px;display:grid;grid-template-columns:2fr 1fr;gap:12px;">
                        <div style="border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);padding:16px;">
                            <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.28);margin-bottom:12px;letter-spacing:0.5px;">ПОСЛЕДНИ НАЛОЗИ</div>
                            <div v-for="(r,i) in [{c:'#10b981'},{c:'#f59e0b'},{c:'#10b981'},{c:'#3b82f6'}]" :key="i" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                                <div :style="{width:'7px',height:'7px',borderRadius:'50%',background:r.c,flexShrink:0}"></div>
                                <div style="flex:1;height:8px;border-radius:4px;background:rgba(255,255,255,0.05);"></div>
                                <div style="width:56px;height:8px;border-radius:4px;background:rgba(255,255,255,0.05);"></div>
                            </div>
                        </div>
                        <div style="border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);padding:16px;">
                            <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.28);margin-bottom:12px;letter-spacing:0.5px;">OCR ДОКУМЕНТИ</div>
                            <div v-for="j in 4" :key="j" style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                                <div style="width:24px;height:24px;border-radius:6px;background:rgba(255,102,0,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <svg width="12" height="12" fill="#ff6600" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                                </div>
                                <div style="flex:1;height:8px;border-radius:4px;background:rgba(255,255,255,0.05);"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section style="padding:80px 24px;">
            <div style="max-width:1100px;margin:0 auto;">
                <div style="text-align:center;margin-bottom:56px;">
                    <h2 style="font-size:clamp(26px,4vw,40px);font-weight:800;letter-spacing:-0.5px;margin:0 0 12px;">Сè што ти треба на едно место</h2>
                    <p style="font-size:17px;color:rgba(255,255,255,0.42);">Дизајниран за македонски сметководители и бизниси</p>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;">
                    <div v-for="f in features" :key="f.title" style="padding:24px;border-radius:18px;border:1px solid rgba(255,255,255,0.07);background:rgba(255,255,255,0.025);">
                        <div :style="{width:'48px',height:'48px',borderRadius:'12px',background:f.color+'1a',display:'flex',alignItems:'center',justifyContent:'center',marginBottom:'16px'}">
                            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.8" :stroke="f.color">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="f.icon" />
                            </svg>
                        </div>
                        <div style="font-weight:700;font-size:15px;margin-bottom:8px;">{{ f.title }}</div>
                        <div style="font-size:13px;color:rgba(255,255,255,0.43);line-height:1.65;">{{ f.desc }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CHART HIGHLIGHT -->
        <section style="padding:40px 24px 80px;">
            <div style="max-width:1100px;margin:0 auto;">
                <div style="border-radius:24px;border:1px solid rgba(255,102,0,0.2);background:linear-gradient(135deg,rgba(255,102,0,0.08),rgba(255,102,0,0.02));padding:52px;display:flex;flex-wrap:wrap;gap:48px;align-items:center;">
                    <div style="flex:1;min-width:260px;">
                        <div style="display:inline-block;padding:4px 12px;border-radius:999px;background:rgba(255,102,0,0.14);color:#ff8533;font-size:11px;font-weight:700;margin-bottom:16px;letter-spacing:0.5px;text-transform:uppercase;">
                            Официјален контен план
                        </div>
                        <h2 style="font-size:clamp(22px,3.5vw,36px);font-weight:800;letter-spacing:-0.5px;margin:0 0 14px;line-height:1.2;">
                            Усогласен со<br>Правилник 174/2011
                        </h2>
                        <p style="font-size:15px;color:rgba(255,255,255,0.48);line-height:1.7;margin:0 0 28px;max-width:420px;">
                            Целиот контен план согласно УЈП — сите класи 0-9, со точни трицифрени сметки. Автоматско предлагање при книжење.
                        </p>
                        <Link href="/register" style="display:inline-block;padding:12px 24px;border-radius:10px;background:#ff6600;color:white;font-weight:600;font-size:14px;text-decoration:none;">
                            Пробај сега →
                        </Link>
                    </div>
                    <div style="width:300px;border-radius:14px;border:1px solid rgba(255,255,255,0.1);background:rgba(15,15,24,0.85);overflow:hidden;">
                        <div style="padding:10px 16px;border-bottom:1px solid rgba(255,255,255,0.06);font-size:10px;color:rgba(255,255,255,0.22);">Контен план — извадок</div>
                        <div style="padding:14px 16px;">
                            <div v-for="row in chartSample" :key="row.code" style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                                <span style="color:#ff6600;font-size:12px;font-weight:700;width:32px;flex-shrink:0;font-family:monospace;">{{ row.code }}</span>
                                <span style="font-size:11px;color:rgba(255,255,255,0.52);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-family:monospace;">{{ row.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section style="padding:60px 24px 100px;text-align:center;">
            <div style="max-width:580px;margin:0 auto;">
                <h2 style="font-size:clamp(30px,5vw,52px);font-weight:800;letter-spacing:-1px;line-height:1.1;margin:0 0 18px;">
                    Готов да го<br>дигитализираш<br>
                    <span style="color:#ff6600;">своето сметководство?</span>
                </h2>
                <p style="font-size:17px;color:rgba(255,255,255,0.42);margin:0 0 36px;">
                    Регистрирај се и почни да го користиш FinanceBuddy портал веднаш.
                </p>
                <Link href="/register" style="display:inline-block;padding:16px 42px;border-radius:14px;background:#ff6600;color:white;font-weight:700;font-size:16px;text-decoration:none;box-shadow:0 0 42px rgba(255,102,0,0.4);">
                    Регистрирај се бесплатно →
                </Link>
            </div>
        </section>

        <!-- FOOTER -->
        <footer style="border-top:1px solid rgba(255,255,255,0.06);padding:26px 24px;">
            <div style="max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,0.32);">
                    <div style="width:20px;height:20px;border-radius:5px;background:#ff6600;display:flex;align-items:center;justify-content:center;">
                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    FinanceBuddy.mk &copy; {{ year }}
                </div>
                <div style="display:flex;gap:24px;font-size:13px;">
                    <Link href="/login" style="color:rgba(255,255,255,0.3);text-decoration:none;">Најава</Link>
                    <Link href="/register" style="color:rgba(255,255,255,0.3);text-decoration:none;">Регистрација</Link>
                </div>
            </div>
        </footer>

    </div>
</template>