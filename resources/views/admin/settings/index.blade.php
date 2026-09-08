@extends('admin.layouts.main')

@section('title', 'এপ সেটিংস')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title fw-bold mb-0">⚙️ কোম্পানি সেটিংস</h5>
                </div>
                <div class="card-body p-4">

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <!-- Company Name -->
                            <div class="col-md-6">
                                <label class="form-label font-semibold">কোম্পানির নাম <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" value="{{ old('company_name', $setting->company_name) }}" class="form-control @error('company_name') is-invalid @enderror" required>
                                @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6">
                                <label class="form-label font-semibold">ফোন নম্বর <span class="text-danger">*</span></label>
                                <input type="text" name="phone" value="{{ old('phone', $setting->phone) }}" class="form-control @error('phone') is-invalid @enderror" required>
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label font-semibold">ইমেইল</label>
                                <input type="email" name="email" value="{{ old('email', $setting->email) }}" class="form-control @error('email') is-invalid @enderror">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <!-- Currency Symbol -->
                            <div class="col-md-6">
                                <label class="form-label font-semibold">মুদ্রার প্রতীক (Currency Symbol) <span class="text-danger">*</span></label>
                                <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $setting->currency_symbol ?? '৳') }}" class="form-control @error('currency_symbol') is-invalid @enderror" required>
                                @error('currency_symbol') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <!-- Logo Upload -->
                            <div class="col-md-12">
                                <label class="form-label font-semibold">কোম্পানি লোগো</label>
                                <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                                @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                @if($setting->logo)
                                    <div class="mt-2">
                                        <small class="text-muted d-block mb-1">বর্তমান লোগো:</small>
                                        <img src="{{ asset('storage/' . $setting->logo) }}" alt="Logo" class="img-thumbnail" style="max-height: 80px;">
                                    </div>
                                @endif
                            </div>

                            <!-- Address -->
                            <div class="col-md-12">
                                <label class="form-label font-semibold">ঠিকানা</label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $setting->address) }}</textarea>
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <!-- Invoice Footer Note -->
                            <div class="col-md-12">
                                <label class="form-label font-semibold">ইনভয়েস ফুটার নোট (Invoice Footer Note)</label>
                                <textarea name="invoice_footer_note" class="form-control @error('invoice_footer_note') is-invalid @enderror" rows="3" placeholder="যেমন: ধন্যবাদ আবার আসবেন!">{{ old('invoice_footer_note', $setting->invoice_footer_note) }}</textarea>
                                @error('invoice_footer_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                💾 সেভ করুন
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection