<?php

namespace App;

use App\Exceptions\ColumnNotFoundException;
use App\Exceptions\NotFoundException;

class QueryBuilder
{
    protected string $model;
    protected string $fileName = '';
    protected array $rows;

    protected array $wheres = [];

    public function __construct(string $model)
    {
        $this->model = $model;
        $this->connect();
    }

    protected function getFileName(): string
    {
        $className = $this->model;
        $explodedClassName = explode('\\', $className);
        $splitCamelCase = preg_split('/(?=[A-Z])/', array_pop($explodedClassName), -1, PREG_SPLIT_NO_EMPTY);
        $snakeCase = implode('_', $splitCamelCase);
        return 'data/' . strtolower($snakeCase) . '.json';
    }

    protected function connect(): void
    {
        // get classes name and find JSON file in directory:
        $this->fileName = self::getFileName();
        if (!file_exists($this->fileName)) {
            // if there is no such file - create it
            file_put_contents($this->fileName, '[]');
        }
    }

    protected function load(): void
    {
        $this->rows = json_decode(file_get_contents($this->fileName), true);
    }

    public function getPrimaryKey(): string
    {
        return 'id';
    }

    protected function getNextId(): int
    {
        if (empty($this->rows)) {
            $this->load();
        }

        if (!empty ($this->rows)) {
            return max(array_column($this->rows, $this->getPrimaryKey())) + 1;
        }

        return 1;
    }

    protected function executeSelectQuery(): array
    {
        $this->load();
        $columns = array_keys($this->rows[0] ?? []);

        if (!empty($this->wheres)) {
            foreach ($this->wheres as $condition) {
                // check if such column already exists in
                if (!empty($columns) && !in_array($condition['column'], $columns)) {
                    throw new ColumnNotFoundException('no such column: ' . $condition['column']);
                }
                switch ($condition['operator']) {
                    case '=':
                        $this->rows = array_filter($this->rows, function ($row) use ($condition) {
                            return $row[$condition['column']] == $condition['value'];
                        });
                        break;
                    case '>':
                        $this->rows = array_filter($this->rows, function ($row) use ($condition) {
                            if (is_integer($row[$condition['column']])) {
                                return $row[$condition['column']] > $condition['value'];
                            }
                            return false;
                        });
                        break;
                    case '<':
                        $this->rows = array_filter($this->rows, function ($row) use ($condition) {
                            if (is_integer($row[$condition['column']])) {
                                return $row[$condition['column']] < $condition['value'];
                            }
                            return false;
                        });
                        break;
                }
            }
        }

        return $this->rows;
    }

    public function insert(array $attributes): Model
    {
        if (!in_array($this->getPrimaryKey(), $attributes)) {
            $attributes[$this->getPrimaryKey()] = $this->getNextId();
        }

        $this->save($attributes);

        return $this->wrapModelClass($attributes);
    }

    public function get(): array
    {
        $rows = $this->executeSelectQuery();
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->wrapModelClass($row);
        }

        return $items;
    }

    public function count(): int
    {
        $rows = $this->executeSelectQuery();
        return count($rows);
    }

    public function wrapModelClass(array $attributes = []): Model
    {
        return new $this->model($attributes);
    }

    public function where($column, $operator, $value = 'null'): void
    {
        $condition = [
            'column' => $column
        ];

        if (is_null($value)) {
            $condition['operator'] = '=';
            $condition['value'] = $operator;
        } else {
            $condition['operator'] = $operator;
            $condition['value'] = $value;
        }

        $this->wheres[] = $condition;
    }

    public function find(int $primaryKeyValue): Model
    {
        $this->where($this->getPrimaryKey(), $primaryKeyValue);
        $rows = $this->executeSelectQuery();

        if (!empty ($rows)) {
            return $this->wrapModelClass($rows[0]);
        }

        throw new NotFoundException();
    }
}