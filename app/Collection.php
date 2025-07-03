<?php

namespace App;

class Collection
{
    protected array $items;

    public function __construct($records)
    {
        $this->items = $records;
    }

    public function first()
    {
        if (!empty($this->items)) {
            return $this->items[0];
        }
        return null;
    }
}