<?php

namespace App\Exports;

use Modules\Season\Models\Season;

class SeasonExport extends BaseExport
{
    public function __construct($columns, $dateRange = [], $type = 'season')
    {
        parent::__construct($columns, $dateRange, $type, __('movie.season_details'));
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            switch ($column) {
                case 'plan_id':
                    $modifiedHeadings[] = __('messages.plan');
                    break;
                case 'entertainment_id':
                    $modifiedHeadings[] = __('messages.lbl_tvshow_name');
                    break;
                case 'name':
                    $modifiedHeadings[] = __('movie.lbl_name');
                    break;
                case 'description':
                    $modifiedHeadings[] = __('movie.lbl_description');
                    break;
                case 'status':
                    $modifiedHeadings[] = __('messages.lbl_status');
                    break;
                case 'season_year':
                    $modifiedHeadings[] = __('messages.lbl_year');
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
        $query = Season::with(['entertainmentdata', 'plan']);

        $query = $query->orderBy('updated_at', 'desc');

        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {

                    case 'status':
                        $selectedData[$column] = __('messages.inactive');
                        if ($row[$column]) {
                            $selectedData[$column] = __('messages.active');
                        }
                        break;

                    case 'is_restricted':
                        $selectedData[$column] = 'no';
                        if ($row[$column]) {
                            $selectedData[$column] = 'yes';
                        }
                        break;

                    case 'plan_id':
                        // Show Plan name instead of ID
                        $selectedData[$column] = $row->plan ? $row->plan->name : ($row[$column] ?? '');
                        break;

                    case 'entertainment_id':
                        // Show TV Show name instead of ID
                        $selectedData[$column] = $row->entertainmentdata ? $row->entertainmentdata->name : ($row[$column] ?? '');
                        break;

                    case 'access':
                        // Capitalize first character of access value
                        $accessValue = $row[$column] ?? '';
                        $selectedData[$column] = !empty($accessValue) ? ucfirst($accessValue) : '';
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
