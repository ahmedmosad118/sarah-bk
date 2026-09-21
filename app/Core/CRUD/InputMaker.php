<?php

namespace App\Core\CRUD;

class InputMaker
{
    /** @var array<Field> */
    protected array $fields = [];
    protected ?string $title = null;
    protected ?string $singularTitle = null;
    protected ?string $model = null;

    public static function make(): static
    {
        return new static();
    }

    public function fields(array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    public function addField(Field $field): static
    {
        $this->fields[] = $field;
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function singularTitle(string $singularTitle): static
    {
        $this->singularTitle = $singularTitle;
        return $this;
    }

    public function model(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getValidationRules(bool $isUpdate = false, mixed $currentId = null): array
    {
        $rules = [];
        foreach ($this->fields as $field) {
            $fieldRules = $field->getRules();
            if (empty($fieldRules)) {
                continue;
            }

            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            // Adjust unique rules on update
            if ($isUpdate && $currentId) {
                $fieldRules = array_map(function ($rule) use ($currentId) {
                    if (str_starts_with($rule, 'unique:')) {
                        return $rule . ',' . $currentId;
                    }
                    return $rule;
                }, $fieldRules);
            }

            $rules[$field->getName()] = $fieldRules;
        }
        return $rules;
    }

    public function getFillableFields(): array
    {
        $fillable = [];
        foreach ($this->fields as $field) {
            $data = $field->toArray();
            if (!$data['hidden_in_form'] && $data['type'] !== 'media' && $data['type'] !== 'file') {
                $fillable[] = $field->getName();
            }
        }
        return $fillable;
    }

    public function toSchema(): array
    {
        $fieldsData = [];

        foreach ($this->fields as $field) {
            $data = $field->toArray();

            // Automatically resolve options for relation fields
            if ($data['type'] === 'relation' && empty($data['options'])) {
                $relationName = $field->getRelation();
                $labelCol = $field->getRelationLabel() ?: 'name';
                $keyCol = $field->getRelationKey() ?: 'id';

                try {
                    $options = [];
                    if ($this->model && class_exists($this->model)) {
                        $dummy = new $this->model();
                        if (method_exists($dummy, $relationName)) {
                            $relInstance = $dummy->{$relationName}();
                            $relatedModelClass = get_class($relInstance->getRelated());

                            $records = $relatedModelClass::query()->latest('id')->take(100)->get();
                            $options = $records->map(function ($r) use ($keyCol, $labelCol) {
                                $val = $r->{$keyCol};
                                $lbl = $r->{$labelCol} ?? $r->title ?? $r->name ?? (string)$val;
                                return ['value' => $val, 'label' => $lbl];
                            })->toArray();
                        }
                    }
                    $data['options'] = $options;
                } catch (\Throwable $e) {
                    $data['options'] = [];
                }
            }

            $fieldsData[] = $data;
        }

        return [
            'title' => $this->title,
            'singular_title' => $this->singularTitle,
            'model' => $this->model,
            'fields' => $fieldsData,
        ];
    }
}
