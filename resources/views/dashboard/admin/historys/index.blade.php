@extends('layouts.argon')
@section('title', 'History Pesanan')
@section('page_title', 'History')

@section('content')
	<div class="flex-auto p-3 pt-0 -mx-3">
		<div class="p-2.5 bg-white shadow-md rounded-xl dark:bg-gray-800 dark:border-gray-700 min-h-[715px]">
			<h2 class="mb-6 text-black text-md dark:text-white">
				👤 {{ Auth::user()->name ?? 'Admin' }}
				🚩 {{ Auth::user()->region->name ?? 'N/A' }}
			</h2>
			<div class="overflow-x-auto">
				<table class="items-center w-full mb-0 align-top border-gray-200 text-slate-500">
					<thead class="align-bottom">
						<tr class="text-xs font-bold text-left text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
							<th class="px-4 py-3">No</th>
							<th class="px-4 py-3">Invoice</th>
							<th class="px-4 py-3">Customer</th>
							<th class="px-4 py-3">Kurir</th>
							<th class="px-4 py-3">Status</th>
							<th class="px-4 py-3">Total</th>
						</tr>
					</thead>
					<tbody>
						@forelse ($orders as $order)
							<tr class="text-sm font-normal text-gray-700 border-b dark:text-gray-400 dark:border-gray-700">
								<td class="px-4 py-2">{{ $loop->iteration }}</td>
								<td class="px-4 py-2 font-mono">{{ $order->invoice_number }}</td>
								<td class="px-4 py-2">{{ $order->customer->name ?? '-' }}</td>
								<td class="px-4 py-2">{{ $order->createdBy->name ?? '-' }}</td>
								<td class="px-4 py-2">
									<span class="inline-block px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded">
										{{ ucfirst(str_replace('_', ' ', $order->status)) }}
									</span>
								</td>
								<td class="px-4 py-2">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
							</tr>
						@empty
							<tr>
								<td colspan="6" class="py-6 text-center text-gray-500">Tidak ada pesanan history.</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection
