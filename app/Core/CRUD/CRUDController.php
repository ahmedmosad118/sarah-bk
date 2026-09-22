<?php

namespace App\Core\CRUD;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class CRUDController extends Controller
{
    /**
     * Eloquent Model Class Name
     */
    protected string $model;

    /**
     * Searchable fields in queries
     */
    protected array $searchable = ['name'];

    /**
     * Default relations to eager load
     */
    protected array $with = [];

    /**
     * Default order by column
     */
    protected string $defaultSortBy = 'id';

    /**
     * Default order direction (asc/desc)
     */
    protected string $defaultSortOrder = 'desc';

    /**
     * Return InputMaker definition schema for this model.
     */
    abstract protected function inputMaker(): InputMaker;

    /**
     * Query builder for current model.
     */
    protected function query(): Builder
    {
        $query = $this->model::query();
        if (!empty($this->with)) {
            $query->with($this->with);
        }
        return $query;
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->query();

        // 1. Search Query
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                foreach ($this->searchable as $index => $field) {
                    if (str_contains($field, '.')) {
                        [$relation, $relField] = explode('.', $field);
                        $method = $index === 0 ? 'whereHas' : 'orWhereHas';
                        $q->$method($relation, function ($rq) use ($relField, $search) {
                            $rq->where($relField, 'like', "%{$search}%");
                        });
                    } else {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $q->$method($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        // 2. Dynamic Field Filters from InputMaker
        foreach ($this->inputMaker()->getFields() as $field) {
            if ($request->filled($field->name) && $field->filterable) {
                $query->where($field->name, $request->input($field->name));
            }
        }

        // 3. Sorting (SQL Injection Whitelisting & Protection)
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $sortBy = preg_match('/^[a-zA-Z0-9_]+$/', $rawSortBy) ? $rawSortBy : $this->defaultSortBy;

        $rawSortOrder = strtolower((string) $request->input('sort_order', $this->defaultSortOrder));
        $sortOrder = in_array($rawSortOrder, ['asc', 'desc'], true) ? $rawSortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // 4. Pagination (DOS Protection: Capped between 1 and 100)
        $rawPerPage = (int) $request->input('per_page', 15);
        $perPage = min(max($rawPerPage, 1), 100);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'schema' => $this->inputMaker()->toSchema(),
        ]);
    }

    public function show(int|string $id): JsonResponse
    {
        $model = $this->query()->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $model,
            'schema' => $this->inputMaker()->toSchema(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $rules = $this->inputMaker()->getValidationRules(false);
        $rules = array_merge($rules, $this->customValidationRules(false));
        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $fillable = $this->inputMaker()->getFillableFields();
            $dataToSave = array_intersect_key($validated, array_flip($fillable));

            $model = new $this->model();
            $model->fill($dataToSave);

            $this->beforeSave($model, $request, false);
            $model->save();
            $this->afterSave($model, $request, false);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ البيانات بنجاح',
                'data' => $model->fresh($this->with),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("CRUD Store Failed for {$this->model}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ البيانات: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $model = $this->model::findOrFail($id);

        $rules = $this->inputMaker()->getValidationRules(true, $id);
        $rules = array_merge($rules, $this->customValidationRules(true, $id));
        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $fillable = $this->inputMaker()->getFillableFields();
            $dataToSave = array_intersect_key($validated, array_flip($fillable));

            $model->fill($dataToSave);

            $this->beforeSave($model, $request, true);
            $model->save();
            $this->afterSave($model, $request, true);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البيانات بنجاح',
                'data' => $model->fresh($this->with),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("CRUD Update Failed for {$this->model} [{$id}]: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البيانات: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $idArray = is_string($ids) ? explode(',', $ids) : (is_array($ids) ? $ids : [$ids]);

        DB::beginTransaction();
        try {
            $models = $this->model::whereIn('id', $idArray)->get();
            foreach ($models as $model) {
                $model->delete();
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف السجلات المحددة بنجاح',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف بعض السجلات لارتباطها ببيانات أخرى في النظام',
            ], 422);
        }
    }

    protected function beforeSave(Model $model, Request $request, bool $isUpdate): void
    {
        // Hook for subclasses
    }

    protected function afterSave(Model $model, Request $request, bool $isUpdate): void
    {
        // Hook for subclasses
    }

    protected function customValidationRules(bool $isUpdate = false, mixed $currentId = null): array
    {
        return [];
    }
}
