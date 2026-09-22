<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\JobTitle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobTitleController extends CRUDController
{
    protected string $model = JobTitle::class;
    protected array $searchable = ['name', 'name_ar', 'name_en', 'code', 'description'];
    protected string $defaultSortBy = 'id';
    protected string $defaultSortOrder = 'asc';

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->title('jobTitles.title')
            ->singularTitle('jobTitles.nameCol')
            ->model(JobTitle::class)
            ->fields([
                Field::text('name_ar', 'jobTitles.nameCol')->required()->col(6),
                Field::text('name_en', 'jobTitles.nameCol')->required()->col(6),
                Field::text('code', 'jobTitles.codeCol')->col(6),
                Field::textarea('description', 'jobTitles.descCol')->col(12),
                Field::boolean('is_active', 'common.active')->default(true)->col(6),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $response = parent::index($request);
        return $response;
    }

    public function all(): JsonResponse
    {
        $jobTitles = JobTitle::where('is_active', true)->orderBy('id')->get();
        return response()->json([
            'success' => true,
            'data' => $jobTitles,
        ]);
    }

    protected function beforeSave(Model $model, Request $request, bool $isUpdate): void
    {
        /** @var JobTitle $model */
        if ($request->filled('name_ar')) {
            $model->name = $request->name_ar;
        } elseif ($request->filled('name_en')) {
            $model->name = $request->name_en;
        }
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $idArray = is_string($ids) ? explode(',', $ids) : (is_array($ids) ? $ids : [$ids]);

        // Check if job titles are used by users
        $usedCount = \App\Models\User::whereIn('job_title_id', $idArray)->count();
        if ($usedCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المسميات الوظيفية لوجود موظفين مسندين إليها. يمكنك تعطيلها بدلاً من ذلك.',
            ], 422);
        }

        return parent::destroy($ids);
    }
}
