<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function __construct(
        protected TenantProvisioningService $provisioningService
    ) {}

    /**
     * List all registered tenants (Platform Admin API).
     */
    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::with('domains')->latest('id')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tenants->items(),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    /**
     * Provision a new Company / Tenant with dedicated database and owner account.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Company info
            'company_name' => 'required|string|max:200',
            'slug' => 'nullable|string|max:50|alpha_dash|unique:central.tenants,slug',
            'company_code' => 'nullable|string|max:50|unique:central.tenants,company_code',
            'domain' => 'nullable|string|max:100|unique:central.domains,domain',
            'plan' => 'nullable|string|in:starter,professional,enterprise',

            // Owner User info
            'owner_name' => 'required|string|max:150',
            'owner_email' => 'required|email|max:150',
            'owner_phone' => 'nullable|string|max:50',
            'owner_password' => 'required|string|min:8',
        ]);

        $slug = Str::slug($validated['slug'] ?? $validated['company_name']);
        $domain = $validated['domain'] ?? ($slug . '.localhost');

        $tenantData = [
            'name' => $validated['company_name'],
            'slug' => $slug,
            'company_code' => $validated['company_code'] ?? null,
            'domain' => $domain,
            'plan' => $validated['plan'] ?? 'enterprise',
        ];

        $ownerData = [
            'name' => $validated['owner_name'],
            'email' => $validated['owner_email'],
            'phone' => $validated['owner_phone'] ?? null,
            'password' => $validated['owner_password'],
        ];

        try {
            $result = $this->provisioningService->provision($tenantData, $ownerData);

            return response()->json([
                'success' => true,
                'message' => __('messages.tenant_provisioned_success'),
                'data' => [
                    'tenant' => [
                        'id' => $result['tenant']->id,
                        'name' => $result['tenant']->name,
                        'slug' => $result['tenant']->slug,
                        'company_code' => $result['tenant']->company_code,
                        'database' => $result['tenant']->database_name,
                        'status' => $result['tenant']->status,
                    ],
                    'domain' => $result['domain']->domain,
                    'owner' => [
                        'id' => $result['owner']->id,
                        'name' => $result['owner']->name,
                        'email' => $result['owner']->email,
                    ],
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.tenant_provision_failed', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Check if a tenant slug / code is available.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $slug = $request->query('slug');
        if (!$slug) {
            return response()->json(['available' => false, 'message' => __('messages.tenant_identifier_required')], 400);
        }

        $exists = Tenant::where('slug', Str::slug($slug))->exists();

        return response()->json([
            'available' => !$exists,
            'slug' => Str::slug($slug),
        ]);
    }
}
