@extends('admin.layouts.main')

@section('title', 'POS Sales Screen')

@section('content')

{{-- ⚠️ এই CSS ইচ্ছাকৃতভাবে @push('style') এর বদলে এখানে সরাসরি বসানো হলো —
     লেআউটের <head>-এ @stack('style') না থাকলেও (শুধু @stack('js') থাকলেও)
     এটা নিশ্চিতভাবে লোড হবে, কারণ @section('content') সবসময় রেন্ডার হয়। --}}
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

<div class="container-fluid p-2 sm:p-4 min-h-screen" style="background:var(--pos-bg); color:var(--pos-ink);" x-data="posSystem()">

    <!-- মোবাইল টগল ট্যাব -->
    <div class="flex lg:hidden bg-white rounded-xl shadow-sm mb-3 p-1 sticky top-0 z-20">
        <button @click="activeTab = 'products'" :class="activeTab === 'products' ? 'text-white' : 'text-gray-600'" :style="activeTab === 'products' ? 'background:var(--pos-primary)' : ''" class="flex-1 py-2.5 text-center font-bold text-sm rounded-lg transition">
            পণ্য তালিকা
        </button>
        <button @click="activeTab = 'cart'" :class="activeTab === 'cart' ? 'text-white' : 'text-gray-600'" :style="activeTab === 'cart' ? 'background:var(--pos-primary)' : ''" class="flex-1 py-2.5 text-center font-bold text-sm rounded-lg transition relative">
            কার্ট
            <span x-show="cart.length > 0" x-text="cart.length" class="ml-1 text-white text-xs px-2 py-0.5 rounded-full" style="background:var(--pos-danger)"></span>
        </button>
    </div>

    <div class="flex flex-col lg:flex-row gap-4 h-auto lg:h-[calc(100vh-100px)]">

        <!-- বাম পাশ: পণ্য তালিকা ও ফিল্টার -->
        <div class="w-full lg:w-7/12 p-3 sm:p-4 flex flex-col h-full bg-white rounded-xl shadow-sm" :class="activeTab === 'products' ? 'block' : 'hidden lg:flex'">
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

            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-3 overflow-y-auto flex-1 pr-1 max-h-[560px] lg:max-h-full">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div class="border border-gray-100 rounded-xl p-3 bg-gray-50/60 flex flex-col justify-between shadow-sm relative hover:shadow-md transition">

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
                             ভ্যালিডেশনের (5kg,1kg,500g,250g,100g,50g) সাথে হুবহু মিলিয়ে পাঠানো হয় -->
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

        <!-- ডান পাশ: কার্ট ও চেকআউট -->
        <div class="w-full lg:w-5/12 flex flex-col justify-between h-full bg-white rounded-xl p-3 sm:p-4 shadow-sm pos-receipt-edge" :class="activeTab === 'cart' ? 'block' : 'hidden lg:flex'">

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

                        <div x-show="open" class="absolute z-30 left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <div @click="selectCustomer(null); open = false" class="p-2 hover:bg-gray-50 cursor-pointer border-b text-sm font-semibold" style="color:var(--pos-primary)">
                               আপনার কাস্টমার সিলেক্ট করুন (নতুন কাস্টমার যুক্ত করতে '+ নতুন' বাটন ব্যবহার করুন)
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

            <div class="bg-gray-50/60 rounded-xl border border-gray-100 flex-1 p-2 overflow-y-auto mb-3 max-h-72 lg:max-h-full pos-cart-scroll-area">
                <table class="w-full text-left border-collapse" x-show="cart.length > 0">
                    <thead>
                        <tr class="border-b text-[11px] sm:text-xs text-gray-500 uppercase">
                            <th class="pb-2">পণ্য</th>
                            <th class="pb-2 text-center">সাইজ</th>
                            <th class="pb-2 text-center">পরিমাণ (কেজি)</th>
                            <th class="pb-2 text-center">মূল্য (৳)</th>
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

            <!-- হিসাব ও চেকআউট (সাব-টোটাল, ডিসকাউন্ট, সর্বমোট, জমা/বাকি, অ্যাকশন বাটন)
                 ⚠️ এটি আগে শুধু ডেস্কটপে (lg:block) দেখানো হতো এবং মোবাইলে আলাদা একটা
                 fixed bottom bar ছিল। এখন এই একই সেকশন সব স্ক্রিনে কার্ট তালিকার ঠিক
                 পরে normal flow-এ (fixed না রেখে) দেখানো হচ্ছে। -->
            <div class="space-y-2 text-xs sm:text-sm">
                <div class="flex justify-between text-gray-700">
                    <span>সাব-টোটাল:</span>
                    <span class="font-bold pos-mono">৳<span x-text="subtotal.toFixed(2)"></span></span>
                </div>

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

                <div class="grid grid-cols-2 gap-2 border-t pt-2">
                    <div>
                        <label class="block text-[10px] sm:text-xs text-gray-500 font-bold">জমা (Paid)</label>
                        <input type="number" x-model.number="paidAmount" min="0" :max="grandTotal" @input="clampPaidAmount()" class="w-full border border-gray-200 p-1.5 rounded-lg text-sm sm:text-md font-bold pos-mono" style="color:var(--pos-primary-dark)">
                    </div>
                    <div>
                        <label class="block text-[10px] sm:text-xs text-gray-500 font-bold">বাকি (Due)</label>
                        <input type="text" readonly :value="'৳' + dueAmount.toFixed(2)" class="w-full border border-gray-200 p-1.5 rounded-lg text-sm sm:text-md font-bold pos-mono bg-gray-50" style="color:var(--pos-danger)">
                    </div>
                </div>

                <div class="flex gap-1.5">
                    <button @click="setPaid(0)" type="button" class="flex-1 text-[10px] font-bold py-1.5 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">বাকি রাখুন</button>
                    <button @click="setPaid(grandTotal / 2)" type="button" class="flex-1 text-[10px] font-bold py-1.5 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">অর্ধেক জমা</button>
                    <button @click="setPaid(grandTotal)" type="button" class="flex-1 text-[10px] font-bold py-1.5 rounded-full" style="background:var(--pos-primary-light); color:var(--pos-primary-dark)">সম্পূর্ণ জমা</button>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2">
                    <button @click="cart = []" class="bg-gray-200 text-gray-700 py-2.5 rounded-xl font-bold hover:bg-gray-300 transition">ক্লিয়ার কার্ট</button>
                    <button @click="processSale()" :disabled="cart.length === 0 || isSubmitting"
                            class="text-white py-2.5 rounded-xl font-bold disabled:opacity-50 flex items-center justify-center gap-2 transition" style="background:var(--pos-primary)">
                        <svg x-show="isSubmitting" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'সেভ হচ্ছে...' : 'বিক্রি সম্পন্ন করুন'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- নতুন কাস্টমার মোডাল -->
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

    <!-- বিক্রি সফল হওয়ার পর অ্যাকশন-বেছে-নিন মডাল -->
    <div x-show="showSaleSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[1100]" x-cloak>
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm space-y-5 text-center">

            <!-- সফল আইকন -->
            <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center" style="background:var(--pos-primary-light)">
                <svg class="w-9 h-9" style="color:var(--pos-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>

            <div>
                <h3 class="text-lg font-bold text-gray-800">বিক্রি সফলভাবে সম্পন্ন হয়েছে!</h3>
                <p class="text-sm text-gray-500 pos-mono mt-1">
                    ইনভয়েস: <span class="font-bold" x-text="completedSale?.invoice_no"></span>
                </p>
                <p class="text-sm text-gray-500 mt-0.5">
                    সর্বমোট: <span class="font-bold pos-mono" style="color:var(--pos-primary-dark)">৳<span x-text="Number(completedSale?.net_amount || 0).toFixed(2)"></span></span>
                    <template x-if="Number(completedSale?.due_amount || 0) > 0">
                        <span> — বাকি: <span class="font-bold pos-mono" style="color:var(--pos-danger)">৳<span x-text="Number(completedSale?.due_amount || 0).toFixed(2)"></span></span></span>
                    </template>
                </p>
            </div>

            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">রসিদ পাঠাতে বা প্রিন্ট করতে বেছে নিন</p>

            <div class="grid grid-cols-2 gap-2.5">
                <button @click="printA4Invoice()" type="button"
                        class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl border-2 border-gray-100 hover:border-[var(--pos-primary)] hover:bg-[var(--pos-primary-light)] transition active:scale-95">
                    <svg class="w-6 h-6" style="color:var(--pos-primary)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h1M7 3h10a2 2 0 012 2v14l-4-2-3 2-3-2-4 2V5a2 2 0 012-2z" />
                    </svg>
                    <span class="text-xs font-bold text-gray-700">A4 প্রিন্ট</span>
                </button>

                <button @click="shareOnWhatsApp()" type="button" :disabled="isGeneratingShare"
                        class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl border-2 border-gray-100 hover:border-emerald-400 hover:bg-emerald-50 transition active:scale-95 disabled:opacity-50">
                    <svg x-show="!isGeneratingShare" class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.76.46 3.48 1.34 5L2 22l5.25-1.38a9.9 9.9 0 004.79 1.22h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm0 1.67c2.2 0 4.26.86 5.82 2.42a8.2 8.2 0 012.42 5.82c0 4.54-3.7 8.24-8.25 8.24a8.2 8.2 0 01-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.18 8.18 0 01-1.26-4.38c0-4.55 3.7-8.24 8.26-8.24z"/>
                    </svg>
                    <svg x-show="isGeneratingShare" class="w-6 h-6 text-emerald-600 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span class="text-xs font-bold text-gray-700" x-text="isGeneratingShare ? 'তৈরি হচ্ছে...' : 'WhatsApp Share'"></span>
                </button>

                <button @click="openPosReceipt()" type="button"
                        class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl border-2 border-gray-100 hover:border-[var(--pos-accent)] hover:bg-[var(--pos-accent-light)] transition active:scale-95">
                    <svg class="w-6 h-6" style="color:var(--pos-accent)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5M6 18h12v4H6v-4zM6 14h12M4 9h16a1 1 0 011 1v5a1 1 0 01-1 1h-2v-3H6v3H4a1 1 0 01-1-1v-5a1 1 0 011-1z" />
                    </svg>
                    <span class="text-xs font-bold text-gray-700">পস প্রিন্ট</span>
                </button>

                <button @click="showSaleSuccessModal = false" type="button"
                        class="flex flex-col items-center justify-center gap-1.5 p-3 rounded-xl border-2 border-gray-100 hover:border-red-300 hover:bg-red-50 transition active:scale-95">
                    <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="text-xs font-bold text-gray-700">বাতিল</span>
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
    // 992px–1024px রেঞ্জে সাইডবার ও POS লেআউট একসাথে না ভেঙে একই পয়েন্টে সুইচ করে।
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
    function posSystem() {
        return {
            activeTab: 'products',
            products: @json($products),
            customers: @json($customers),
            searchQuery: '',
            selectedCategory: null,

            customerSearchText: '',
            selectedCustomer: null,
            selectedCustomerId: null,
            saleDate: '',

            // ⬇️ নতুন — ৬টা প্যাকেট সাইজের কনফিগ, ব্যাকএন্ডের storeSale ভ্যালিডেশনের
            // 'items.*.unit_type' => 'required|in:5kg,1kg,500g,250g,100g,50g' এর সাথে
            // key হুবহু মিলে যায় (আগে এখানে 'half_kg' ব্যবহার হতো, যেটা ব্যাকএন্ডে
            // কখনোই ভ্যালিড ছিল না — প্রতিটি বিক্রিই এরর দিতো)। priceField প্রতিটা
            // key-কে Product মডেলের সংশ্লিষ্ট দামের কলামের সাথে ম্যাপ করে।
            unitTypes: [
                { key: '5kg',  label: '৫ কেজি',    shortLabel: '৫কেজি',   grams: 5000, priceField: 'price_5kg' },
                { key: '1kg',  label: '১ কেজি',    shortLabel: '১কেজি',   grams: 1000, priceField: 'price_1kg' },
                { key: '500g', label: '৫০০ গ্রাম', shortLabel: '৫০০গ্রা', grams: 500,  priceField: 'price_half_kg' },
                { key: '250g', label: '২৫০ গ্রাম', shortLabel: '২৫০গ্রা', grams: 250,  priceField: 'price_250g' },
                { key: '100g', label: '১০০ গ্রাম', shortLabel: '১০০গ্রা', grams: 100,  priceField: 'price_100g' },
                { key: '50g',  label: '৫০ গ্রাম',  shortLabel: '৫০গ্রা',  grams: 50,   priceField: 'price_50g' },
            ],

            cart: [],
            discountType: 'flat',
            discountValue: 0,
            paidAmount: 0,

            showCustomerModal: false,
            newCustomer: { name: '', phone: '', address: '', opening_due: 0 },

            showReceiptModal: false,
            showSaleSuccessModal: false, // ⬅️ নতুন — বিক্রি সম্পন্ন হওয়ার পর প্রথমে এটাই খোলে
            completedSale: null,

            isSubmitting: false,
            isSavingCustomer: false,
            isGeneratingShare: false,

            init() {
                this.saleDate = this.formatDatetimeLocal(new Date());
            },

            // ================================================================
            // 📱 নেটিভ (Flutter WebView) অ্যাপ ডিটেকশন ও হেল্পার
            // ================================================================

            // ফ্লাটার অ্যাপের ভিতরে (flutter_inappwebview) চললে true রিটার্ন করে।
            // ব্রাউজারে চললে false — তখন আগের ওয়েব-বেসড লজিক (window.print,
            // navigator.share) ফলব্যাক হিসেবে কাজ করবে।
            get isNativeApp() {
                return typeof window.flutter_inappwebview !== 'undefined';
            },

            // Blob কে base64 data URL এ কনভার্ট করে — Flutter bridge এ পাঠানোর জন্য
            blobToBase64(blob) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result); // data:application/pdf;base64,xxxx
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
            },

            // datetime-local ইনপুটের জন্য লোকাল টাইমজোন অনুযায়ী সঠিক ফরম্যাট (YYYY-MM-DDTHH:mm)
            formatDatetimeLocal(date) {
                const pad = n => String(n).padStart(2, '0');
                return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
                    + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
            },

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

            // ⚠️ বাগ ফিক্স: আগে শুধু product.stock_in_grams দেখে বাটন disable হতো,
            // কার্টে ইতিমধ্যে কতটা যোগ হয়েছে তা হিসাবে নিত না — ফলে স্টকের চেয়ে বেশি বিক্রি
            // হয়ে যাওয়ার সুযোগ ছিল। এখন real-time অবশিষ্ট স্টক হিসাব হয়।
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

                if (typeof toastr !== 'undefined') {
                    toastr.success(product.name + ' (' + this.unitLabel(unitType) + ') কার্টে যোগ হয়েছে', '', { timeOut: 900 });
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
                const pct = Math.min(100, parseFloat(this.discountValue) || 0);
                return (this.subtotal * pct) / 100;
            },

            get grandTotal() {
                return Math.max(0, this.subtotal - this.discountAmount);
            },

            get dueAmount() {
                return Math.max(0, this.grandTotal - (parseFloat(this.paidAmount) || 0));
            },

            // জমার পরিমাণ কখনো সর্বমোটের চেয়ে বেশি বা ঋণাত্মক হতে পারবে না
            clampPaidAmount() {
                let val = parseFloat(this.paidAmount) || 0;
                val = Math.max(0, Math.min(val, this.grandTotal));
                this.paidAmount = val;
            },

            setPaid(amount) {
                this.paidAmount = Math.round(Math.max(0, Math.min(amount, this.grandTotal)) * 100) / 100;
            },

            csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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
                    const response = await fetch("{{ route('pos.customer.store') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": this.csrfToken()
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

            async processSale() {
                if (this.isSubmitting) return; // ডাবল-ক্লিক/ডাবল-সাবমিট প্রতিরোধ

                if (!this.selectedCustomerId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'কাস্টমার নির্বাচন করুন',
                        text: 'বিক্রি সম্পন্ন করার আগে অনুগ্রহ করে একজন কাস্টমার সিলেক্ট করুন!',
                        confirmButtonColor: '#0F6F4C'
                    });
                    this.activeTab = 'cart';
                    return;
                }

                if (this.cart.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'কার্ট খালি',
                        text: 'বিক্রি করার জন্য কার্টে অন্তত একটি পণ্য যুক্ত করুন!',
                        confirmButtonColor: '#0F6F4C'
                    });
                    this.activeTab = 'products';
                    return;
                }

                this.clampPaidAmount();

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
                    paid_amount: this.paidAmount,
                    sale_date: this.saleDate,
                };

                this.isSubmitting = true;

                try {
                    const response = await fetch("{{ route('pos.sale.store') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": this.csrfToken()
                        },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        this.completedSale = result.sale;
                        this.cart = [];
                        this.discountValue = 0;
                        this.paidAmount = 0;
                        this.resetCustomer();
                        this.saleDate = this.formatDatetimeLocal(new Date());
                        this.showSaleSuccessModal = true; // ⬅️ আগে showReceiptModal সরাসরি খুলত, এখন প্রথমে অ্যাকশন-বেছে-নিন মডাল

                        if (typeof toastr !== 'undefined') {
                            toastr.success('বিক্রি সফলভাবে সম্পন্ন হয়েছে!');
                        }
                    } else {
                        let errorMessage = result.message || 'ডাটা প্রসেস করতে ব্যর্থ হয়েছে।';
                        if (result.errors) {
                            errorMessage = Object.values(result.errors).flat().join('<br>');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'বিক্রি সম্পন্ন হয়নি!',
                            html: errorMessage,
                            confirmButtonColor: '#C6413A'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'সার্ভার এরর!',
                        text: 'ডাটা সংরক্ষণ করা সম্ভব হয়নি। ইন্টারনেট সংযোগ পরীক্ষা করুন এবং পুনরায় চেষ্টা করুন।',
                        confirmButtonColor: '#C6413A'
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            // ================================================================
            // 🖨️ A4 ইনভয়েস প্রিন্ট
            // নেটিভ অ্যাপে থাকলে: PDF blob বানিয়ে Flutter bridge এ পাঠায়
            // (Flutter সাইডে সিস্টেম প্রিন্ট ডায়ালগ খুলবে — printing প্যাকেজ দিয়ে)
            // ব্রাউজারে থাকলে: আগের মতোই নতুন ট্যাবে ইনভয়েস পেজ খোলে
            // ================================================================
            async printA4Invoice() {
                if (!this.completedSale) return;

                if (this.isNativeApp) {
                    try {
                        const pdfBlob = await this.generateInvoicePdfBlob(this.completedSale.id);
                        const base64 = await this.blobToBase64(pdfBlob);
                        const fileName = `Invoice-${this.completedSale.invoice_no}.pdf`;

                        const result = await window.flutter_inappwebview.callHandler('printInvoice', base64, fileName);

                        if (result && result.success === false) {
                            throw new Error(result.message || 'প্রিন্ট করা সম্ভব হয়নি');
                        }
                    } catch (e) {
                        console.error(e);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'প্রিন্ট করা সম্ভব হয়নি',
                                text: 'ইনভয়েস প্রিন্ট করতে সমস্যা হয়েছে। আবার চেষ্টা করুন।',
                                confirmButtonColor: '#C6413A'
                            });
                        }
                    }
                } else {
                    // ব্রাউজারে (মোবাইল অ্যাপ নয়) — আগের মতোই
                    window.open(`/admin/sales/${this.completedSale.id}/invoice`, '_blank');
                }
            },

            // ================================================================
            // 📤 WhatsApp / নেটিভ শেয়ার
            // নেটিভ অ্যাপে থাকলে: PDF blob বানিয়ে Flutter bridge এর মাধ্যমে
            // সরাসরি নেটিভ শেয়ার শিট খোলে (share_plus প্যাকেজ)
            // ব্রাউজারে থাকলে: Web Share API → না থাকলে ডাউনলোড + wa.me ফলব্যাক
            // ================================================================
            async shareOnWhatsApp() {
                if (!this.completedSale || this.isGeneratingShare) return;
                this.isGeneratingShare = true;

                try {
                    const pdfBlob = await this.generateInvoicePdfBlob(this.completedSale.id);
                    const fileName = `Invoice-${this.completedSale.invoice_no}.pdf`;

                    if (this.isNativeApp) {
                        // নেটিভ অ্যাপ — সরাসরি Flutter bridge এর মাধ্যমে শেয়ার শিট খুলবে,
                        // যেখানে WhatsApp সহ সব শেয়ার-সক্ষম অ্যাপ দেখানো হবে
                        const base64 = await this.blobToBase64(pdfBlob);
                        const result = await window.flutter_inappwebview.callHandler(
                            'shareInvoice',
                            base64,
                            fileName,
                            'ইনভয়েস ' + this.completedSale.invoice_no
                        );

                        if (result && result.success === false) {
                            throw new Error(result.message || 'শেয়ার করা সম্ভব হয়নি');
                        }
                    } else if (navigator.canShare && navigator.canShare({ files: [new File([pdfBlob], fileName)] })) {
                        // ব্রাউজার — Web Share API সাপোর্ট থাকলে
                        const file = new File([pdfBlob], fileName, { type: 'application/pdf' });
                        await navigator.share({
                            files: [file],
                            title: 'ইনভয়েস ' + this.completedSale.invoice_no,
                            text: 'ইনভয়েস ' + this.completedSale.invoice_no
                        });
                    } else {
                        // ফলব্যাক: PDF ডাউনলোড + WhatsApp Web ওপেন (কোনো নির্দিষ্ট নম্বর
                        // ছাড়াই, যাতে ইউজার যেকোনো কন্টাক্ট বেছে নিতে পারেন)
                        const url = URL.createObjectURL(pdfBlob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = fileName;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);

                        if (typeof Swal !== 'undefined') {
                            await Swal.fire({
                                icon: 'info',
                                title: 'PDF ডাউনলোড হয়েছে',
                                text: 'আপনার ব্রাউজারে সরাসরি শেয়ার সমর্থিত নয় — WhatsApp খুলছে, ডাউনলোড হওয়া PDF ফাইলটা ম্যানুয়ালি অ্যাটাচ করে পাঠান।',
                                confirmButtonColor: '#0F6F4C'
                            });
                        }
                        window.open('https://wa.me/', '_blank');
                    }
                } catch (e) {
                    // ইউজার নিজে শেয়ার শীট বাতিল করলে (AbortError) কোনো এরর দেখানোর দরকার নেই
                    if (e.name !== 'AbortError') {
                        console.error(e);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'ত্রুটি!',
                                text: 'রসিদের PDF তৈরি বা শেয়ার করা সম্ভব হয়নি।',
                                confirmButtonColor: '#C6413A'
                            });
                        }
                    }
                } finally {
                    this.isGeneratingShare = false;
                }
            },

            // ইনভয়েস পেজটা একটা লুকানো iframe-এ (?embed=1 দিয়ে, প্রিন্ট-বাটন/অটো-প্রিন্ট ছাড়া)
            // লোড করে html2canvas দিয়ে ক্যাপচার করে, তারপর jsPDF দিয়ে PDF বানায়।
            // ব্রাউজার নিজেই HTML রেন্ডার করছে বলে বাংলা ফন্ট ঠিক সেভাবেই আসে যেভাবে স্ক্রিনে দেখা যায়।
            // ⚠️ এই ফাংশনটা নেটিভ অ্যাপ ও ব্রাউজার — দুই ক্ষেত্রেই অপরিবর্তিত থাকছে,
            // কারণ flutter_inappwebview এর ভিতরেও html2canvas/jsPDF ঠিকঠাক চলে।
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

                    // ফন্ট/ছবি লোড হওয়ার জন্য একটু অপেক্ষা
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

            // html2canvas ও jsPDF শুধু WhatsApp শেয়ার করার সময়ই দরকার — তাই প্রথমবার
            // ব্যবহারের সময় lazy-load করা হচ্ছে, পেজ লোডের সময় অযথা ভারী হবে না
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

            openPosReceipt() {
                this.showSaleSuccessModal = false;

                if (this.isNativeApp) {
                    window.flutter_inappwebview.callHandler('openBluetoothPrinterScreen', {
                        invoice_no: this.completedSale?.invoice_no,
                        grand_total: this.completedSale?.net_amount,
                        due_amount: this.completedSale?.due_amount,
                    });
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'শুধু মোবাইল অ্যাপে সম্ভব',
                        text: 'পস (থার্মাল) প্রিন্ট শুধুমাত্র মোবাইল অ্যাপ থেকে Bluetooth প্রিন্টার দিয়ে করা যাবে।',
                        confirmButtonColor: '#0F6F4C'
                    });
                }
            },
        }
    }
</script>
@endpush