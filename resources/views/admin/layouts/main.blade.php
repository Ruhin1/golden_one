@php
    $setting = \App\Models\Setting::first();
    
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $setting->company_name }} - @yield('title')</title>

    <link rel="icon" type="image/x-icon" href="">

    {{-- Bootstrap 5 --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    {{-- Font Awesome --}}
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    {{-- DataTables --}}
    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css"
    >

    {{-- Toastr --}}
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"
    >

    {{-- SweetAlert2 --}}
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"
    >

    {{-- Bootstrap Icons --}}
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    {{-- Page Specific CSS --}}
    @stack('css')


    <style>

        /* =========================================================
           GLOBAL
        ========================================================= */

        body {
            background-color: #f8f9fa;
        }

        /* মোবাইলে সাইডবার খোলা থাকলে পেছনের পেইজ স্ক্রল বন্ধ রাখা */
        body.sidebar-open-no-scroll {
            overflow: hidden;
        }


        /* =========================================================
           SIDEBAR
        ========================================================= */

        #sidebar {
            width: 260px;
            background-color: #031131;
            color: #adb5bd;
            transition: transform 0.3s ease, width 0.3s ease;
            min-height: 100vh;
            z-index: 1050;
            flex-shrink: 0;
        }

        #sidebar.collapsed {
            width: 70px;
        }

        /* =========================================================
           SIDEBAR — MOBILE / TABLET (OFF-CANVAS)
           992px এর নিচে সাইডবার fixed + স্ক্রিনের বাইরে থাকবে,
           টগল করলে স্লাইড-ইন করে ঢুকবে (overlay হিসেবে)।
        ========================================================= */

        @media (max-width: 991.98px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 270px;
                max-width: 82vw;
                transform: translateX(-100%);
                box-shadow: 4px 0 24px rgba(0, 0, 0, 0.25);
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
                overflow-y: auto;
            }

            #sidebar.mobile-open {
                transform: translateX(0);
            }

            /* মোবাইলে "collapsed" ক্লাস কোনো width কমাবে না — off-canvas এই একই আচরণ সামলায় */
            #sidebar.collapsed {
                width: 270px;
                max-width: 82vw;
            }
        }

        /* =========================================================
           SIDEBAR BACKDROP (মোবাইল/ট্যাবলেট)
        ========================================================= */

        #sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.45);
            z-index: 1040;
        }

        #sidebar-backdrop.show {
            display: block;
        }


        /* =========================================================
           SIDEBAR MENU
        ========================================================= */

        #sidebar .nav-link {
            color: #adb5bd;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.2s ease;
        }

        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
        }


        /* =========================================================
           SIDEBAR SECTION HEADINGS
           Example:
           বিক্রয়
           কাস্টমার
           পণ্য ও স্টক
           সিস্টেম
        ========================================================= */

        #sidebar .sidebar-heading {
            display: block !important;
            width: 100%;
            padding: 8px 20px 4px;
            margin-top: 12px;
            margin-bottom: 4px;
        }

        #sidebar .sidebar-heading small {
            display: block !important;
            color: #6f8298 !important;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.4;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }


        /* =========================================================
           COLLAPSED SIDEBAR
        ========================================================= */

        #sidebar.collapsed .sidebar-text,
        #sidebar.collapsed .dropdown-toggle::after {
            display: none;
        }

        #sidebar.collapsed .sidebar-heading {
            display: none !important;
        }

        #sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 12px 0;
        }


        /* =========================================================
           SUBMENU
        ========================================================= */

        .submenu {
            background-color: #020b21;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .submenu .nav-link {
            padding-left: 50px;
        }

        #sidebar.collapsed .submenu {
            display: none !important;
        }


        /* =========================================================
           MAIN LAYOUT
        ========================================================= */

        .main-wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        .content-area {
            flex-grow: 1;
            height: 100vh;
            overflow-y: auto;
            min-width: 0; /* flex ওভারফ্লো ফিক্স — চাইল্ড কন্টেন্ট সাইডবারের উপর উঠে যাওয়া বন্ধ করে */
        }


        /* =========================================================
           AVATAR
        ========================================================= */

        .avatar-img {
            width: 35px;
            height: 35px;
            object-fit: cover;
            border-radius: 50%;
        }


        /* =========================================================
           HEADER — মোবাইল রেসপনসিভনেস
        ========================================================= */

        header.navbar {
            flex-wrap: nowrap;
            padding-top: env(safe-area-inset-top);
        }

        header.navbar h5 {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 1rem;
        }

        @media (max-width: 575.98px) {
            header.navbar {
                padding-left: 12px !important;
                padding-right: 12px !important;
                height: auto !important;
                min-height: 56px;
            }

            header.navbar h5 {
                max-width: 34vw;
                font-size: 0.9rem;
            }

            .avatar-img {
                width: 30px;
                height: 30px;
            }

            main.p-4 {
                padding: 0.85rem !important;
            }
        }

        /* টাচ-ফ্রেন্ডলি ন্যূনতম টার্গেট সাইজ */
        @media (max-width: 991.98px) {
            header .btn,
            #sidebar .nav-link {
                min-height: 44px;
            }
        }


        /* =========================================================
           FOCUS MODE — হেডার ও সাইডবার সম্পূর্ণ হাইড
        ========================================================= */

        body.focus-mode #sidebar,
        body.focus-mode header.navbar {
            display: none !important;
        }

        body.focus-mode #sidebar-backdrop {
            display: none !important;
        }

        #focus-mode-show-btn {
            display: none;
            position: fixed;
            right: 230px;
            top: 0;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background-color: #031131;
            color: #fff;
            border: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
            z-index: 1060;
            padding-bottom: env(safe-area-inset-bottom);
        }

        body.focus-mode #focus-mode-show-btn {
            display: flex;
        }

    </style>
</head>
<body> 

    {{-- মোবাইলে সাইডবার খোলা অবস্থায় পেছনের কন্টেন্ট ঢেকে রাখার জন্য ব্যাকড্রপ --}}
    <div id="sidebar-backdrop" onclick="closeSidebar()"></div>

    {{-- Focus Mode চালু থাকলে দেখানোর জন্য ফ্লোটিং "শো" বাটন --}}
    <button id="focus-mode-show-btn" onclick="toggleFocusMode()" title="টপবার ও সাইডবার দেখান">
        <i class="fas fa-eye"></i>
    </button>

    <div class="main-wrapper">
        <aside id="sidebar" class="d-flex flex-column justify-content-between">
        <div>
            <div class="d-flex align-items-center p-3 border-b border-secondary border-opacity-25" style="height: 64px;">
                


                    @if ($setting->logo)
                        <img src="{{ asset('storage/' . $setting->logo) }}" alt="{{ $setting->company_name }}" style="width: 100px; height: 64px; object-fit: contain;">
                    @else
                        <span class="fw-bold text-dark fs-5">{{ $setting->company_name }}</span>
                    @endif
                
                    
                
            </div>


<ul class="nav flex-column mt-3">


    {{-- =========================================================
         ড্যাশবোর্ড
    ========================================================== --}}
    <li class="sidebar-heading px-3 mt-2 mb-2">
        <small class="text-uppercase text-muted">ড্যাশবোর্ড</small>
    </li>

    {{-- ড্যাশবোর্ড --}}
    <li class="nav-item">
        <a href="{{ route('dashboard1') }}"
        class="nav-link {{ Route::is('dashboard1') ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt me-3 text-center" style="width: 20px;"></i>
            <span class="sidebar-text">ড্যাশবোর্ড</span>
        </a>
    </li>

    {{-- =========================================================
         বিক্রয়
    ========================================================== --}}
    <li class="sidebar-heading px-3 mt-2 mb-2">
        <small class="text-uppercase text-muted">বিক্রয়</small>
    </li>

    {{-- নতুন বিক্রয় --}}
    <li class="nav-item">
        <a href="{{ route('pos.index') }}"
           class="nav-link {{ Route::is('pos.index') ? 'active' : '' }}">
            <i class="fas fa-cash-register me-3 text-center" style="width: 20px;"></i>
            <span class="sidebar-text">নতুন বিক্রয়</span>
        </a>
    </li>

    {{-- বিক্রয় হিস্ট্রি --}}
    <li class="nav-item">
        <a href="{{ route('pos.sales.index') }}"
           class="nav-link {{ Route::is('pos.sales.*') ? 'active' : '' }}">
            <i class="fas fa-receipt me-3 text-center" style="width: 20px;"></i>
            <span class="sidebar-text">বিক্রয় হিস্ট্রি</span>
        </a>
    </li>


    {{-- =========================================================
         কাস্টমার
    ========================================================== --}}
    <li class="sidebar-heading px-3 mt-3 mb-2">
        <small class="text-uppercase text-muted">কাস্টমার</small>
    </li>

    {{-- কাস্টমার তালিকা --}}
    <li class="nav-item">
        <a href="{{ route('customers.index') }}"
           class="nav-link {{ Route::is('customers.index', 'customers.edit') ? 'active' : '' }}">
            <i class="fas fa-users me-3 text-center" style="width: 20px;"></i>
            <span class="sidebar-text">কাস্টমার তালিকা</span>
        </a>
    </li>

    {{-- কাস্টমারের বকেয়া --}}
    <li class="nav-item">
        <a href="{{ route('customers.due_list') }}"
           class="nav-link {{ Route::is('customers.due_list') ? 'active' : '' }}">
            <i class="fas fa-hand-holding-usd me-3 text-center" style="width: 20px;"></i>
            <span class="sidebar-text">কাস্টমারের বকেয়া</span>
        </a>
    </li>


    {{-- =========================================================
         পণ্য ও স্টক
    ========================================================== --}}
    <li class="sidebar-heading px-3 mt-3 mb-2">
        <small class="text-uppercase text-muted">পণ্য ও স্টক</small>
    </li>

    {{-- পণ্য তালিকা --}}
    <li class="nav-item">
        <a href="{{ route('products.index') }}"
           class="nav-link {{ Route::is('products.*') ? 'active' : '' }}">
            <i class="fas fa-boxes-stacked me-3 text-center" style="width: 20px;"></i>
            <span class="sidebar-text">পণ্য তালিকা</span>
        </a>
    </li>

    {{-- পণ্য ক্যাটাগরি --}}
    <li class="nav-item">
        <a href="{{ route('categories.index') }}"
           class="nav-link {{ Route::is('categories.*') ? 'active' : '' }}">
            <i class="fas fa-layer-group me-3 text-center" style="width: 20px;"></i>
            <span class="sidebar-text">পণ্য ক্যাটাগরি</span>
        </a>
    </li>

    {{-- স্টক মুভমেন্ট --}}
    <li class="nav-item">
        <a href="{{ route('stock.movements.index') }}"
           class="nav-link {{ Route::is('stock.movements.*') ? 'active' : '' }}">
            <i class="fas fa-arrow-right-arrow-left me-3 text-center" style="width: 20px;"></i>
            <span class="sidebar-text">স্টক মুভমেন্ট</span>
        </a>
    </li>


    {{-- =========================================================
         সিস্টেম
    ========================================================== --}}
    <li class="sidebar-heading px-3 mt-3 mb-2">
        <small class="text-uppercase text-muted">সিস্টেম</small>
    </li>

    {{-- ব্যবহারকারী ব্যবস্থাপনা --}}
    <li class="nav-item">
        <a href="{{ route('users.index') }}"
           class="nav-link {{ Route::is('users.*') ? 'active' : '' }}">
            <i class="fas fa-user-cog me-3 text-center" style="width: 20px;"></i>
            <span class="sidebar-text">ব্যবহারকারী ব্যবস্থাপনা</span>
        </a>
    </li>

    {{-- সিস্টেম সেটিংস --}}
    <li class="nav-item">
        <a href="{{ route('settings.index') }}"
           class="nav-link {{ Route::is('settings.*') ? 'active' : '' }}">
            <i class="fas fa-cog me-3 text-center" style="width: 20px;"></i>
            <span class="sidebar-text">সিস্টেম সেটিংস</span>
        </a>
    </li>

</ul>


            </div>

            <div class="p-3 border-top border-secondary border-opacity-25">
                <button onclick="toggleSidebar()" class="btn btn-link nav-link w-100 text-start border-0 m-0 p-2">
                    <i id="collapse-icon" class="fas fa-arrow-alt-circle-left me-3 text-center" style="width: 20px;"></i>
                    <span class="sidebar-text">Collapse</span>
                </button>
            </div>
        </aside>

        <div class="content-area d-flex flex-column justify-content-between">
        <header class="navbar navbar-expand bg-white border-bottom px-4" style="height: 64px;">
            <div class="container-fluid p-0 d-flex justify-content-between align-items-center">

                <div class="d-flex align-items-center">
                    <button onclick="toggleSidebar()" class="btn btn-link text-dark p-0 me-3 d-lg-none">
                        <i class="fas fa-bars fs-4"></i>
                    </button>

                    <h5 class="m-0 font-weight-bold text-secondary">
                        @yield('title')
                    </h5>
                </div>

                <div class="d-flex align-items-center gap-2">

                    <!-- Focus Mode Toggle: টপবার ও সাইডবার হাইড/শো করে -->
                    <button
                        onclick="toggleFocusMode()"
                        type="button"
                        class="btn btn-outline-secondary btn-sm rounded-circle shadow-sm"
                        style="width:40px;height:40px;"
                        title="টপবার ও সাইডবার হাইড করুন">
                        <i class="fas fa-eye-slash"></i>
                    </button>

                    <!-- Quick Action Menu -->
                    {{-- <div class="dropdown">
                        <button
                            class="btn btn-primary btn-sm rounded-circle shadow-sm"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            style="width:40px;height:40px;">
                            <i class="fas fa-plus"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">

                            <li>
                                <a class="dropdown-item" href="{{ route('cards.index') }}">
                                    <i class="fas fa-id-card text-primary me-2"></i>
                                    কার্ড তৈরী
                                </a>
                            </li>

                           

                        </ul>
                    </div> --}}

                    <!-- User -->
                    <div class="dropdown" style="cursor: pointer;">
                        <div class="d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                            @php $user = auth()->user(); @endphp

                            <img src="{{ asset('storage/' . $setting->logo) }}"
                                alt="{{  $setting->company_name }}"
                                class="avatar-img border">

                            <span class="text-sm font-medium text-dark d-none d-sm-inline">
                                {{  $setting->company_name }}
                            </span>
                        </div>
                    </div>

                    <!-- Logout -->
                    <a href="#"
                        class="btn btn-outline-danger btn-sm d-flex align-items-center gap-2 px-3"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">

                        <i class="fas fa-sign-out-alt"></i>
                        <span class="d-none d-sm-inline">Logout</span>
                    </a>

                    <form id="logout-form"
                        action="{{ route('logout') }}"
                        method="POST"
                        class="d-none">
                        @csrf
                    </form>

                </div>

            </div>
        </header>        
        <main class="p-4 flex-grow-1">
                @yield('content')
            </main>
            <footer class="bg-white border-top text-center text-muted py-2" style="font-size: 12px; height: 40px;">
                &copy; {{ date('Y') }} <a href="https://www.facebook.com/mdtonmoyislamruhin" target="_blank" class="text-decoration-none text-muted">MD Tonmoy Islam Ruhin</a> All rights reserved.
            </footer>
            </div>
        </div> 

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const MOBILE_BREAKPOINT = 991.98;

        function isMobileView() {
            return window.innerWidth <= MOBILE_BREAKPOINT;
        }

        // Sidebar Toggle Function — মোবাইলে off-canvas স্লাইড, ডেস্কটপে collapse/expand
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const collapseIcon = document.getElementById('collapse-icon');
            const backdrop = document.getElementById('sidebar-backdrop');

            if (isMobileView()) {
                const isOpen = sidebar.classList.toggle('mobile-open');
                backdrop.classList.toggle('show', isOpen);
                document.body.classList.toggle('sidebar-open-no-scroll', isOpen);
                return;
            }

            sidebar.classList.toggle('collapsed');

            if (sidebar.classList.contains('collapsed')) {
                collapseIcon.classList.replace('fa-arrow-alt-circle-left', 'fa-arrow-alt-circle-right');
                // কলাপ্স হলে ড্রপডাউন বন্ধ করে দেওয়া
                $('.submenu').collapse('hide');
            } else {
                collapseIcon.classList.replace('fa-arrow-alt-circle-right', 'fa-arrow-alt-circle-left');
            }
        }

        // মোবাইলে সাইডবার বন্ধ করার জন্য (ব্যাকড্রপ ক্লিক বা মেনু আইটেম ক্লিকে ব্যবহৃত হয়)
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');

            sidebar.classList.remove('mobile-open');
            backdrop.classList.remove('show');
            document.body.classList.remove('sidebar-open-no-scroll');
        }

        // Focus Mode Toggle — টপবার ও সাইডবার সম্পূর্ণ হাইড/শো করে
        function toggleFocusMode() {
            document.body.classList.toggle('focus-mode');

            // Focus mode চালু করার সময় মোবাইল সাইডবার খোলা থাকলে বন্ধ করে দেওয়া
            if (document.body.classList.contains('focus-mode')) {
                closeSidebar();
            }
        }

        // মোবাইলে সাইডবারের যেকোনো মেনু-লিংকে ক্লিক করলে অটো বন্ধ হয়ে যাবে
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('#sidebar .nav-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (isMobileView()) {
                        closeSidebar();
                    }
                });
            });
        });

        // স্ক্রিন resize/rotate করার সময় ডেস্কটপ ভিউতে গেলে মোবাইল স্টেট রিসেট করা
        window.addEventListener('resize', function () {
            if (!isMobileView()) {
                closeSidebar();
            }
        });

        // Global AJAX Setup for Laravel CSRF
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>

    @stack('js')
</body>
</html>