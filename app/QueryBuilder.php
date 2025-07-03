<?php

namespace App;

use App\Exceptions\ColumnNotFoundException;
use App\Exceptions\NotFoundException;

class QueryBuilder
{
    protected string $model;
    protected string $fileName = '';
    protected array $modelProperties = [];

    protected array $rows;
    protected array $wheres = [];

    public function __construct(string $model, array $modelProperties)
    {
        $this->model = $model;
        $this->modelProperties = $modelProperties;
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
    }

    protected function load(): void
    {
        if (!file_exists($this->fileName)) {
            // if there is no such file - create it
            $this->storeFile();
        }

        $this->rows = json_decode(file_get_contents($this->fileName), true);
    }

    public function getPrimaryKey(): string
    {
        if (isset($this->modelProperties['primaryKey'])) {
            return $this->modelProperties['primaryKey'];
        }

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
                    case '!=':
                        $this->rows = array_filter($this->rows, function ($row) use ($condition) {
                            if (is_integer($row[$condition['column']])) {
                                return $row[$condition['column']] != $condition['value'];
                            }
                            return false;
                        });
                        break;
                }
            }
        }

        return $this->rows;
    }

    protected function save(array $attributes): void
    {
        $this->load();

        $key = $attributes[$this->getPrimaryKey()];
        $found = false;
        foreach ($this->rows as $rowKey => $row) {
            if ($key === $row[$this->getPrimaryKey()]) {
                $this->rows[$rowKey] = $attributes;
                $found = true;
            }
        }

        if (!$found) {
            $this->rows[] = $attributes;
        }

        $this->storeFile($this->rows);
    }

    public function insert(array $attributes): Model
    {
        if (!in_array($this->getPrimaryKey(), $attributes)) {
            $attributes[$this->getPrimaryKey()] = $this->getNextId();
        }

        $this->save($attributes);

        return $this->wrapModelClass($attributes);
    }

    public function update(array $attributes): Model
    {
        $this->save($attributes);
        return $this->wrapModelClass($attributes);
    }

    /* @throws ColumnNotFoundException */
    public function get(): array
    {
        $rows = $this->executeSelectQuery();
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->wrapModelClass($row);
        }

        return $items;
    }

    /* @throws ColumnNotFoundException */
    public function count(): int
    {
        $rows = $this->executeSelectQuery();
        return count($rows);
    }

    public function wrapModelClass(array $attributes = []): Model
    {
        return new $this->model($attributes);
    }

    public function where($column, $operator, $value = null): void
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
        dd($this->getPrimaryKey());
        $this->where($this->getPrimaryKey(), $primaryKeyValue);
        dd($this->wheres);
        $rows = $this->executeSelectQuery();

        if (!empty ($rows)) {
            return $this->wrapModelClass(array_shift($rows));
        }

        throw new NotFoundException();
    }

    public function delete(array $attributes)
    {
        $key = $attributes[$this->getPrimaryKey()];

        $this->where($this->getPrimaryKey(), '!=', $key);
        $newRows = $this->executeSelectQuery();
        $this->storeFile($newRows);
    }

    protected function storeFile(array $rows = []): void
    {
        $this->rows = $rows;
        file_put_contents($this->fileName, json_encode($rows));
    }
}