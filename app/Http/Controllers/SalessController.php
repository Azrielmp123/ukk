<?php

namespace App\Http\Controllers;

use App\Models\customers;
use App\Models\detail_sales;
use App\Models\products;
use App\Models\saless;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalessController extends Controller
{
    /**
     * Menampilkan semua data penjualan.
     */
    public function index(Request $request)
{
    $query = saless::with('customer', 'user', 'detail_sales')->orderBy('id', 'desc');

    // Filter per hari
    if ($request->filled('date')) {
        $query->whereDate('sale_date', $request->date);
    }

    // Filter per minggu (dari tanggal tertentu ke awal & akhir minggu itu)
    if ($request->filled('week')) {
        $weekParts = explode('-W', $request->week);
        $year = $weekParts[0];
        $week = $weekParts[1];

        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->setISODate($year, $week)->endOfWeek(Carbon::SUNDAY);

        $query->whereBetween('sale_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()]);
    }

    // Filter per bulan
    if ($request->filled('month')) {
        $month = Carbon::parse($request->month);
        $query->whereMonth('sale_date', $month->month)
              ->whereYear('sale_date', $month->year);
    }

    // Filter per tahun
if ($request->filled('year')) {
    $query->whereYear('sale_date', $request->year);
}

    $saless = $query->get();

    return view('module.pembelian.index', compact('saless'));
}


    /**
     * Tampilkan form untuk membuat transaksi baru.
     */
    public function create()
    {
        // Ambil semua produk untuk ditampilkan dalam form
        $products = products::all();
        return view('module.pembelian.create', compact('products'));
    }

    /**
     * Simpan produk yang dipilih ke session, lalu arahkan ke halaman detail transaksi.
     */
    public function store(Request $request)
    {
        if (!$request->has('shop')) {
            return back()->with('error', 'Pilih produk terlebih dahulu!');
        }

        // Hapus data sebelumnya agar tidak duplikat
        session()->forget('shop');

        $selectedProducts = $request->shop;

        if (!is_array($selectedProducts)) {
            return back()->with('error', 'Format data tidak valid!');
        }

        // Filter produk: hanya yang memiliki jumlah lebih dari 0, dan hapus duplikat berdasarkan ID
        $filteredProducts = collect($selectedProducts)
            ->mapWithKeys(function ($item) {
                $parts = explode(';', $item);
                if (count($parts) > 3) {
                    $id = $parts[0];
                    return [$id => $item];
                }
                return [];
            })
            ->values()
            ->toArray();

        // Simpan ke sesi
        session(['shop' => $filteredProducts]);

        // Arahkan ke halaman detail pembelian
        return redirect()->route('sales.post');
    }

    /**
     * Menampilkan halaman detail pembelian berdasarkan data di session.
     */
    public function post()
    {
        $shop = session('shop', []);
        return view('module.pembelian.detail', compact('shop'));
    }

    /**
     * Menyimpan transaksi penjualan baru, baik untuk member maupun non-member.
     */
    public function createsales(Request $request)
    {
        $request->validate([
            'total_pay' => 'required',
        ], [
            'total_pay.required' => 'Berapa jumlah uang yang dibayarkan?',
        ]);

        // Menghapus karakter selain angka (format Rupiah)
        $newPrice = (int) preg_replace('/\D/', '', $request->total_price);
        $newPay = (int) preg_replace('/\D/', '', $request->total_pay);
        $newreturn = $newPay - $newPrice;

        if ($request->member === 'Member') {
            // Cek apakah customer sudah pernah beli
            $existCustomer = customers::where('no_hp', $request->no_hp)->first();
            $point = floor($newPrice / 100); // 1 point per 100 rupiah

            if ($existCustomer) {
                // Tambah point jika sudah pernah
                $existCustomer->update([
                    'point' => $existCustomer->point + $point,
                ]);
                $customer_id = $existCustomer->id;
            } else {
                // Buat customer baru
                $existCustomer = customers::create([
                    'name' => "",
                    'no_hp' => $request->no_hp,
                    'point' => $point,
                ]);
                $customer_id = $existCustomer->id;
            }

            // Simpan data penjualan
            $sales = saless::create([
                'sale_date' => Carbon::now()->format('Y-m-d'),
                'total_price' => $newPrice,
                'total_pay' => $newPay,
                'total_return' => $newreturn,
                'customer_id' => $customer_id,
                'user_id' => Auth::id(),
                'point' => $point,
                'total_point' => 0,
            ]);

            $detailSalesData = [];

            foreach ($request->shop as $shopItem) {
                $item = explode(';', $shopItem);
                $productId = (int) $item[0];
                $amount = (int) $item[3];
                $subtotal = (int) $item[4];

                $detailSalesData[] = [
                    'sale_id' => $sales->id,
                    'product_id' => $productId,
                    'amount' => $amount,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Kurangi stok produk
                $product = products::find($productId);
                if ($product) {
                    $newStock = $product->stock - $amount;
                    if ($newStock < 0) {
                        return redirect()->back()->withErrors(['error' => 'Stok tidak mencukupi untuk produk ' . $product->name]);
                    }
                    $product->update(['stock' => $newStock]);
                }
            }

            // Simpan semua detail penjualan sekaligus
            detail_sales::insert($detailSalesData);

            // Arahkan ke halaman registrasi member (untuk lengkapi data)
            return redirect()->route('sales.create.member', ['id' => saless::latest()->first()->id])
                ->with('message', 'Silahkan daftar sebagai member');
        } else {
            // Jika bukan member (customer dipilih dari dropdown)
            $sales = saless::create([
                'sale_date' => Carbon::now()->format('Y-m-d'),
                'total_price' => $newPrice,
                'total_pay' => $newPay,
                'total_return' => $newreturn,
                'customer_id' => $request->customer_id,
                'user_id' => Auth::id(),
                'point' => 0,
                'total_point' => 0,
            ]);

            $detailSalesData = [];

            foreach ($request->shop as $shopItem) {
                $item = explode(';', $shopItem);
                $productId = (int) $item[0];
                $amount = (int) $item[3];
                $subtotal = (int) $item[4];

                $detailSalesData[] = [
                    'sale_id' => $sales->id,
                    'product_id' => $productId,
                    'amount' => $amount,
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Kurangi stok produk
                $product = products::find($productId);
                if ($product) {
                    $newStock = $product->stock - $amount;
                    if ($newStock < 0) {
                        return redirect()->back()->withErrors(['error' => 'Stok tidak mencukupi untuk produk ' . $product->name]);
                    }
                    $product->update(['stock' => $newStock]);
                }
            }

            detail_sales::insert($detailSalesData);

            // Arahkan ke halaman print
            return redirect()->route('sales.print.show', ['id' => $sales->id])->with('Silahkan Print');
        }
    }

    /**
     * Menampilkan detail penjualan untuk proses pendaftaran member.
     */
    public function createmember($id)
    {
        $sale = saless::with('detail_sales.product')->findOrFail($id);

        // Cek apakah ini pembelian pertama atau bukan
        $notFirst = saless::where('customer_id', $sale->customer->id)->count() != 1 ? true : false;

        return view('module.pembelian.view-member', compact('sale','notFirst'));
    }

}