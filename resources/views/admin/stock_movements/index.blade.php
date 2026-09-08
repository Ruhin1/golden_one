@extends('admin.layouts.main')

@section('title', 'স্টক মুভমেন্ট')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold m-0">Stock Movements</h1>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10 text-success p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="fw-semibold text-uppercase">Total Stock In</small>
                        <h4 class="fw-bold mb-0 mt-1">
                            {{ number_format($stats['total_in']) }} gm 
                            <small class="text-muted fs-6">({{ number_format($stats['total_in'] / 1000, 2) }} KG)</small>
                        </h4>
                    </div>
                    <span class="fs-1">📥</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10 text-danger p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="fw-semibold text-uppercase">Total Stock Out</small>
                        <h4 class="fw-bold mb-0 mt-1">
                            {{ number_format($stats['total_out']) }} gm 
                            <small class="text-muted fs-6">({{ number_format($stats['total_out'] / 1000, 2) }} KG)</small>
                        </h4>
                    </div>
                    <span class="fs-1">📤</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Date & Type Filter Form -->
    <div class="card p-3 mb-4 shadow-sm border-0">
        <form method="GET" action="{{ route('stock.movements.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold">Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Stock In (ইন)</option>
                    <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Stock Out (আউট)</option>
                </select>
            </div>

            <div class="col-md-3 col-sm-6">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('stock.movements.index') }}" class="btn btn-light border">Reset</a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Product</th>
                        <th scope="col">Type</th>
                        <th scope="col">Quantity</th>
                        <th scope="col">Note</th>
                        <th scope="col">Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    {{ $sl= 0 }}
                    @forelse($movements as $movement)
                        <tr>
                            <td>#{{ ++$sl }}</td>
                            <td class="fw-semibold text-dark">
                                {{ $movement->product->name ?? 'Deleted Product (' . $movement->product_id . ')' }}
                            </td>
                            <td>
                                @if($movement->type === 'in')
                                    <span class="badge bg-success-subtle text-success px-2 py-1 border border-success-subtle">
                                        ↓ Stock In
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger px-2 py-1 border border-danger-subtle">
                                        ↑ Stock Out
                                    </span>
                                @endif
                            </td>
                            <td class="fw-bold {{ $movement->type === 'in' ? 'text-success' : 'text-danger' }}">
                                {{ $movement->type === 'in' ? '+' : '-' }}{{ number_format($movement->quantity_in_grams) }} gm
                                <span class="text-muted fw-normal small">({{ number_format($movement->quantity_in_grams / 1000, 2) }} kg)</span>
                            </td>
                            <td class="text-muted small">
                                {{ $movement->note ?? '-' }}
                            </td>
                            <td class="text-muted small">
                                {{ $movement->created_at->format('d M Y, h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">কোন স্টক মুভমেন্ট এর রেকর্ড পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="card-footer bg-white p-3 border-0">
            {{ $movements->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection