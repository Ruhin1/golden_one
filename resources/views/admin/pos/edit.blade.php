@extends('admin.layouts.main')

@section('title', 'POS Sale এডিট')

@section('content')

{{-- ⚠️ এই CSS ইচ্ছাকৃতভাবে @push('style') এর বদলে এখানে সরাসরি বসানো হলো —
     লেআউটের <head>-এ @stack('style') না থাকলেও (শুধু @stack('js') থাকলেও)
     এটা নিশ্চিতভাবে লোড হবে, কারণ @section('content') সবসময় রেন্ডার হয়।
     পণ্য-বিক্রয় (POS Sales Screen) পেজের সাথে ভিজ্যুয়াল কনসিস্টেন্সি বজায় রাখতে
     একই ফন্ট, একই কালার-স্কিম এখানেও ব্যবহার করা হলো। --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    :root {
        --pos-primary: #0F6F4C;      /* গাঢ় ইমেরাল্ড — তাজা সবজির রঙ */
        --pos-primary-dark: #0B5A3D;
        --pos-primary-light: #E7F3EC;
        --pos-accent: #D98E2B;       /* অ্যাম্বার — ওজন/দামের হাইলাইট */
        --pos-accent-light: #FBF0DD;
        --pos-bg: #F6F4EF;           /* উষ্ণ অফ-হোয়াইট */
        --pos-ink: #1C231F;
        --pos-danger: #C6413A;
    }

    body { font-family: 'Hind Siliguri', sans-serif; }
    .pos-mono { font-family: 'JetBrains Mono', monospace; font-variant-numeric: tabular-nums; }

    [x-cloak] { display: none !important; }

    /* রসিদের মতো ছেঁড়া/টিয়ার-এজ কার্ট প্যানেল */
    .pos-receipt-edge {
        position: relative;
    }
    .pos-receipt-edge::before {
        content: '';
        position: absolute;
        top: -1px; left: 0; right: 0; height: 10px;
        background-image: radial-gradient(circle at 8px 0, transparent 7px, var(--pos-bg) 7.5px);
        background-size: 16px 10px;
        background-repeat: repeat-x;
    }

    @media print {
        body * { visibility: hidden; }
        #receipt-print-area, #receipt-print-area * { visibility: visible; }
        #receipt-print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 80mm;
            padding: 5mm;
            font-family: 'JetBrains Mono', monospace;
        }
    }

    @media (max-width: 1023px) {
        .pos-cart-scroll-area { max-height: 40vh; }
    }
</style>

<div class="container-fluid p-2 sm:p-4 min-h-screen" style="background:var(--pos-bg); color:var(--pos-ink);" x-data="posEditSystem()">

    <!-- হেডলাইন ও ব্যাক বাটন -->
    <div class="flex justify-between items-center bg-white p-3 rounded-xl shadow-sm mb-3">
        <h2 class="text-base sm:text-lg font-bold text-gray-800 flex items-center gap-2">
            <span>ইনভয়েস এডিট:</span>
            <span class="pos-mono" style="color:var(--pos-primary)">#{{ $sale->invoice_no }}</span>
        </h2>
        <a href="{{ route('pos.sales.index') }}" class="bg-gray-700 hover:bg-gray-800 text-white text-xs sm:text-sm font-bold px-3 py-2 rounded-lg transition">
            ← তালিকায় ফিরে যান
        </a>
    </div>

    <!-- মোবাইল স্ক্রিনের জন্য টগল ট্যাব (পণ্য / কার্ট) -->
    <div class="flex lg:hidden bg-white rounded-xl shadow-sm mb-3 p-1 sticky top-0 z-20">
        <button @click="activeTab = 'products'" :class="activeTab === 'products' ? 'text-white' : 'text-gray-600'" :style="activeTab === 'products' ? 'background:var(--pos-primary)' : ''" class="flex-1 py-2.5 text-center font-bold text-sm rounded-lg transition">
            পণ্য তালিকা
        </button>
        <button @click="activeTab = 'cart'" :class="activeTab === 'cart' ? 'text-white' : 'text-gray-600'" :style="activeTab === 'cart' ? 'background:var(--pos-primary)' : ''" class="flex-1 py-2.5 text-center font-bold text-sm rounded-lg transition relative">
            কার্ট
            <span x-show="cart.length > 0" x-text="cart.length" class="ml-1 text-white text-xs px-2 py-0.5 rounded-full" style="background:var(--pos-danger)"></span>
        </button>
    </div>

    <div class="flex flex-col lg:flex-row gap-4 h-auto lg:h-[calc(100vh-140px)]">

        <!-- বাম পাশ: পণ্য তালিকা ও ফিল্টার -->
        <div class="w-full lg:w-7/12 p-3 sm:p-4 flex flex-col h-full bg-white rounded-xl shadow-sm" :class="activeTab === 'products' ? 'block' : 'hidden lg:flex'">
            <!-- সার্চ ও ক্যাটাগরি -->
            <div class="mb-4 space-y-3">
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="পণ্য খুঁজুন (নাম দিয়ে)..." class="w-full p-2.5 sm:p-3 pr-9 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 text-sm sm:text-base" style="--tw-ring-color:var(--pos-primary)">
                    <button x-show="searchQuery" @click="searchQuery = ''" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 font-bold">×</button>
                </div>

                <div class="flex space-x-2 overflow-x-auto pb-1 scrollbar-thin">
                    <button @click="selectedCategory = null"
                            :class="selectedCategory === null ? 'text-white' : 'bg-gray-100 text-gray-700'"
                            :style="selectedCategory === null ? 'background:var(--pos-primary)' : ''"
                            class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full font-semibold text-xs sm:text-sm whitespace-nowrap transition">সব পণ্য</button>
                    @foreach($categories as $cat)
                        <button @click="selectedCategory = {{ $cat->id }}"
                                :class="selectedCategory === {{ $cat->id }} ? 'text-white' : 'bg-gray-100 text-gray-700'"
                                :style="selectedCategory === {{ $cat->id }} ? 'background:var(--pos-primary)' : ''"
                                class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full font-semibold text-xs sm:text-sm whitespace-nowrap transition">
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- প্রোডাক্ট গ্রিড -->
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-3 overflow-y-auto flex-1 pr-1 max-h-[560px] lg:max-h-full">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div class="border border-gray-100 rounded-xl p-3 bg-gray-50/60 flex flex-col justify-between shadow-sm relative hover:shadow-md transition">

                        <!-- স্টক অ্যালার্ট ব্যাজ (কার্টে ইতিমধ্যে যোগ হওয়া পরিমাণ বাদ দিয়ে রিয়েল-টাইম অবশিষ্ট স্টক দেখায়) -->
                        <div class="absolute top-2 right-2">
                            <template x-if="remainingStock(product) <= 0">
                                <span class="text-white text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:var(--pos-danger)">স্টক আউট</span>
                            </template>
                            <template x-if="remainingStock(product) > 0 && remainingStock(product) <= 2000">
                                <span class="text-white text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:var(--pos-accent)" x-text="'কম স্টক (' + formatStock(remainingStock(product)) + ')'"></span>
                            </template>
                            <template x-if="remainingStock(product) > 2000">
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" style="background:var(--pos-primary-light); color:var(--pos-primary-dark)" x-text="'স্টক: ' + formatStock(remainingStock(product))"></span>
                            </template>
                        </div>

                        <div>
                            <img :src="product.image ? '/storage/' + product.image : 'https://via.placeholder.com/150'" class="h-20 sm:h-24 w-full object-cover rounded-lg mb-2" loading="lazy">
                            <h4 class="font-bold text-gray-800 text-sm sm:text-md leading-tight" x-text="product.name"></h4>

                            <!-- ⬇️ এখন প্রোডাক্টে যেসব সাইজের দাম সেট করা আছে (>0), শুধু সেগুলোই দেখানো হয় -->
                            <div class="flex flex-wrap gap-x-2 gap-y-0.5 text-[10px] sm:text-[11px] text-gray-500 mt-1 pos-mono">
                                <template x-for="ut in availableUnits(product)" :key="'price-' + product.id + '-' + ut.key">
                                    <span><span x-text="ut.shortLabel"></span>: <span class="font-bold text-gray-700">৳<span x-text="product[ut.priceField]"></span></span></span>
                                </template>
                            </div>
                        </div>

                        <!-- ⬇️ আগে শুধু ১কেজি/৫০০গ্রাম দুটো বাটন ছিল — এখন প্রোডাক্টে যে ৬টা
                             সাইজের (৫কেজি, ১কেজি, ৫০০গ্রা, ২৫০গ্রা, ১০০গ্রা, ৫০গ্রা) দাম সেট আছে
                             সেগুলোর জন্যই বাটন তৈরি হয়, এবং unit_type কী এখন ব্যাকএন্ডের
                             updateSale ভ্যালিডেশনের (5kg,1kg,500g,250g,100g,50g) সাথে হুবহু মিলিয়ে পাঠানো হয় -->
                        <div class="grid grid-cols-3 gap-1 mt-3">
                            <template x-for="ut in availableUnits(product)" :key="'btn-' + product.id + '-' + ut.key">
                                <button @click="addToCart(product, ut.key)" :disabled="remainingStock(product) < ut.grams"
                                        class="text-white py-1.5 px-1 rounded-lg text-[10px] sm:text-[11px] font-bold disabled:opacity-30 disabled:cursor-not-allowed active:scale-95 transition"
                                        :style="'background:' + (ut.grams >= 1000 ? 'var(--pos-primary)' : 'var(--pos-accent)')">
                                    + <span x-text="ut.shortLabel"></span>
                                </button>
                            </template>
                        </div>
                        <div x-show="availableUnits(product).length === 0" class="text-[10px] text-gray-400 text-center mt-3 py-1.5">
                            কোনো দাম সেট করা নেই
                        </div>
                    </div>
                </template>

                <div x-show="filteredProducts.length === 0" class="col-span-full text-center text-gray-400 text-sm py-10">
                    কোনো পণ্য পাওয়া যায়নি
                </div>
            </div>
        </div>

        <!-- ডান পাশ: কার্ট ও আপডেট অপশন -->
        <div class="w-full lg:w-5/12 flex flex-col justify-between h-full bg-white rounded-xl p-3 sm:p-4 shadow-sm pos-receipt-edge" :class="activeTab === 'cart' ? 'block' : 'hidden lg:flex'">

            <!-- কাস্টমার লাইভ সার্চ সিলেক্টর -->
            <div class="relative mb-3" x-data="{ open: false }">
                <label class="block text-xs font-bold text-gray-600 mb-1">কাস্টমার সিলেক্ট করুন</label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input type="text"
                               x-model="customerSearchText"
                               @focus="open = true"
                               @click.outside="open = false"
                               placeholder="নাম বা মোবাইল দিয়ে কাস্টমার খুঁজুন..."
                               class="w-full p-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2" style="--tw-ring-color:var(--pos-primary)">

                        <button x-show="selectedCustomer" @click="resetCustomer()" type="button" class="absolute right-2 top-2 text-gray-400 font-bold hover:text-red-500">×</button>

                        <!-- ড্রপডাউন সার্চ রেজাল্ট -->
                        <div x-show="open" class="absolute z-30 left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <div @click="selectCustomer(null); open = false" class="p-2 hover:bg-gray-50 cursor-pointer border-b text-sm font-semibold" style="color:var(--pos-primary)">
                                General Customer / Walk-in
                            </div>
                            <template x-for="cust in filteredCustomers" :key="cust.id">
                                <div @click="selectCustomer(cust); open = false" class="p-2 hover:bg-gray-50 cursor-pointer text-sm border-b">
                                    <div class="font-bold text-gray-800" x-text="cust.name"></div>
                                    <div class="text-xs text-gray-500 pos-mono" x-text="cust.phone + (cust.current_due > 0 ? ' (বাকি: ৳' + cust.current_due + ')' : '')"></div>
                                </div>
                            </template>
                            <div x-show="filteredCustomers.length === 0" class="p-2 text-xs text-gray-500 text-center">
                                কোনো কাস্টমার পাওয়া যায়নি
                            </div>
                        </div>
                    </div>
                    <button @click="showCustomerModal = true" type="button" class="bg-gray-800 text-white px-3 py-2 rounded-lg hover:bg-gray-900 font-bold text-xs whitespace-nowrap">
                        + নতুন
                    </button>
                </div>
            </div>
            <!-- বিক্রির তারিখ ও সময় (ম্যানুয়ালী পরিবর্তনযোগ্য) -->
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-600 mb-1">বিক্রির তারিখ ও সময়</label>
                <input type="datetime-local"
                    x-model="saleDate"
                    class="w-full p-2 border border-gray-200 rounded-lg text-sm pos-mono focus:outline-none focus:ring-2"
                    style="--tw-ring-color:var(--pos-primary)">
            </div>

            <!-- কার্ট আইটেম তালিকা -->
            <div class="bg-gray-50/60 rounded-xl border border-gray-100 flex-1 p-2 overflow-y-auto mb-3 max-h-72 lg:max-h-full pos-cart-scroll-area">
                <table class="w-full text-left border-collapse" x-show="cart.length > 0">
                    <thead>
                        <tr class="border-b text-[11px] sm:text-xs text-gray-500 uppercase">
                            <th class="pb-2">পণ্য</th>
                            <th class="pb-2 text-center">সাইজ</th>
                            <th class="pb-2 text-center">পরিমাণ (কেজি)</th>
                            <th class="pb-2 text-center">বিক্রি মূল্য (৳)</th>
                            <th class="pb-2 text-right">মোট</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody class="text-xs sm:text-sm">
                        <template x-for="(item, index) in cart" :key="index">
                            <tr class="border-b bg-white">
                                <td class="py-2 px-1 font-medium text-gray-800" x-text="item.product.name"></td>
                                <td class="py-2 text-center text-xs text-gray-500" x-text="unitLabel(item.unit_type)"></td>
                                <td class="py-2 text-center">
                                    <div class="flex items-center justify-center space-x-1">
                                        <button @click="updateQty(index, -1)" class="w-6 h-6 bg-gray-200 rounded font-bold text-xs active:scale-90 transition">-</button>
                                        <input type="number"
                                               inputmode="numeric"
                                               min="1"
                                               step="1"
                                               :value="item.quantity"
                                               @change="setQtyDirect(index, $event.target.value)"
                                               @click="$event.target.select()"
                                               class="w-12 border rounded text-center p-1 text-xs font-bold pos-mono bg-white focus:bg-yellow-50">
                                        <button @click="updateQty(index, 1)" class="w-6 h-6 bg-gray-200 rounded font-bold text-xs active:scale-90 transition">+</button>
                                    </div>
                                </td>
                                <td class="py-2 text-center">
                                    <input type="number" step="0.01" min="0" x-model.number="item.applied_price" class="w-16 border rounded text-center p-1 text-xs font-bold pos-mono bg-yellow-50 focus:bg-white" style="color:var(--pos-primary-dark)">
                                </td>
                                <td class="py-2 text-right font-bold text-gray-800 pos-mono" x-text="'৳' + ((item.applied_price || 0) * item.quantity).toFixed(2)"></td>
                                <td class="py-2 text-center">
                                    <button @click="removeItem(index)" class="text-red-500 font-bold hover:text-red-700 ml-1 text-base leading-none">×</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div x-show="cart.length === 0" class="text-center text-gray-400 text-sm py-10">
                    কার্ট খালি — বাম পাশ থেকে পণ্য যুক্ত করুন
                </div>
            </div>

            <!-- হিসাব ও আপডেট বাটন — সব স্ক্রিনে কার্ট তালিকার ঠিক পরে normal flow-এ (fixed না রেখে) দেখানো হচ্ছে -->
            <div class="space-y-2 text-xs sm:text-sm">
                <div class="flex justify-between text-gray-700">
                    <span>সাব-টোটাল:</span>
                    <span class="font-bold pos-mono">৳<span x-text="subtotal.toFixed(2)"></span></span>
                </div>

                <!-- ডিসকাউন্ট -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <select x-model="discountType" class="w-full border border-gray-200 p-1.5 rounded-lg text-xs">
                            <option value="flat">Flat (৳)</option>
                            <option value="percentage">Percentage (%)</option>
                        </select>
                    </div>
                    <div>
                        <input type="number" x-model.number="discountValue" placeholder="ছাড়" min="0" :max="discountType === 'percentage' ? 100 : null" class="w-full border border-gray-200 p-1.5 rounded-lg text-xs pos-mono">
                    </div>
                </div>

                <div class="flex justify-between text-base sm:text-lg font-bold text-gray-900 border-t pt-2">
                    <span>সর্বমোট:</span>
                    <span class="pos-mono" style="color:var(--pos-primary)">৳<span x-text="grandTotal.toFixed(2)"></span></span>
                </div>

                <!-- জমা (read-only) ও বাকি -->
                <div class="grid grid-cols-2 gap-2 border-t pt-2">
                    <div>
                        <label class="block text-[10px] sm:text-xs text-gray-500 font-bold">জমা (Paid)</label>
                        <input type="text" readonly :value="'৳' + Number(paidAmount).toFixed(2)" class="w-full border border-gray-200 p-1.5 rounded-lg text-sm sm:text-md font-bold pos-mono bg-gray-50 cursor-not-allowed" style="color:var(--pos-primary-dark)">
                        <p class="text-[10px] text-gray-400 mt-0.5">অতিরিক্ত জমা নিতে হলে "বকেয়া পেমেন্ট" পেজ ব্যবহার করুন</p>
                    </div>
                    <div>
                        <label class="block text-[10px] sm:text-xs text-gray-500 font-bold">বাকি (Due)</label>
                        <input type="text" readonly :value="'৳' + dueAmount.toFixed(2)" class="w-full border border-gray-200 p-1.5 rounded-lg text-sm sm:text-md font-bold pos-mono bg-gray-50" style="color:var(--pos-danger)">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2">
                    <a href="{{ route('pos.sales.index') }}" class="bg-gray-200 text-gray-700 py-2.5 rounded-xl font-bold hover:bg-gray-300 text-center block transition">বাতিল করুন</a>
                    <button @click="updateSale()" :disabled="cart.length === 0 || isSubmitting"
                            class="text-white py-2.5 rounded-xl font-bold disabled:opacity-50 flex items-center justify-center gap-2 transition" style="background:var(--pos-primary)">
                        <svg x-show="isSubmitting" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'সেভ হচ্ছে...' : 'আপডেট সম্পন্ন করুন'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- কাস্টমার এনট্রি মোডাল -->
    <div x-show="showCustomerModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[1100]" x-cloak>
        <div class="bg-white rounded-xl p-5 w-full max-w-md space-y-3">
            <h3 class="text-base sm:text-lg font-bold text-gray-800 border-b pb-2">নতুন কাস্টমার যুক্ত করুন</h3>
            <div>
                <label class="block text-xs font-bold mb-1">কাস্টমারের নাম <span class="text-red-500">*</span></label>
                <input type="text" x-model="newCustomer.name" placeholder="যেমন: আব্দুর রহিম" class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1">মোবাইল নম্বর <span class="text-red-500">*</span></label>
                <input type="text" x-model="newCustomer.phone" placeholder="017xxxxxxxx" class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold mb-1">ঠিকানা</label>
                <textarea x-model="newCustomer.address" placeholder="গ্রাম/রাস্তা, থানা, জেলা" class="w-full border rounded-lg p-2 text-sm" rows="2"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold mb-1">ওপেনিং বাকি (যদি থাকে)</label>
                <input type="number" step="0.01" min="0" x-model.number="newCustomer.opening_due" placeholder="0.00" class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div class="flex justify-end space-x-2 pt-2 border-t">
                <button @click="showCustomerModal = false" type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm">বাতিল</button>
                <button @click="saveCustomer()" type="button" :disabled="isSavingCustomer" class="px-4 py-2 text-white rounded-lg font-bold text-sm disabled:opacity-50" style="background:var(--pos-primary)">
                    <span x-text="isSavingCustomer ? 'সংরক্ষণ হচ্ছে...' : 'সংরক্ষণ করুন'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')

<script>
    // ⚠️ Tailwind-এর 'lg' ব্রেকপয়েন্ট (ডিফল্ট 1024px) কে main.blade.php-এর
    // Bootstrap সাইডবার ব্রেকপয়েন্টের (992px) সাথে মিলিয়ে দেওয়া হলো, যাতে
    // 992px–1024px রেঞ্জে সাইডবার ও এই পেজের লেআউট একসাথে একই পয়েন্টে সুইচ করে।
    tailwind.config = {
        theme: {
            extend: {
                screens: {
                    lg: '992px'
                }
            }
        }
    };
</script>


<script>
    function posEditSystem() {
        return {
            activeTab: 'cart', // এডিট পেজে শুরুতে কার্ট ট্যাবে ফোকাস থাকবে
            products: @json($products),
            customers: @json($customers),
            searchQuery: '',
            selectedCategory: null,

            // ⬇️ নতুন — ৬টা প্যাকেট সাইজের কনফিগ, ব্যাকএন্ডের updateSale ভ্যালিডেশনের
            // 'items.*.unit_type' => 'required|in:5kg,1kg,500g,250g,100g,50g' এর সাথে
            // key হুবহু মিলে যায় (আগে এখানে 'half_kg' ব্যবহার হতো, যেটা ব্যাকএন্ডে
            // কখনোই ভ্যালিড ছিল না — প্রতিটি আপডেটই এরর দিতো)। priceField প্রতিটা
            // key-কে Product মডেলের সংশ্লিষ্ট দামের কলামের সাথে ম্যাপ করে।
            unitTypes: [
                { key: '5kg',  label: '৫ কেজি',    shortLabel: '৫কেজি',   grams: 5000, priceField: 'price_5kg' },
                { key: '1kg',  label: '১ কেজি',    shortLabel: '১কেজি',   grams: 1000, priceField: 'price_1kg' },
                { key: '500g', label: '৫০০ গ্রাম', shortLabel: '৫০০গ্রা', grams: 500,  priceField: 'price_half_kg' },
                { key: '250g', label: '২৫০ গ্রাম', shortLabel: '২৫০গ্রা', grams: 250,  priceField: 'price_250g' },
                { key: '100g', label: '১০০ গ্রাম', shortLabel: '১০০গ্রা', grams: 100,  priceField: 'price_100g' },
                { key: '50g',  label: '৫০ গ্রাম',  shortLabel: '৫০গ্রা',  grams: 50,   priceField: 'price_50g' },
            ],

            // পূর্বে সংরক্ষিত সেল ডাটা
            saleId: {{ $sale->id }},
            customerSearchText: '{{ $sale->customer ? addslashes($sale->customer->name . " (" . $sale->customer->phone . ")") : "" }}',
            selectedCustomer: @json($sale->customer ?? null),
            selectedCustomerId: {{ $sale->customer_id ?? 'null' }},

            // পূর্বে কার্টে থাকা পণ্য লোড
            cart: @json($sale->items).map(item => ({
                product: item.product,
                unit_type: item.unit_type,
                applied_price: parseFloat(item.applied_price || item.unit_price || 0),
                quantity: parseInt(item.quantity)
            })),

            discountType: '{{ $sale->discount_type ?? "flat" }}',
            discountValue: {{ $sale->discount_value ?? 0 }},
            saleDate: '{{ $sale->sale_date ? $sale->sale_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}',

            // ⚠️ এটা এখন শুধু দেখানোর জন্য (read-only) — সার্ভার থেকে আসা প্রকৃত জমার পরিমাণ।
            // এই মান কখনো ফর্ম থেকে সরাসরি পাঠানো হয় না; সার্ভার নিজেই এটা নিয়ন্ত্রণ করে
            // (কাস্টমার বকেয়া-পেমেন্ট জমা দিলে এই মান স্বয়ংক্রিয়ভাবে আপডেট হয়ে যায়)।
            paidAmount: {{ $sale->paid_amount ?? 0 }},

            showCustomerModal: false,
            newCustomer: { name: '', phone: '', address: '', opening_due: 0 },

            isSubmitting: false,
            isSavingCustomer: false,

            get filteredProducts() {
                const q = this.searchQuery.trim().toLowerCase();
                return this.products.filter(product => {
                    const matchesCategory = !this.selectedCategory || product.category_id === this.selectedCategory;
                    const matchesSearch = !q || product.name.toLowerCase().includes(q);
                    return matchesCategory && matchesSearch;
                });
            },

            get filteredCustomers() {
                if (!this.customerSearchText) {
                    return this.customers.slice(0, 30);
                }
                const query = this.customerSearchText.toLowerCase();
                return this.customers.filter(c =>
                    c.name.toLowerCase().includes(query) || c.phone.includes(query)
                ).slice(0, 50);
            },

            selectCustomer(customer) {
                if (!customer) {
                    this.selectedCustomer = null;
                    this.selectedCustomerId = null;
                    this.customerSearchText = '';
                } else {
                    this.selectedCustomer = customer;
                    this.selectedCustomerId = customer.id;
                    this.customerSearchText = customer.name + ' (' + customer.phone + ')';
                }
            },

            resetCustomer() {
                this.selectedCustomer = null;
                this.selectedCustomerId = null;
                this.customerSearchText = '';
            },

            formatStock(grams) {
                if (grams >= 1000) {
                    return (grams / 1000).toFixed(1) + ' কেজি';
                }
                return Math.max(0, grams) + ' গ্রাম';
            },

            // ── ইউনিট-টাইপ হেল্পার ──────────────────────────────────────
            unitConfig(unitType) {
                return this.unitTypes.find(u => u.key === unitType) || null;
            },

            gramsForUnit(unitType) {
                const u = this.unitConfig(unitType);
                return u ? u.grams : 0;
            },

            unitLabel(unitType) {
                const u = this.unitConfig(unitType);
                return u ? u.label : unitType;
            },

            // এই প্রোডাক্টে যে সাইজগুলোর দাম বসানো আছে (>0), শুধু সেগুলোই বাটন হিসেবে দেখানো হয়
            availableUnits(product) {
                return this.unitTypes.filter(u => parseFloat(product[u.priceField] || 0) > 0);
            },

            priceForUnit(product, unitType) {
                const u = this.unitConfig(unitType);
                if (!u) return 0;
                return parseFloat(product[u.priceField] || 0);
            },

            // এই পণ্যটা কার্টে (যেকোনো ইউনিটে) মোট কত গ্রাম ইতিমধ্যে যোগ করা হয়েছে
            cartWeightForProduct(productId) {
                return this.cart.reduce((sum, item) => {
                    if (item.product.id !== productId) return sum;
                    return sum + (this.gramsForUnit(item.unit_type) * item.quantity);
                }, 0);
            },

            // ⚠️ পণ্য-বিক্রয় (POS Sales Screen) পেজের মতোই — শুধু product.stock_in_grams নয়,
            // কার্টে ইতিমধ্যে কতটা যোগ হয়েছে তা বাদ দিয়ে রিয়েল-টাইম অবশিষ্ট স্টক হিসাব করা হয়,
            // যাতে স্টকের চেয়ে বেশি বিক্রি করে ফেলার সুযোগ না থাকে।
            remainingStock(product) {
                return product.stock_in_grams - this.cartWeightForProduct(product.id);
            },

            addToCart(product, unitType) {
                const grams = this.gramsForUnit(unitType);
                if (this.remainingStock(product) < grams) {
                    this.notifyStockLimit(product);
                    return;
                }

                const defaultPrice = this.priceForUnit(product, unitType);
                const existingIndex = this.cart.findIndex(item => item.product.id === product.id && item.unit_type === unitType);

                if (existingIndex > -1) {
                    this.cart[existingIndex].quantity++;
                } else {
                    this.cart.push({
                        product: product,
                        unit_type: unitType,
                        applied_price: defaultPrice,
                        quantity: 1
                    });
                }

                // মোবাইলে পণ্য যোগ করার সাথে সাথে দ্রুত ফিডব্যাক
                if (window.innerWidth < 992 && typeof toastr !== 'undefined') {
                    toastr.success(product.name + ' (' + this.unitLabel(unitType) + ') কার্টে যোগ হয়েছে', '', { timeOut: 1000 });
                }
            },

            updateQty(index, delta) {
                const item = this.cart[index];
                if (delta > 0) {
                    const product = this.products.find(p => p.id === item.product.id) || item.product;
                    if (this.remainingStock(product) < this.gramsForUnit(item.unit_type)) {
                        this.notifyStockLimit(product);
                        return;
                    }
                }
                item.quantity += delta;
                if (item.quantity <= 0) {
                    this.cart.splice(index, 1);
                }
            },

            // পরিমাণ ফিল্ডে সরাসরি সংখ্যা টাইপ করে বসালে (দামের ফিল্ডের মতো) — স্টক লিমিটের
            // মধ্যে রেখে সেট করে, বেশি হলে সর্বোচ্চ সম্ভব পরিমাণে ক্ল্যাম্প করে দেয়।
            setQtyDirect(index, rawValue) {
                const item = this.cart[index];
                if (!item) return;

                const product = this.products.find(p => p.id === item.product.id) || item.product;
                const unitGrams = this.gramsForUnit(item.unit_type);

                let qty = parseInt(rawValue, 10);
                if (!Number.isFinite(qty) || qty < 1) {
                    qty = 1;
                }

                // এই আইটেম বাদে কার্টে একই প্রোডাক্টের বাকি সব এন্ট্রি মিলিয়ে কত গ্রাম আছে
                const otherGrams = this.cart.reduce((sum, cartItem, i) => {
                    if (i === index || cartItem.product.id !== product.id) return sum;
                    return sum + (this.gramsForUnit(cartItem.unit_type) * cartItem.quantity);
                }, 0);

                const maxQty = Math.max(1, Math.floor((product.stock_in_grams - otherGrams) / unitGrams));

                if (qty > maxQty) {
                    qty = maxQty;
                    this.notifyStockLimit(product);
                }

                item.quantity = qty;
            },

            removeItem(index) {
                this.cart.splice(index, 1);
            },

            notifyStockLimit(product) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'পর্যাপ্ত স্টক নেই',
                        text: `'${product.name}' এর অবশিষ্ট স্টক শেষ, আর যোগ করা যাবে না।`,
                        confirmButtonColor: '#0F6F4C'
                    });
                }
            },

            get subtotal() {
                return this.cart.reduce((sum, item) => sum + ((parseFloat(item.applied_price) || 0) * item.quantity), 0);
            },

            get discountAmount() {
                if (this.discountType === 'flat') {
                    return parseFloat(this.discountValue) || 0;
                }
                return (this.subtotal * (parseFloat(this.discountValue) || 0)) / 100;
            },

            get grandTotal() {
                return Math.max(0, this.subtotal - this.discountAmount);
            },

            // এটা শুধুই একটা প্রিভিউ — চূড়ান্ত সঠিক due সবসময় সেভ করার পর সার্ভার থেকেই আসে
            get dueAmount() {
                return Math.max(0, this.grandTotal - (parseFloat(this.paidAmount) || 0));
            },

            async saveCustomer() {
                if (!this.newCustomer.name || !this.newCustomer.phone) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'সতর্কতা',
                        text: 'কাস্টমারের নাম ও মোবাইল নম্বর দেওয়া আবশ্যক!',
                        confirmButtonColor: '#0F6F4C'
                    });
                    return;
                }

                if (this.isSavingCustomer) return;
                this.isSavingCustomer = true;

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch("{{ route('pos.customer.store') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": csrfToken
                        },
                        body: JSON.stringify(this.newCustomer)
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        this.customers.unshift(result.customer);
                        this.selectCustomer(result.customer);
                        this.showCustomerModal = false;
                        this.newCustomer = { name: '', phone: '', address: '', opening_due: 0 };

                        Swal.fire({
                            icon: 'success',
                            title: 'সফল!',
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        let errorMessage = result.message || 'কাস্টমার যুক্ত করা সম্ভব হয়নি';
                        if (result.errors) {
                            errorMessage = Object.values(result.errors).flat().join('<br>');
                        }
                        Swal.fire({ icon: 'error', title: 'ব্যর্থ!', html: errorMessage, confirmButtonColor: '#C6413A' });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ত্রুটি!',
                        text: 'কাস্টমার সেভ করার সময় সিস্টেম এরর ঘটেছে! ইন্টারনেট সংযোগ পরীক্ষা করুন।',
                        confirmButtonColor: '#C6413A'
                    });
                } finally {
                    this.isSavingCustomer = false;
                }
            },

            async updateSale() {
                if (this.isSubmitting) return; // ডাবল-ক্লিক/ডাবল-সাবমিট প্রতিরোধ

                if (!this.selectedCustomerId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'কাস্টমার নির্বাচন করুন',
                        text: 'বিক্রি আপডেট করার আগে অনুগ্রহ করে একজন কাস্টমার সিলেক্ট করুন!',
                        confirmButtonColor: '#0F6F4C'
                    });
                    this.activeTab = 'cart';
                    return;
                }

                if (this.cart.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'কার্ট খালি',
                        text: 'কার্টে অন্তত একটি পণ্য যুক্ত করুন!',
                        confirmButtonColor: '#0F6F4C'
                    });
                    this.activeTab = 'products';
                    return;
                }

                // ⚠️ paid_amount ইচ্ছাকৃতভাবে payload-এ নেই — এটা সার্ভার নিজে হিসাব করে বসায়,
                // যাতে কাস্টমারের বকেয়া-পেমেন্ট থেকে আসা জমা কখনো এই ফর্মের মাধ্যমে মুছে না যায়।
                const payload = {
                    customer_id: this.selectedCustomerId,
                    items: this.cart.map(item => ({
                        product_id: item.product.id,
                        unit_type: item.unit_type, // ⬅️ এখন 5kg/1kg/500g/250g/100g/50g — ব্যাকএন্ডের এনামের সাথে হুবহু মিলে
                        quantity: item.quantity,
                        applied_price: item.applied_price
                    })),
                    discount_type: this.discountType,
                    discount_value: this.discountValue,
                    sale_date: this.saleDate,
                };

                this.isSubmitting = true;

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    const response = await fetch(`/admin/sales/${this.saleId}`, {
                        method: "PUT",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": csrfToken
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'সফল!',
                            text: result.message || 'বিক্রয়ের তথ্য সফলভাবে আপডেট করা হয়েছে!',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = "{{ route('pos.sales.index') }}";
                        });
                    } else {
                        let errorMessage = result.message || 'আপডেট করা সম্ভব হয়নি';
                        if (result.errors) {
                            errorMessage = Object.values(result.errors).flat().join('<br>');
                        }
                        Swal.fire({ icon: 'error', title: 'ব্যর্থ!', html: errorMessage, confirmButtonColor: '#C6413A' });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ত্রুটি!',
                        text: 'সার্ভারে সমস্যা হয়েছে, পুনরায় চেষ্টা করুন! ইন্টারনেট সংযোগ পরীক্ষা করুন।',
                        confirmButtonColor: '#C6413A'
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },
        }
    }
</script>
@endpush