<?php

namespace Arm092\LivewireDatatables\Exports;

use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatatableExport extends DefaultValueBinder implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnWidths, WithStyles, WithCustomValueBinder
{
    use Exportable;

    public $collection;
    protected string $downloadFileName = 'DatatableExport.xlsx';
    public $styles = [];
    public $columnWidths = [];

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value) && str_starts_with($value, '=')) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function collection(): Enumerable
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return array_keys((array) $this->collection->first());
    }

    public function setFileName($fileName)
    {
        $this->downloadFileName = $fileName;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->downloadFileName;
    }

    public function setColumnWidths($columnWidths)
    {
        $this->columnWidths = $columnWidths;

        return $this;
    }

    public function getColumnWidths(): array
    {
        return $this->columnWidths;
    }

    public function columnWidths(): array
    {
        return $this->getColumnWidths();
    }

    public function setStyles($styles)
    {
        $this->styles = $styles;

        return $this;
    }

    public function getStyles(): array
    {
        return $this->styles;
    }

    public function styles(Worksheet $sheet): ?array
    {
        return $this->getStyles();
    }

    public function download(
        ?string $fileName = null,
        ?string $writerType = null,
        ?array $headers = null,
    ): BinaryFileResponse
    {
        return Excel::download(
            $this,
            $fileName ?? $this->getFileName(),
            $writerType,
            $headers ?? [],
        );
    }
}
