<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\PageMargins;

abstract class BaseExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents, ShouldAutoSize
{
    public array $columns;
    public array $dateRange;
    public $type;
    public string $reportName;

    public function __construct($columns, $dateRange, $type, $reportName = 'Report')
    {
        $this->columns = $columns;
        $this->dateRange = $dateRange;
        $this->type = $type;
        $this->reportName = $reportName;
    }

    public function startCell(): string
    {
        // Start actual data headings after report info
        // If type is available: row 5, if not: row 4
        $startRow = !empty($this->type) ? 5 : 4;
        return "A{$startRow}";
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->getDelegate()->getParent()
                    ->getDefaultStyle()
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                // Report user
                $generatedBy = (Auth::user()->first_name . ' ' . Auth::user()->last_name) ?? 'System';

                // Convert to timezone
                $generatedAt = formatDateTimeWithTimezone(now());


                // Get the last column based on number of columns
                $lastColumn = $this->getLastColumn();

                // Set report info in merged cells
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->setCellValue('A1', $this->reportName);

                $currentRow = 2;

                // Only show type if it's available and not empty
                if (!empty($this->type)) {
                    $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
                    $sheet->setCellValue("A{$currentRow}", __('messages.lbl_type') . ': ' . ucfirst($this->type));
                    $currentRow++;
                }

                $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", __('messages.lbl_generated_by') . ': ' . $generatedBy);
                $currentRow++;

                $sheet->mergeCells("A{$currentRow}:{$lastColumn}{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", __('messages.lbl_generated_on') . ': ' . $generatedAt);

                // Apply styles
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle("A1:A{$currentRow}")->getAlignment()->setHorizontal('left');
                // Bold headings row dynamically
                $headingsRow = !empty($this->type) ? 5 : 4;
                $sheet->getStyle("A{$headingsRow}:{$lastColumn}{$headingsRow}")->getFont()->setBold(true);

                // Remove borders from report info section
                $sheet->getStyle("A1:{$lastColumn}{$currentRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_NONE,
                        ],
                    ],
                ]);

                // Optimize for PDF full-page rendering
                $worksheet = $sheet->getDelegate();
                // Use A4 portrait by default; change to LANDSCAPE if needed
                // For 3 columns or less, use portrait; for more, use landscape
                $orientation = count($this->columns) > 3
                    ? PageSetup::ORIENTATION_LANDSCAPE
                    : PageSetup::ORIENTATION_PORTRAIT;

                $pageSetup = $worksheet->getPageSetup();
                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setOrientation($orientation)
                    ->setHorizontalCentered(true);

                // For CastCrew exports (Actor/Director Report), use scale instead of fit-to-width
                $isCastCrewExport = (strpos($this->reportName, 'Actor Report') !== false || strpos($this->reportName, 'Director Report') !== false);
                if ($isCastCrewExport) {
                    $pageSetup->setFitToWidth(false)
                        ->setFitToHeight(false)
                        ->setScale(85); // Scale down to fit all columns including wide bio
                } else {
                    $pageSetup->setFitToWidth(1)
                        ->setFitToHeight(0)
                        ->setScale(100);
                }

                // Tight margins to utilize page area
                $worksheet->getPageMargins()
                    ->setTop(0.25)
                    ->setBottom(0.25)
                    ->setLeft(0.25)
                    ->setRight(0.25);

                // Define print area to actual used range
                $lastRow = $worksheet->getHighestRow();
                $worksheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$lastRow}");

                // Ensure all content is visible in Excel/PDF
                // 1) Wrap text for all data cells (headings + body)
                $sheet->getStyle("A{$headingsRow}:{$lastColumn}{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical('top');

                // 2) Auto row height for wrapped text
                $worksheet->getDefaultRowDimension()->setRowHeight(-1);

                // 3) Apply per-field column widths (so PDF wraps correctly) or autosize
                $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);
                // Map normalized heading key => column width (characters)
                // Keys use snake_case of headings/columns (e.g., Target Selection => target_selection)
                $columnWidthMap = [
                    // Long text
                    'target_selection' => 50,
                    'description'      => 50,
                    'remarks'          => 50,
                    'notes'            => 50,
                    'bio'              => 60, // Wide column for bio to prevent truncation
                    'title'            => 30,
                    'redirect_url'     => 20,
                    'target_categories' => 30,
                    'server_url' => 15,
                    'server_url1' => 15,
                    'stream_type' => 10,
                    'embedded' => 10,

                    'bio' => 20,
                    'user_details'=> 25,
                    'name'=>5,
                    'coupon_discount'=>10,
                    'tax_amount'=>10,
                    'total_amount'=>10,
                    'duration'=>10,
                    'payment_method'=>10,
                    'status'=>10,


                    // Common identifiers
                    'name'             => 30,
                    'place_of_birth'   => 20,
                    'dob'              => 15,
                    'type_name'        => 10,
                    'gender'           => 10,
                    'banner_for'       => 5,
                    'placement'        => 10,
                    'type'             => 10,
                    'target_type'      =>10,
                    'content_type'     => 10,
                    'content_rating'       => 20,
                    'imdb_rating'        => 10,
                    'like_count'        => 7,
                    'watch_count'        => 7,
                    'is_restricted'        => 10,
                    // Dates and status
                    'start_date'       => 10,
                    'end_date'         => 10,
                    'status'           => 6,
                ];
                $headingMap = array_map(static function ($h) {
                    return strtolower(str_replace(' ', '_', $h));
                }, $this->columns ?: []);
                for ($col = 1; $col <= $lastColumnIndex; $col++) {
                    $idx = $col - 1;
                    $headingKey = $headingMap[$idx] ?? '';
                    if ($headingKey && array_key_exists($headingKey, $columnWidthMap)) {
                        $worksheet->getColumnDimensionByColumn($col)
                            ->setAutoSize(false)
                            ->setWidth($columnWidthMap[$headingKey]);
                    } else {
                        $worksheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                    }
                }

                // 4) Explicitly set each used row to auto-height to avoid PDF line clipping
                for ($row = $headingsRow; $row <= $lastRow; $row++) {
                    $worksheet->getRowDimension($row)->setRowHeight(-1);
                }
            },
        ];
    }

    // Helper method to get the last column based on number of columns
    protected function getLastColumn(): string
    {
        $lastColumnIndex = count($this->columns) - 1;
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumnIndex + 1);
    }

    // Abstract methods that must be implemented by child classes
    abstract public function headings(): array;
    abstract public function collection();
}
