@extends('admin.layouts.main')

@section('title', 'ক্যাটাগরি ম্যানেজমেন্ট')

@section('content')
<div class="container-fluid p-0">
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-tags me-2"></i>ক্যাটাগরি তালিকা
            </h5>
            <button type="button" class="btn btn-primary btn-sm rounded-2" id="btnCreateCategory">
                <i class="fas fa-plus me-1"></i> নতুন ক্যাটাগরি
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle w-100" id="categoryTable">
                    <thead class="table-dark">
                        <tr>
                            <th width="8%">#</th>
                            <th>ক্যাটাগরির নাম</th>
                            <th width="15%">মোট প্রোডাক্ট</th>
                            <th width="15%">স্ট্যাটাস</th>
                            <th width="15%" class="text-center">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">নতুন ক্যাটাগরি যোগ করুন</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="categoryForm">
                <input type="hidden" id="category_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label font-weight-bold">ক্যাটাগরির নাম <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="যেমন: মশলা, তেল, ইত্যাদি" required>
                        <span class="text-danger small" id="error_name"></span>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">এক্টিভ রাখুন</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">বন্ধ করুন</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnSave">
                        <i class="fas fa-save me-1"></i> সংরক্ষণ করুন
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0px !important;
    }
</style>
@endpush

@push('js')
<script>
    $(document).ready(function () {
        // DataTables Initialization
        let table = $('#categoryTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: "{{ route('categories.index') }}",
            columns: [
                { 
                    data: null, 
                    render: (data, type, row, meta) => meta.row + 1 
                },
                { data: 'name' },
                { 
                    data: 'products_count',
                    render: (data) => `<span class="badge bg-secondary">${data} টি</span>`
                },
                {
                    data: 'is_active',
                    render: function (data, type, row) {
                        let isChecked = data ? 'checked' : '';
                        return `
                            <div class="form-check form-switch">
                                <input class="form-check-input status-toggle" type="checkbox" 
                                    data-id="${row.id}" ${isChecked}>
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
                            <button class="btn btn-sm btn-info text-white editBtn me-1" data-id="${row.id}" title="এডিট">
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

        // Clear Errors & Form
        function resetForm() {
            $('#categoryForm')[0].reset();
            $('#category_id').val('');
            $('#error_name').text('');
            $('#is_active').prop('checked', true);
        }

        // Open Modal for Create
        $('#btnCreateCategory').click(function () {
            resetForm();
            $('#modalTitle').text('নতুন ক্যাটাগরি যোগ করুন');
            $('#categoryModal').modal('show');
        });

        // Submit Form (Create & Update)
        $('#categoryForm').submit(function (e) {
            e.preventDefault();
            
            let id = $('#category_id').val();
            let url = id ? `{{ url('admin/categories') }}/${id}` : "{{ route('categories.store') }}";
            let type = id ? 'PUT' : 'POST';

            let formData = {
                name: $('#name').val(),
                is_active: $('#is_active').is(':checked') ? 1 : 0
            };

            $('#btnSave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> প্রসেস হচ্ছে...');

            $.ajax({
                url: url,
                type: type,
                data: formData,
                success: function (response) {
                    $('#categoryModal').modal('hide');
                    table.ajax.reload();
                    toastr.success(response.message);
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        if (errors.name) {
                            $('#error_name').text(errors.name[0]);
                        }
                    } else {
                        toastr.error('একটি ত্রুটি ঘটেছে, আবার চেষ্টা করুন।');
                    }
                },
                complete: function () {
                    $('#btnSave').prop('disabled', false).html('<i class="fas fa-save me-1"></i> সংরক্ষণ করুন');
                }
            });
        });

        // Open Modal for Edit
        $(document).on('click', '.editBtn', function () {
            let id = $(this).data('id');
            resetForm();

            $.get(`{{ url('admin/categories') }}/${id}/edit`, function (response) {
                let data = response.data;
                $('#category_id').val(data.id);
                $('#name').val(data.name);
                $('#is_active').prop('checked', data.is_active == 1);
                $('#modalTitle').text('ক্যাটাগরি সম্পাদনা করুন');
                $('#categoryModal').modal('show');
            });
        });

        // Delete Category
        $(document).on('click', '.deleteBtn', function () {
            let id = $(this).data('id');

            Swal.fire({
                title: 'আপনি কি নিশ্চিত?',
                text: "এই ক্যাটাগরি ডিলিট করলে তা আর ফেরত পাওয়া যাবে না!",
                icon: 'warning',
                showCancelButton: true,
                confirmColor: '#d33',
                cancelColor: '#3085d6',
                confirmButtonText: 'হ্যাঁ, মুছে ফেলুন!',
                cancelButtonText: 'বাতিল'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/categories') }}/${id}`,
                        type: 'DELETE',
                        success: function (response) {
                            table.ajax.reload();
                            toastr.success(response.message);
                        },
                        error: function (xhr) {
                            let msg = xhr.responseJSON ? xhr.responseJSON.message : 'ডিলিট করা সম্ভব হয়নি!';
                            toastr.error(msg);
                        }
                    });
                }
            });
        });

        // Toggle Status
        $(document).on('change', '.status-toggle', function () {
            let id = $(this).data('id');

            $.post(`{{ url('admin/categories') }}/${id}/status`, function (response) {
                toastr.success(response.message);
            }).fail(function () {
                table.ajax.reload();
                toastr.error('স্ট্যাটাস আপডেট ব্যর্থ হয়েছে!');
            });
        });
    });
</script>
@endpush