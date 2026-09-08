<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice_{{ $sale->invoice_no }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --pos-primary: #0F6F4C;
            --pos-primary-dark: #0B5A3D;
            --pos-primary-light: #E7F3EC;
            --pos-accent: #D98E2B;
            --pos-accent-light: #FBF0DD;
            --pos-danger: #C6413A;
            --pos-danger-light: #FBEAE8;
            --pos-ink: #1C231F;
            --pos-muted: #6B7280;
        }

        @page { size: A4 portrait; margin: 14mm; }

        * { box-sizing: border-box; }

        body {
            font-family: 'Hind Siliguri', sans-serif;
            color: var(--pos-ink);
            background: #EDECE7;
            margin: 0;
            padding: 0;
        }

        .mono { font-family: 'JetBrains Mono', monospace; font-variant-numeric: tabular-nums; }

        .no-print-bar {
            position: sticky;
            top: 0;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 16px;
            display: flex;
            justify-content: center;
            gap: 10px;
            z-index: 10;
        }
        .no-print-bar button {
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-print { background: var(--pos-primary); color: #fff; }
        .btn-close-page { background: #e5e7eb; color: #374151; }

        #invoice-capture-area {
            max-width: 800px;
            margin: 24px auto;
            background: #fff;
            padding: 40px 44px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid var(--pos-primary);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .invoice-header img { max-height: 50px; margin-bottom: 6px; }
        .company-name { font-size: 20px; font-weight: 700; color: var(--pos-primary-dark); margin: 0 0 2px; }
        .company-meta { font-size: 12px; color: var(--pos-muted); margin: 0; line-height: 1.5; }

        .invoice-meta-box { text-align: right; font-size: 13px; }
        .invoice-pill {
            display: inline-block;
            background: var(--pos-primary);
            color: #fff;
            font-weight: 700;
            padding: 5px 16px;
            border-radius: 5px;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .invoice-meta-box p { margin: 2px 0; color: var(--pos-ink); }
        .invoice-meta-box .label { color: var(--pos-muted); }

        .bill-to-box {
            background: var(--pos-primary-light);
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 22px;
            font-size: 13px;
        }
        .bill-to-box .label { font-size: 11px; font-weight: 700; color: var(--pos-primary-dark); text-transform: uppercase; letter-spacing: 0.5px; }
        .bill-to-box .cust-name { font-size: 15px; font-weight: 700; margin: 3px 0; }
        .bill-to-box p { margin: 1px 0; color: #374151; }

        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
        table.items-table th {
            background: var(--pos-ink);
            color: #fff;
            text-align: left;
            padding: 8px 10px;
            font-weight: 600;
        }
        table.items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
        }
        table.items-table th.num, table.items-table td.num { text-align: right; }
        table.items-table th.center, table.items-table td.center { text-align: center; }

        .totals-wrap { display: flex; justify-content: flex-end; margin-bottom: 24px; }
        .totals-box { width: 300px; font-size: 13px; }
        .totals-box .row { display: flex; justify-content: space-between; padding: 5px 0; }
        .totals-box .row.grand {
            background: var(--pos-accent-light);
            font-weight: 700;
            padding: 7px 10px;
            border-radius: 5px;
            margin: 4px 0;
        }
        .totals-box .row.prev-due { color: var(--pos-danger); }
        .totals-box .row.total-due {
            background: var(--pos-danger);
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            padding: 10px 12px;
            border-radius: 6px;
            margin-top: 8px;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 70px;
            font-size: 12px;
        }
        .signature-line {
            border-top: 1px dashed #9CA3AF;
            width: 170px;
            text-align: center;
            padding-top: 6px;
            color: var(--pos-muted);
        }

        .invoice-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 14px;
            border-top: 1px solid #eee;
            font-size: 11px;
            color: var(--pos-muted);
        }

        @media print {
            body { background: #fff; }
            .no-print-bar { display: none !important; }
            #invoice-capture-area { margin: 0; box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

    @if(!$isEmbed)
    <div class="no-print-bar">
        <button class="btn-print" onclick="window.print()">🖨️ প্রিন্ট করুন</button>
        <button class="btn-close-page" onclick="window.close()">বন্ধ করুন</button>
    </div>
    @endif

    <div id="invoice-capture-area">
        <div class="invoice-header">
            <div>
                @if(!empty($settings->logo))
                    <img src="{{ asset('storage/' . $settings->logo) }}" alt="Logo">
                @endif
                <p class="company-name">{{ $settings->company_name ?? 'GF Food and Beverage Ltd' }}</p>
                <p class="company-meta">
                    @if(!empty($settings->address)) {{ $settings->address }}<br> @endif
                    @if(!empty($settings->phone)) ফোন: {{ $settings->phone }} @endif
                    @if(!empty($settings->email)) &nbsp;|&nbsp; {{ $settings->email }} @endif
                </p>
            </div>
            <div class="invoice-meta-box">
                <div class="invoice-pill">ইনভয়েস</div>
                <p><span class="label">নং:</span> <strong class="mono">#{{ $sale->invoice_no }}</strong></p>
                <p><span class="label">তারিখ:</span> {{ $sale->sale_date->format('d M, Y') }}</p>
                @if($sale->user)
                    <p><span class="label">বিক্রয়কারী:</span> {{ $sale->user->name }}</p>
                @endif
            </div>
        </div>

        <div class="bill-to-box">
            <div class="label">বিল প্রাপক</div>
            @if($sale->customer)
                <p class="cust-name">{{ $sale->customer->name }}</p>
                @if($sale->customer->phone)<p>মোবাইল: {{ $sale->customer->phone }}</p>@endif
                @if($sale->customer->address)<p>{{ $sale->customer->address }}</p>@endif
            @else
                <p class="cust-name">ক্যাশ / ওয়াক-ইন কাস্টমার</p>
            @endif
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:6%">ক্র.</th>
                    <th style="width:39%">পণ্যের বিবরণ</th>
                    <th class="center" style="width:15%">পরিমাণ</th>
                    <th class="num" style="width:20%">একক মূল্য</th>
                    <th class="num" style="width:20%">মোট</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product->name ?? 'N/A' }}
                        @php
                            $weights = [
                                '5kg'  => '(৫ কেজি)',
                                '1kg'  => '(১ কেজি)',
                                '500g' => '(৫০০ গ্রাম)',
                                '250g' => '(২৫০ গ্রাম)',
                                '100g' => '(১০০ গ্রাম)',
                                '50g'  => '(৫০ গ্রাম)',
                            ];
                        @endphp

                        <span style="color:var(--pos-primary-dark); font-size:12px;">
                            {{ $weights[$item->unit_type] ?? '' }}
                        </span>

                    </td> 
                    <td class="center mono">{{ $item->quantity }}</td>
                    <td class="num mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($item->applied_price, 2) }}</td>
                    <td class="num mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach 
            </tbody>
        </table>

        <div class="totals-wrap">
            <div class="totals-box">
                <div class="row">
                    <span>সাব-টোটাল:</span>
                    <span class="mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($sale->gross_amount, 2) }}</span>
                </div>
                <div class="row">
                    <span>ছাড় (-):</span>
                    <span class="mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($sale->discount_amount, 2) }}</span>
                </div>
                <div class="row grand">
                    <span>ইনভয়েস সর্বমোট:</span>
                    <span class="mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($sale->net_amount, 2) }}</span>
                </div>
                <div class="row">
                    <span>জমা:</span>
                    <span class="mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($sale->paid_amount, 2) }}</span>
                </div>
                <div class="row">
                    <span><strong>এই ইনভয়েসের বকেয়া:</strong></span>
                    <span class="mono"><strong>{{ $settings->currency_symbol ?? '৳' }}{{ number_format($sale->due_amount, 2) }}</strong></span>
                </div>

                @if($sale->customer)
                    @if($previousDueCount > 0)
                    <div class="row prev-due">
                        <span>পূর্ববর্তী বকেয়া ({{ $previousDueCount }} ইনভয়েস):</span>
                        <span class="mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($previousDue, 2) }}</span>
                    </div>
                    @endif

                    @if($openingDueRemaining > 0)
                    <div class="row prev-due">
                        <span>প্রারম্ভিক বকেয়া:</span>
                        <span class="mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($openingDueRemaining, 2) }}</span>
                    </div>
                    @endif

                    <div class="row total-due">
                        <span>সর্বমোট বকেয়া (Total Due):</span>
                        <span class="mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($totalDue, 2) }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="signature-section">
            <div class="signature-line">কর্তৃপক্ষের স্বাক্ষর</div>
            <div class="signature-line">কাস্টমারের স্বাক্ষর</div>
        </div>

        <div class="invoice-footer">
            {{ $settings->invoice_footer_note ?? 'আমাদের সাথে ব্যবসা করার জন্য ধন্যবাদ!' }}
        </div>
    </div>

    @if(!$isEmbed)
    <script>
        window.onload = function () {
            setTimeout(function () { window.print(); }, 500);
        };
    </script>
    @endif
</body>
</html>