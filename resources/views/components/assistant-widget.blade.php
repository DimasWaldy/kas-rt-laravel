@php
    $bottomOffsetClass = $bottomOffsetClass ?? 'bottom-5';
@endphp

<div
    x-data="assistantWidget({
        endpoint: @js(route('assistant.chat')),
        csrfToken: @js(csrf_token()),
    })"
    @keydown.escape.window="close()"
    class="fixed right-4 z-50 {{ $bottomOffsetClass }} sm:right-6"
>
    {{-- Panel Chat Mengambang --}}
    <section
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        aria-label="Asisten Customer Service Smart RW"
        class="mb-3 flex h-[min(34rem,calc(100vh-7.5rem))] w-[calc(100vw-2rem)] max-w-[24rem] flex-col overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-2xl shadow-emerald-950/15 dark:border-slate-700 dark:bg-slate-900"
    >
        {{-- Header Chat --}}
        <header class="flex items-center justify-between bg-emerald-700 px-4 py-3.5 text-white">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-lg">
                    <i class="fa-solid fa-robot"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="truncate text-sm font-black tracking-tight">Asisten Smart RW</h2>
                    <p class="flex items-center gap-1.5 text-[11px] font-semibold text-emerald-100">
                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                        <span class="truncate">CS Layanan &amp; UMKM Warga</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    @click="resetChat()"
                    title="Mulai obrolan baru"
                    aria-label="Reset percakapan"
                    class="flex h-8 w-8 items-center justify-center rounded-xl text-emerald-100 transition hover:bg-white/15 hover:text-white"
                >
                    <i class="fa-solid fa-rotate-right text-xs"></i>
                </button>
                <button
                    type="button"
                    @click="close()"
                    aria-label="Tutup chat asisten"
                    class="flex h-8 w-8 items-center justify-center rounded-xl text-emerald-100 transition hover:bg-white/15 hover:text-white"
                >
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </header>

        {{-- Daftar Pesan --}}
        <div x-ref="chatScroll" class="flex-1 space-y-3 overflow-y-auto bg-slate-50/80 p-4 dark:bg-slate-950/70">
            <template x-for="item in messages" :key="item.id">
                <div :class="item.role === 'user' ? 'flex justify-end' : 'flex flex-col items-start space-y-2'">
                    {{-- Bubble Pesan --}}
                    <div
                        :class="item.role === 'user'
                            ? 'max-w-[85%] rounded-2xl rounded-br-md bg-emerald-600 px-3.5 py-2.5 text-xs leading-relaxed font-medium text-white shadow-xs'
                            : 'max-w-[92%] rounded-2xl rounded-bl-md border border-slate-200/80 bg-white px-3.5 py-2.5 text-xs leading-relaxed text-slate-700 shadow-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200'"
                    >
                        <div x-html="formatReplyHtml(item.text)"></div>
                    </div>

                    {{-- Rekomendasi Produk UMKM --}}
                    <template x-if="item.role === 'model' && item.products && item.products.length > 0">
                        <div class="w-full space-y-2">
                            <template x-for="produk in item.products" :key="'prod-' + produk.id">
                                <div class="rounded-2xl border border-emerald-100 bg-white p-3 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                                    <div class="flex items-start gap-2.5">
                                        <template x-if="produk.foto_url">
                                            <img :src="produk.foto_url" :alt="produk.nama_produk" width="48" height="48" loading="lazy" decoding="async" class="h-12 w-12 shrink-0 rounded-xl object-cover">
                                        </template>
                                        <template x-if="!produk.foto_url">
                                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                                <i class="fa-solid fa-bag-shopping"></i>
                                            </span>
                                        </template>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-black text-slate-900 dark:text-slate-100" x-text="produk.nama_produk"></p>
                                            <p class="text-xs font-extrabold text-emerald-700 dark:text-emerald-400" x-text="produk.harga_formatted"></p>
                                            <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">
                                                <span x-text="produk.nama_usaha"></span> &middot; <span x-text="produk.rt_name"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <a
                                        :href="produk.whatsapp_order_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-2.5 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-[11px] font-black text-white transition hover:bg-emerald-700"
                                    >
                                        <i class="fa-brands fa-whatsapp text-sm"></i>
                                        <span>Order via WhatsApp</span>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Rekomendasi Toko UMKM (jika tidak ada produk spesifik) --}}
                    <template x-if="item.role === 'model' && (!item.products || item.products.length === 0) && item.stores && item.stores.length > 0">
                        <div class="w-full space-y-2">
                            <template x-for="store in item.stores" :key="'store-' + store.id">
                                <div class="rounded-2xl border border-emerald-100 bg-white p-3 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-black text-slate-900 dark:text-slate-100" x-text="store.nama_usaha"></p>
                                            <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">
                                                <span x-text="store.kategori_label"></span> &middot; <span x-text="store.rt_name"></span>
                                            </p>
                                        </div>
                                        <span
                                            :class="store.is_buka ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
                                            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-black"
                                            x-text="store.is_buka ? 'Buka' : 'Tutup'"
                                        ></span>
                                    </div>
                                    <a
                                        :href="store.whatsapp_chat_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-2.5 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-[11px] font-black text-white transition hover:bg-emerald-700"
                                    >
                                        <i class="fa-brands fa-whatsapp text-sm"></i>
                                        <span>Chat WhatsApp</span>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Tombol Shortcut Menu / Panduan --}}
                    <template x-if="item.role === 'model' && item.links && item.links.length > 0">
                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            <template x-for="link in item.links" :key="link.url">
                                <a
                                    :href="link.url"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] font-bold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-950"
                                >
                                    <i :class="'fa-solid ' + link.icon + ' text-[10px]'"></i>
                                    <span x-text="link.label"></span>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Quick Prompts Awal --}}
            <div x-show="messages.length <= 1 && !loading" class="flex flex-wrap gap-1.5 pt-1">
                <template x-for="prompt in quickPrompts" :key="prompt">
                    <button
                        type="button"
                        @click="askQuickPrompt(prompt)"
                        class="rounded-xl border border-emerald-200 bg-white px-2.5 py-1.5 text-left text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-50 dark:border-slate-700 dark:bg-slate-900 dark:text-emerald-400 dark:hover:bg-slate-800"
                        x-text="prompt"
                    ></button>
                </template>
            </div>

            {{-- Indikator Mengetik --}}
            <div x-show="loading" x-cloak class="flex items-center gap-2 rounded-2xl border border-slate-200/80 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-500 shadow-xs w-fit dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                <i class="fa-solid fa-circle-notch fa-spin text-emerald-600"></i>
                <span>Asisten sedang mengetik...</span>
            </div>
        </div>

        {{-- Form Kirim Pesan --}}
        <form @submit.prevent="sendMessage()" class="flex items-center gap-2 border-t border-slate-100 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
            <input
                x-ref="chatInput"
                type="text"
                x-model="input"
                maxlength="500"
                :disabled="loading"
                placeholder="Tanya surat, iuran, UMKM..."
                aria-label="Ketik pertanyaan Anda untuk Asisten Smart RW"
                class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-semibold text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:outline-hidden disabled:opacity-60 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
            >
            <button
                type="submit"
                :disabled="loading || !input.trim()"
                aria-label="Kirim pesan"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <i class="fa-solid fa-paper-plane text-xs"></i>
            </button>
        </form>
    </section>

    {{-- Tombol Mengambang Pojok Kanan Bawah --}}
    <div class="flex justify-end">
        <button
            type="button"
            @click="toggle()"
            :aria-expanded="open.toString()"
            aria-label="Buka Asisten Customer Service Smart RW"
            class="inline-flex items-center gap-2.5 rounded-full bg-emerald-600 px-4 py-3 text-sm font-black text-white shadow-xl shadow-emerald-900/20 ring-4 ring-white/80 transition hover:-translate-y-0.5 hover:bg-emerald-700 dark:ring-slate-950/80"
        >
            <i :class="open ? 'fa-solid fa-xmark' : 'fa-solid fa-comments'" class="text-base"></i>
            <span x-text="open ? 'Tutup CS' : 'Tanya CS'"></span>
        </button>
    </div>
</div>
