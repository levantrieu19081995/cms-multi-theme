<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use UniSharp\LaravelFilemanager\Lfm;
use UniSharp\LaravelFilemanager\LfmPath;

class FileController extends Controller
{

    /**
     * Set up needed functions.
     *
     * @return object|null
     */
    public function __get($var_name)
    {
        if ($var_name === 'lfm') {
            return app(LfmPath::class);
        } elseif ($var_name === 'helper') {
            return app(Lfm::class);
        }
    }

    public function index()
    {
        return view('pages.file.index')->withHelper($this->helper);
    }
}
