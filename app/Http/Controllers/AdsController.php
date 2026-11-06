<?php

namespace App\Http\Controllers;

use App\DataProviders\AdsDataProvider;
use App\DataProviders\OptionDataProvider;
use App\Facades\Toastr;
use App\Http\Requests\Ad\CreateAdRequest;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;

class AdsController extends AbstractAdsController
{
    public function __construct( AdsDataProvider $adsDataProvider, OptionDataProvider $optionDataProvider)
    {
        parent::__construct($adsDataProvider,$optionDataProvider,"ads");

    }
}
