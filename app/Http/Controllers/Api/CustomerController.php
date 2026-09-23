<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends CRUDController
{
    protected string $model = Customer::class;
    protected array $searchable = ['name', 'company_name', 'phone', 'whatsapp', 'email', 'address'];
    protected string $defaultSortBy = 'id';
    protected string $defaultSortOrder = 'desc';

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->title('customers.title')
            ->singularTitle('customers.singular')
            ->model(Customer::class)
            ->fields([
                Field::select('customer_type', 'customers.customerType', [
                    ['value' => 'individual', 'label' => 'customers.individual'],
                    ['value' => 'company', 'label' => 'customers.company'],
                ])->default('individual')->required()->col(6),

                Field::select('status', 'common.status', [
                    ['value' => 'active', 'label' => 'common.active'],
                    ['value' => 'inactive', 'label' => 'common.inactive'],
                ])->default('active')->required()->col(6),

                Field::text('name', 'customers.name')->required()->col(6),
                Field::text('company_name', 'customers.companyName')->col(6),

                Field::tel('phone', 'customers.phone')->col(6),
                Field::tel('whatsapp', 'customers.whatsapp')->col(6),

                Field::email('email', 'customers.email')->col(6),
                Field::textarea('address', 'customers.address')->col(6),

                Field::textarea('notes', 'customers.notes')->col(12),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('customers.view');

        $query = $this->query();

        // 1. Search Query
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // 2. Filter by Customer Type
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        // 3. Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 4. Sorting (SQL Injection Whitelisting & Protection)
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $sortBy = preg_match('/^[a-zA-Z0-9_]+$/', $rawSortBy) ? $rawSortBy : $this->defaultSortBy;

        $rawSortOrder = strtolower((string) $request->input('sort_order', $this->defaultSortOrder));
        $sortOrder = in_array($rawSortOrder, ['asc', 'desc'], true) ? $rawSortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // 5. Pagination (DOS Protection: Capped 1 - 100)
        $rawPerPage = (int) $request->input('per_page', 15);
        $perPage = min(max($rawPerPage, 1), 100);
        $paginated = $query->paginate($perPage);

        // High Performance Stats Summary via SQL Counts
        $stats = [
            'total' => Customer::count(),
            'active' => Customer::where('status', 'active')->count(),
            'inactive' => Customer::where('status', 'inactive')->count(),
            'individuals' => Customer::where('customer_type', 'individual')->count(),
            'companies' => Customer::where('customer_type', 'company')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'stats' => $stats,
            'schema' => $this->inputMaker()->toSchema(),
        ]);
    }

    /**
     * Return lightweight list of customers for select dropdowns.
     */
    public function all(Request $request): JsonResponse
    {
        $this->authorizePermission('customers.view');

        $customers = Customer::select('id', 'name', 'company_name', 'phone', 'whatsapp', 'email', 'customer_type', 'status')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function show(int|string $id): JsonResponse
    {
        $this->authorizePermission('customers.view');
        return parent::show($id);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('customers.create');
        return parent::store($request);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('customers.update');
        return parent::update($request, $id);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission('customers.delete');
        return parent::destroy($ids);
    }

    protected function customValidationRules(bool $isUpdate = false, mixed $currentId = null): array
    {
        return [
            'customer_type' => ['required', 'string', 'in:individual,company'],
            'name' => ['required', 'string', 'max:200'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'documents' => ['nullable'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function afterSave(Model $model, Request $request, bool $isUpdate): void
    {
        /** @var Customer $model */
        if ($request->hasFile('documents')) {
            $files = is_array($request->file('documents')) ? $request->file('documents') : [$request->file('documents')];
            foreach ($files as $file) {
                // Ensure extension is not dangerous
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'pht', 'exe', 'sh', 'bat', 'cmd'], true)) {
                    continue;
                }
                $model->addMedia($file)->toMediaCollection('documents');
            }
        }
    }

    private function authorizePermission(string|array $permission): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        if ($user->hasRole('Owner') || $user->hasRole('Super Admin')) {
            return;
        }

        $perms = is_array($permission) ? $permission : [$permission];
        $hasAny = false;
        foreach ($perms as $perm) {
            if ($user->hasPermissionTo($perm)) {
                $hasAny = true;
                break;
            }
        }

        if (!$hasAny) {
            abort(403, __('messages.permissions_denied_any', ['permissions' => implode(', ', $perms)]));
        }
    }
}
