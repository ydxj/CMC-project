<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class BaseExport implements FromArray
{
    /**
     * @param list<string> $headings
     * @param list<array<int, mixed>> $sampleRows
     */
    public function __construct(
        private array $headings,
        private array $sampleRows = []
    ) {
    }

    public function array(): array
    {
        return [
            $this->headings,
            ...$this->sampleRows,
        ];
    }
}
