@extends('admin.layouts.main')

@section('title', $customer->name . ' - বিবরণী ড্যাশবোর্ড')

@push('css')
<style>
    @media (max-width: 576px) {
        .kpi-card .fw-bold { font-size: 1.1rem !important; }
        .kpi-card { padding: 0.75rem !important; }
    }

    .ledger-card {
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 14px;
        background: #fff;
    }
    .ledger-card + .ledger-card { margin-top: 10px; }
    .ledger-card.opening { background: #fff8e6; border-color: #fde68a; }

    #rowActionModal .action-btn { padding: 16px 8px; border-radius: 12px; }
    #rowActionModal .action-btn i { font-size: 1.5rem; }

    .btn-whatsapp {
        background: #25D366;
        color: #fff;
        border: none;
    }
    .btn-whatsapp:hover { background: #1EBE5A; color: #fff; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3" x-data="statementPage()">

    <!-- Header Actions -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark fs-5 fs-lg-4">{{ $customer->name }}</h4>
            <span class="text-muted small">মোবাইল: {{ $customer->phone }} | ঠিকানা: {{ $customer->address ?? 'N/A' }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-success fw-semibold flex-fill flex-lg-grow-0" data-bs-toggle="modal" data-bs-target="#paymentModal">
                ➕ টাকা জমা নিন
            </button>
            <button type="button" class="btn btn-warning fw-semibold text-dark flex-fill flex-lg-grow-0" onclick="alert('পস প্রিন্ট ফিচারটি শীঘ্রই যুক্ত করা হচ্ছে।')">
                📱 পস প্রিন্ট
            </button>
            <button type="button" class="btn btn-primary fw-semibold flex-fill flex-lg-grow-0" :disabled="isGeneratingStatementPrint" @click="printStatementA4()">
                <span x-show="!isGeneratingStatementPrint">🖨️ স্টেটমেন্ট প্রিন্ট A4 (PDF)</span>
                <span x-show="isGeneratingStatementPrint"><span class="spinner-border spinner-border-sm me-1"></span> তৈরি হচ্ছে...</span>
            </button>
            <button type="button" class="btn btn-whatsapp fw-semibold flex-fill flex-lg-grow-0" :disabled="isGeneratingStatementShare" @click="shareStatementOnWhatsApp()">
                <span x-show="!isGeneratingStatementShare">💬 স্টেটমেন্ট শেয়ার WhatsApp</span>
                <span x-show="isGeneratingStatementShare"><span class="spinner-border spinner-border-sm me-1"></span> তৈরি হচ্ছে...</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Summary KPI Cards -->
    <div class="row row-cols-2 row-cols-md-4 g-2 g-md-3 mb-4">
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm bg-white border-start border-4 border-primary p-3 h-100">
                <span class="text-muted small fw-semibold">সর্বমোট বিক্রয়</span>
                <h4 class="fw-bold text-primary mb-0">৳ {{ number_format($stats['total_sales'], 2) }}</h4>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm bg-white border-start border-4 border-success p-3 h-100">
                <span class="text-muted small fw-semibold">সর্বমোট জমা</span>
                <h4 class="fw-bold text-success mb-0">৳ {{ number_format($stats['total_paid'], 2) }}</h4>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm bg-white border-start border-4 border-warning p-3 h-100">
                <span class="text-muted small fw-semibold">ওপেনিং বকেয়া</span>
                <h4 class="fw-bold text-warning mb-0">৳ {{ number_format($stats['opening_due'], 2) }}</h4>
            </div>
        </div>
        <div class="col">
            <div class="card kpi-card border-0 shadow-sm bg-white border-start border-4 border-danger p-3 h-100">
                <span class="text-muted small fw-semibold">বর্তমান অবশিষ্ট বকেয়া</span>
                <h4 class="fw-bold text-danger mb-0">৳ {{ number_format($stats['current_due'], 2) }}</h4>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm p-3 mb-4">
        <form method="GET" action="{{ route('customers.statement', $customer->id) }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">শুরুর তারিখ</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold">শেষের তারিখ</label>
                <input type="date" name="to_date" value="{{ $toDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold">লেনদেনের ধরন</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="all" {{ $filterType === 'all' ? 'selected' : '' }}>সকল লেনদেন (All)</option>
                    <option value="invoice" {{ $filterType === 'invoice' ? 'selected' : '' }}>শুধু ইনভয়েস (Invoices)</option>
                    <option value="payment" {{ $filterType === 'payment' ? 'selected' : '' }}>শুধু জমা (Payments)</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-semibold">🔍 ফিল্টার করুন</button>
                <a href="{{ route('customers.statement', $customer->id) }}" class="btn btn-sm btn-outline-secondary">রিসেট</a>
            </div>
        </form>
    </div>

    <!-- ডেস্কটপ টেবিল ভিউ (lg ও তার ওপরে) -->
    <div class="card border-0 shadow-sm d-none d-lg-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">তারিখ</th>
                            <th>রেফারেন্স / বিবরণ</th>
                            <th class="text-center">টাইপ</th>
                            <th class="text-end">বিল/দেনা (৳)</th>
                            <th class="text-end">জমা (৳)</th>
                            <th class="text-end">অবশিষ্ট (৳)</th>
                            <th class="text-center">প্রিন্ট</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledger as $item)
                            <tr class="{{ $item->transaction_type === 'opening_due' ? 'table-warning' : '' }}">
                                <td class="text-center">
                                    {{ $item->date_time ? date('d-m-Y', strtotime($item->date_time)) : '-' }}
                                </td>
                                <td>
                                    @if($item->transaction_type === 'opening_due')
                                        <strong>প্রারম্ভিক বকেয়া (Opening Due)</strong>
                                    @elseif($item->transaction_type === 'invoice')
                                        <div><strong>ইনভয়েস #{{ $item->ref_no }}</strong></div>
                                        @if(isset($item->total_amount) && $item->total_amount > 0)
                                            <div class="small text-muted mt-1">
                                                <span class="badge bg-light text-dark border me-1">মোট: ৳ {{ number_format($item->total_amount, 2) }}</span>
                                                @if(!empty($item->discount) && $item->discount > 0)
                                                    <span class="badge bg-light text-danger border me-1">ছাড়: ৳ {{ number_format($item->discount, 2) }}</span>
                                                @endif
                                                @if(!empty($item->instant_paid) && $item->instant_paid > 0)
                                                    <span class="badge bg-light text-success border me-1">জমা: ৳ {{ number_format($item->instant_paid, 2) }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-success fw-semibold">বকেয়া পরিশোধ ({{ $item->note ?? 'Cash' }}) - #{{ $item->ref_no }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->transaction_type === 'opening_due')
                                        <span class="badge bg-warning text-dark">Opening</span>
                                    @elseif($item->transaction_type === 'invoice')
                                        <span class="badge bg-primary">Invoice</span>
                                    @else
                                        <span class="badge bg-success">Payment</span>
                                    @endif
                                </td>
                                <td class="text-end text-danger fw-semibold">{{ $item->debit > 0 ? number_format($item->debit, 2) : '-' }}</td>
                                <td class="text-end text-success fw-semibold">{{ $item->credit > 0 ? number_format($item->credit, 2) : '-' }}</td>
                                <td class="text-end fw-bold">{{ number_format($item->balance, 2) }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-dark"
                                            @click="openRowActions('{{ $item->transaction_type }}', {{ $item->id ?? 'null' }}, '{{ $item->ref_no }}', {{ (float)($item->debit ?: $item->credit) }})"
                                            title="প্রিন্ট/শেয়ার">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">কোনো বিবরণ পাওয়া যায়নি।</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- মোবাইল কার্ড ভিউ (lg এর নিচে) -->
    <div class="d-lg-none">
        @forelse($ledger as $item)
        <div class="ledger-card {{ $item->transaction_type === 'opening_due' ? 'opening' : '' }}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    @if($item->transaction_type === 'opening_due')
                        <span class="badge bg-warning text-dark">Opening</span>
                    @elseif($item->transaction_type === 'invoice')
                        <span class="badge bg-primary">Invoice #{{ $item->ref_no }}</span>
                    @else
                        <span class="badge bg-success">Payment #{{ $item->ref_no }}</span>
                    @endif
                    <div class="small text-muted mt-1">{{ $item->date_time ? date('d M, Y', strtotime($item->date_time)) : '-' }}</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark"
                        @click="openRowActions('{{ $item->transaction_type }}', {{ $item->id ?? 'null' }}, '{{ $item->ref_no }}', {{ (float)($item->debit ?: $item->credit) }})">
                    <i class="bi bi-printer"></i>
                </button>
            </div>

            <div class="mt-2 small">
                @if($item->transaction_type === 'opening_due')
                    প্রারম্ভিক বকেয়া
                @elseif($item->transaction_type === 'invoice')
                    @if(!empty($item->instant_paid) && $item->instant_paid > 0)
                        তাৎক্ষণিক জমা: ৳{{ number_format($item->instant_paid, 2) }}
                    @endif
                @else
                    {{ $item->note ?? 'Cash' }}
                @endif
            </div>

            <div class="d-flex justify-content-between mt-2 pt-2 border-top small">
                <div>দেনা: <span class="text-danger fw-bold">{{ $item->debit > 0 ? '৳'.number_format($item->debit, 2) : '-' }}</span></div>
                <div>জমা: <span class="text-success fw-bold">{{ $item->credit > 0 ? '৳'.number_format($item->credit, 2) : '-' }}</span></div>
                <div>অবশিষ্ট: <span class="fw-bold">৳{{ number_format($item->balance, 2) }}</span></div>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted bg-white rounded-3 shadow-sm">কোনো বিবরণ পাওয়া যায়নি।</div>
        @endforelse
    </div>

    <!-- প্রতি-লেনদেন প্রিন্ট/শেয়ার অ্যাকশন মডাল -->
    <div class="modal fade" id="rowActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">প্রিন্ট/শেয়ার অ্যাকশন</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pt-2 pb-4">
                    <div class="badge bg-dark font-monospace fs-6 mb-3" x-text="selectedRefNo"></div>

                    <div class="row row-cols-2 g-2">
                        <div class="col">
                            <button type="button" class="btn btn-outline-primary w-100 action-btn d-flex flex-column align-items-center gap-1" @click="printRowA4()">
                                <i class="bi bi-file-earmark-pdf"></i>
                                <span class="small fw-bold">A4 প্রিন্ট</span>
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-outline-success w-100 action-btn d-flex flex-column align-items-center gap-1" :disabled="isGeneratingRowShare" @click="shareRowOnWhatsApp()">
                                <i class="bi bi-whatsapp" x-show="!isGeneratingRowShare"></i>
                                <span class="spinner-border spinner-border-sm" x-show="isGeneratingRowShare"></span>
                                <span class="small fw-bold" x-text="isGeneratingRowShare ? 'তৈরি হচ্ছে...' : 'WhatsApp Share'"></span>
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-outline-dark w-100 action-btn d-flex flex-column align-items-center gap-1" @click="openRowPosReceipt()">
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

<!-- বকেয়া জমার মডাল -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">বকেয়া জমা নিন</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('customer.payments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">বর্তমান মোট বকেয়া</label>
                        <input type="text" class="form-control bg-light text-danger fw-bold" value="৳ {{ number_format($stats['current_due'], 2) }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">জমার পরিমাণ (৳)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required max="{{ $stats['current_due'] }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">পেমেন্ট মেথড</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
                            <option value="Bank">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">জমার তারিখ</label>
                        <input type="datetime-local" name="payment_date" value="{{ date('Y-m-d\TH:i') }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">নোট</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
                    <button type="submit" class="btn btn-success">💾 জমা কনফার্ম করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    function statementPage() {
        return {
            customerId: {{ $customer->id }},
            filterType: @json($filterType),
            fromDate: @json($fromDate),
            toDate: @json($toDate),

            isGeneratingStatementPrint: false,
            isGeneratingStatementShare: false,
            isGeneratingRowShare: false,

            selectedType: null,
            selectedId: null,
            selectedRefNo: '',
            selectedAmount: 0,

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

            statementPrintUrl() {
                const params = new URLSearchParams({
                    type: this.filterType || 'all',
                    from_date: this.fromDate || '',
                    to_date: this.toDate || ''
                });
                return `{{ route('customers.statement.print', $customer->id) }}?${params.toString()}`;
            },

            // "স্টেটমেন্ট প্রিন্ট A4 (PDF)" — ব্রাউজারে নতুন ট্যাব, নেটিভ অ্যাপে bridge প্রিন্ট
            async printStatementA4() {
                if (this.isGeneratingStatementPrint) return;

                if (!this.isNativeApp) {
                    window.open(this.statementPrintUrl(), '_blank');
                    return;
                }

                this.isGeneratingStatementPrint = true;
                try {
                    const pdfBlob = await this.generatePdfBlobFromUrl(this.statementPrintUrl(), 'statement-capture-area');
                    const base64 = await this.blobToBase64(pdfBlob);
                    const fileName = `Statement-Customer-{{ $customer->id }}.pdf`;

                    const result = await window.flutter_inappwebview.callHandler('printInvoice', base64, fileName);
                    if (result && result.success === false) {
                        throw new Error(result.message || 'প্রিন্ট করা সম্ভব হয়নি');
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire({ icon: 'error', title: 'প্রিন্ট করা সম্ভব হয়নি', text: 'আবার চেষ্টা করুন।', confirmButtonColor: '#C6413A' });
                } finally {
                    this.isGeneratingStatementPrint = false;
                }
            },

            // "স্টেটমেন্ট শেয়ার WhatsApp"
            async shareStatementOnWhatsApp() {
                if (this.isGeneratingStatementShare) return;
                this.isGeneratingStatementShare = true;
                try {
                    const pdfBlob = await this.generatePdfBlobFromUrl(this.statementPrintUrl(), 'statement-capture-area');
                    const fileName = `Statement-Customer-{{ $customer->id }}.pdf`;
                    await this.shareOrDownload(pdfBlob, fileName, '{{ $customer->name }} — স্টেটমেন্ট');
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        console.error(e);
                        Swal.fire('ত্রুটি!', 'স্টেটমেন্টের PDF তৈরি বা শেয়ার করা সম্ভব হয়নি।', 'error');
                    }
                } finally {
                    this.isGeneratingStatementShare = false;
                }
            },

            // প্রতিটা লেনদেন-রো-এর প্রিন্টার বাটনে ক্লিক করলে
            openRowActions(type, id, refNo, amount) {
                this.selectedType = type;
                this.selectedId = id;
                this.selectedRefNo = refNo;
                this.selectedAmount = amount;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('rowActionModal')).show();
            },

            // টাইপ অনুযায়ী সঠিক প্রিন্ট-পেজের URL
            getRowPrintUrl() {
                if (this.selectedType === 'invoice') {
                    return `/admin/sales/${this.selectedId}/invoice`;
                }
                if (this.selectedType === 'payment') {
                    return `/admin/customer-payments/${this.selectedId}/receipt`;
                }
                if (this.selectedType === 'opening_due') {
                    return `/admin/customers/${this.customerId}/opening-due-invoice`;
                }
                return null;
            },

            async printRowA4() {
                const url = this.getRowPrintUrl();
                if (!url) return;

                if (!this.isNativeApp) {
                    window.open(url, '_blank');
                    return;
                }

                try {
                    const pdfBlob = await this.generatePdfBlobFromUrl(url, 'invoice-capture-area');
                    const base64 = await this.blobToBase64(pdfBlob);
                    const fileName = `${this.selectedRefNo}.pdf`;

                    const result = await window.flutter_inappwebview.callHandler('printInvoice', base64, fileName);
                    if (result && result.success === false) {
                        throw new Error(result.message || 'প্রিন্ট করা সম্ভব হয়নি');
                    }
                } catch (e) {
                    console.error(e);
                    Swal.fire({ icon: 'error', title: 'প্রিন্ট করা সম্ভব হয়নি', text: 'আবার চেষ্টা করুন।', confirmButtonColor: '#C6413A' });
                }
            },

            async shareRowOnWhatsApp() {
                if (this.isGeneratingRowShare) return;
                const url = this.getRowPrintUrl();
                if (!url) return;

                this.isGeneratingRowShare = true;
                try {
                    const pdfBlob = await this.generatePdfBlobFromUrl(url, 'invoice-capture-area');
                    const fileName = `${this.selectedRefNo}.pdf`;
                    await this.shareOrDownload(pdfBlob, fileName, 'রসিদ ' + this.selectedRefNo);
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        console.error(e);
                        Swal.fire('ত্রুটি!', 'PDF তৈরি বা শেয়ার করা সম্ভব হয়নি।', 'error');
                    }
                } finally {
                    this.isGeneratingRowShare = false;
                }
            },

            // "পস প্রিন্ট" — নেটিভ অ্যাপে Bluetooth প্রিন্টার স্ক্রিন খোলে, ব্রাউজারে জানিয়ে দেয়
            openRowPosReceipt() {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('rowActionModal')).hide();

                if (this.isNativeApp) {
                    window.flutter_inappwebview.callHandler('openBluetoothPrinterScreen', {
                        invoice_no: this.selectedRefNo,
                        grand_total: this.selectedAmount,
                        due_amount: 0,
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

            // নেটিভ অ্যাপে থাকলে Flutter bridge দিয়ে শেয়ার শিট, ব্রাউজারে Web Share API
            // → না থাকলে ডাউনলোড + wa.me ফলব্যাক (কোনো নির্দিষ্ট নম্বর ছাড়াই)
            async shareOrDownload(pdfBlob, fileName, title) {
                if (this.isNativeApp) {
                    const base64 = await this.blobToBase64(pdfBlob);
                    const result = await window.flutter_inappwebview.callHandler('shareInvoice', base64, fileName, title);
                    if (result && result.success === false) {
                        throw new Error(result.message || 'শেয়ার করা সম্ভব হয়নি');
                    }
                    return;
                }

                const file = new File([pdfBlob], fileName, { type: 'application/pdf' });
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ files: [file], title, text: title });
                    return;
                }

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
                    confirmButtonColor: '#0F6F4C'
                });
                window.open('https://wa.me/', '_blank');
            },

            // যেকোনো প্রিন্টযোগ্য পেজকে (?embed=1 দিয়ে, no-print bar/অটো-প্রিন্ট ছাড়া)
            // একটা লুকানো iframe-এ লোড করে html2canvas দিয়ে ক্যাপচার করে PDF বানায়।
            // captureId প্যারামিটার দিয়ে ভিন্ন ভিন্ন পেজের ভিন্ন ভিন্ন capture-element বেছে নেওয়া হয়:
            // ইনভয়েস/ওপেনিং-ডিউ/পেমেন্ট রসিদ → "invoice-capture-area"
            // পুরো স্টেটমেন্ট → "statement-capture-area"
            async generatePdfBlobFromUrl(url, captureId) {
                await this.ensurePdfLibsLoaded();

                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.left = '-9999px';
                iframe.style.top = '0';
                iframe.style.width = '800px';
                iframe.style.height = '1400px';
                document.body.appendChild(iframe);

                try {
                    await new Promise((resolve, reject) => {
                        iframe.onload = resolve;
                        iframe.onerror = reject;
                        iframe.src = url + (url.includes('?') ? '&' : '?') + 'embed=1';
                    });

                    await new Promise(r => setTimeout(r, 500));

                    const target = iframe.contentDocument.getElementById(captureId);
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
        }
    }
</script>
@endpush