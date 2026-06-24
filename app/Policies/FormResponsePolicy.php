<?php

namespace App\Policies;

/**
 * Managing submissions (list/view/delete) is gated by the `form-responses`
 * custom permission set. Submitting a response is gated by `forms.show` (anyone
 * who can view a form may fill it), so create/update are intentionally unused.
 */
class FormResponsePolicy extends BasePolicy
{
    protected function key(): string
    {
        return 'form-responses';
    }
}
