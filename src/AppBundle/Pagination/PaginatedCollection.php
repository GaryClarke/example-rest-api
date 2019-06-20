<?php

namespace AppBundle\Pagination;

class PaginatedCollection
{
    private $items;
    private $total;
    private $count;

    public function __construct($items, $total)
    {
        $this->items = $items;
        $this->total = $total;
        $this->count = count($items);
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getCount()
    {
        return $this->count;
    }
}