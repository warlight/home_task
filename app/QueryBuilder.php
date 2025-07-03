<?php

namespace App;

use App\Exceptions\ColumnNotFoundException;
use App\Exceptions\JsonFileException;
use App\Exceptions\NotFoundException;
use Exception;

class QueryBuilder
{
    protected string $model;
    protected string $fileName = '';
    protected array $modelProperties = [];
    protected array $selectColumns = [];

    protected array $rows;
    protected array $wheres = [];

    public function __construct(string $model, array $modelProperties)
    {
        $this->model = $model;
        $this->modelProperties = $modelProperties;
        $this->fileName = self::getFileName();
    }

    protected function getFileName(): string
    {
        $className = $this->model;
        $explodedClassName = explode('\\', $className);
        $splitCamelCase = preg_split('/(?=[A-Z])/', array_pop($explodedClassName), -1, PREG_SPLIT_NO_EMPTY);
        $snakeCase = implode('_', $splitCamelCase);
        return 'data/' . strtolower($snakeCase) . '.json';
    }

    /**
     * @throws JsonFileException
     */
    protected function load(): void
    {
        if (!file_exists($this->fileName)) {
            // if there is no such file - create it
            $this->storeFile();
        }

        $fileContent = file_get_contents($this->fileName);
        $rows = json_decode($fileContent, true);
        if (is_null($rows) && !empty($fileContent)) {
            throw new JsonFileException('File ' . $this->fileName . ' contains invalid JSON');
        }
        $this->rows = $rows ?? [];
    }

    protected function getPrimaryKey(): string
    {
        if (isset($this->modelProperties['primaryKey'])) {
            return $this->modelProperties['primaryKey'];
        }

        return 'id';
    }

    /**
     * @throws JsonFileException
     */
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

    /**
     * @throws JsonFileException
     * @throws ColumnNotFoundException
     */
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

        if (!empty($this->selectColumns)) {
            if (!empty($columns)) {
                foreach ($this->selectColumns as $selectColumn) {
                    if (!in_array($selectColumn, $columns)) {
                        throw new ColumnNotFoundException('no such column: ' . $selectColumn);
                    }
                }
            }
            $this->rows = array_map(function ($row) {
                $newRow = [];
                foreach ($this->selectColumns as $column) {
                    $newRow[$column] = $row[$column];
                }
                return $newRow;
            }, $this->rows);
        }

        // reset conditions
        $this->selectColumns = [];
        $this->wheres = [];

        return $this->rows;
    }

    /**
     * @throws JsonFileException
     */
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

    /**
     * @throws JsonFileException
     */
    public function insert(array $attributes): Model
    {
        if (!in_array($this->getPrimaryKey(), $attributes)) {
            $attributes[$this->getPrimaryKey()] = $this->getNextId();
        }

        $this->save($attributes);

        return $this->wrapModelClass($attributes);
    }

    /**
     * @throws JsonFileException
     */
    public function update(array $attributes): Model
    {
        $this->save($attributes);
        return $this->wrapModelClass($attributes);
    }

    /* @throws ColumnNotFoundException
     * @throws JsonFileException
     */
    public function get(): array
    {
        $rows = $this->executeSelectQuery();
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->wrapModelClass($row);
        }

        return $items;
    }

    /* @throws ColumnNotFoundException
     * @throws JsonFileException
     */
    public function count(): int
    {
        $rows = $this->executeSelectQuery();
        return count($rows);
    }

    public function wrapModelClass(array $attributes = []): Model
    {
        return new $this->model($attributes);
    }

    public function select(string|array|null $columns): self
    {
        if (!is_null($columns)) {
            $this->selectColumns = is_array($columns) ? $columns : [$columns];
        }

        return $this;
    }

    public function where($column, $operator, $value = null): self
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

        return $this;
    }

    /**
     * @throws NotFoundException
     * @throws ColumnNotFoundException|JsonFileException
     */
    public function find(int $primaryKeyValue): ?Model
    {
        $this->where($this->getPrimaryKey(), $primaryKeyValue);
        $rows = $this->executeSelectQuery();

        if (!empty ($rows)) {
            return $this->wrapModelClass(array_shift($rows));
        }

        throw new NotFoundException('No such record with primary key value ' . $primaryKeyValue);
    }

    /**
     * @throws JsonFileException
     * @throws ColumnNotFoundException
     */
    public function delete(array $attributes): void
    {
        $key = $attributes[$this->getPrimaryKey()];

        $this->where($this->getPrimaryKey(), '!=', $key);
        $newRows = $this->executeSelectQuery();
        $this->storeFile($newRows);
    }

    /**
     * @throws JsonFileException
     */
    protected function storeFile(array $rows = []): void
    {
        $this->rows = $rows;
        try {
            file_put_contents($this->fileName, json_encode($rows));
        } catch (Exception $exception) {
            throw new JsonFileException($error->getMessage());
        }
    }
}