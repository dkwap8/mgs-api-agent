<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-950 dark:text-white">Profile Settings</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage your personal profile and your business settings.</p>
        </div>

        <!-- Message -->
        <div v-if="message.text" :class="messageClass" class="rounded-2xl p-4 text-sm font-medium">
            {{ message.text }}
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- User Profile -->
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <h3 class="mb-4 font-semibold text-slate-950 dark:text-white">User Profile</h3>
                <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Your account details. Contact support to request an identity or login-email change.</p>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">First name</label>
                            <input v-model="profile.first_name" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Last name</label>
                            <input v-model="profile.last_name" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Phone number</label>
                        <input v-model="profile.phone" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Login email</label>
                        <input v-model="profile.email" readonly type="email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">This is the email you sign in with.</p>
                    </div>

                    <p class="text-xs text-slate-400 dark:text-slate-500">Contact support to request changes to your verified identity or login details.</p>

                    <router-link to="/user/help" class="inline-block rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Contact Support</router-link>
                </div>
            </div>

            <!-- Business Profile -->
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <h3 class="mb-4 font-semibold text-slate-950 dark:text-white">Business Profile</h3>
                <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Set your business verification (KYB) — contact support to change</p>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Business Name</label>
                        <input v-model="business.name" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Business Email</label>
                        <input v-model="business.email" type="email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">This email is used for transaction notifications</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Business Phone</label>
                        <input v-model="business.phone" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Business Address</label>
                        <input v-model="business.address" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Business Category</label>
                        <select v-model="business.category" disabled class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                            <option value="">Select category</option>
                            <option value="E-commerce">E-commerce</option>
                            <option value="Financial Services">Financial Services</option>
                            <option value="Education">Education</option>
                            <option value="Logistics">Logistics</option>
                            <option value="VTU / Data">VTU / Data Services</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <p class="text-xs text-slate-400 dark:text-slate-500">You can update business contact details here. Contact support to change the verified business name or category.</p>

                    <button @click="updateBusiness" :disabled="loading" class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50">
                        {{ loading ? 'Updating...' : 'Update Business' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <h3 class="mb-4 font-semibold text-slate-950 dark:text-white">Security Settings</h3>
                <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Manage your account security and authentication preferences</p>

                <div class="space-y-4">
                    <UserSecuritySettings />

                    <div>
                        <h4 class="mb-2 font-medium text-slate-950 dark:text-white">Change Password</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Ensure your account is using a long, random password to stay secure.</p>

                        <form @submit.prevent="changePassword" class="mt-3 space-y-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Current Password</label>
                                <div class="relative">
                                    <input v-model="password.current" :type="showPass.current ? 'text' : 'password'" placeholder="Enter current password" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 pr-10 text-sm text-slate-950 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                                    <button type="button" @click="showPass.current = !showPass.current" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                        {{ showPass.current ? '🙈' : '👁️' }}
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">New Password</label>
                                <div class="relative">
                                    <input v-model="password.new" :type="showPass.new ? 'text' : 'password'" placeholder="Enter new password" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 pr-10 text-sm text-slate-950 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                                    <button type="button" @click="showPass.new = !showPass.new" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                        {{ showPass.new ? '🙈' : '👁️' }}
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Confirm New Password</label>
                                <div class="relative">
                                    <input v-model="password.confirm" :type="showPass.confirm ? 'text' : 'password'" placeholder="Confirm new password" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 pr-10 text-sm text-slate-950 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                                    <button type="button" @click="showPass.confirm = !showPass.confirm" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                        {{ showPass.confirm ? '🙈' : '👁️' }}
                                    </button>
                                </div>
                            </div>
                            <button type="submit" :disabled="loading" class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50">
                                Change Password
                            </button>
                            <p class="text-xs text-slate-400 dark:text-slate-500">Forgot your password? Reset it by email.</p>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Withdrawal PIN -->
            <div class="rounded-2xl bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_32px_rgba(15,23,42,0.06)] dark:bg-slate-950">
                <h3 class="mb-4 font-semibold text-slate-950 dark:text-white">Withdrawal PIN</h3>
                <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">This PIN will be required before withdrawals or payouts.</p>

                <form @submit.prevent="setupWithdrawalPin" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">New PIN</label>
                        <div class="relative">
                            <input v-model="pin.pin" :type="showPin.pin ? 'text' : 'password'" placeholder="4-digit PIN" maxlength="4" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 pr-10 text-sm text-slate-950 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                            <button type="button" @click="showPin.pin = !showPin.pin" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                {{ showPin.pin ? '🙈' : '👁️' }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Confirm PIN</label>
                        <div class="relative">
                            <input v-model="pin.confirm" :type="showPin.confirm ? 'text' : 'password'" placeholder="Confirm PIN" maxlength="4" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 pr-10 text-sm text-slate-950 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                            <button type="button" @click="showPin.confirm = !showPin.confirm" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                {{ showPin.confirm ? '🙈' : '👁️' }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Account Password</label>
                        <div class="relative">
                            <input v-model="pin.password" :type="showPin.password ? 'text' : 'password'" placeholder="Your password" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 pr-10 text-sm text-slate-950 focus:border-indigo-500 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                            <button type="button" @click="showPin.password = !showPin.password" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                {{ showPin.password ? '🙈' : '👁️' }}
                            </button>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading" class="rounded-xl bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 disabled:opacity-50">
                        {{ loading ? 'Saving...' : 'Create / Change PIN' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import UserSecuritySettings from '../../components/UserSecuritySettings.vue';

export default {
    name: 'Settings',
    components: { UserSecuritySettings },

    setup() {
        const loading = ref(false);

        const message = ref({ type: '', text: '' });

        const profile = ref({
            first_name: '',
            last_name: '',
            phone: '',
            email: '',
        });

        const business = ref({
            name: '',
            email: '',
            phone: '',
            address: '',
            category: '',
        });

        const password = ref({
            current: '',
            new: '',
            confirm: '',
        });

        const pin = ref({
            pin: '',
            confirm: '',
            password: '',
        });

        const showPass = ref({ current: false, new: false, confirm: false });
        const showPin = ref({ pin: false, confirm: false, password: false });

        const messageClass = computed(() => {
            return message.value.type === 'success'
                ? 'bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800'
                : 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800';
        });

        const showMessage = (type, text) => {
            message.value = { type, text };
            setTimeout(() => { message.value = { type: '', text: '' }; }, 5000);
        };

        const fetchProfile = async () => {
            try {
                const response = await axios.get('/api/v1/auth/me');
                const user = response.data.data?.user || response.data.data || response.data.user || {};

                profile.value = {
                    first_name: user.first_name || (user.name || '').split(' ')[0] || '',
                    last_name: user.last_name || (user.name || '').split(' ').slice(1).join(' '),
                    phone: user.phone || '',
                    email: user.email || '',
                };

                const merchantResponse = await axios.get('/api/v1/merchant/profile');
                const currentBusiness = merchantResponse.data.data?.current_business || {};
                const details = currentBusiness.metadata || {};
                const activation = merchantResponse.data.data?.businesses?.find(item => item.id === currentBusiness.id)?.activation || {};
                business.value = {
                    name: currentBusiness.name || '',
                    email: details.business_email ?? activation.business_email ?? '',
                    phone: details.business_phone ?? activation.business_phone ?? '',
                    address: details.business_address ?? activation.street_address ?? '',
                    category: details.business_category ?? activation.business_category ?? '',
                };
            } catch (error) {
                console.error('Error fetching profile:', error.response?.data || error);
            }
        };

        const updateBusiness = async () => {
            loading.value = true;
            try {
                await axios.put('/api/v1/merchant/profile', {
                    business_email: business.value.email,
                    business_phone: business.value.phone,
                    business_address: business.value.address,
                });
                showMessage('success', 'Business profile updated successfully!');
            } catch (error) {
                showMessage('error', error.response?.data?.message || 'Failed to update business profile');
            } finally {
                loading.value = false;
            }
        };

        const changePassword = async () => {
            if (!password.value.current || !password.value.new || !password.value.confirm) {
                showMessage('error', 'Please fill all password fields.');
                return;
            }
            if (password.value.new !== password.value.confirm) {
                showMessage('error', 'Passwords do not match.');
                return;
            }
            loading.value = true;
            try {
                await axios.post('/api/v1/auth/change-password', {
                    current_password: password.value.current,
                    password: password.value.new,
                    password_confirmation: password.value.confirm,
                });
                password.value = { current: '', new: '', confirm: '' };
                showMessage('success', 'Password changed successfully!');
            } catch (error) {
                showMessage('error', error.response?.data?.message || 'Failed to change password');
            } finally {
                loading.value = false;
            }
        };

        const setupWithdrawalPin = async () => {
            if (!/^[0-9]{4}$/.test(pin.value.pin)) {
                showMessage('error', 'PIN must be exactly 4 digits.');
                return;
            }
            if (pin.value.pin !== pin.value.confirm) {
                showMessage('error', 'PIN confirmation does not match.');
                return;
            }
            if (!pin.value.password) {
                showMessage('error', 'Account password is required.');
                return;
            }
            loading.value = true;
            try {
                await axios.post('/api/v1/auth/setup-withdrawal-pin', {
                    pin: pin.value.pin,
                    pin_confirmation: pin.value.confirm,
                    password: pin.value.password,
                });
                pin.value = { pin: '', confirm: '', password: '' };
                showMessage('success', 'Withdrawal PIN saved successfully!');
            } catch (error) {
                showMessage('error', error.response?.data?.message || 'Failed to save withdrawal PIN');
            } finally {
                loading.value = false;
            }
        };

        onMounted(fetchProfile);

        return {
            loading,
            message,
            messageClass,
            profile,
            business,
            password,
            pin,
            showPass,
            showPin,
            updateBusiness,
            changePassword,
            setupWithdrawalPin,
        };
    },
};
</script>