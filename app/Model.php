<?php

namespace App;

use App\Exceptions\ColumnNotFoundException;
use App\Exceptions\JsonFileException;
use App\Exceptions\LogicException;
use App\Exceptions\NotFoundException;

abstract class Model
{
    protected array $attributes = [];
    protected static QueryBuilder $queryBuilder;

    protected static function getQueryBuilder(): QueryBuilder
    {
        if (empty(self::$queryBuilder)) {
            self::$queryBuilder = new QueryBuilder(get_called_class(), get_class_vars(get_called_class()));
        }

        return self::$queryBuilder;
    }

    public static function select(array|string $columns): QueryBuilder
    {
        $queryBuilder = self::getQueryBuilder();
        return $queryBuilder->select($columns);
    }

    public static function where($column, $operator, $value = null): Model
    {
        $queryBuilder = self::getQueryBuilder();
        $queryBuilder->where($column, $operator, $value);

        return $queryBuilder->wrapModelClass();
    }

    /**
     * @throws JsonFileException
     */
    public static function create(array $attributes): Model
    {
        $queryBuilder = self::getQueryBuilder();
        return $queryBuilder->insert($attributes);
    }

    /**
     * @throws JsonFileException
     * @throws NotFoundException
     * @throws ColumnNotFoundException
     */
    public static function find($keyValue): ?Model
    {
        return (self::getQueryBuilder())->find($keyValue);
    }

    /**
     * @throws JsonFileException
     * @throws ColumnNotFoundException
     */
    public static function first(): ?Model
    {
        $queryBuilder = self::getQueryBuilder();
        $items = $queryBuilder->get();
        if (!empty($items)) {
            return $items[0];
        } else {
            return null;
        }
    }

    /**
     * @throws JsonFileException
     * @throws ColumnNotFoundException
     */
    public static function get(array|string|null $columns = null): Collection
    {
        $queryBuilder = self::getQueryBuilder();
        $items = $queryBuilder->select($columns)->get();
        return new Collection($items);
    }

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @throws LogicException
     */
    public function __get($name)
    {
        $exploded = explode('_', $name);
        $ucFirstExploded = array_map('ucfirst', $exploded);
        $getterName = 'get' . implode('', $ucFirstExploded);
        if (method_exists($this, $getterName)) {
            return $this->$getterName();
        }

        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        throw new LogicException('No such attribute: ' . $name);
    }

    /**
     * @throws JsonFileException
     * @throws ColumnNotFoundException
     */
    public function update(array $updateAttributes): Model
    {
        $queryBuilder = self::getQueryBuilder();
        foreach ($updateAttributes as $updatedAttribute => $newValue) {
            if (!isset($this->attributes[$updatedAttribute])) {
                throw new ColumnNotFoundException('no such column ' . $updatedAttribute);
            }
            $this->attributes[$updatedAttribute] = $newValue;
        }

        return $queryBuilder->update($this->attributes);
    }

    /**
     * @throws JsonFileException
     * @throws LogicException
     * @throws ColumnNotFoundException
     */
    public function destroy(): void
    {
        if (empty ($this->attributes)) {
            throw new LogicException('Nothing to delete');
        }

        (self::getQueryBuilder())->delete($this->attributes);
    }
}