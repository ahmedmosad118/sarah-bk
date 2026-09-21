<?php

namespace App\Core\CRUD;

class Field
{
    protected string $name;
    protected string $label;
    protected string $type = 'text'; // text, email, tel, password, number, currency, percentage, textarea, select, select2, boolean, switch, date, datetime, file, media, relation, hidden
    protected ?string $placeholder = null;
    protected ?string $helpText = null;
    protected mixed $defaultValue = null;
    protected array|string $rules = [];
    protected int $col = 12;
    protected bool $required = false;
    protected bool $readonly = false;
    protected bool $hiddenInTable = false;
    protected bool $hiddenInForm = false;
    protected array $options = [];
    protected ?string $relation = null;
    protected ?string $relationKey = 'id';
    protected ?string $relationLabel = 'name';
    protected ?string $min = null;
    protected ?string $max = null;
    protected ?string $step = null;
    protected bool $multiple = false;

    public function __construct(string $name, string $label, string $type = 'text')
    {
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
    }

    public static function make(string $name, string $label, string $type = 'text'): static
    {
        return new static($name, $label, $type);
    }

    public static function text(string $name, string $label): static
    {
        return new static($name, $label, 'text');
    }

    public static function email(string $name, string $label): static
    {
        return new static($name, $label, 'email');
    }

    public static function tel(string $name, string $label): static
    {
        return new static($name, $label, 'tel');
    }

    public static function password(string $name, string $label): static
    {
        return new static($name, $label, 'password');
    }

    public static function number(string $name, string $label): static
    {
        return new static($name, $label, 'number');
    }

    public static function currency(string $name, string $label): static
    {
        return new static($name, $label, 'currency');
    }

    public static function percentage(string $name, string $label): static
    {
        return new static($name, $label, 'percentage');
    }

    public static function textarea(string $name, string $label): static
    {
        return new static($name, $label, 'textarea');
    }

    public static function select(string $name, string $label, array $options = []): static
    {
        $field = new static($name, $label, 'select');
        $field->options = $options;
        return $field;
    }

    public static function boolean(string $name, string $label): static
    {
        return new static($name, $label, 'boolean');
    }

    public static function date(string $name, string $label): static
    {
        return new static($name, $label, 'date');
    }

    public static function datetime(string $name, string $label): static
    {
        return new static($name, $label, 'datetime');
    }

    public static function file(string $name, string $label): static
    {
        return new static($name, $label, 'file');
    }

    public static function media(string $name, string $label): static
    {
        return new static($name, $label, 'media');
    }

    public static function relation(string $name, string $label, string $relation, string $labelColumn = 'name', string $keyColumn = 'id'): static
    {
        $field = new static($name, $label, 'relation');
        $field->relation = $relation;
        $field->relationLabel = $labelColumn;
        $field->relationKey = $keyColumn;
        return $field;
    }

    public static function hidden(string $name): static
    {
        $field = new static($name, '', 'hidden');
        $field->hiddenInTable = true;
        return $field;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function help(string $helpText): static
    {
        $this->helpText = $helpText;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->defaultValue = $value;
        return $this;
    }

    public function col(int $col): static
    {
        $this->col = $col;
        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;
        return $this;
    }

    public function hiddenInTable(bool $hidden = true): static
    {
        $this->hiddenInTable = $hidden;
        return $this;
    }

    public function hiddenInForm(bool $hidden = true): static
    {
        $this->hiddenInForm = $hidden;
        return $this;
    }

    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function rules(array|string $rules): static
    {
        $this->rules = $rules;
        return $this;
    }

    public function min(string|int|float $min): static
    {
        $this->min = (string)$min;
        return $this;
    }

    public function max(string|int|float $max): static
    {
        $this->max = (string)$max;
        return $this;
    }

    public function step(string|float $step): static
    {
        $this->step = (string)$step;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRules(): array|string
    {
        $rules = $this->rules;
        if ($this->required) {
            if (is_array($rules)) {
                if (!in_array('required', $rules)) {
                    array_unshift($rules, 'required');
                }
            } elseif (is_string($rules)) {
                $rules = empty($rules) ? 'required' : "required|{$rules}";
            }
        }
        return $rules;
    }

    public function getRelation(): ?string
    {
        return $this->relation;
    }

    public function getRelationLabel(): ?string
    {
        return $this->relationLabel;
    }

    public function getRelationKey(): ?string
    {
        return $this->relationKey;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'placeholder' => $this->placeholder,
            'help_text' => $this->helpText,
            'default' => $this->defaultValue,
            'col' => $this->col,
            'required' => $this->required,
            'readonly' => $this->readonly,
            'hidden_in_table' => $this->hiddenInTable,
            'hidden_in_form' => $this->hiddenInForm,
            'options' => $this->options,
            'relation' => $this->relation,
            'relation_key' => $this->relationKey,
            'relation_label' => $this->relationLabel,
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
            'multiple' => $this->multiple,
        ];
    }
}
