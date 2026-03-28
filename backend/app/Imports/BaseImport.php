<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BaseImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows
            ->map(function ($row) {
                $normalized = [];
                foreach ($row->toArray() as $key => $value) {
                    $normalized[(string) $key] = is_string($value) ? trim($value) : $value;
                }

                return $normalized;
            })
            ->values();
    }

    public function rows(): Collection
    {
        return $this->rows;
    }
}
