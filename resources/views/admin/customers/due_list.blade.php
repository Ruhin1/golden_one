@extends('admin.layouts.main')

@section('title', 'বকেয়া কাস্টমার তালিকা')

@section('content')
<div class="container py-4">
    
    <!-- Title & Search Bar -->
    <div class="row align-items-center mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <h4 class="fw-bold text-danger mb-1">
                ⚠️ বকেয়া কাস্টমার তালিকা
            </h4>
            <p class="text-muted small mb-0">যেসব কাস্টমারের নিকট টাকা বকেয়া রয়েছে তাদের তালিকা</p>
        </div>
        <div class="col-md-6">
            <!-- Live Search Input -->
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-muted">🔍</span>
                <input type="text" id="searchInput" name="search" class="form-control border-start-0" placeholder="কাস্টমারের নাম বা নম্বর লিখলেই অটো সার্চ হবে..." value="{{ request('search') }}" autocomplete="off">
                <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" style="{{ request('search') ? '' : 'display: none;' }}">
                    ✖ রিসেট
                </button>
            </div>
        </div>
    </div>

    <!-- Customer Cards Grid -->
    <div id="customerCardsContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" style="transition: opacity 0.2s;">
        @forelse($customers as $customer)
            <div class="col">
                <div class="card h-100 shadow-sm border-0 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 bg-danger" style="height: 4px;"></div>
                    
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold text-dark mb-1">{{ $customer->name }}</h5>
                                <p class="text-muted small mb-0">
                                    📞 {{ $customer->phone }}
                                </p>
                            </div>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                বকেয়া
                            </span>
                        </div>

                        <div class="bg-light p-3 rounded mb-3">
                            <small class="text-muted d-block mb-1 fw-semibold">বর্তমান বকেয়া পরিমাণ</small>
                            <h4 class="fw-bold text-danger mb-0">
                                ৳ {{ number_format($customer->current_due, 2) }}
                            </h4>
                        </div>

                        @if($customer->address)
                            <p class="text-muted small mb-3">
                                📍 {{ Str::limit($customer->address, 45) }}
                            </p>
                        @endif
                    </div>

                    <div class="card-footer bg-white border-0 pt-0 pb-4 px-4">
                        <a href="{{ route('customers.statement', $customer->id) }}" class="btn btn-danger w-100 fw-bold shadow-sm">
                            💳 বকেয়া পরিশোধ
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 bg-white rounded shadow-sm border">
                    <div class="fs-1 text-muted mb-2">🎉</div>
                    <h5 class="fw-bold text-secondary">কোনো বকেয়া কাস্টমার পাওয়া যায়নি!</h5>
                    <p class="text-muted small mb-0">সব কাস্টমারের বকেয়া পরিশোধিত রয়েছে অথবা অনুসন্ধানের ফলাফল পাওয়া যায়নি।</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" class="d-flex justify-content-center mt-4">
        {{ $customers->withQueryString()->links() }}
    </div>

</div>

<!-- JavaScript for Live Search -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const cardsContainer = document.getElementById('customerCardsContainer');
        const paginationContainer = document.getElementById('paginationContainer');

        let debounceTimer;

        // টাইপ করার সাথে সাথে ইভেন্ট ট্রিপ হবে
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();

            // রিসেট বাটন দেখানো বা লুকানো
            if (query.length > 0) {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
            }

            // Debounce: টাইপ করা থামা পর্যন্ত ৩০০ms অপেক্ষা করবে (যাতে সার্ভারে অতিরিক্ত লোড না পড়ে)
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                performLiveSearch(query);
            }, 300);
        });

        // রিসেট বাটনে ক্লিক করলে
        clearSearchBtn.addEventListener('click', function () {
            searchInput.value = '';
            clearSearchBtn.style.display = 'none';
            performLiveSearch('');
        });

        // লাইভ সার্চ ফাংশন
        function performLiveSearch(query) {
            // হালকা লোডিং ইফেক্ট (ঝাপসা হবে)
            cardsContainer.style.opacity = '0.4';

            const url = `{{ route('customers.due_list') }}?search=${encodeURIComponent(query)}`;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // কার্ড এরিয়া আপডেট
                const newCards = doc.getElementById('customerCardsContainer');
                if (newCards) {
                    cardsContainer.innerHTML = newCards.innerHTML;
                }

                // পেজিনেশন লিঙ্ক আপডেট
                const newPagination = doc.getElementById('paginationContainer');
                if (newPagination) {
                    paginationContainer.innerHTML = newPagination.innerHTML;
                }

                // ব্রাউজারের URL আপডেট (যাতে পেজ রিফ্রেশ করলেও সার্চ থেকে যায়)
                window.history.pushState({}, '', url);
            })
            .catch(error => {
                console.error('Search error:', error);
            })
            .finally(() => {
                cardsContainer.style.opacity = '1';
            });
        }
    });
</script>
@endsection