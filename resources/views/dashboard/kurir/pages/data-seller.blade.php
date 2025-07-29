@extends('layouts.argon')
@section('title', 'Dashboard Kurir')
@section('content')
@include('dashboard.kurir.modal.tmbh-seller')

<div class="flex flex-wrap -mx-3">
    <div class="flex-none w-full max-w-full px-3">
        <div class="relative flex flex-col min-w-0 mb-6 break-words bg-white border-0 border-transparent border-solid shadow-xl dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
            <div class="p-6 pb-0 mb-0 border-b-0 border-b-solid rounded-t-2xl border-b-transparent">
                <h6 class="dark:text-white">Data Seller</h6>
                <div class="flex justify-end mb-3">
                    <button type="submit" data-modal-toggle="crud-modal"
                        class="px-3 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-green focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Tambah Data
                    </button>
                </div>
            </div>
            <div class="flex-auto px-0 pt-0 pb-2">
                <div class="p-0 overflow-x-auto">
                    <table class="items-center w-full mb-0 align-top border-collapse dark:border-white/40 text-slate-500">
                        <thead class="bg-white text-gray-600 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Nama</th>
                                <th class="px-4 py-3 text-center font-semibold">Alamat</th>
                                <th class="px-4 py-3 text-center font-semibold">No. HP</th>
                                <th class="px-4 py-3 text-center font-semibold"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td class="p-2 align-middle border-b whitespace-nowrap">
                                    <div class="flex px-2 py-1">
                                        <div>
                                            <h6 class="text-sm font-medium">John Michael</h6>
                                            <!-- <p class="text-xs text-slate-500">john@creative-tim.com</p> -->
                                        </div>
                                    </div>
                                </td>
                                <td class="p-2 text-center align-middle border-b whitespace-nowrap">
                                    <p class="text-xs font-semibold">Jl. Melati No.09</p>

                                </td>
                                <td class="p-2 text-center align-middle border-b whitespace-nowrap">
                                    <span class="text-xs text-slate-500 font-semibold">+62896xxxxxxxx</span>
                                </td>
                                <td class="p-2 align-middle border-b whitespace-nowrap">
                                    <a href="#" class="text-xs text-blue-600 font-semibold">Edit</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="p-2 align-middle border-b whitespace-nowrap">
                                    <div class="flex px-2 py-1">
                                        <div>
                                            <h6 class="text-sm font-medium">John Michael</h6>
                                            <!-- <p class="text-xs text-slate-500">john@creative-tim.com</p> -->
                                        </div>
                                    </div>
                                </td>
                                <td class="p-2 text-center align-middle border-b whitespace-nowrap">
                                    <p class="text-xs font-semibold">Jl. Melati No.09</p>

                                </td>
                                <td class="p-2 text-center align-middle border-b whitespace-nowrap">
                                    <span class="text-xs text-slate-500 font-semibold">+62896xxxxxxxx</span>
                                </td>
                                <td class="p-2 align-middle border-b whitespace-nowrap">
                                    <a href="#" class="text-xs text-blue-600 font-semibold">Edit</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="p-2 align-middle border-b whitespace-nowrap">
                                    <div class="flex px-2 py-1">
                                        <div>
                                            <h6 class="text-sm font-medium">John Michael</h6>
                                            <!-- <p class="text-xs text-slate-500">john@creative-tim.com</p> -->
                                        </div>
                                    </div>
                                </td>
                                <td class="p-2 text-center align-middle border-b whitespace-nowrap">
                                    <p class="text-xs font-semibold">Jl. Melati No.09</p>

                                </td>
                                <td class="p-2 text-center align-middle border-b whitespace-nowrap">
                                    <span class="text-xs text-slate-500 font-semibold">+62896xxxxxxxx</span>
                                </td>
                                <td class="p-2 align-middle border-b whitespace-nowrap">
                                    <a href="#" class="text-xs text-blue-600 font-semibold">Edit</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/argon/js/plugins/chartjs.min.js"></script>
<script src="/assets/argon/js/plugins/perfect-scrollbar.min.js" async></script>
<script src="/assets/argon/js/assets/argon-dashboard-tailwind.js?v=1.0.1" async></script>

@endsection