@extends('main')
@section('title', '| Pembelian')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-body">

                    <div class="row justify-content-between mb-3">

                        {{-- Kiri: Filter dan Export --}}
                        <div class="col-md-8">
                            @if (Auth::user()->role == 'employee' || Auth::user()->role == 'admin' )
                            <a href="{{ route('sales.exportexcel', request()->query()) }}" class="btn btn-info mb-3">
                                Export Penjualan (.xlsx)
                            </a>                            
                            @endif

                            <<form action="{{ route('sales') }}" method="GET">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Tanggal</label>
                                        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Minggu</label>
                                        <input type="week" name="week" class="form-control" value="{{ request('week') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Bulan</label>
                                        <input type="month" name="month" class="form-control" value="{{ request('month') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Tahun</label>
                                        <input type="number" name="year" class="form-control" value="{{ request('year') }}">
                                    </div>
                                    <div class="col-md-12 mt-3">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('sales') }}" class="btn btn-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>
                            
                        </div>

                        {{-- Kanan: Tambah Penjualan --}}
                        @if (Auth::user()->role == 'employee')
                            <div class="col-md-4 text-end align-self-end">
                                <a href="{{ route('sales.create') }}" class="btn btn-primary">
                                    <i class="mdi mdi-plus"></i> Tambah Penjualan
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Tabel Penjualan --}}
                    <div class="table-responsive">
                        <table id="salesTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Tanggal Penjualan</th>
                                    <th>Total Harga</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($saless as $i => $sale)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $sale->customer->name ?? 'NON-MEMBER' }}</td>
                                    <td>{{ $sale->sale_date }}</td>
                                    <td>{{ 'Rp. ' . number_format($sale->total_price, 0, ',', '.') }}</td>
                                    <td>{{ $sale->user->name ?? 'Pegawai Tidak Ada' }}</td>
                                    <td>
                                        <div class="d-flex justify-content-around">
                                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#lihat-{{ $sale->id }}">
                                                <i class="mdi mdi-eye"></i>
                                            </button>
                                            <a href="{{ route('download', $sale->id) }}" class="btn btn-info">
                                                <i class="mdi mdi-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Modal Detail Penjualan --}}
                                <div class="modal fade" id="lihat-{{ $sale->id }}" tabindex="-1" aria-labelledby="modalLihat" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="modalLihat">Detail Penjualan</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">

                                                <div class="row mb-2">
                                                    <div class="col-6">
                                                        <small>
                                                            Member Status: {{ $sale->customer ? 'Member' : 'Bukan Member' }}<br>
                                                            No. HP: {{ $sale->customer->no_hp ?? '-' }}<br>
                                                            Poin Member: {{ $sale->customer->point ?? '-' }}
                                                        </small>
                                                    </div>
                                                    <div class="col-6">
                                                        <small>
                                                            Bergabung Sejak: 
                                                            {{ $sale->customer ? \Carbon\Carbon::parse($sale->customer->created_at)->format('d F Y') : '-' }}
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="row text-center fw-bold border-bottom py-2">
                                                    <div class="col-3">Nama Produk</div>
                                                    <div class="col-3">Qty</div>
                                                    <div class="col-3">Harga</div>
                                                    <div class="col-3">Subtotal</div>
                                                </div>

                                                @foreach ($sale->detail_sales as $item)
                                                <div class="row text-center py-1">
                                                    <div class="col-3">{{ $item->product->name }}</div>
                                                    <div class="col-3">{{ $item->amount }}</div>
                                                    <div class="col-3">Rp. {{ number_format($item->product->price, 0, ',', '.') }}</div>
                                                    <div class="col-3">Rp. {{ number_format($item->subtotal, 0, ',', '.') }}</div>
                                                </div>
                                                @endforeach

                                                <div class="row mt-3 text-end">
                                                    <div class="col-9"><b>Total</b></div>
                                                    <div class="col-3"><b>Rp. {{ number_format($sale->total_price, 0, ',', '.') }}</b></div>
                                                </div>

                                                <div class="row mt-3">
                                                    <center>
                                                        Dibuat pada: {{ $sale->created_at }} <br>
                                                        Oleh: {{ $sale->user->name ?? 'Pegawai Tidak Ada' }}
                                                    </center>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection