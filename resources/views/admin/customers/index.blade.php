@extends('admin.layouts.main')

@section('title', 'কাস্টমার ম্যানেজমেন্ট')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div>
                <h5 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-users me-2"></i>কাস্টমার তালিকা
                </h5>
                <small class="text-muted">আপনার শপের সকল কাস্টমারের তথ্য ও বকেয়া হিসেব ট্র্যাক করুন</small>
            </div>
            <button type="button" class="btn btn-primary btn-md rounded-2 w-100 w-sm-auto" id="btnCreateCustomer">
                <i class="fas fa-user-plus me-1"></i> নতুন কাস্টমার যোগ করুন
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-2 p-md-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="customerTable">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th>কাস্টমারের নাম</th>
                            <th>ফোন নম্বর</th>
                            <th>ঠিকানা</th>
                            <th>ওপেনিং বকেয়া</th>
                            <th>বর্তমান বকেয়া</th>
                            <th width="10%">স্ট্যাটাস</th>
                            <th width="12%" class="text-center">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalTitle">নতুন কাস্টমার যোগ করুন</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="customerForm">
                <input type="hidden" id="customer_id">

                <div class="modal-body p-3 p-md-4">
                    <div class="row g-3">

                        <!-- Customer Name -->
                        <div class="col-12">
                            <label class="form-label fw-bold">কাস্টমারের নাম <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg fs-6" id="name" name="name" placeholder="যেমন: আব্দুর রহিম" required>
                            <span class="text-danger small error-text" id="error_name"></span>
                        </div>

                        <!-- Customer Phone -->
                        <div class="col-12">
                            <label class="form-label fw-bold">ফোন নম্বর</label>
                            <input type="text" class="form-control form-control-lg fs-6" id="phone" name="phone" placeholder="যেমন: 017xxxxxxxx">
                            <span class="text-danger small error-text" id="error_phone"></span>
                        </div>

                        <!-- Opening Due -->
                        <div class="col-12">
                            <label class="form-label fw-bold">ওপেনিং বকেয়া (৳)</label>
                            <input type="number" step="0.01" class="form-control form-control-lg fs-6" id="opening_due" name="opening_due" placeholder="0.00">
                            <span class="text-danger small error-text" id="error_opening_due"></span>
                        </div>

                        <!-- Customer Address -->
                        <div class="col-12">
                            <label class="form-label fw-bold">ঠিকানা</label>
                            <textarea class="form-control fs-6" id="address" name="address" rows="2" placeholder="কাস্টমারের সম্পূর্ণ ঠিকানা..."></textarea>
                            <span class="text-danger small error-text" id="error_address"></span>
                        </div>

                        <!-- Status Switch -->
                        <div class="col-12">
                            <div class="form-check form-switch mt-1">
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
@endsection

@push('js')
<script>
    $(document).ready(function () {
        let table = $('#customerTable').DataTable({
            processing: true,
            serverSide: false,
            responsive: true,
            ajax: "{{ route('customers.index') }}",
            columns: [
                {
                    data: null,
                    render: (data, type, row, meta) => meta.row + 1
                },
                {
                    data: 'name',
                    render: (data) => `<span class="fw-bold text-dark">${data}</span>`
                },
                {
                    data: 'phone',
                    render: (data) => data ? `<span class="text-secondary"><i class="fas fa-phone-alt me-1 small"></i>${data}</span>` : '<span class="badge bg-light text-muted">N/A</span>'
                },
                {
                    data: 'address',
                    render: (data) => data ? `<small class="text-muted">${data}</small>` : '<span class="badge bg-light text-muted">N/A</span>'
                },
                {
                    data: 'opening_due',
                    render: (data) => `৳${parseFloat(data).toFixed(2)}`
                },
                {
                    data: 'current_due',
                    render: function (data) {
                        let amount = parseFloat(data);
                        if (amount > 0) {
                            return `<span class="badge bg-danger fs-6 px-2 py-1">৳${amount.toFixed(2)}</span>`;
                        } else {
                            return `<span class="badge bg-success fs-6 px-2 py-1">৳0.00</span>`;
                        }
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
                        // ডিলিট বাটনে data-due অ্যাট্রিবিউটে current_due রাখা হলো,
                        // যাতে ক্লিক করার সাথে সাথেই (সার্ভারে না গিয়ে) বকেয়া থাকলে
                        // ব্যবহারকারীকে আগেভাগে সতর্ক করা যায় — দ্রুত ও পরিষ্কার UX-এর জন্য।
                        return `
                            <button class="btn btn-sm btn-info text-white editBtn me-1" data-id="${row.id}" title="সম্পাদনা">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger deleteBtn" data-id="${row.id}" data-due="${row.current_due}" data-name="${row.name}" title="মুছে ফেলুন">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        `;
                    }
                }
            ]
        });

        function resetForm() {
            $('#customerForm')[0].reset();
            $('#customer_id').val('');
            $('.error-text').text('');
            $('#is_active').prop('checked', true);
        }

        $('#btnCreateCustomer').click(function () {
            resetForm();
            $('#modalTitle').text('নতুন কাস্টমার যোগ করুন');
            $('#customerModal').modal('show');
        });

        $('#customerForm').submit(function (e) {
            e.preventDefault();
            $('.error-text').text('');

            let id = $('#customer_id').val();
            let url = id ? `{{ url('admin/customers') }}/${id}/update` : "{{ route('customers.store') }}";

            let formData = {
                name: $('#name').val(),
                phone: $('#phone').val(),
                address: $('#address').val(),
                opening_due: $('#opening_due').val(),
                is_active: $('#is_active').is(':checked') ? 1 : 0
            };

            $('#btnSave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> প্রসেস হচ্ছে...');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function (response) {
                    $('#customerModal').modal('hide');
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

        $(document).on('click', '.editBtn', function () {
            let id = $(this).data('id');
            resetForm();

            $.get(`{{ url('admin/customers') }}/${id}/edit`, function (response) {
                let data = response.data;
                $('#customer_id').val(data.id);
                $('#name').val(data.name);
                $('#phone').val(data.phone);
                $('#address').val(data.address);
                $('#opening_due').val(data.opening_due);
                $('#is_active').prop('checked', data.is_active == 1);

                $('#modalTitle').text('কাস্টমার তথ্য সম্পাদনা করুন');
                $('#customerModal').modal('show');
            });
        });

        $(document).on('click', '.deleteBtn', function () {
            let id = $(this).data('id');
            let currentDue = parseFloat($(this).data('due')) || 0;
            let customerName = $(this).data('name') || '';

            // ⚠️ ফিক্স (client-side দ্রুত সতর্কতা): বকেয়া থাকলে সার্ভারে রিকোয়েস্ট
            // পাঠানোর আগেই স্পষ্টভাবে জানিয়ে দেওয়া হচ্ছে কেন ডিলিট করা যাবে না।
            // এটা শুধু UX-এর জন্য — আসল নিয়ন্ত্রণ এখনো সার্ভারেই আছে (নিচের error handler দ্রষ্টব্য)।
            if (currentDue > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ডিলিট করা যাবে না',
                    text: `${customerName}-এর ৳${currentDue.toFixed(2)} বকেয়া এখনো অপরিশোধিত আছে — বকেয়া শূন্য না হওয়া পর্যন্ত ডিলিট করা যাবে না।`,
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            Swal.fire({
                title: 'আপনি কি নিশ্চিত?',
                text: "এই কাস্টমারকে মুছে ফেলা হবে!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'হ্যাঁ, ডিলিট করুন!',
                cancelButtonText: 'বাতিল'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/customers') }}/${id}`,
                        type: 'DELETE',
                        success: function (response) {
                            table.ajax.reload();
                            toastr.success(response.message);
                        },
                        error: function (xhr) {
                            // ⚠️ ফিক্স: এটাই আসল/নির্ভরযোগ্য চেক (সার্ভার-সাইড) — উপরের
                            // ক্লায়েন্ট-সাইড চেক পাশ কাটিয়ে গেলেও (যেমন: এর মধ্যে অন্য
                            // ট্যাবে নতুন sale হয়ে বকেয়া তৈরি হলে) সার্ভার আটকে দেবে এবং
                            // এখানে তার নির্দিষ্ট বার্তা দেখানো হচ্ছে — আগে সবসময় জেনেরিক
                            // বার্তা দেখাত, আসল কারণ বোঝা যেত না।
                            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                                toastr.error(xhr.responseJSON.message);
                            } else {
                                toastr.error('কাস্টমার ডিলিট করতে সমস্যা হয়েছে!');
                            }
                        }
                    });
                }
            });
        });

        $(document).on('change', '.status-toggle', function () {
            let id = $(this).data('id');

            $.post(`{{ url('admin/customers') }}/${id}/status`, function (response) {
                toastr.success(response.message);
            }).fail(function () {
                table.ajax.reload();
                toastr.error('স্ট্যাটাস পরিবর্তন ব্যর্থ হয়েছে!');
            });
        });
    });
</script>
@endpush