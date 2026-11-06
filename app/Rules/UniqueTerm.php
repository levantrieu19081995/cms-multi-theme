<?php

namespace App\Rules;

use App\Enums\Taxonomy as TaxonomyEnum;
use App\Models\Taxonomy;
use App\Models\Term;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueTerm implements ValidationRule
{
    private $id;

    private $type;

    public function __construct($id, $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // $attribute => name
        // $value => Cát, đá
        $exists = Term::where($attribute, $value)
            ->whereHas('taxonomy', function ($query) {
                $query->where('taxonomy', $this->type);
            })
            ->where('term_id', '!=', $this->id)
            ->exists();
        if ($exists) {
            $fail('Trường :attribute đã có trong cơ sở dữ liệu.');
        }
    }
}
