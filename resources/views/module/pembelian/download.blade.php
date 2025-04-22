<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bukti Penjualan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        #receipt {
            box-shadow: 5px 10px 15px rgba(0, 0, 0, 0.5);
            padding: 20px;
            margin: 30px auto 0 auto;
            width: 500px;
            background: #fff;
        }

        h2 {
            font-size: .9rem;
        }

        p {
            font-size: .8rem;
            color: #666;
            line-height: 1.2rem;
        }

        .store-info {
            text-align: center;
            font-size: .8rem;
            margin-bottom: 15px;
        }

        #member-info {
            text-align: left;
            font-size: .75rem;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
        }

        td {
            padding: 5px 10px;
            border: 1px solid #eee;
        }

        .tabletitle h2 {
            font-size: .6rem;
            font-weight: bold;
        }

        .service {
            border-bottom: 1px solid #eee;
        }

        .itemtext {
            font-size: .7rem;
        }

        .itemtext-left {
            text-align: left;
        }

        .summary-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    
}

.summary-table td {
    border: none;
    font-size: .75rem;
    padding: 3px 10px;
    
}

.summary {
    margin-top: 20px;
    font-size: .8rem;
    text-align: right;
}

.summary .row {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin: 5px 0;
}

.summary .row span {
    display: inline-block;
    min-width: 180px;
    text-align: right;
    font-weight: normal;
    color: #666; /* Sesuaikan warna teks dengan yang ada di tabel */
}

.summary .row h3 {
    font-size: .8rem;
    font-weight: normal; /* Sesuaikan ketebalan dengan teks dalam tabel */
    color: #666; /* Sesuaikan warna teks dengan yang ada di tabel */
    margin: 0 0 0 10px;
    text-align: right;
}


    

        #legalcopy {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
<div id="receipt">
    <div class="store-info">
        <strong>FlexyLite</strong><br>
        Jl. Wangun Alamat No. 123, Bogor<br>
        Telp: 0812-3456-7890
    </div>

    <div id="member-info">
        <small>
            Member Status : {{ $sale['customer'] ? 'Member' : 'Bukan Member' }}<br>
            No. HP : {{ $sale['customer'] ? $sale['customer']['no_hp'] : '-' }}<br>
            Bergabung Sejak :
            {{ $sale['customer'] ? \Carbon\Carbon::parse($sale['customer']['created_at'])->format('d F Y') : '-' }}<br>
            Poin Member : {{ $sale['customer'] ? $sale['customer']['poin'] : '-' }}
        </small>
    </div>

    <div id="bot">
        <div id="table">
            <table>
                <tr class="tabletitle">
                    <td class="itemtext-left"><h2>Nama Produk</h2></td>
                    <td><h2>Qty</h2></td>
                    <td><h2>Harga</h2></td>
                    <td><h2>Sub Total</h2></td>
                </tr>
                @foreach ($sale['detail_sales'] as $item)
                    <tr class="service">
                        <td class="itemtext itemtext-left">{{ $item['product']['name'] }}</td>
                        <td class="itemtext">{{ $item['amount'] }}</td>
                        <td class="itemtext">Rp. {{ number_format($item['product']['price'], '0', ',', '.') }}</td>
                        <td class="itemtext">Rp. {{ number_format($item['subtotal'], '0', ',', '.') }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        <!-- Summary table aligned under 'Sub Total' column -->
        <table class="summary-table">
            <tr>
                <td class="label" colspan="3">Total Harga:</td>
                <td class="value">Rp. {{ number_format($sale['total_price'], '0', ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label" colspan="3">Poin Digunakan:</td>
                <td class="value">{{ $sale['point'] }}</td>
            </tr>
            <tr>
                <td class="label" colspan="3">Harga Setelah Poin:</td>
                <td class="value">Rp. {{ number_format($sale['total_point'], '0', ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label" colspan="3">Total Kembalian:</td>
                <td class="value">Rp. {{ number_format($sale['total_return'], '0', ',', '.') }}</td>
            </tr>
            <!-- Add Total Pembayaran -->
            <tr>
                <td class="label" colspan="3"><strong>Total Pembayaran:</strong></td>
                <td class="value"><strong>Rp.5.000.000.000</strong></td>
            </tr>
        </table>

        <div id="legalcopy">
            <p>{{ $sale['created_at'] }} | {{ $sale['user']['name'] }}</p>
            <p><strong>Terima kasih atas pembelian Anda!</strong></p>
        </div>
    </div>
</div>

</body>

</html>
