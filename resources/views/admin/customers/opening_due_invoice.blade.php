<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opening-Due_{{ $customer->name }}</title>
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
        body { font-family: 'Hind Siliguri', sans-serif; color: var(--pos-ink); background: #EDECE7; margin: 0; padding: 0; }
        .mono { font-family: 'JetBrains Mono', monospace; font-variant-numeric: tabular-nums; }

        .no-print-bar {
            position: sticky; top: 0; background: #fff; border-bottom: 1px solid #e5e7eb;
            padding: 12px 16px; display: flex; justify-content: center; gap: 10px; z-index: 10;
        }
        .no-print-bar button { border: none; border-radius: 8px; padding: 10px 20px; font-weight: 700; font-size: 14px; cursor: pointer; }
        .btn-print { background: var(--pos-primary); color: #fff; }
        .btn-close-page { background: #e5e7eb; color: #374151; }

        #invoice-capture-area { max-width: 800px; margin: 24px auto; background: #fff; padding: 40px 44px; }

        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid var(--pos-accent); padding-bottom: 16px; margin-bottom: 20px; }
        .invoice-header img { max-height: 50px; margin-bottom: 6px; }
        .company-name { font-size: 20px; font-weight: 700; color: var(--pos-primary-dark); margin: 0 0 2px; }
        .company-meta { font-size: 12px; color: var(--pos-muted); margin: 0; line-height: 1.5; }

        .invoice-meta-box { text-align: right; font-size: 13px; }
        .invoice-pill { display: inline-block; background: var(--pos-accent); color: #fff; font-weight: 700; padding: 5px 16px; border-radius: 5px; margin-bottom: 8px; letter-spacing: 0.5px; }
        .invoice-meta-box p { margin: 2px 0; color: var(--pos-ink); }
        .invoice-meta-box .label { color: var(--pos-muted); }

        .bill-to-box { background: var(--pos-primary-light); border-radius: 8px; padding: 14px 18px; margin-bottom: 22px; font-size: 13px; }
        .bill-to-box .label { font-size: 11px; font-weight: 700; color: var(--pos-primary-dark); text-transform: uppercase; letter-spacing: 0.5px; }
        .bill-to-box .cust-name { font-size: 15px; font-weight: 700; margin: 3px 0; }
        .bill-to-box p { margin: 1px 0; color: #374151; }

        .details-box { border: 1px solid #eee; border-radius: 8px; overflow: hidden; margin-bottom: 24px; }
        .details-box .row { display: flex; justify-content: space-between; padding: 12px 16px; font-size: 13px; border-bottom: 1px solid #f3f4f6; }
        .details-box .row:last-child { border-bottom: none; }
        .details-box .row.remaining { background: var(--pos-danger); color: #fff; font-weight: 700; font-size: 15px; }

        .signature-section { display: flex; justify-content: space-between; margin-top: 70px; font-size: 12px; }
        .signature-line { border-top: 1px dashed #9CA3AF; width: 170px; text-align: center; padding-top: 6px; color: var(--pos-muted); }

        .invoice-footer { text-align: center; margin-top: 30px; padding-top: 14px; border-top: 1px solid #eee; font-size: 11px; color: var(--pos-muted); }

        @media print {
            body { background: #fff; }
            .no-print-bar { display: none !important; }
            #invoice-capture-area { margin: 0; padding: 0; }
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
                <div class="invoice-pill">প্রারম্ভিক বকেয়া</div>
                <p><span class="label">তারিখ:</span> {{ optional($customer->created_at)->format('d M, Y') ?? '-' }}</p>
            </div>
        </div>

        <div class="bill-to-box">
            <div class="label">কাস্টমার</div>
            <p class="cust-name">{{ $customer->name }}</p>
            @if($customer->phone)<p>মোবাইল: {{ $customer->phone }}</p>@endif
            @if($customer->address)<p>{{ $customer->address }}</p>@endif
        </div>

        <div class="details-box">
            <div class="row">
                <span>মোট প্রারম্ভিক বকেয়া (সফটওয়্যার ব্যবহারের আগের):</span>
                <span class="mono"><strong>{{ $settings->currency_symbol ?? '৳' }}{{ number_format($customer->opening_due, 2) }}</strong></span>
            </div>
            <div class="row">
                <span>এখন পর্যন্ত পরিশোধিত:</span>
                <span class="mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($customer->opening_due_paid, 2) }}</span>
            </div>
            <div class="row remaining">
                <span>অবশিষ্ট প্রারম্ভিক বকেয়া:</span>
                <span class="mono">{{ $settings->currency_symbol ?? '৳' }}{{ number_format($remaining, 2) }}</span>
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
        window.onload = function () { setTimeout(function () { window.print(); }, 500); };
    </script>
    @endif
</body>
</html>