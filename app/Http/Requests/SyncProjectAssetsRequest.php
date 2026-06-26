<?php

namespace App\Http\Requests;

class SyncProjectAssetsRequest extends BaseFormRequest
{
    /**
     * The full set of asset tokens the project should be bound to. `present`
     * (not `required`) so an empty array is valid — it detaches every asset.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assets' => ['present', 'array'],
            'assets.*' => ['string', 'exists:assets,token'],
        ];
    }
}
