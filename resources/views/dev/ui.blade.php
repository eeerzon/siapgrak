@extends('layouts.dashboard')

@section('title', 'Component Gallery')

@section('content')
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-slate-800">Siapgrak — Component Gallery</h2>
        <p class="mt-1 text-sm text-slate-500">F0 Design Foundation. Referensi visual untuk F1-F12 — jangan desain ulang komponen di halaman lain, pakai yang di sini.</p>
    </div>

    <div class="space-y-12">
        {{-- Typography --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Typography</h3>
            <x-ui.card class="space-y-3">
                <p class="text-2xl font-semibold text-slate-900">Display — text-2xl font-semibold</p>
                <p class="text-xl font-semibold text-slate-900">H1 — text-xl font-semibold</p>
                <p class="text-lg font-semibold text-slate-800">H2 — text-lg font-semibold</p>
                <p class="text-base font-semibold text-slate-800">H3 — text-base font-semibold</p>
                <p class="text-sm text-slate-700">Body — text-sm text-slate-700 (default untuk isi halaman & form)</p>
                <p class="text-xs text-slate-500">Small — text-xs text-slate-500 (meta info, timestamp, helper text)</p>
                <p class="text-xs uppercase tracking-wide text-slate-400">Caption — text-xs uppercase tracking-wide text-slate-400 (label section, eyebrow)</p>
            </x-ui.card>
        </section>

        {{-- Color Tokens --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Color Tokens</h3>
            <x-ui.card>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ([
                        ['Surface', 'bg-white border border-slate-200', 'bg-white'],
                        ['Surface Subtle', 'bg-slate-50', 'bg-slate-50'],
                        ['Border', 'bg-slate-200', 'border-slate-200'],
                        ['Text Primary', 'bg-slate-900', 'text-slate-900'],
                        ['Text Secondary', 'bg-slate-500', 'text-slate-500'],
                        ['Primary (Button)', 'bg-slate-800', 'bg-slate-800'],
                        ['Accent', 'bg-sky-600', 'bg-sky-600'],
                        ['Success', 'bg-emerald-500', 'emerald-500'],
                        ['Warning', 'bg-amber-500', 'amber-500'],
                        ['Danger', 'bg-red-600', 'red-600'],
                    ] as [$label, $swatch, $className])
                        <div>
                            <div class="h-12 w-full rounded-md {{ $swatch }}"></div>
                            <p class="mt-2 text-xs font-medium text-slate-700">{{ $label }}</p>
                            <p class="text-xs text-slate-400">{{ $className }}</p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </section>

        {{-- Buttons --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Buttons</h3>
            <x-ui.card class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="primary">Primary</x-ui.button>
                <x-ui.button variant="secondary">Secondary</x-ui.button>
                <x-ui.button variant="danger">Danger</x-ui.button>
                <x-ui.button variant="ghost">Ghost</x-ui.button>
                <x-ui.button variant="primary" size="sm">Primary Small</x-ui.button>
                <x-ui.button variant="primary" disabled>Disabled</x-ui.button>
                <x-ui.button variant="primary" href="#">As Link</x-ui.button>
            </x-ui.card>
        </section>

        {{-- Badges --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Status Badges</h3>
            <x-ui.card class="flex flex-wrap items-center gap-3">
                <x-ui.badge variant="neutral">Draft</x-ui.badge>
                <x-ui.badge variant="info">Registration Open</x-ui.badge>
                <x-ui.badge variant="success">Approved</x-ui.badge>
                <x-ui.badge variant="warning">Pending Review</x-ui.badge>
                <x-ui.badge variant="danger">Rejected</x-ui.badge>
            </x-ui.card>
            <p class="mt-2 text-xs text-slate-400">Warna badge punya makna TETAP di seluruh app — jangan dipakai untuk hal lain di luar status.</p>
        </section>

        {{-- Form Controls --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Form Controls</h3>
            <x-ui.card class="max-w-md space-y-4">
                <x-ui.input name="gallery_name" label="Text Input" placeholder="Nama team" />
                <x-ui.select name="gallery_select" label="Select">
                    <option value="">Pilih opsi</option>
                    <option value="a">Opsi A</option>
                    <option value="b">Opsi B</option>
                </x-ui.select>
                <x-ui.textarea name="gallery_textarea" label="Textarea" placeholder="Deskripsi..." />
                <div>
                    <x-ui.input name="gallery_error" label="Input dengan Error" value="salah@format" />
                    <p class="mt-1 text-sm text-red-600">Contoh pesan error — biasanya muncul otomatis dari @@error() session.</p>
                </div>
            </x-ui.card>
        </section>

        {{-- Cards --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Cards</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.card>
                    <p class="font-medium text-slate-800">Card Standar</p>
                    <p class="mt-1 text-sm text-slate-500">Dipakai untuk section container di halaman detail (Organization Settings, Team Overview, dst).</p>
                </x-ui.card>
                <x-ui.card>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Contoh KPI Card</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">82</p>
                    <p class="text-sm text-slate-500">Participants</p>
                </x-ui.card>
            </div>
        </section>

        {{-- Table/List --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Table / List</h3>
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <x-ui.table-th>Team</x-ui.table-th>
                        <x-ui.table-th>Organization</x-ui.table-th>
                        <x-ui.table-th>Status</x-ui.table-th>
                        <x-ui.table-th align="right">Aksi</x-ui.table-th>
                    </tr>
                </x-slot:head>
                <tr>
                    <x-ui.table-td>Paskibra Utama</x-ui.table-td>
                    <x-ui.table-td muted>SMPN 7 Bandung</x-ui.table-td>
                    <x-ui.table-td><x-ui.badge variant="success">Active</x-ui.badge></x-ui.table-td>
                    <x-ui.table-td align="right"><a href="#" class="text-sm text-slate-600 hover:underline">Lihat</a></x-ui.table-td>
                </tr>
                <tr>
                    <x-ui.table-td>Paskibra Madya</x-ui.table-td>
                    <x-ui.table-td muted>SMAN 1 Bandung</x-ui.table-td>
                    <x-ui.table-td><x-ui.badge variant="neutral">Inactive</x-ui.badge></x-ui.table-td>
                    <x-ui.table-td align="right"><a href="#" class="text-sm text-slate-600 hover:underline">Lihat</a></x-ui.table-td>
                </tr>
            </x-ui.table>
            <p class="mt-2 text-xs text-slate-400">Di mobile (&lt; md), pola list halaman sebenarnya beralih ke card grid, bukan table sempit — lihat contoh di halaman Teams.</p>
        </section>

        {{-- Empty / Loading / Error States --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Empty / Loading / Error States</h3>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.empty-state title="Belum ada data" description="Copy ini berubah sesuai konteks — beda antara 'belum ada sama sekali' vs 'hasil filter kosong'.">
                    <x-slot:action>
                        <x-ui.button variant="primary" size="sm">Tambah Data</x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>

                <div class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                    <x-ui.skeleton class="h-4 w-3/4" />
                    <x-ui.skeleton class="h-4 w-1/2" />
                    <x-ui.skeleton class="h-4 w-full" />
                    <x-ui.skeleton class="h-4 w-2/3" />
                </div>

                <x-ui.error-state description="Coba muat ulang halaman. Kalau masih gagal, hubungi tim teknis.">
                    <x-slot:action>
                        <x-ui.button variant="secondary" size="sm">Muat Ulang</x-ui.button>
                    </x-slot:action>
                </x-ui.error-state>
            </div>
        </section>

        {{-- Drawer & Modal --}}
        <section x-data="{ drawerOpen: false, modalOpen: false }">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Drawer &amp; Modal</h3>
            <x-ui.card class="flex flex-wrap gap-3">
                <div x-data="{ open: false }">
                    <x-ui.button variant="secondary" @click="open = true">Buka Modal</x-ui.button>
                    <x-ui.modal title="Contoh Modal">
                        <p class="text-sm text-slate-600">Modal dipakai untuk konfirmasi atau form pendek (mis. tambah anggota roster). Klik backdrop atau Escape untuk tutup.</p>
                        <div class="mt-4 flex justify-end gap-2">
                            <x-ui.button variant="ghost" @click="open = false">Batal</x-ui.button>
                            <x-ui.button variant="primary" @click="open = false">Simpan</x-ui.button>
                        </div>
                    </x-ui.modal>
                </div>

                <div x-data="{ open: false }">
                    <x-ui.button variant="secondary" @click="open = true">Buka Drawer</x-ui.button>
                    <x-ui.drawer title="Contoh Drawer — Quick View">
                        <p class="text-sm text-slate-600">Drawer dipakai untuk quick view data (mis. Participant detail di F6) — full page tetap dipakai untuk edit besar.</p>
                    </x-ui.drawer>
                </div>
            </x-ui.card>
        </section>

        {{-- Admin Shell (F1) --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Admin Shell</h3>
            <x-ui.card>
                <p class="text-sm text-slate-600">
                    Sidebar (desktop), topbar (mobile), dan bottom nav (mobile) sudah live di halaman ini juga —
                    ini bukan mockup terisolasi, ini chrome sungguhan dari <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">layouts.dashboard</code>.
                    Resize window ke &lt;768px atau lihat di HP untuk lihat versi mobile.
                </p>
                <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-slate-600">
                    <li>Sidebar: <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">components/layout/sidebar.blade.php</code> — nav + org context + account (bawah)</li>
                    <li>Topbar (mobile): <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">components/layout/topbar.blade.php</code> — brand + org context saja, tanpa hamburger</li>
                    <li>Bottom nav (mobile): <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">components/layout/mobile-nav.blade.php</code> — max 3 tab, "Lainnya" = account menu</li>
                </ul>
            </x-ui.card>
        </section>

        {{-- Navigation States --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Navigation States</h3>
            <x-ui.card class="max-w-xs space-y-1">
                <div class="flex items-center gap-2.5 rounded-md bg-slate-100 px-3 py-2 text-sm font-medium text-slate-900">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.7M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    Teams — Active
                </div>
                <div class="flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                    Organizations — Default
                </div>
            </x-ui.card>
        </section>

        {{-- Breadcrumb --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Breadcrumb</h3>
            <x-ui.card>
                <x-layout.breadcrumb :items="[
                    ['label' => 'Teams', 'href' => '#'],
                    ['label' => 'Paskibra Utama', 'href' => '#'],
                    ['label' => 'Roster'],
                ]" class="mb-0" />
                <p class="mt-2 text-xs text-slate-400">Dipakai hanya untuk halaman yang &ge;1 level di bawah index (show, edit, sub-resource) — index tidak perlu breadcrumb.</p>
            </x-ui.card>
        </section>

        {{-- Page Header --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Page Header</h3>
            <x-ui.card>
                <x-layout.page-header title="Teams" description="Kelola tim yang digunakan dalam kompetisi.">
                    <x-slot:actions>
                        <x-ui.button size="sm">+ Add Team</x-ui.button>
                    </x-slot:actions>
                </x-layout.page-header>
                <p class="text-xs text-slate-400">Pattern resmi tiap halaman F2-F12 — jangan bikin header sendiri di halaman baru.</p>
            </x-ui.card>
        </section>

        {{-- Mobile Bottom Nav --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Mobile Bottom Nav</h3>
            <x-ui.card class="mx-auto max-w-xs">
                <div class="flex border-t border-slate-200">
                    <div class="flex flex-1 flex-col items-center gap-0.5 py-2 text-xs font-medium text-slate-900">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.7M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                        Teams
                    </div>
                    <div class="flex flex-1 flex-col items-center gap-0.5 py-2 text-xs font-medium text-slate-500">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                        Organizations
                    </div>
                    <div class="flex flex-1 flex-col items-center gap-0.5 py-2 text-xs font-medium text-slate-500">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                        Lainnya
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-400">Static mockup di skala kartu — versi live/interaktif ada di bawah layar HP ini (&lt;768px).</p>
            </x-ui.card>
        </section>

        {{-- Organization Context --}}
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-400">Organization Context</h3>
            <x-ui.card class="max-w-xs">
                <div class="rounded-md bg-slate-50 px-3 py-2">
                    <p class="truncate text-sm font-medium text-slate-700">FORBASI Kota Bandung</p>
                    <p class="text-xs text-slate-400">Organization</p>
                </div>
                <p class="mt-2 text-xs text-slate-400">
                    Tampilan sekarang statis (nama organisasi pertama user) — bukan switcher aktif, karena belum ada
                    konsep "active organization" di backend. Jadi switcher multi-organisasi menyusul begitu benar-benar
                    dibutuhkan (Events perlu org-scoping), bukan dibangun sekarang.
                </p>
            </x-ui.card>
        </section>
    </div>
@endsection
