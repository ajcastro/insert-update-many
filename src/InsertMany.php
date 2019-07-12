<?php

namespace AjCastro\InsertUpdateMany;

use Illuminate\Database\Eloquent\Model;

class InsertMany
{
    protected $query;
    public $timestamps = true;
    protected $createdAtColumn;
    protected $updatedAtColumn;

    public function __construct($query, $timestamps = true, $createdAtColumn = 'created_at', $updatedAtColumn = 'updated_at')
    {
        $this->query = $query;
        $this->timestamps = $timestamps;
        $this->createdAtColumn = $createdAtColumn;
        $this->updatedAtColumn = $updatedAtColumn;
    }

    public function insert($rows)
    {
        $ts = now();

        $rows = collect($rows)->map(function ($row) use ($ts) {
            $timestamps = $this->timestamps;
            $createdAtColumn = $this->createdAtColumn;
            $updatedAtColumn = $this->updatedAtColumn;

            if ($row instanceof Model) {
                $timestamps = $row->usesTimestamps();
                $createdAtColumn = $row->getCreatedAtColumn();
                $updatedAtColumn = $row->getUpdatedAtColumn();
                $row->{$createdAtColumn} = $ts;
                $row->{$updatedAtColumn} = $ts;
                $row = array_only($row->getAttributes(), $row->getFillable());
            }

            if ($timestamps) {
                $row = $row + [$createdAtColumn => $ts, $updatedAtColumn => $ts];
            }

            return $row;
        })
        ->all();

        return $this->query->insert($rows);
    }
}
