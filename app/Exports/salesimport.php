<?php

namespace App\Exports;

use App\Models\saless;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class salesimport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = saless::with('customer', 'user', 'detail_sales')->orderBy('id','desc');

        if ($this->request->date) {
            $query->whereDate('sale_date', $this->request->date);
        }

        if ($this->request->week) {
            $week = explode('-W', $this->request->week); // format: 2025-W15
            $year = $week[0];
            $weekNumber = $week[1];

            $startOfWeek = \Carbon\Carbon::now()->setISODate($year, $weekNumber)->startOfWeek();
            $endOfWeek = \Carbon\Carbon::now()->setISODate($year, $weekNumber)->endOfWeek();
            $query->whereBetween('sale_date', [$startOfWeek, $endOfWeek]);
        }

        if ($this->request->month) {
            $query->whereMonth('sale_date', date('m', strtotime($this->request->month)))
                  ->whereYear('sale_date', date('Y', strtotime($this->request->month)));
        }

        if ($this->request->year) {
            $query->whereYear('sale_date', $this->request->year);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'nama pembeli',
            'No HP Pembeli',
            'point Pembeli',
            'product',
            'Total Harga',
            'total bayar',
            'total discount point',
            'total kembalian',
            'tanggal pembelian',
        ];
    }

    public function map($item): array
    {
        return [
            optional($item->customer)->name ?? 'Bukan Member',
            optional($item->customer)->no_hp ?? '-',
            optional($item->customer)->point ?? 0,
            $item->detail_sales->map(function ($detail) {
                return optional($detail->product)->name
                    ? optional($detail->product)->name . ' (' . $detail->amount . ' : Rp. ' . number_format( $detail->subtotal, 0, ',', '.') . ')'
                    : 'Produk tidak tersedia';
            })->implode(', '),
            $item->detail_sales->sum('subtotal'),
            $item->total_pay,
            $item->total_price - optional($item->customer)->point ?? 0,
            $item->total_return,
            $item->created_at,
        ];
    }
}