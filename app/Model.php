<?php

namespace App;

abstract class Model
{
    protected array $fillable = [];
    protected array $attributes = [];
    protected static array $data = [];

    protected static function getFileName(): string
    {
        $className = get_called_class();
        $explodedClassName = explode('\\', $className);
        $splitCamelCase = preg_split('/(?=[A-Z])/', array_pop($explodedClassName), -1, PREG_SPLIT_NO_EMPTY);
        $snakeCase = implode('_', $splitCamelCase);
        return 'data/' . strtolower($snakeCase) . '.json';
    }

    protected static function connect(): void
    {
        // get classes name and find JSON file in directory:
        $fileName = self::getFileName();
        if (!file_exists($fileName)) {
            // if there is no such file - create it
            file_put_contents($fileName, '[]');
        }

        self::$data = json_decode(file_get_contents($fileName), true);
    }

    protected static function store(): void
    {
        $fileName = self::getFileName();
        file_put_contents($fileName, json_encode(self::$data));
    }

    protected static function getNextId(): int
    {
        if (!empty(self::$data)) {
            return max(array_column(self::$data, 'id')) + 1;
        }

        return 1;
    }

    public static function create(array $attributes)
    {
        self::connect();
        $attributes['id'] = self::getNextId();
        self::$data = array_merge(self::$data, [$attributes]);
        self::store();
        $className = get_called_class();
        return new $className($attributes);
    }

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public static function get()
    {
        self::connect();
        $collectionItems = [];
        $className = get_called_class();
        foreach (self::$data as $item) {
            $collectionItems[] = new $className($item);
        }
        return new Collection($collectionItems);
    }
}