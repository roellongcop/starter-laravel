<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * The kinds of question a form field can be. Stored as the `type` of each entry
 * in a Form's `form_fields` JSON array; each type carries its own `config`
 * shape (see App\Http\Requests\BaseFormRequest::formFieldsRules()).
 */
enum FieldType: string
{
    use HasOptions;

    case Text = 'text';
    case Paragraph = 'paragraph';
    case Date = 'date';
    case Duration = 'duration';
    case Range = 'range';
    case List = 'list';
}
