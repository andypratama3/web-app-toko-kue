{{--
    ResponsiveTable — membungkus <table> agar:
      • < 768px  → tiap <tr> jadi kartu (label kiri, nilai kanan)
                   label dibaca dari data-label tiap <td>
      • ≥ 768px  → tabel normal, scroll horizontal bila kolom banyak

    Pemakaian:
        <x-admin.responsive-table>
            <table class="admin-table w-full text-sm">
                <thead>…</thead>
                <tbody>…</tbody>
            </table>
        </x-admin.responsive-table>

    Wrapper memakai -mx-* + px-* supaya tabel bisa menyentuh tepi layar
    di mobile tanpa membuat halaman ikut melebar.
--}}
@props([
    'minHeight' => null,
    'class' => '',
])

<div class="admin-table-scroll -mx-3 px-3 sm:-mx-4 sm:px-4 {{ $class }}"
    @if ($minHeight) style="min-height: {{ $minHeight }}px" @endif>
    {{ $slot }}
</div>
