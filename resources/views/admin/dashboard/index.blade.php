@extends('admin.layouts.main')

@section('title', 'ড্যাশবোর্ড')

@push('css')
<style>
    .kpi-card { border-radius: 14px; }
    .kpi-card .kpi-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
    }
    @media (max-width: 576px) {
        .kpi-card .fs-4 { font-size: 1.1rem !important; }
        .kpi-card .card-body { padding: 0.85rem !important; }
    }
    .chart-card canvas { max-height: 320px; }
    .list-card .list-row { padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
    .list-card .list-row:last-child { border-bottom: none; }
    .quick-action-btn { border-radius: 12px; padding: 14px 10px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 py-md-4">

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 mb-md-4">
        <div>
            <h4 class="fw-bold mb-1 fs-5 fs-sm-4">📊 ড্যাশবোর্ড</h4>
            <p class="text-muted small mb-0">আপনার ব্যবসার সার্বিক চিত্র — {{ now()->format('d M, Y') }}</p>
        </div>
    </div>

    <!-- কুইক অ্যাকশন -->
    <div class="row row-cols-2 row-cols-md-4 g-2 mb-3 mb-md-4">
        <div class="col">
            <a href="{{ route('pos.index') }}" class="btn btn-primary w-100 quick-action-btn fw-bold d-flex flex-column align-items-center gap-1">
                <i class="bi bi-cart-plus fs-4"></i> <span class="small">নতুন বিক্রি</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('customers.due_list') }}" class="btn btn-danger w-100 quick-action-btn fw-bold d-flex flex-column align-items-center gap-1">
                <i class="bi bi-cash-coin fs-4"></i> <span class="small">বকেয়া জমা নিন</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('customers.index') }}" class="btn btn-outline-dark w-100 quick-action-btn fw-bold d-flex flex-column align-items-center gap-1">
                <i class="bi bi-person-plus fs-4"></i> <span class="small">নতুন কাস্টমার</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('pos.sales.index') }}" class="btn btn-outline-secondary w-100 quick-action-btn fw-bold d-flex flex-column align-items-center gap-1">
                <i class="bi bi-receipt fs-4"></i> <span class="small">সব ইনভয়েস</span>
            </a>
        </div>
    </div>

    <!-- আজকের অবস্থা -->
    <h6 class="fw-bold text-muted text-uppercase small mb-2">আজকের অবস্থা</h6>
    <div class="row row-cols-2 row-cols-md-4 g-2 g-md-3 mb-3 mb-md-4">
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-semibold">আজকের বিক্রয়</div>
                        <div class="fs-4 fw-extrabold text-dark">৳{{ number_format($todaySales, 2) }}</div>
                    </div>
                    <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-graph-up"></i></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-semibold">আজকের আদায়</div>
                        <div class="fs-4 fw-extrabold text-success">৳{{ number_format($todayTotalCollected, 2) }}</div>
                    </div>
                    <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-wallet2"></i></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-semibold">আজকের নতুন বকেয়া</div>
                        <div class="fs-4 fw-extrabold text-danger">৳{{ number_format($todayDueCreated, 2) }}</div>
                    </div>
                    <div class="kpi-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-circle"></i></div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-semibold">আজকের ইনভয়েস</div>
                        <div class="fs-4 fw-extrabold text-dark">{{ number_format($todayInvoiceCount) }} টি</div>
                    </div>
                    <div class="kpi-icon bg-info-subtle text-info"><i class="bi bi-file-earmark-text"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- সামগ্রিক অবস্থা -->
    <h6 class="fw-bold text-muted text-uppercase small mb-2">সামগ্রিক ব্যবসার অবস্থা</h6>
    <div class="row row-cols-2 row-cols-md-4 g-2 g-md-3 mb-3 mb-md-4">
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm bg-danger text-white h-100">
                <div class="card-body p-3">
                    <div class="small opacity-75 fw-bold">মোট অবশিষ্ট বকেয়া</div>
                    <div class="fs-4 fw-extrabold mt-1">৳{{ number_format($totalDue, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm bg-dark text-white h-100">
                <div class="card-body p-3">
                    <div class="small opacity-75 fw-bold">বকেয়া থাকা কাস্টমার</div>
                    <div class="fs-4 fw-extrabold mt-1">{{ number_format($customersWithDue) }} জন</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm bg-primary text-white h-100">
                <div class="card-body p-3">
                    <div class="small opacity-75 fw-bold">মোট কাস্টমার</div>
                    <div class="fs-4 fw-extrabold mt-1">{{ number_format($totalCustomers) }} জন</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm bg-success text-white h-100">
                <div class="card-body p-3">
                    <div class="small opacity-75 fw-bold">আনুমানিক স্টক ভ্যালু</div>
                    <div class="fs-4 fw-extrabold mt-1">৳{{ number_format($stockValue, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- চার্ট -->
    <div class="row g-3 mb-3 mb-md-4">
        <div class="col-12 col-lg-8">
            <div class="card chart-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">গত ৩০ দিনের বিক্রয় ট্রেন্ড</h6>
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card chart-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">ক্যাটাগরি অনুযায়ী বিক্রয় (৩০ দিন)</h6>
                    @if($categorySales->count() > 0)
                        <canvas id="categoryChart"></canvas>
                    @else
                        <p class="text-muted text-center py-5 mb-0">এই সময়ে কোনো বিক্রয় নেই</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- সতর্কতা ও তালিকা -->
    <div class="row g-3 mb-3 mb-md-4">
        <div class="col-12 col-lg-6">
            <div class="card list-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-box-seam text-warning me-1"></i> কম স্টক থাকা পণ্য</h6>
                    @forelse($lowStockProducts as $p)
                        <div class="list-row d-flex justify-content-between align-items-center">
                            <span>{{ $p->name }}</span>
                            <span class="badge {{ $p->stock_in_grams <= 0 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                {{ $p->stock_in_grams >= 1000 ? number_format($p->stock_in_grams/1000, 1).' কেজি' : $p->stock_in_grams.' গ্রাম' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">সব পণ্যের স্টক পর্যাপ্ত আছে 🎉</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card list-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-circle text-danger me-1"></i> সর্বোচ্চ বকেয়া থাকা কাস্টমার</h6>
                    @forelse($topDueCustomers as $c)
                        <div class="list-row d-flex justify-content-between align-items-center">
                            <a href="{{ route('customers.statement', $c->id) }}" class="text-dark text-decoration-none">{{ $c->name }}</a>
                            <span class="fw-bold text-danger">৳{{ number_format($c->current_due, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">কোনো বকেয়া নেই 🎉</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3 mb-md-4">
        <div class="col-12 col-lg-6">
            <div class="card list-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-receipt text-primary me-1"></i> সাম্প্রতিক ইনভয়েস</h6>
                    @forelse($recentSales as $s)
                        <div class="list-row d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small">{{ $s->invoice_no }}</div>
                                <div class="text-muted" style="font-size:11px">{{ $s->customer->name ?? 'ক্যাশ' }} · {{ $s->sale_date->format('d M, h:i A') }}</div>
                            </div>
                            <span class="fw-bold">৳{{ number_format($s->net_amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">কোনো ইনভয়েস নেই</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card list-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-cash-coin text-success me-1"></i> সাম্প্রতিক জমা/পেমেন্ট</h6>
                    @forelse($recentPayments as $pmt)
                        <div class="list-row d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small">{{ $pmt->customer->name ?? '-' }}</div>
                                <div class="text-muted" style="font-size:11px">{{ $pmt->payment_method }} · {{ $pmt->payment_date->format('d M, h:i A') }}</div>
                            </div>
                            <span class="fw-bold text-success">৳{{ number_format($pmt->amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">কোনো পেমেন্ট নেই</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="card list-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-star text-warning me-1"></i> টপ সেলিং পণ্য (৩০ দিন)</h6>
                    @forelse($topProducts as $tp)
                        <div class="list-row d-flex justify-content-between align-items-center">
                            <span>{{ $tp->name }} <span class="text-muted small">({{ $tp->qty }}টি)</span></span>
                            <span class="fw-bold">৳{{ number_format($tp->revenue, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">এই সময়ে কোনো বিক্রয় নেই</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card list-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="bi bi-people text-info me-1"></i> স্টাফ পারফরম্যান্স (৩০ দিন)</h6>
                    @forelse($staffPerformance as $sp)
                        <div class="list-row d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold small">{{ $sp->user->name ?? 'অজানা' }}</div>
                                <div class="text-muted" style="font-size:11px">{{ $sp->invoice_count }}টি ইনভয়েস</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold small">৳{{ number_format($sp->total_sales, 2) }}</div>
                                <div class="text-success" style="font-size:11px">আদায়: ৳{{ number_format($sp->total_paid, 2) }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">এই সময়ে কোনো বিক্রয় নেই</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const trendData = @json($trend);
    const categoryData = @json($categorySales);

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendData.map(d => d.date),
            datasets: [
                { label: 'বিক্রয়', data: trendData.map(d => d.sales), borderColor: '#0F6F4C', backgroundColor: 'rgba(15,111,76,0.08)', tension: 0.3, fill: true },
                { label: 'আদায়', data: trendData.map(d => d.paid), borderColor: '#198754', backgroundColor: 'transparent', tension: 0.3 },
                { label: 'বকেয়া', data: trendData.map(d => d.due), borderColor: '#C6413A', backgroundColor: 'transparent', tension: 0.3 },
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: { y: { beginAtZero: true } }
        }
    });

    @if($categorySales->count() > 0)
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: categoryData.map(d => d.category),
            datasets: [{
                data: categoryData.map(d => d.total),
                backgroundColor: ['#0F6F4C', '#D98E2B', '#0d6efd', '#C6413A', '#6f42c1', '#20c997', '#fd7e14', '#6c757d']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    @endif
</script>
@endpush