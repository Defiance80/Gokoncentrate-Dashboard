<?php

namespace App\Exports;

use Modules\Banner\Models\Banner;

class BannerExport extends BaseExport
{
    public function __construct($columns, $dateRange = [], $type = 'banner')
    {
        parent::__construct($columns, $dateRange, $type, __('messages.lbl_banners'));
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            switch ($column) {
                case 'type':
                    $modifiedHeadings[] = __('messages.lbl_type');
                    break;
                case 'banner_for':
                    $modifiedHeadings[] = __('banner.lbl_banner_for');
                    break;
                case 'status':
                    $modifiedHeadings[] = __('messages.lbl_status');
                    break;
                default:
                    $modifiedHeadings[] = ucwords(str_replace('_', ' ', $column));
                    break;
            }
        }

        return $modifiedHeadings;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Banner::query();

        $query = $query->orderBy('updated_at', 'desc');

        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {

                    case 'type':
                        $selectedData[$column] = ucfirst($row[$column]);
                        break;

                    case 'banner_for':
                        $selectedData[$column] = ucfirst($row[$column]);
                        break;

                    case 'status':
                        $selectedData[$column] = __('messages.inactive');
                        if ($row[$column]) {
                            $selectedData[$column] = __('messages.active');
                        }
                        break;

                    default:
                        $selectedData[$column] = $row[$column];
                        break;
                }
            }

            return $selectedData;
        });

        return $newQuery;
    }
}
