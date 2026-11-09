<?php

namespace Modules\CmsBackend\Http\Controllers\Shared;

use App\DataProviders\TaxonomyDataProvider;
use App\Enums\Taxonomy as EnumsTaxonomy;

class CategoryController extends AbstractTaxonomyController
{
    public function __construct(TaxonomyDataProvider $taxonomyDataProvider)
    {
        parent::__construct($taxonomyDataProvider, EnumsTaxonomy::CATEGORY, __('chuyên mục'), 'pages.category.index', 'chuyen-muc.index');
    }
}
