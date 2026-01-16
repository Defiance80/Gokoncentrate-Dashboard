<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Subscriptions\Models\Subscription;
use currency;
class SubscriptionExport implements FromCollection, WithHeadings
{
    public array $columns;

    public function __construct($columns)
    {
        $this->columns = $columns;
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            // Capitalize each word and replace underscores with spaces
            $modifiedHeadings[] = ucwords(str_replace('_', ' ', $column));
        }

        return $modifiedHeadings;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Subscription::with(['user', 'plan','subscription_transaction']) // eager load relationships
        ->orderBy('updated_at', 'desc')
        ->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {

                    case 'status':
                        $selectedData[$column] = $row[$column];
                        break;

                    case 'user_details':
                        $user = $row->user;
                        $selectedData[$column] = $user
                            ? 'Name: ' . ($user->full_name ?? '-') . ', Email: ' . ($user->email ?? '-')
                            : 'Name: -, Email: -';
                        break;

                    case 'duration':
                        $selectedData[$column] = $row->plan
                            ? ($row->plan->duration_value . ' ' . $row->plan->duration)
                            : '-';
                        break;
                        
                    case 'payment_method':
                        $selectedData[$column] = $row->subscription_transaction
                            ? ($row->subscription_transaction->payment_type)
                            : '-';
                        break;

                    case 'amount':
                        $selectedData[$column] = $row[$column] 
                            ? \Currency::format($row[$column])
                            : '-';
                        break;

                    case 'tax_amount':
                        $selectedData[$column] = $row[$column] 
                            ? \Currency::format($row[$column])
                            : '-';
                        break;

                    case 'total_amount':
                        $selectedData[$column] = $row[$column] 
                            ? \Currency::format($row[$column])
                            : '-';
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
