@extends('admin.layouts.main')

@section('title', 'ইউজার ম্যানেজমেন্ট')

@push('css')
{{-- Tailwind CSS CDN (যা লেআউটে Tailwind না থাকলেও স্টাইল ঠিক করে দেবে) --}}
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div x-data="userManagement()" x-init="fetchUsers()" class="p-6 bg-white rounded-xl shadow-md max-w-6xl mx-auto my-4">
    
    {{-- হেডার ও সার্চ বার --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">ইউজার ম্যানেজমেন্ট</h2>
            <p class="text-sm text-gray-500">সহজেই ইউজার যোগ, এডিট ও ডিলিট করুন</p>
        </div>

        <div class="flex items-center space-x-3 w-full md:w-auto">
            <input type="text" 
                   x-model="search" 
                   @input.debounce.500ms="fetchUsers()" 
                   placeholder="নাম বা ইমেইল দিয়ে খুঁজুন..." 
                   class="w-full md:w-64 border-gray-300 rounded-lg px-3 py-2 border text-sm focus:ring-blue-500 focus:border-blue-500">
            
            <button @click="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg text-sm transition flex items-center shrink-0">
                <svg class="mr-1 inline-block" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                নতুন ইউজার
            </button>
        </div>
    </div>

    {{-- নোটিফিকেশন মেসেজ --}}
    <div x-show="alert.show" 
         x-transition 
         :class="alert.type === 'success' ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400'"
         class="p-4 mb-4 text-sm rounded-lg border flex justify-between items-center" style="display: none;">
        <span x-text="alert.message"></span>
        <button @click="alert.show = false" class="font-bold text-lg">&times;</button>
    </div>

    {{-- ইউজার টেবিল --}}
    <div class="overflow-x-auto relative rounded-lg border border-gray-200">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                <tr>
                    <th scope="col" class="py-3 px-6">নাম</th>
                    <th scope="col" class="py-3 px-6">ইমেইল</th>
                    <th scope="col" class="py-3 px-6">তৈরির তারিখ</th>
                    <th scope="col" class="py-3 px-6 text-right">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr>
                        <td colspan="4" class="text-center py-6 text-gray-500">ডাটা লোড হচ্ছে...</td>
                    </tr>
                </template>

                <template x-if="!loading && users.length === 0">
                    <tr>
                        <td colspan="4" class="text-center py-6 text-gray-500">কোনো ইউজার পাওয়া যায়নি।</td>
                    </tr>
                </template>

                <template x-for="user in users" :key="user.id">
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="py-4 px-6 font-medium text-gray-900" x-text="user.name"></td>
                        <td class="py-4 px-6 text-gray-600" x-text="user.email"></td>
                        <td class="py-4 px-6 text-gray-500" x-text="formatDate(user.created_at)"></td>
                        <td class="py-4 px-6 text-right space-x-2">
                            <button @click="openModal(user)" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs bg-indigo-50 px-2.5 py-1.5 rounded">
                                এডিট
                            </button>
                            <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900 font-medium text-xs bg-red-50 px-2.5 py-1.5 rounded">
                                ডিলিট
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- পেজিনেশন বাটন --}}
    <div class="flex justify-between items-center mt-4" x-show="pagination.last_page > 1">
        <span class="text-sm text-gray-600">
            মোট ইউজার: <strong x-text="pagination.total"></strong>
        </span>
        <div class="inline-flex space-x-1">
            <button @click="fetchUsers(pagination.current_page - 1)" 
                    :disabled="pagination.current_page === 1" 
                    class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded disabled:opacity-50">পূর্ববর্তী</button>
            <button @click="fetchUsers(pagination.current_page + 1)" 
                    :disabled="pagination.current_page === pagination.last_page" 
                    class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded disabled:opacity-50">পরবর্তী</button>
        </div>
    </div>

    {{-- ==================== CREATE / EDIT MODAL ==================== --}}
    <div x-show="isModalOpen" 
         x-transition.opacity
         class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4" 
         style="display: none;">
        
        <div @click.outside="closeModal()" class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 relative">
            
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-lg font-bold text-gray-800" x-text="isEdit ? 'ইউজার আপডেট করুন' : 'নতুন ইউজার যোগ করুন'"></h3>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            <form @submit.prevent="saveUser()">
                {{-- নাম --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">নাম <span class="text-red-500">*</span></label>
                    <input type="text" x-model="formData.name" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm focus:ring-blue-500 focus:border-blue-500">
                    <template x-if="errors.name">
                        <p class="text-red-500 text-xs mt-1" x-text="errors.name[0]"></p>
                    </template>
                </div>

                {{-- ইমেইল --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">ইমেইল <span class="text-red-500">*</span></label>
                    <input type="email" x-model="formData.email" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm focus:ring-blue-500 focus:border-blue-500">
                    <template x-if="errors.email">
                        <p class="text-red-500 text-xs mt-1" x-text="errors.email[0]"></p>
                    </template>
                </div>

                {{-- পাসওয়ার্ড --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                        পাসওয়ার্ড <span x-show="!isEdit" class="text-red-500">*</span>
                    </label>
                    <input type="password" x-model="formData.password" placeholder="••••••••" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm focus:ring-blue-500 focus:border-blue-500">
                    <template x-if="isEdit">
                        <span class="text-xs text-gray-400">পাসওয়ার্ড পরিবর্তন না করতে চাইলে খালি রাখুন।</span>
                    </template>
                    <template x-if="errors.password">
                        <p class="text-red-500 text-xs mt-1" x-text="errors.password[0]"></p>
                    </template>
                </div>

                {{-- কনফার্ম পাসওয়ার্ড --}}
                <div class="mb-6">
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">কনফার্ম পাসওয়ার্ড</label>
                    <input type="password" x-model="formData.password_confirmation" placeholder="••••••••" class="w-full border-gray-300 rounded-lg p-2.5 border text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- অ্যাকশন বাটন --}}
                <div class="flex justify-end space-x-3 border-t pt-4">
                    <button type="button" @click="closeModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">বাতিল</button>
                    <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg disabled:opacity-50 flex items-center">
                        <span x-show="saving" class="mr-2">প্রসেসিং...</span>
                        <span x-text="isEdit ? 'আপডেট করুন' : 'সেভ করুন'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
function userManagement() {
    return {
        users: [],
        search: '',
        loading: false,
        saving: false,
        isModalOpen: false,
        isEdit: false,
        errors: {},
        pagination: { current_page: 1, last_page: 1, total: 0 },
        alert: { show: false, message: '', type: 'success' },

        formData: {
            id: null,
            name: '',
            email: '',
            password: '',
            password_confirmation: ''
        },

        getHeaders() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };
        },

        async fetchUsers(page = 1) {
            this.loading = true;
            try {
                let response = await fetch(`/admin/users?page=${page}&search=${encodeURIComponent(this.search)}`, {
                    headers: this.getHeaders()
                });
                let data = await response.json();
                this.users = data.data;
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    total: data.total
                };
            } catch (error) {
                this.showAlert('ডাটা লোড করতে সমস্যা হয়েছে!', 'error');
            } finally {
                this.loading = false;
            }
        },

        openModal(user = null) {
            this.errors = {};
            if (user) {
                this.isEdit = true;
                this.formData = {
                    id: user.id,
                    name: user.name,
                    email: user.email,
                    password: '',
                    password_confirmation: ''
                };
            } else {
                this.isEdit = false;
                this.formData = { id: null, name: '', email: '', password: '', password_confirmation: '' };
            }
            this.isModalOpen = true;
        },

        closeModal() {
            this.isModalOpen = false;
        },

        async saveUser() {
            this.saving = true;
            this.errors = {};

            let url = this.isEdit ? `/admin/users/${this.formData.id}` : '/admin/users';
            let method = this.isEdit ? 'PUT' : 'POST';

            try {
                let response = await fetch(url, {
                    method: method,
                    headers: this.getHeaders(),
                    body: JSON.stringify(this.formData)
                });

                let result = await response.json();

                if (response.ok) {
                    this.closeModal();
                    this.showAlert(result.message, 'success');
                    this.fetchUsers(this.pagination.current_page);
                } else if (response.status === 422) {
                    this.errors = result.errors;
                } else {
                    this.showAlert(result.message || 'কোনো সমস্যা হয়েছে!', 'error');
                }
            } catch (error) {
                this.showAlert('সার্ভারের সাথে সংযোগ বিচ্ছিন্ন হয়েছে!', 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteUser(id) {
            if (!confirm('আপনি কি নিশ্চিত যে এই ইউজারটিকে ডিলিট করতে চান?')) return;

            try {
                let response = await fetch(`/admin/users/${id}`, {
                    method: 'DELETE',
                    headers: this.getHeaders()
                });

                let result = await response.json();

                if (response.ok) {
                    this.showAlert(result.message, 'success');
                    this.fetchUsers(this.pagination.current_page);
                } else {
                    this.showAlert(result.message || 'ডিলিট করা সম্ভব হয়নি!', 'error');
                }
            } catch (error) {
                this.showAlert('সার্ভারের সাথে সংযোগ সমস্যা!', 'error');
            }
        },

        showAlert(message, type = 'success') {
            this.alert = { show: true, message: message, type: type };
            setTimeout(() => { this.alert.show = false; }, 4000);
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('bn-BD', options);
        }
    }
}
</script>
@endpush