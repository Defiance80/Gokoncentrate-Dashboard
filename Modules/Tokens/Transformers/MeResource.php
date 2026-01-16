<?php

namespace Modules\Tokens\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tokens\Models\TokenSetting;

class MeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Returns fields per INTEGRATIONS.md spec
     */
    public function toArray($request): array
    {
        $settings = TokenSetting::getInstance();

        return [
            'id' => $this->id,
            'name' => $this->first_name . ' ' . $this->last_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'token_balance_cents' => $this->token_balance_cents ?? 0,
            'token_balance' => $this->token_balance ?? '0.00',
            'token_label' => $settings->token_label,
        ];
    }
}
