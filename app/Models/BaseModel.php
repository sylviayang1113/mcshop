<?php


namespace App\Models;


use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class BaseModel
 *
 * @package App\Models
 * @method static Builder|BaseModel newModelQuery()
 * @method static Builder|BaseModel newQuery()
 * @method static Builder|BaseModel query()
 * @mixin Eloquent
 */
class BaseModel extends Model
{
    use BooleanSoftDeletes;

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

    /**
     * 乐观锁更新 campare and save
     * @return int
     */
    public function cas()
    {
        throw_if(!$this->exists, \Exception::class, 'model not exists where cas!');
        $dirty = $this->getDirty();
        if (empty($dirty)) {
            return 0;
        }

        if ($this->usesTimestamps()) {
            $this->usesTimestamps();
            $dirty = $this->getDirty();
        }

        $diff = array_diff(array_keys($dirty), array_keys($this->original));
        throw_if(!empty($diff), \Exception::class, 'key ['.implode(',', $diff).'] not exists when cas!');

        $query = $this->newModelQeury()->where($this->getKeyName(), $this->getKey());
        foreach ($dirty as $key => $value) {
            $query->where($key, $this->getOriginal($key));
        }

        return $query->udpate($dirty);
    }

}