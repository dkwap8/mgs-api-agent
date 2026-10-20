<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\ActivityLogService;
use App\Services\VirtualAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSettingController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,

            'data' => [
                'app_name' => SystemSetting::getValue(
                    'app_name',
                    'MGS API'
                ),

                'support_email' => SystemSetting::getValue(
                    'support_email'
                ),

                'support_phone' => SystemSetting::getValue(
                    'support_phone'
                ),

                'maintenance_mode' =>
                    SystemSetting::getBoolean(
                        'maintenance_mode',
                        false
                    ),

                'merchant_auto_approval' =>
                    SystemSetting::getBoolean(
                        'merchant_auto_approval',
                        false
                    ),

                'virtual_accounts_enabled' =>
                    SystemSetting::getBoolean(
                        'virtual_accounts_enabled',
                        true
                    ),

                'enforce_virtual_account_policy' =>
                    SystemSetting::getBoolean(
                        'enforce_virtual_account_policy',
                        false
                    ),

                'virtual_account_requirement' =>
                    SystemSetting::getValue(
                        'virtual_account_requirement',
                        VirtualAccountService::REQUIREMENT_NONE
                    ),

                'default_auto_generate_virtual_account' =>
                    SystemSetting::getBoolean(
                        'default_auto_generate_virtual_account',
                        true
                    ),

                'default_allow_multiple_virtual_accounts' =>
                    SystemSetting::getBoolean(
                        'default_allow_multiple_virtual_accounts',
                        false
                    ),

                'maximum_virtual_accounts_per_customer' =>
                    SystemSetting::getInteger(
                        'maximum_virtual_accounts_per_customer'
                    ),

                'default_charge' => (float)
                    SystemSetting::getValue(
                        'default_charge',
                        0
                    ),

                'default_limit' => (float)
                    SystemSetting::getValue(
                        'default_limit',
                        0
                    ),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'app_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'support_email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],

            'support_phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'maintenance_mode' => [
                'sometimes',
                'boolean',
            ],

            'merchant_auto_approval' => [
                'sometimes',
                'boolean',
            ],

            'virtual_accounts_enabled' => [
                'sometimes',
                'boolean',
            ],

            'enforce_virtual_account_policy' => [
                'sometimes',
                'boolean',
            ],

            'virtual_account_requirement' => [
                'sometimes',

                Rule::in([
                    VirtualAccountService::REQUIREMENT_NONE,
                    VirtualAccountService::REQUIREMENT_BVN,
                    VirtualAccountService::REQUIREMENT_NIN,
                    VirtualAccountService::REQUIREMENT_BVN_OR_NIN,
                    VirtualAccountService::REQUIREMENT_BVN_AND_NIN,
                ]),
            ],

            'default_auto_generate_virtual_account' => [
                'sometimes',
                'boolean',
            ],

            'default_allow_multiple_virtual_accounts' => [
                'sometimes',
                'boolean',
            ],

            'maximum_virtual_accounts_per_customer' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:1000',
            ],

            'default_charge' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'default_limit' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
        ]);

        $virtualAccountKeys = [
            'virtual_accounts_enabled',
            'enforce_virtual_account_policy',
            'virtual_account_requirement',
            'default_auto_generate_virtual_account',
            'default_allow_multiple_virtual_accounts',
            'maximum_virtual_accounts_per_customer',
        ];

        foreach ($validated as $key => $value) {
            $group = in_array(
                $key,
                $virtualAccountKeys,
                true
            )
                ? 'virtual_accounts'
                : 'general';

            $oldValue = SystemSetting::getValue($key);

            SystemSetting::setValue(
                $key,
                $value,
                $group
            );

            if ((string) $oldValue !== (string) $value) {
                $this->activityLogService
                    ->systemSettingChanged(
                        $key,
                        $oldValue,
                        $value,
                        $request->user()?->id,
                        $request
                    );
            }
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Settings updated successfully.',

            'data' => $this->settingsData(),
        ]);
    }

    private function settingsData(): array
    {
        return [
            'app_name' => SystemSetting::getValue(
                'app_name',
                'MGS API'
            ),

            'support_email' =>
                SystemSetting::getValue(
                    'support_email'
                ),

            'support_phone' =>
                SystemSetting::getValue(
                    'support_phone'
                ),

            'maintenance_mode' =>
                SystemSetting::getBoolean(
                    'maintenance_mode'
                ),

            'merchant_auto_approval' =>
                SystemSetting::getBoolean(
                    'merchant_auto_approval'
                ),

            'virtual_accounts_enabled' =>
                SystemSetting::getBoolean(
                    'virtual_accounts_enabled',
                    true
                ),

            'enforce_virtual_account_policy' =>
                SystemSetting::getBoolean(
                    'enforce_virtual_account_policy'
                ),

            'virtual_account_requirement' =>
                SystemSetting::getValue(
                    'virtual_account_requirement',
                    VirtualAccountService::REQUIREMENT_NONE
                ),

            'default_auto_generate_virtual_account' =>
                SystemSetting::getBoolean(
                    'default_auto_generate_virtual_account',
                    true
                ),

            'default_allow_multiple_virtual_accounts' =>
                SystemSetting::getBoolean(
                    'default_allow_multiple_virtual_accounts'
                ),

            'maximum_virtual_accounts_per_customer' =>
                SystemSetting::getInteger(
                    'maximum_virtual_accounts_per_customer'
                ),

            'default_charge' => (float)
                SystemSetting::getValue(
                    'default_charge',
                    0
                ),

            'default_limit' => (float)
                SystemSetting::getValue(
                    'default_limit',
                    0
                ),
        ];
    }
}