<?php


namespace App\Models;


use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BaseModel extends Model
{
    public const CREATED_AT = 'add_time';
    public const UPDATED_AT = 'update_time';

    public $defaultCasts = ['deleted' => 'boolean'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        parent::mergeCasts($this->defaultCasts);
    }

    public static function new ()
    {
        return new static();
    }

    public function getTable()
    {
        return $this->table ?? Str::snake(class_basename($this));
    }

    public function toArray()
    {
        $items = parent::toArray();
        //如果有过滤空字段的需求
        $items = array_filter($items, function ($item) {
            return !is_null($item);
        });
        $keys = array_keys($items);
        $keys = array_map(function ($key) {
            return lcfirst(Str::studly($key));
        }, $keys);
        $values = array_values($items);
        return array_combine($keys, $values);
    }

    public function serializeDate(DateTimeInterface $date)
    {
        return Carbon::instance($date)->toDateTimeString();
    }
}