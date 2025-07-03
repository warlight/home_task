<?php

namespace App;

class Collection
{
    protected array $items;

    public function __construct($records)
    {
        $this->items = $records;
    }

    public function where($column, $value)
    {
        //
    }
}