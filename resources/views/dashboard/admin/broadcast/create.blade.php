@extends('layouts.argon')
@section('title', 'Broadcast Baru')
@section('page_title', 'Broadcast Baru')

@php
    $templatesJson = $templates->map(fn ($t) => [
        'id' => $t->id,
        'name' => $t->name,
        'body_text' => $t->body_text,
        'header_text' => $t->header_text,
        'button_text' => $t->button_text,
        'parameters_count' => $t->parameters_count,
        'category' => \App\Services\WhatsApp\WhatsappMetaService::templateCategoryLabel($t->category ?? ''),
        'header_format' => \App\Services\WhatsApp\WhatsAppBroadcastService::headerFormat($t->components ?? []),
        'header_example_url' => \App\Services\WhatsApp\WhatsAppBroadcastService::headerExampleUrl($t->components ?? []),
        'is_active' => (bool) $t->is_active,
    ])->values();

    $userRegionId = auth()->user()->region_id;
@endphp

@section('content')
<div class="min-h-[715px] bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
    <div class="p-6">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Kirim Broadcast Promosi</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gunakan template yang sudah di-<strong>approve Meta</strong> agar bisa dikirim ke customer di luar 24 jam sesi chat.
            </p>
        </div>

        @if($errors->has('parameters') || $errors->has('header_media_file') || $errors->has('header_media_url'))
            <div class="mb-4 p-3 text-sm text-red-800 bg-red-100 rounded-lg dark:bg-red-900 dark:text-red-200">
                @if($errors->has('parameters'))
                    {{ $errors->first('parameters') }}<br>
                @endif
                @if($errors->has('header_media_file'))
                    {{ $errors->first('header_media_file') }}<br>
                @endif
                @if($errors->has('header_media_url'))
                    {{ $errors->first('header_media_url') }}
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('admin.broadcast.store') }}" enctype="multipart/form-data" x-data="broadcastForm()" x-init="init()">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- KOLOM KIRI: Template & Parameter --}}
                <div class="space-y-5">
                    <div>
                        <label for="whatsapp_template_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Template WhatsApp *</label>
                        <select name="whatsapp_template_id" id="whatsapp_template_id" required
                            x-model="selectedTemplateId" @change="selectTemplate()"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            <option value="">-- Pilih Template --</option>
                            <template x-for="t in templates" :key="t.id">
                                <option :value="t.id" x-text="`${t.name} (${t.category})`"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label for="title" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Judul Campaign (opsional)</label>
                        <input type="text" name="title" id="title"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="mis. Promo Lebaran 2026">
                    </div>

                    {{-- Header Media (template bergambar/video/dokumen) --}}
                    <template x-if="selectedTemplate && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(selectedTemplate.header_format)">
                        <div class="p-4 border rounded-lg border-[#8BA870]/40 bg-green-50/50 dark:bg-green-900/10">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"
                                    x-text="`Media Header (${selectedTemplate.header_format === 'IMAGE' ? 'Gambar' : selectedTemplate.header_format === 'VIDEO' ? 'Video' : 'Dokumen'})`"></h3>
                                <span class="text-xs text-gray-400 dark:text-gray-500">Opsional</span>
                            </div>

                            <template x-if="selectedTemplate.header_example_url">
                                <img :src="selectedTemplate.header_example_url" alt="Media bawaan template"
                                    class="w-full max-h-40 object-cover rounded-lg mb-3 border border-gray-200">
                            </template>

                            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                                Template ini sudah dilengkapi media yang di-<strong>approve Meta</strong>. Boleh dipakai
                                polos, atau diganti media promosi sendiri (jenis media harus sama dengan template).
                            </p>

                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer dark:text-gray-300">
                                    <input type="radio" name="header_media_choice" value="template" x-model="headerMediaChoice"
                                        class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500">
                                    Pakai media bawaan template (default)
                                </label>

                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer dark:text-gray-300">
                                    <input type="radio" name="header_media_choice" value="upload" x-model="headerMediaChoice"
                                        class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500">
                                    Upload gambar/video baru
                                </label>
                                <div x-show="headerMediaChoice === 'upload'" class="pt-1 pl-6">
                                    <input type="file" name="header_media_file" id="header_media_file"
                                        :accept="selectedTemplate.header_format === 'VIDEO' ? '.mp4,.3gp,.mov,video/*' : selectedTemplate.header_format === 'DOCUMENT' ? '.pdf,application/pdf' : '.jpg,.jpeg,.png,.webp,image/*'"
                                        class="block w-full text-sm text-gray-600 dark:text-gray-300 cursor-pointer file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[#8BA870] file:text-white file:font-medium file:cursor-pointer">
                                    <span class="block mt-1 text-xs text-gray-400 dark:text-gray-500"
                                        x-text="selectedTemplate.header_format === 'VIDEO' ? 'Format: MP4 / 3GP / MOV, maks 20 MB' : selectedTemplate.header_format === 'DOCUMENT' ? 'Format: PDF, maks 10 MB' : 'Format: JPG / PNG / WEBP, maks 10 MB'"></span>
                                </div>

                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer dark:text-gray-300">
                                    <input type="radio" name="header_media_choice" value="url" x-model="headerMediaChoice"
                                        class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500">
                                    Pakai URL gambar/video
                                </label>
                                <div x-show="headerMediaChoice === 'url'" class="pt-1 pl-6">
                                    <input type="url" name="header_media_url" id="header_media_url" x-model="headerMediaUrl"
                                        placeholder="https://kuepandanasli.com/assets/promo-hampers.png"
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-[#8BA870] focus:border-[#8BA870] block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <span class="block mt-1 text-xs text-gray-400 dark:text-gray-500">URL harus dapat diakses publik oleh WhatsApp/Meta.</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Parameter Template --}}
                    <template x-if="selectedTemplate">
                        <div class="p-4 border rounded-lg dark:border-gray-600">
                            <div class="mb-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Isi Parameter Template</h3>
                                    <span class="text-xs text-gray-400" x-text="`${selectedTemplate.parameters_count} parameter`"></span>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">
                                    Token <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 text-white dark:text-white rounded">{nama}</code> akan diganti nama masing-masing penerima.
                                </p>
                            </div>

                            <div class="space-y-3">
                                <template x-for="key in paramKeys" :key="key">
                                    <div>
                                        <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300"
                                            x-text="`Parameter ${key}`"></label>
                                        <input type="text"
                                            :name="`parameters[${key}]`"
                                            x-model="params[key]"
                                            @input="updatePreview()"
                                            placeholder='mis. Promo spesial atau "{nama}" untuk nama customer'
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                </template>
                            </div>

                            {{-- Preview --}}
                            <div class="mt-4">
                                <div class="mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Pratinjau Pesan</div>
                                <div x-show="headerMediaChoice === 'template' && selectedTemplate.header_example_url" class="mb-2">
                                    <img :src="selectedTemplate.header_example_url" alt="Media bawaan template"
                                        class="max-h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-600">
                                </div>
                                <div class="p-3 text-sm bg-gray-50 border border-gray-200 rounded-lg whitespace-pre-wrap dark:bg-gray-700 dark:border-gray-600 dark:text-white" x-text="preview">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- KOLOM KANAN: Penerima & Kirim --}}
                <div class="space-y-5">
                    <div class="p-4 border rounded-lg dark:border-gray-600">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Pilih Penerima</h3>

                        <div class="mb-3">
                            <label for="region_id" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Cabang</label>
                            <select name="region_id" id="region_id" x-model="selectedRegionId"
                                @change="resetCount()"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option :value="myRegionId" x-text="`Cabang Saya${myRegionId ? '' : ' (default)'}`"></option>
                                <option value="0">Semua Cabang</option>
                                <template x-for="r in regions" :key="r.id">
                                    <option :value="r.id" x-text="r.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="customer_category_id" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Kategori Customer (opsional)</label>
                            <select name="customer_category_id" id="customer_category_id" x-model="selectedCategoryId"
                                @change="resetCount()"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Semua Kategori</option>
                                <template x-for="c in categories" :key="c.id">
                                    <option :value="c.id" x-text="c.name"></option>
                                </template>
                            </select>
                        </div>

                        <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer dark:text-gray-300">
                            <input type="checkbox" name="only_opt_in" value="1" x-model="onlyOptIn" @change="resetCount()"
                                class="mt-0.5 w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600">
                            <span>
                                Hanya customer yang sudah pernah chat WhatsApp (opt-in)
                                <span class="block text-xs text-gray-400">Disarankan agar aman dari kebijakan Meta (24 jam window + template).</span>
                            </span>
                        </label>

                        <button type="button" @click="fetchCount()" :disabled="countLoading"
                            class="mt-4 inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 disabled:opacity-50">
                            <i class="mr-1 fas fa-users"></i>
                            <span x-text="countLoading ? 'Memeriksa...' : 'Hitung Jumlah Penerima'"></span>
                        </button>

                        <template x-if="count !== null">
                            <div class="mt-3 p-3 text-sm rounded-lg"
                                :class="count > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'">
                                <i class="mr-1 fas" :class="count > 0 ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                                <span x-text="`${count} penerima akan menerima broadcast.`"></span>
                            </div>
                        </template>
                    </div>

                    <button type="submit"
                        :disabled="count === null || count === 0 || !selectedTemplate"
                        class="inline-flex w-full items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 disabled:opacity-50">
                        <i class="mr-2 fas fa-paper-plane"></i> Kirim Broadcast Sekarang
                    </button>
                    <a href="{{ route('admin.broadcast.index') }}"
                        class="inline-flex w-full items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Batal
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    window.BROADCAST_DATA = {
        templates: @json($templatesJson),
        categories: @json($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()),
        regions: @json($regions->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values()),
        myRegionId: @json($userRegionId),
    };

    function broadcastForm() {
        return {
            templates: window.BROADCAST_DATA.templates,
            categories: window.BROADCAST_DATA.categories,
            regions: window.BROADCAST_DATA.regions,
            myRegionId: window.BROADCAST_DATA.myRegionId,
            selectedTemplateId: '',
            selectedRegionId: window.BROADCAST_DATA.myRegionId ?? '',
            selectedCategoryId: '',
            onlyOptIn: true,
            params: {},
            preview: '',
            count: null,
            countLoading: false,
            headerMediaChoice: 'template',
            headerMediaUrl: '',
            headerMediaFile: null,

            init() {
                if (this.myRegionId) this.selectedRegionId = this.myRegionId;
            },

            get selectedHeaderFormat() {
                return this.selectedTemplate?.header_format || 'TEXT';
            },

            get selectedTemplate() {
                return this.templates.find(t => t.id == this.selectedTemplateId) || null;
            },

            get paramKeys() {
                if (!this.selectedTemplate || !this.selectedTemplate.parameters_count) return [];
                return Array.from({ length: this.selectedTemplate.parameters_count }, (_, i) => i + 1);
            },

            selectTemplate() {
                this.params = {};
                this.preview = '';
                this.count = null;
                this.headerMediaChoice = 'template';
                this.headerMediaUrl = '';
                this.headerMediaFile = null;
                if (!this.selectedTemplate) return;
                for (const key of this.paramKeys) {
                    this.params[key] = '';
                }
                this.updatePreview();
            },

            get allowedMediaType() {
                const f = this.selectedHeaderFormat;
                if (f === 'IMAGE') return 'image';
                if (f === 'VIDEO') return 'video';
                if (f === 'DOCUMENT') return 'document';
                return null;
            },

            updatePreview() {
                if (!this.selectedTemplate) { this.preview = ''; return; }
                let text = '';
                if (this.allowedMediaType) {
                    const labelMap = { image: 'Gambar', video: 'Video', document: 'Dokumen' };
                    const label = labelMap[this.allowedMediaType] || 'Media';
                    const src = this.headerMediaChoice === 'template'
                        ? 'media bawaan template'
                        : (this.headerMediaUrl || 'file terpilih');
                    text += `[${label}: ${src}]\n\n`;
                } else if (this.selectedTemplate.header_text) {
                    text += this.selectedTemplate.header_text + '\n\n';
                }
                text += this.selectedTemplate.body_text || '';
                if (this.selectedTemplate.button_text) text += '\n\n[Tombol: ' + this.selectedTemplate.button_text + ']';
                const openTag = '{'.repeat(2);
                const closeTag = '}'.repeat(2);
                for (const key in this.params) {
                    const tag = openTag + key + closeTag;
                    const value = this.params[key];
                    text = text.split(tag).join(value || tag);
                }
                this.preview = text;
                this.count = null;
            },

            resetCount() {
                this.count = null;
            },

            async fetchCount() {
                this.countLoading = true;
                try {
                    const params = new URLSearchParams({
                        region_id: this.selectedRegionId || '',
                        customer_category_id: this.selectedCategoryId || '',
                        only_opt_in: this.onlyOptIn ? '1' : '0',
                    });
                    const res = await fetch(`{{ route('admin.broadcast.preview-count') }}?${params}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await res.json();
                    this.count = data.count;
                } catch (e) {
                    this.count = 0;
                } finally {
                    this.countLoading = false;
                }
            },
        };
    }
</script>
@endsection