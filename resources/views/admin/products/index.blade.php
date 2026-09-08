@extends('admin.layouts.main')

@section('title', 'প্রোডাক্ট ও স্টক ম্যানেজমেন্ট')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div>
                <h5 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-box-open me-2"></i>প্রোডাক্ট ও ইনভেন্টরি তালিকা
                </h5>
                <small class="text-muted">আপনার শপের পণ্য সমূহের স্টক ও মুভমেন্ট ট্রাক করুন</small>
            </div>
            <button type="button" class="btn btn-primary btn-md rounded-2 w-100 w-sm-auto" id="btnCreateProduct">
                <i class="fas fa-plus me-1"></i> নতুন পণ্য যোগ করুন
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-2 p-md-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="productTable">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th width="8%">ছবি</th>
                            <th>পণ্যের নাম ও ক্যাটাগরি</th>
                            <th>১ কেজি মূল্য</th>
                            <th>৫০ গ্রাম মূল্য</th>
                            <th>মোট স্টক</th>
                            <th width="8%">স্ট্যাটাস</th>
                            <th width="18%" class="text-center">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Main Product Create/Edit Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalTitle">নতুন প্রোডাক্ট যোগ করুন</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="productForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="product_id" name="product_id">
                
                <div class="modal-body p-3 p-md-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">ক্যাটাগরি <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg fs-6" id="category_id" name="category_id" required>
                                <option value="">-- ক্যাটাগরি নির্বাচন করুন --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <span class="text-danger small error-text" id="error_category_id"></span>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">পণ্যের নাম <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg fs-6" id="name" name="name" placeholder="যেমন: প্রিমিয়াম হলুদের গুঁড়া" required>
                            <span class="text-danger small error-text" id="error_name"></span>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">১ কেজি মূল্য (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-lg fs-6" id="price_1kg" name="price_1kg" placeholder="0.00" required>
                            <span class="text-danger small error-text" id="error_price_1kg"></span>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">৫০০ গ্রাম মূল্য (৳) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-lg fs-6" id="price_half_kg" name="price_half_kg" placeholder="0.00" required>
                            <span class="text-danger small error-text" id="error_price_half_kg"></span>
                        </div>

                        <!-- Initial Stock Inputs (Visible only when creating new product) -->
                        <div class="col-6 col-md-3 initial-stock-field">
                            <label class="form-label fw-bold">প্রাথমিক স্টক (কেজি)</label>
                            <input type="number" min="0" class="form-control form-control-lg fs-6" id="stock_kg" name="stock_kg" placeholder="0">
                            <span class="text-danger small error-text" id="error_stock_kg"></span>
                        </div>

                        <div class="col-6 col-md-3 initial-stock-field">
                            <label class="form-label fw-bold">প্রাথমিক স্টক (গ্রাম)</label>
                            <input type="number" min="0" max="999" class="form-control form-control-lg fs-6" id="stock_gram" name="stock_gram" placeholder="0">
                            <span class="text-danger small error-text" id="error_stock_gram"></span>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold">পণ্যের ছবি (POS friendly)</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <span class="text-danger small error-text" id="error_image"></span>
                            <div class="mt-2" id="imagePreviewContainer" style="display: none;">
                                <img id="imagePreview" src="" alt="Product Preview" class="img-thumbnail rounded" style="height: 60px;">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked style="width: 45px; height: 22px;">
                                <label class="form-check-label fw-bold ms-2 pt-1" for="is_active">এক্টিভ রাখুন</label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary py-2 px-3" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary py-2 px-4 fw-bold" id="btnSave">
                        <i class="fas fa-save me-1"></i> সংরক্ষণ করুন
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stock Adjust Modal (নতুন স্টক আপডেট মডাল) -->
<div class="modal fade" id="stockAdjustModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-boxes me-2"></i>স্টক এডজাস্টমেন্ট</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="stockAdjustForm">
                @csrf
                <input type="hidden" id="adjust_product_id">
                
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 mb-3">
                        <strong id="adjustProductName"></strong><br>
                        <small>বর্তমান স্টক: <span id="adjustCurrentStock" class="fw-bold text-dark"></span></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold d-block">অ্যাকশন টাইপ <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="type" id="type_in" value="in" checked>
                            <label class="btn btn-outline-success fw-bold py-2" for="type_in">
                                <i class="fas fa-plus-circle me-1"></i> স্টক ইন (বৃদ্ধি ➕)
                            </label>

                            <input type="radio" class="btn-check" name="type" id="type_out" value="out">
                            <label class="btn btn-outline-danger fw-bold py-2" for="type_out">
                                <i class="fas fa-minus-circle me-1"></i> স্টক আউট (হ্রাস ➖)
                            </label>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">পরিমাণ (কেজি)</label>
                            <input type="number" min="0" class="form-control form-control-lg fs-6" id="adjust_kg" name="adjust_kg" placeholder="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">পরিমাণ (গ্রাম)</label>
                            <input type="number" min="0" max="999" class="form-control form-control-lg fs-6" id="adjust_gram" name="adjust_gram" placeholder="0">
                        </div>
                    </div>

                    <div>
                        <label class="form-label fw-bold">নোট / কারণ (ঐচ্ছিক)</label>
                        <input type="text" class="form-control" id="adjust_note" name="note" placeholder="যেমন: নতুন সাপ্লাই এসেছে / নষ্ট হয়ে গেছে">
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary py-2 px-3" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-warning py-2 px-4 fw-bold" id="btnSaveStock">
                        <i class="fas fa-sync-alt me-1"></i> আপডেট করুন
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stock Movement History Sheet Modal (স্টক মুভমেন্ট শিট মডাল) -->
<div class="modal fade" id="stockHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-list-alt me-2"></i>স্টক মুভমেন্ট শিট - <span id="historyProductName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-3">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-secondary sticky-top">
                            <tr>
                                <th width="5%">#</th>
                                <th>তারিখ ও সময়</th>
                                <th>টাইপ</th>
                                <th>পরিমাণ</th>
                                <th>নোট / মন্তব্য</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <!-- Dynamic Content -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer bg-light p-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বন্ধ করুন</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $(document).ready(function () {
        let table = $('#productTable').DataTable({
            processing: true,
            serverSide: false,
            responsive: true,
            ajax: "{{ route('products.index') }}",
            columns: [
                { 
                    data: null, 
                    render: (data, type, row, meta) => meta.row + 1 
                },
                {
                    data: 'image',
                    render: function (data) {
                        let imgUrl = data ? `{{ asset('storage') }}/${data}` : `https://via.placeholder.com/50?text=No+Img`;
                        return `<img src="${imgUrl}" class="img-thumbnail rounded" style="width: 45px; height: 45px; object-fit: cover;">`;
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return `
                            <div class="fw-bold text-dark">${row.name}</div>
                            <small class="badge bg-light text-dark border">${row.category ? row.category.name : 'N/A'}</small>
                        `;
                    }
                },
                {
                    data: 'price_1kg',
                    render: (data) => `<span class="fw-bold text-success">৳${parseFloat(data).toFixed(2)}</span>`
                },
                {
                    data: 'price_half_kg',
                    render: (data) => `<span class="fw-bold text-info">৳${parseFloat(data).toFixed(2)}</span>`
                },
                {
                    data: 'stock_in_grams',
                    render: function (data) {
                        return formatStock(data);
                    }
                },
                {
                    data: 'is_active',
                    render: function (data, type, row) {
                        let isChecked = data ? 'checked' : '';
                        return `
                            <div class="form-check form-switch">
                                <input class="form-check-input status-toggle" type="checkbox" data-id="${row.id}" ${isChecked} style="cursor:pointer;">
                            </div>
                        `;
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        return `
                            <button class="btn btn-sm btn-warning text-dark adjustStockBtn me-1" data-id="${row.id}" data-name="${row.name}" data-stock="${row.stock_in_grams}" title="স্টক ইন/আউট করুন">
                                <i class="fas fa-boxes"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary historyBtn me-1" data-id="${row.id}" title="স্টক মুভমেন্ট শিট">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-sm btn-info text-white editBtn me-1" data-id="${row.id}" title="সম্পাদনা">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger deleteBtn" data-id="${row.id}" title="মুছে ফেলুন">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        `;
                    }
                }
            ]
        });

        // স্টক ফরম্যাট হেলপার ফাংশন
        function formatStock(totalGrams) {
            totalGrams = parseInt(totalGrams) || 0;
            let kg = Math.floor(totalGrams / 1000);
            let gram = totalGrams % 1000;

            if (kg > 0 && gram > 0) {
                return `<span class="badge bg-primary fs-6 px-2 py-1">${kg} কেজি ${gram} গ্রাম</span>`;
            } else if (kg > 0 && gram === 0) {
                return `<span class="badge bg-primary fs-6 px-2 py-1">${kg} কেজি</span>`;
            } else if (kg === 0 && gram > 0) {
                return `<span class="badge bg-primary fs-6 px-2 py-1">${gram} গ্রাম</span>`;
            } else {
                return `<span class="badge bg-danger fs-6 px-2 py-1">0 কেজি</span>`;
            }
        }

        // Image local preview logic
        $('#image').change(function () {
            let file = this.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function (e) {
                    $('#imagePreview').attr('src', e.target.result);
                    $('#imagePreviewContainer').show();
                }
                reader.readAsDataURL(file);
            }
        });

        function resetForm() {
            $('#productForm')[0].reset();
            $('#product_id').val('');
            $('.error-text').text('');
            $('#is_active').prop('checked', true);
            $('#imagePreviewContainer').hide();
            $('#imagePreview').attr('src', '');
        }

        $('#btnCreateProduct').click(function () {
            resetForm();
            $('.initial-stock-field').show(); // নতুন প্রডাক্টে স্টক ফিল্ড দেখাবে
            $('#modalTitle').text('নতুন প্রোডাক্ট যোগ করুন');
            $('#productModal').modal('show');
        });

        // ক্রিয়েট এবং এডিট ফর্ম সাবমিট
        $('#productForm').submit(function (e) {
            e.preventDefault();
            $('.error-text').text('');
            
            let id = $('#product_id').val();
            let url = id ? `{{ url('admin/products') }}/${id}/update` : "{{ route('products.store') }}";
            let formData = new FormData(this);

            $('#btnSave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> প্রসেস হচ্ছে...');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    $('#productModal').modal('hide');
                    table.ajax.reload();
                    toastr.success(response.message);
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function (key, value) {
                            $(`#error_${key}`).text(value[0]);
                        });
                    } else {
                        toastr.error('একটি ত্রুটি ঘটেছে, পুনরায় চেষ্টা করুন।');
                    }
                },
                complete: function () {
                    $('#btnSave').prop('disabled', false).html('<i class="fas fa-save me-1"></i> সংরক্ষণ করুন');
                }
            });
        });

        // এডিট প্রোডাক্ট
        $(document).on('click', '.editBtn', function () {
            let id = $(this).data('id');
            resetForm();
            $('.initial-stock-field').hide(); // এডিট করার সময় মূল স্টক এখান থেকে পরিবর্তন নিষিদ্ধ

            $.get(`{{ url('admin/products') }}/${id}/edit`, function (response) {
                let data = response.data;
                $('#product_id').val(data.id);
                $('#category_id').val(data.category_id);
                $('#name').val(data.name);
                $('#price_1kg').val(data.price_1kg);
                $('#price_half_kg').val(data.price_half_kg);

                if (data.image_url) {
                    $('#imagePreview').attr('src', data.image_url);
                    $('#imagePreviewContainer').show();
                }

                $('#is_active').prop('checked', data.is_active == 1);
                $('#modalTitle').text('প্রোডাক্ট সম্পাদনা করুন');
                $('#productModal').modal('show');
            });
        });

        // স্টক এডজাস্টমেন্ট মডাল ওপেন
        $(document).on('click', '.adjustStockBtn', function () {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let stockGrams = $(this).data('stock');

            $('#adjust_product_id').val(id);
            $('#adjustProductName').text(name);
            $('#adjustCurrentStock').html(formatStock(stockGrams));
            
            $('#stockAdjustForm')[0].reset();
            $('#type_in').prop('checked', true);

            $('#stockAdjustModal').modal('show');
        });

        // স্টক এডজাস্টমেন্ট ফর্ম সাবমিট (ইন / আউট)
        $('#stockAdjustForm').submit(function (e) {
            e.preventDefault();

            let id = $('#adjust_product_id').val();
            let formData = $(this).serialize();

            $('#btnSaveStock').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> আপডেট হচ্ছে...');

            $.ajax({
                url: `{{ url('admin/products') }}/${id}/adjust-stock`,
                type: 'POST',
                data: formData,
                success: function (response) {
                    $('#stockAdjustModal').modal('hide');
                    table.ajax.reload();
                    toastr.success(response.message);
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('স্টক আপডেট করতে সমস্যা হয়েছে!');
                    }
                },
                complete: function () {
                    $('#btnSaveStock').prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> আপডেট করুন');
                }
            });
        });

        // স্টক মুভমেন্ট হিস্ট্রি শিট দেখা
        $(document).on('click', '.historyBtn', function () {
            let id = $(this).data('id');

            $('#historyTableBody').html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin me-1"></i> লোড হচ্ছে...</td></tr>');
            $('#stockHistoryModal').modal('show');

            $.get(`{{ url('admin/products') }}/${id}/movements`, function (response) {
                $('#historyProductName').text(response.product_name);

                let rows = '';
                if (response.movements.length > 0) {
                    $.each(response.movements, function (index, item) {
                        let date = new Date(item.created_at).toLocaleString('bn-BD', {
                            dateStyle: 'medium',
                            timeStyle: 'short'
                        });

                        let typeBadge = item.type === 'in' 
                            ? '<span class="badge bg-success"><i class="fas fa-arrow-down me-1"></i> স্টক ইন</span>' 
                            : '<span class="badge bg-danger"><i class="fas fa-arrow-up me-1"></i> স্টক আউট</span>';

                        rows += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${date}</td>
                                <td>${typeBadge}</td>
                                <td class="fw-bold">${formatStock(item.quantity_in_grams)}</td>
                                <td>${item.note || 'N/A'}</td>
                            </tr>
                        `;
                    });
                } else {
                    rows = '<tr><td colspan="5" class="text-center text-muted">কোন স্টক মুভমেন্ট রেকর্ড পাওয়া যায়নি!</td></tr>';
                }

                $('#historyTableBody').html(rows);
            });
        });

        // ডিলিট অপারেশন
        $(document).on('click', '.deleteBtn', function () {
            let id = $(this).data('id');

            Swal.fire({
                title: 'আপনি কি নিশ্চিত?',
                text: "এই প্রোডাক্টটি মুছে ফেলা হবে!",
                icon: 'warning',
                showCancelButton: true,
                confirmColor: '#d33',
                cancelColor: '#3085d6',
                confirmButtonText: 'হ্যাঁ, ডিলিট করুন!',
                cancelButtonText: 'বাতিল'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/products') }}/${id}`,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function (response) {
                            table.ajax.reload();
                            toastr.success(response.message);
                        },
                        error: function () {
                            toastr.error('প্রোডাক্ট ডিলিট করতে সমস্যা হয়েছে!');
                        }
                    });
                }
            });
        });

        // স্ট্যাটাস টগল
        $(document).on('change', '.status-toggle', function () {
            let id = $(this).data('id');

            $.post(`{{ url('admin/products') }}/${id}/status`, { _token: '{{ csrf_token() }}' }, function (response) {
                toastr.success(response.message);
            }).fail(function () {
                table.ajax.reload();
                toastr.error('স্ট্যাটাস পরিবর্তন ব্যর্থ হয়েছে!');
            });
        });
    });
</script>
@endpush