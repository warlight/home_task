<?php

namespace App;

abstract class Model
{
    protected array $fillable = [];
    protected array $attributes = [];
    protected static array $data = [];
    protected static QueryBuilder $queryBuilder;

    public static function getQueryBuilder(): QueryBuilder
    {
        if (empty(self::$queryBuilder)) {
            self::$queryBuilder = new QueryBuilder(get_called_class());
        }

        return self::$queryBuilder;
    }

//    protected static function store(): void
//    {
//        $queryBuilder = self::getQueryBuilder();
//
//
//        $fileName = self::getFileName();
//        file_put_contents($fileName, json_encode(self::$data));
//    }


    public static function where($column, $operator, $value = null): Model
    {
        $queryBuilder = self::getQueryBuilder();
        $queryBuilder->where($column, $operator, $value);

        return $queryBuilder->wrapModelClass();
    }

    public static function create(array $attributes)
    {
        $queryBuilder = self::getQueryBuilder();
        $queryBuilder->insert($attributes);

        $attributes['id'] = self::getNextId();
        self::$data = array_merge(self::$data, [$attributes]);
        self::store();
        $className = get_called_class();
        return new $className($attributes);
    }

    public static function find($keyValue)
    {
        return (self::getQueryBuilder())->find($keyValue);
    }

    public static function get(): Collection
    {
        $queryBuilder = self::getQueryBuilder();
        $items = $queryBuilder->get();
        return new Collection($items);
    }

    public function first(): Model
    {
        $queryBuilder = self::getQueryBuilder();
        $items = $queryBuilder->get();
        if (!empty($items)) {
            return $items[0];
        } else {
            return $queryBuilder->wrapModelClass([]);
        }
    }

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

//    public function first(): Model
//    {
//        return $this->wrapModelClass([]);
//    }
}