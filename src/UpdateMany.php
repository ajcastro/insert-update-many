<?php

namespace AjCastro\InsertUpdateMany;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class UpdateMany
{
    protected $table;
    protected $key = 'id';
    protected $columns = [];
    protected $updatedAtColumn;

    /**
     * Contruct and pass rows for multiple update.
     *
     * @param string $table
     * @param string $key
     * @param array $columns
     * @param QueryBuilder $query
     */
    public function __construct($table, $key = 'id', array $columns = [], $updatedAtColumn = 'updated_at')
    {
        $this->table = $table;
        $this->key = $key;
        $this->columns = $columns;
        $this->updatedAtColumn = $updatedAtColumn;
    }

    /**
     * Set the key.
     *
     * @param  string  $key
     * @return $this
     */
    public function key($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Set the columns to update.
     *
     * @param  array  $columns
     * @return $this
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set updated_at column.
     *
     * @param  string $updatedAtColumn
     * @return $this
     */
    public function updatedAtColumn($updatedAtColumn)
    {
        $this->updatedAtColumn = $updatedAtColumn;
        return $this;
    }

    /**
     * Execute update statement on the given rows.
     *
     * @param  array|collect $rows
     * @return void
     */
    public function update($rows)
    {
        if (collect($rows)->isEmpty()) {
            return;
        }

        if (empty($this->columns)) {
            $this->columns = $this->getColumnsFromRows($rows);
        }

        if ($this->updatedAtColumn) {
            $ts = now();
            foreach ($rows as $row) {
                $row[$this->updatedAtColumn] = $ts;
            }
        }

        DB::statement($this->updateSql($rows));
    }

    /**
     * Get columns from rows.
     *
     * @param  array|collect $rows
     * @return array
     */
    protected function getColumnsFromRows($rows)
    {
        $row = [];

        foreach ($rows as $r) {
            if ($r instanceof Model) {
                $r = $r->getAttributes();
            }

            $row = array_merge($row, $r);
        }

        return array_keys($row);
    }

    /**
     * Return the columns to be updated.
     *
     * @return array
     */
    public function getColumns()
    {
        if ($this->updatedAtColumn) {
            $this->columns[] = $this->updatedAtColumn;
        }

        return array_unique($this->columns);
    }

    /**
     * Return the update sql.
     *
     * @param  array|collect $rows
     * @return string
     */
    protected function updateSql($rows)
    {
        $updateColumns = implode(', ', $this->updateColumns($rows));
        $whereInKeys   = implode(', ', $this->whereInKeys($rows));

        return "UPDATE `{$this->table}` SET {$updateColumns} where `{$this->key}` in ({$whereInKeys})";
    }

    /**
     * Return the where in keys.
     *
     * @param  array|collect $rows
     * @return array
     */
    protected function whereInKeys($rows)
    {
        return collect($rows)->pluck($this->key)->all();
    }

    /**
     * Return the update columns.
     *
     * @param  array|collect $rows
     * @return array
     */
    protected function updateColumns($rows)
    {
        $updates = [];

        foreach ($this->getColumns() as $column) {
            $cases = $this->cases($column, $rows);

            if (empty($cases)) {
                continue;
            }

            $updates[] = " `{$column}` = ".
            ' CASE '.
            implode(' ', $cases).
            " ELSE `{$column}` END";
        }

        return $updates;
    }

    /**
     * Return an array of column cases.
     *
     * @param  string $column
     * @param  array|collect $rows
     * @return array
     */
    protected function cases($column, $rows)
    {
        $cases = [];

        foreach ($rows as $row) {
            $value = addslashes($row[$column]);

            // Set null in mysql database
            if (is_null($row[$column])) {
                $value = 'null';
            } else {
                $value = "'{$value}'";
            }

            if ($this->includeCase($row, $column)) {
                $cases[] = "WHEN `{$this->key}` = '{$row[$this->key]}' THEN {$value}";
            }
        }

        return $cases;
    }

    /**
     * Check if the case will be included.
     *
     * @param  array|model $row
     * @param  string $column
     * @return bool
     */
    protected function includeCase($row, $column)
    {
        if ($row instanceof Model) {
            return $row->isDirty($column);
        }

        return true;
    }
}
