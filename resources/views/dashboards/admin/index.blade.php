@extends('layouts.argon')

@section('title', 'Dashboard Admin')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="mb-4 card">
        <div class="pb-0 card-header">
          <h6>Monitoring Kurir di Region {{ Auth::user()->region }}</h6>
        </div>
        <div class="px-0 pt-0 pb-2 card-body">
          <div class="p-0 table-responsive">
            <table class="table mb-0 align-items-center">
              <thead>
                <tr>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Kurir</th>
                  <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Email</th>
                </tr>
              </thead>
              <tbody>
                @forelse($couriers as $kurir)
                <tr>
                  <td>
                    <div class="px-2 py-1 d-flex">
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="mb-0 text-sm">{{ $kurir->name }}</h6>
                      </div>
                    </div>
                  </td>
                  <td>
                    <p class="mb-0 text-xs font-weight-bold">{{ $kurir->email }}</p>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="2" class="py-4 text-center">Tidak ada data kurir di region ini.</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
