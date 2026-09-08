<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement_{{ $customer->name }}_{{ date('d-m-Y') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1e293b;
            background: #fff;
            font-size: 13px;
        }
        .statement-wrapper {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }
        .brand-header {
            border-bottom: 3px solid #0f172a;
            padding-bottom: 15px;
        }
        .company-title {
            color: #0f172a;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .statement-pill {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            padding: 6px 20px;
            border-radius: 4px;
            display: inline-block;
            text-transform: uppercase;
            font-size: 14px;
        }
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
        }
        .statement-table {
            border: 1px solid #cbd5e1;
            width: 100%;
        }
        .statement-table th {
            background-color: #1e293b !important;
            color: #ffffff !important;
            font-weight: 600;
            text-align: center;
            padding: 8px;
            border: 1px solid #334155;
        }
        .statement-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
        }
        .opening-row {
            background-color: #fffbe6 !important;
        }
        .payment-row {
            background-color: #f0fdf4 !important;
        }
        .summary-box {
            background-color: #0f172a;
            color: #fff;
            padding: 12px;
            border-radius: 6px;
        }
        .signature-section {
            margin-top: 60px;
        }
        .signature-line {
            border-top: 1px dashed #94a3b8;
            width: 180px;
            text-align: center;
            padding-top: 5px;
            font-weight: 600;
            color: #475569;
        }

        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

    @if(!($isEmbed ?? false))
    <!-- Floating Top Action Bar -->
    <div class="no-print bg-light p-3 border-bottom mb-4 text-center">
        <button onclick="window.print()" class="btn btn-primary fw-bold px-4">🖨️ প্রিন্ট করুন / PDF সেভ করুন</button>
        <button onclick="window.close()" class="btn btn-outline-secondary fw-bold ms-2">বন্ধ করুন</button>
    </div>
    @endif

    <div class="statement-wrapper" id="statement-capture-area">
        <!-- Header -->
        <div class="row brand-header align-items-center mb-4">
            <div class="col-6">
                @if(isset($settings->logo) && $settings->logo)
                    <img src="{{ asset('storage/' . $settings->logo) }}" alt="Logo" style="max-height: 55px;" class="mb-2">
                @endif
                <h4 class="company-title mb-0">{{ $settings->company_name ?? 'GF Food and Beverage LTD' }}</h4>
                <p class="text-muted small mb-0">{{ $settings->address ?? 'ভানুগাছ বাজার, কমলগঞ্জ, মৌলভীবাজার' }}</p>
                <p class="text-muted small mb-0">ফোন: {{ $settings->phone ?? '01344-558778' }}</p>
            </div>
            <div class="col-6 text-end">
                <div class="statement-pill mb-2">কাস্টমার স্টেটমেন্ট</div>
                <p class="mb-0 text-muted small">তৈরির তারিখ: <strong>{{ date('d/m/Y') }}</strong></p>
                @if($fromDate && $toDate)
                    <p class="mb-0 text-muted small">সময়সীমা: {{ date('d-m-Y', strtotime($fromDate)) }} হতে {{ date('d-m-Y', strtotime($toDate)) }}</p>
                @endif
            </div>
        </div>

        <!-- Customer Profile Card -->
        <div class="info-card mb-4">
            <div class="row">
                <div class="col-7">
                    <p class="mb-1">কাস্টমারের নাম: <strong>{{ $customer->name }}</strong></p>
                    <p class="mb-0">মোবাইল নম্বর: <strong>{{ $customer->phone }}</strong></p>
                </div>
                <div class="col-5 text-end">
                    <p class="mb-1">ঠিকানা: <strong>{{ $customer->address ?? 'N/A' }}</strong></p>
                    <p class="mb-0">লেনদেনের ধরন: <strong>{{ strtoupper($filterType) }}</strong></p>
                </div>
            </div>
        </div>

        <!-- Statement Table -->
        <table class="statement-table align-middle mb-4">
            <thead>
                <tr>
                    <th style="width: 14%;">তারিখ</th>
                    <th style="width: 38%;">বিবরণ / রেফারেন্স</th>
                    <th style="width: 16%;">বিল/দেনা (৳)</th>
                    <th style="width: 16%;">জমা (৳)</th>
                    <th style="width: 16%;">অবশিষ্ট (৳)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledger as $item)
                    <tr class="{{ $item->transaction_type === 'opening_due' ? 'opening-row' : ($item->transaction_type === 'payment' ? 'payment-row' : '') }}">
                        <td class="text-center">{{ $item->date_time ? date('d-m-Y', strtotime($item->date_time)) : '-' }}</td>
                        <td>
                            @if($item->transaction_type === 'opening_due')
                                <strong>প্রারম্ভিক বকেয়া (Opening Due)</strong>
                            @elseif($item->transaction_type === 'invoice')
                                <div><strong>ইনভয়েস মেমো #{{ $item->ref_no }}</strong></div>
                                @if(isset($item->total_amount) && $item->total_amount > 0)
                                    <div style="font-size: 11px; color: #475569; margin-top: 2px;">
                                        (মোট: {{ number_format($item->total_amount, 2) }} ৳
                                        @if(!empty($item->discount) && $item->discount > 0) | কমিশন: {{ number_format($item->discount, 2) }} ৳ @endif
                                        @if(!empty($item->instant_paid) && $item->instant_paid > 0) | জমা: {{ number_format($item->instant_paid, 2) }} ৳ @endif)
                                    </div>
                                @endif
                            @else
                                <span class="text-success fw-semibold">বকেয়া জমা ({{ $item->note ?? 'Cash' }}) - #{{ $item->ref_no }}</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $item->debit > 0 ? number_format($item->debit, 2) : '-' }}</td>
                        <td class="text-end text-success fw-semibold">{{ $item->credit > 0 ? number_format($item->credit, 2) : '-' }}</td>
                        <td class="text-end fw-bold">{{ number_format($item->balance, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">কোনো রেকর্ড পাওয়া যায়নি।</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary Totals Block -->
        <div class="row justify-content-end mb-4">
            <div class="col-6">
                <table class="table table-sm table-borderless mb-0">
                    @if($stats['opening_due'] > 0)
                    <tr>
                        <td class="text-end fw-semibold">প্রারম্ভিক বকেয়া:</td>
                        <td class="text-end fw-bold text-warning" style="width: 130px;">{{ number_format($stats['opening_due'], 2) }} ৳</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-end fw-semibold">মোট বিক্রয় বিল:</td>
                        <td class="text-end fw-bold" style="width: 130px;">{{ number_format($stats['total_sales'], 2) }} ৳</td>
                    </tr>
                    <tr>
                        <td class="text-end fw-semibold">মোট প্রাপ্ত জমা:</td>
                        <td class="text-end fw-bold text-success" style="width: 130px;">{{ number_format($stats['total_paid'], 2) }} ৳</td>
                    </tr>
                </table>
                <div class="summary-box mt-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-6">মোট অবশিষ্ট বকেয়া:</span>
                    <span class="fw-bold fs-5">{{ number_format($stats['current_due'], 2) }} ৳</span>
                </div>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="d-flex justify-content-between signature-section">
            <div class="signature-line">কাস্টমারের স্বাক্ষর</div>
            <div class="signature-line">কর্তৃপক্ষের স্বাক্ষর</div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 pt-3 border-top text-muted extra-small">
            {{ $settings->invoice_footer_note ?? 'এটি একটি কম্পিউটার জেনারেটেড স্টেটমেন্ট। কোনো হস্তাক্ষরের প্রয়োজন নেই।' }}
        </div>
    </div>

    @if(!($isEmbed ?? false))
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
    @endif
</body>
</html>