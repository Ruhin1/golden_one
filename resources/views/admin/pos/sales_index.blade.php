@extends('admin.layouts.main')

@section('title', 'বিক্রির ইতিহাস ও ইনভয়েসসমূহ')

@push('css')
<style>
    .due-toggle-btn {
        border-radius: 50px !important;
        font-weight: 600;
        padding: 0.5rem 1.1rem;
    }
    .due-toggle-btn .badge {
        font-weight: 700;
    }

    /* ⬇️ মোবাইল রেসপন্সিভনেস */
    @media (max-width: 576px) {
        .summary-card .fs-4 { font-size: 1.15rem !important; }
        .summary-card .card-body { padding: 0.65rem 0.75rem; }
    }

    .sale-card {
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 14px;
        background: #fff;
    }
    .sale-card + .sale-card { margin-top: 10px; }
    .sale-card .amounts-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 6px;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 8px 10px;
        margin: 10px 0;
        text-align: center;
    }
    .sale-card .amounts-grid .label { font-size: 10px; color: #6b7280; font-weight: 600; }
    .sale-card .amounts-grid .value { font-size: 13px; font-weight: 700; }
    .sale-card .action-row .btn { flex: 1; }

    #actionModal .action-btn {
        padding: 16px 8px;
        border-radius: 12px;
    }
    #actionModal .action-btn i { font-size: 1.5rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 py-md-4" x-data="salesHistoryPage()">

    <!-- ১. হেডার ও দ্রুত অ্যাকশন -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 mb-md-4">
        <div>
            <h4 class="fw-bold mb-1 fs-5 fs-sm-4">📋 বিক্রির ইতিহাস ও ইনভয়েসসমূহ</h4>
            <p class="text-muted small mb-0">সকল বিক্রির তথ্য, ভাউচার প্রিন্ট, সম্পাদনা ও হিসাব চেক করুন</p>
        </div>
        <a href="{{ route('pos.index') }}" class="btn btn-primary fw-bold shadow-sm w-100 w-sm-auto">
            <i class="bi bi-cart-plus me-1"></i> নতুন বিক্রি (POS)
        </a>
    </div>

    <!-- ২. সমারি কার্ডস (মোবাইলে ২ কলাম, বড় স্ক্রিনে ৩/৫ কলাম) -->
    <div class="row row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-xl-5 g-2 g-md-3 mb-3 mb-md-4">
        <div class="col">
            <div class="card summary-card border-0 shadow-sm bg-primary text-white h-100">
                <div class="card-body p-3">
                    <div class="small opacity-75 fw-bold">মোট ইনভয়েস</div>
                    <div class="fs-4 fw-extrabold mt-1">{{ number_format($summary['total_count'] ?? 0) }} টি</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card summary-card border-0 shadow-sm bg-dark text-white h-100">
                <div class="card-body p-3">
                    <div class="small opacity-75 fw-bold">মোট বিক্রয় মূল্য</div>
                    <div class="fs-4 fw-extrabold mt-1">৳{{ number_format($summary['total_sales'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card summary-card border-0 shadow-sm bg-success text-white h-100">
                <div class="card-body p-3">
                    <div class="small opacity-75 fw-bold">মোট আদায় (নগদ)</div>
                    <div class="fs-4 fw-extrabold mt-1">৳{{ number_format($summary['total_paid'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card summary-card border-0 shadow-sm bg-danger text-white h-100">
                <div class="card-body p-3">
                    <div class="small opacity-75 fw-bold">মোট বকেয়া</div>
                    <div class="fs-4 fw-extrabold mt-1">৳{{ number_format($summary['total_due'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card summary-card border-0 shadow-sm bg-warning text-dark h-100">
                <div class="card-body p-3">
                    <div class="small opacity-75 fw-bold">মোট ছাড় (Discount)</div>
                    <div class="fs-4 fw-extrabold mt-1">৳{{ number_format($summary['total_discount'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ৩. বকেয়া/পরিশোধিত টগল বাটন -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small fw-bold me-1 w-100 w-sm-auto mb-1 mb-sm-0">দেখান:</span>

            @php
                $dueUrl  = request()->fullUrlWithQuery(['due_filter' => 'due', 'page' => 1]);
                $paidUrl = request()->fullUrlWithQuery(['due_filter' => 'paid', 'page' => 1]);
                $allUrl  = request()->fullUrlWithQuery(['due_filter' => 'all', 'page' => 1]);
            @endphp

            <a href="{{ $dueUrl }}" class="btn due-toggle-btn {{ $dueFilter === 'due' ? 'btn-danger' : 'btn-outline-danger' }}">
                <i class="bi bi-exclamation-circle me-1"></i> বকেয়া সেল
                <span class="badge {{ $dueFilter === 'due' ? 'bg-white text-danger' : 'bg-danger-subtle text-danger' }} ms-1">{{ number_format($dueCount) }}</span>
            </a>

            <a href="{{ $paidUrl }}" class="btn due-toggle-btn {{ $dueFilter === 'paid' ? 'btn-success' : 'btn-outline-success' }}">
                <i class="bi bi-check-circle me-1"></i> সম্পূর্ণ পরিশোধিত
                <span class="badge {{ $dueFilter === 'paid' ? 'bg-white text-success' : 'bg-success-subtle text-success' }} ms-1">{{ number_format($paidCount) }}</span>
            </a>

            <a href="{{ $allUrl }}" class="btn due-toggle-btn {{ $dueFilter === 'all' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                <i class="bi bi-list-ul me-1"></i> সকল
                <span class="badge {{ $dueFilter === 'all' ? 'bg-white text-secondary' : 'bg-secondary-subtle text-secondary' }} ms-1">{{ number_format($allCount) }}</span>
            </a>
        </div>
    </div>

    <!-- ৪. সার্চ ও ফিল্টার বার -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('pos.sales.index') }}" class="row g-2 align-items-center">
                <input type="hidden" name="due_filter" value="{{ $dueFilter }}">

                <div class="col-12 col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="ইনভয়েস বা কাস্টমার ফোন দিয়ে খুঁজুন...">
                </div>
                <div class="col-6 col-md-2">
                    <select name="customer_id" class="form-select">
                        <option value="">-- সকল কাস্টমার --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->phone }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select">
                        <option value="">-- সকল স্ট্যাটাস --</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>পরিশোধিত (Paid)</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>আংশিক (Partial)</option>
                        <option value="due" {{ request('status') == 'due' ? 'selected' : '' }}>বকেয়া (Due)</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control" title="শুরুর তারিখ" placeholder="শুরুর তারিখ">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control" title="শেষের তারিখ" placeholder="শেষের তারিখ">
                </div>
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-secondary flex-fill px-2" title="খুঁজুন"><i class="bi bi-search"></i> <span class="d-md-none">খুঁজুন</span></button>
                    <a href="{{ route('pos.sales.index') }}" class="btn btn-outline-secondary flex-fill px-2 text-center" title="রিসেট"><i class="bi bi-arrow-counterclockwise"></i> <span class="d-md-none">রিসেট</span></a>
                </div>
            </form>

            @if(request()->filled('start_date') || request()->filled('end_date'))
                <div class="mt-2 small text-muted">
                    <i class="bi bi-funnel-fill me-1"></i>
                    তারিখ ফিল্টার সক্রিয়:
                    @if(request('start_date')) {{ \Carbon\Carbon::parse(request('start_date'))->format('d M, Y') }} @endif
                    @if(request('start_date') && request('end_date')) থেকে @endif
                    @if(request('end_date')) {{ \Carbon\Carbon::parse(request('end_date'))->format('d M, Y') }} @endif
                    পর্যন্ত —
                    <a href="{{ request()->fullUrlWithQuery(['start_date' => null, 'end_date' => null, 'page' => 1]) }}" class="text-danger">তারিখ ফিল্টার সরান</a>
                </div>
            @endif
        </div>
    </div>

    <!-- ৫. বিক্রির তালিকা — ডেস্কটপে টেবিল, মোবাইলে কার্ড-লিস্ট -->

    <!-- ৫ক. ডেস্কটপ/ট্যাবলেট টেবিল ভিউ (lg ও তার ওপরে) -->
    <div class="card border-0 shadow-sm d-none d-lg-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">তারিখ ও সময়</th>
                            <th>ইনভয়েস নং</th>
                            <th>কাস্টমার</th>
                            <th class="text-center">আইটেম</th>
                            <th class="text-end">মোট মূল্য</th>
                            <th class="text-end">আদায়</th>
                            <th class="text-end">বকেয়া</th>
                            <th class="text-center">স্ট্যাটাস</th>
                            <th class="text-center pe-3">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr>
                            <td class="ps-3">
                                <span class="fw-bold d-block">{{ $sale->sale_date->format('d M, Y') }}</span>
                                <small class="text-muted">{{ $sale->sale_date->format('h:i A') }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace fs-6">{{ $sale->invoice_no }}</span>
                            </td>
                            <td>
                                @if($sale->customer)
                                    <div class="fw-bold">{{ $sale->customer->name }}</div>
                                    <small class="text-muted">{{ $sale->customer->phone }}</small>
                                @else
                                    <span class="text-muted">কাস্টমার ডিলিট করা হয়েছে</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-info text-dark">{{ $sale->items->count() }} টি</span>
                            </td>
                            <td class="text-end fw-bold">৳{{ number_format($sale->net_amount, 2) }}</td>
                            <td class="text-end text-success fw-bold">৳{{ number_format($sale->paid_amount, 2) }}</td>
                            <td class="text-end text-danger fw-bold">
                                {{ $sale->due_amount > 0 ? '৳'.number_format($sale->due_amount, 2) : '-' }}
                            </td>
                            <td class="text-center">
                                @if($sale->status == 'paid')
                                    <span class="badge bg-success">পরিশোধিত</span>
                                @elseif($sale->status == 'partial')
                                    <span class="badge bg-warning text-dark">আংশিক</span>
                                @else
                                    <span class="badge bg-danger">বকেয়া</span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-dark" @click="openInvoiceActions({{ $sale->id }}, '{{ $sale->invoice_no }}', {{ $sale->net_amount }}, {{ $sale->due_amount }})" title="ইনভয়েস অ্যাকশন (প্রিন্ট/শেয়ার)">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                    <a href="{{ route('pos.sales.edit-data', $sale->id) }}" class="btn btn-sm btn-outline-primary" title="সম্পাদনা (Edit)">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-outline-danger" @click="confirmDelete({{ $sale->id }}, '{{ $sale->invoice_no }}')" title="মুছে ফেলুন">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                @if($dueFilter === 'due')
                                    কোনো বকেয়া সেল পাওয়া যায়নি — সব হিসাব ক্লিয়ার! 🎉
                                @elseif($dueFilter === 'paid')
                                    সম্পূর্ণ পরিশোধিত কোনো সেল পাওয়া যায়নি।
                                @else
                                    কোনো বিক্রির তথ্য পাওয়া যায়নি!
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sales->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $sales->links() }}
        </div>
        @endif
    </div>

    <!-- ৫খ. মোবাইল/ট্যাবলেট কার্ড ভিউ (lg এর নিচে) -->
    <div class="d-lg-none">
        @forelse($sales as $sale)
        <div class="sale-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge bg-light text-dark border font-monospace">{{ $sale->invoice_no }}</span>
                    <div class="small text-muted mt-1">
                        {{ $sale->sale_date->format('d M, Y') }} · {{ $sale->sale_date->format('h:i A') }}
                    </div>
                </div>
                @if($sale->status == 'paid')
                    <span class="badge bg-success">পরিশোধিত</span>
                @elseif($sale->status == 'partial')
                    <span class="badge bg-warning text-dark">আংশিক</span>
                @else
                    <span class="badge bg-danger">বকেয়া</span>
                @endif
            </div>

            <div class="mt-2">
                @if($sale->customer)
                    <div class="fw-bold">{{ $sale->customer->name }}</div>
                    <div class="small text-muted">{{ $sale->customer->phone }} · {{ $sale->items->count() }} টি আইটেম</div>
                @else
                    <div class="text-muted">কাস্টমার ডিলিট করা হয়েছে</div>
                @endif
            </div>

            <div class="amounts-grid">
                <div>
                    <div class="label">মোট মূল্য</div>
                    <div class="value">৳{{ number_format($sale->net_amount, 2) }}</div>
                </div>
                <div>
                    <div class="label">আদায়</div>
                    <div class="value text-success">৳{{ number_format($sale->paid_amount, 2) }}</div>
                </div>
                <div>
                    <div class="label">বকেয়া</div>
                    <div class="value text-danger">{{ $sale->due_amount > 0 ? '৳'.number_format($sale->due_amount, 2) : '-' }}</div>
                </div>
            </div>

            <div class="d-flex gap-2 action-row">
                <button class="btn btn-outline-dark btn-sm" @click="openInvoiceActions({{ $sale->id }}, '{{ $sale->invoice_no }}', {{ $sale->net_amount }}, {{ $sale->due_amount }})" title="ইনভয়েস অ্যাকশন">
                    <i class="bi bi-printer"></i> প্রিন্ট
                </button>
                <a href="{{ route('pos.sales.edit-data', $sale->id) }}" class="btn btn-outline-primary btn-sm" title="সম্পাদনা">
                    <i class="bi bi-pencil"></i> এডিট
                </a>
                <button class="btn btn-outline-danger btn-sm" @click="confirmDelete({{ $sale->id }}, '{{ $sale->invoice_no }}')" title="মুছে ফেলুন">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted bg-white rounded-3 shadow-sm">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            @if($dueFilter === 'due')
                কোনো বকেয়া সেল পাওয়া যায়নি — সব হিসাব ক্লিয়ার! 🎉
            @elseif($dueFilter === 'paid')
                সম্পূর্ণ পরিশোধিত কোনো সেল পাওয়া যায়নি।
            @else
                কোনো বিক্রির তথ্য পাওয়া যায়নি!
            @endif
        </div>
        @endforelse

        @if($sales->hasPages())
        <div class="mt-3 d-flex justify-content-center">
            {{ $sales->links() }}
        </div>
        @endif
    </div>

    <!-- ৬. ইনভয়েস অ্যাকশন মডাল — প্রিন্টার বাটনে ক্লিক করলে এখন এটাই খোলে -->
    <div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">ইনভয়েস অ্যাকশন</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pt-2 pb-4">
                    <div class="badge bg-dark font-monospace fs-6 mb-3" x-text="selectedInvoiceNo"></div>

                    <div class="row row-cols-2 g-2">
                        <div class="col">
                            <button type="button" class="btn btn-outline-primary w-100 action-btn d-flex flex-column align-items-center gap-1" @click="printA4Invoice()">
                                <i class="bi bi-file-earmark-pdf"></i>
                                <span class="small fw-bold">A4 প্রিন্ট</span>
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-outline-success w-100 action-btn d-flex flex-column align-items-center gap-1" :disabled="isGeneratingShare" @click="shareOnWhatsApp()">
                                <i class="bi bi-whatsapp" x-show="!isGeneratingShare"></i>
                                <span class="spinner-border spinner-border-sm" x-show="isGeneratingShare"></span>
                                <span class="small fw-bold" x-text="isGeneratingShare ? 'তৈরি হচ্ছে...' : 'WhatsApp Share'"></span>
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-outline-dark w-100 action-btn d-flex flex-column align-items-center gap-1" @click="openPosReceipt()">
                                <i class="bi bi-printer"></i>
                                <span class="small fw-bold">পস প্রিন্ট</span>
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-outline-secondary w-100 action-btn d-flex flex-column align-items-center gap-1" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle"></i>
                                <span class="small fw-bold">বাতিল</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('js')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    function salesHistoryPage() {
        return {
            customers: @json($customers ?? []),
            allProducts: @json($products ?? []),

            // ইনভয়েস অ্যাকশন মডালের জন্য
            selectedSaleId: null,
            selectedInvoiceNo: '',
            selectedSaleNetAmount: 0,
            selectedSaleDueAmount: 0,
            isGeneratingShare: false,

            // ================================================================
            // 📱 নেটিভ (Flutter WebView) অ্যাপ ডিটেকশন ও হেল্পার
            // POS পেজের posSystem()-এর সাথে হুবহু মিলিয়ে রাখা হলো
            // ================================================================
            get isNativeApp() {
                return typeof window.flutter_inappwebview !== 'undefined';
            },

            blobToBase64(blob) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
            },

            // প্রিন্টার বাটনে ক্লিক করলে অ্যাকশন-বেছে-নিন মডাল খোলে
            openInvoiceActions(saleId, invoiceNo, netAmount, dueAmount) {
                this.selectedSaleId = saleId;
                this.selectedInvoiceNo = invoiceNo;
                this.selectedSaleNetAmount = netAmount;
                this.selectedSaleDueAmount = dueAmount;
                const modalEl = document.getElementById('actionModal');
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            },

            // ================================================================
            // 🖨️ A4 ইনভয়েস প্রিন্ট
            // নেটিভ অ্যাপে থাকলে: PDF blob বানিয়ে Flutter bridge এ পাঠায়
            // ব্রাউজারে থাকলে: নতুন ট্যাবে ইনভয়েস পেজ খোলে
            // ================================================================
            async printA4Invoice() {
                if (!this.selectedSaleId) return;

                if (this.isNativeApp) {
                    try {
                        const pdfBlob = await this.generateInvoicePdfBlob(this.selectedSaleId);
                        const base64 = await this.blobToBase64(pdfBlob);
                        const fileName = `Invoice-${this.selectedInvoiceNo}.pdf`;

                        const result = await window.flutter_inappwebview.callHandler('printInvoice', base64, fileName);

                        if (result && result.success === false) {
                            throw new Error(result.message || 'প্রিন্ট করা সম্ভব হয়নি');
                        }
                    } catch (e) {
                        console.error(e);
                        Swal.fire({
                            icon: 'error',
                            title: 'প্রিন্ট করা সম্ভব হয়নি',
                            text: 'ইনভয়েস প্রিন্ট করতে সমস্যা হয়েছে। আবার চেষ্টা করুন।',
                            confirmButtonColor: '#C6413A'
                        });
                    }
                } else {
                    window.open(`/admin/sales/${this.selectedSaleId}/invoice`, '_blank');
                }
            },

            // ================================================================
            // 📤 WhatsApp / নেটিভ শেয়ার
            // নেটিভ অ্যাপে থাকলে: Flutter bridge দিয়ে সরাসরি নেটিভ শেয়ার শিট
            // ব্রাউজারে থাকলে: Web Share API → না থাকলে ডাউনলোড + wa.me ফলব্যাক
            // ================================================================
            async shareOnWhatsApp() {
                if (!this.selectedSaleId || this.isGeneratingShare) return;
                this.isGeneratingShare = true;

                try {
                    const pdfBlob = await this.generateInvoicePdfBlob(this.selectedSaleId);
                    const fileName = `Invoice-${this.selectedInvoiceNo}.pdf`;

                    if (this.isNativeApp) {
                        const base64 = await this.blobToBase64(pdfBlob);
                        const result = await window.flutter_inappwebview.callHandler(
                            'shareInvoice',
                            base64,
                            fileName,
                            'ইনভয়েস ' + this.selectedInvoiceNo
                        );

                        if (result && result.success === false) {
                            throw new Error(result.message || 'শেয়ার করা সম্ভব হয়নি');
                        }
                    } else if (navigator.canShare && navigator.canShare({ files: [new File([pdfBlob], fileName)] })) {
                        const file = new File([pdfBlob], fileName, { type: 'application/pdf' });
                        await navigator.share({
                            files: [file],
                            title: 'ইনভয়েস ' + this.selectedInvoiceNo,
                            text: 'ইনভয়েস ' + this.selectedInvoiceNo
                        });
                    } else {
                        const url = URL.createObjectURL(pdfBlob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = fileName;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);

                        await Swal.fire({
                            icon: 'info',
                            title: 'PDF ডাউনলোড হয়েছে',
                            text: 'আপনার ব্রাউজারে সরাসরি শেয়ার সমর্থিত নয় — WhatsApp খুলছে, ডাউনলোড হওয়া PDF ফাইলটা ম্যানুয়ালি অ্যাটাচ করে পাঠান।',
                            confirmButtonColor: '#0d6efd'
                        });
                        window.open('https://wa.me/', '_blank');
                    }
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        console.error(e);
                        Swal.fire('ত্রুটি!', 'রসিদের PDF তৈরি বা শেয়ার করা সম্ভব হয়নি।', 'error');
                    }
                } finally {
                    this.isGeneratingShare = false;
                }
            },

            // "পস প্রিন্ট" — নেটিভ অ্যাপে Bluetooth প্রিন্টার স্ক্রিন খোলে, ব্রাউজারে জানিয়ে দেয়
            // এটা শুধু অ্যাপ থেকেই সম্ভব
            openPosReceipt() {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('actionModal')).hide();

                if (this.isNativeApp) {
                    window.flutter_inappwebview.callHandler('openBluetoothPrinterScreen', {
                        invoice_no: this.selectedInvoiceNo,
                        grand_total: this.selectedSaleNetAmount,
                        due_amount: this.selectedSaleDueAmount,
                    });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'শুধু মোবাইল অ্যাপে সম্ভব',
                        text: 'পস (থার্মাল) প্রিন্ট শুধুমাত্র মোবাইল অ্যাপ থেকে Bluetooth প্রিন্টার দিয়ে করা যাবে।',
                        confirmButtonColor: '#0F6F4C'
                    });
                }
            },

            // ইনভয়েস পেজটা একটা লুকানো iframe-এ (?embed=1) লোড করে html2canvas দিয়ে
            // ক্যাপচার করে, তারপর jsPDF দিয়ে PDF বানায় — A4 প্রিন্ট (নেটিভ) ও
            // WhatsApp শেয়ার দুই জায়গাতেই ব্যবহার হয়
            async generateInvoicePdfBlob(saleId) {
                await this.ensurePdfLibsLoaded();

                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.left = '-9999px';
                iframe.style.top = '0';
                iframe.style.width = '800px';
                iframe.style.height = '1200px';
                document.body.appendChild(iframe);

                try {
                    await new Promise((resolve, reject) => {
                        iframe.onload = resolve;
                        iframe.onerror = reject;
                        iframe.src = `/admin/sales/${saleId}/invoice?embed=1`;
                    });

                    await new Promise(r => setTimeout(r, 500));

                    const target = iframe.contentDocument.getElementById('invoice-capture-area');
                    const canvas = await html2canvas(target, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });

                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF('p', 'pt', 'a4');
                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = (canvas.height * pageWidth) / canvas.width;
                    pdf.addImage(canvas.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, pageWidth, pageHeight);

                    return pdf.output('blob');
                } finally {
                    document.body.removeChild(iframe);
                }
            },

            ensurePdfLibsLoaded() {
                const loadScript = (src) => new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = src;
                    s.onload = resolve;
                    s.onerror = reject;
                    document.head.appendChild(s);
                });

                const tasks = [];
                if (!window.html2canvas) {
                    tasks.push(loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'));
                }
                if (!window.jspdf) {
                    tasks.push(loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'));
                }
                return Promise.all(tasks);
            },

            confirmDelete(saleId, invoiceNo) {
                Swal.fire({
                    title: 'আপনি কি নিশ্চিত?',
                    text: `ইনভয়েস (${invoiceNo}) ডিলিট করলে স্টকের পণ্য বৃদ্ধি পাবে এবং কাস্টমারের বকেয়া কমে যাবে!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'হ্যাঁ, ডিলিট করুন!',
                    cancelButtonText: 'বাতিল'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const response = await fetch(`/admin/sales/${saleId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            });
                            const res = await response.json();

                            if (res.success) {
                                Swal.fire('ডিলিট সফল!', res.message, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('ত্রুটি!', res.message, 'error');
                            }
                        } catch (e) {
                            Swal.fire('ত্রুটি!', 'মুছে ফেলা সম্ভব হয়নি', 'error');
                        }
                    }
                });
            }
        }
    }
</script>
@endpush 