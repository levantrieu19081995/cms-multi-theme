<?php

namespace App\Http\Controllers;

use App\DataProviders\TagDataProvider;
use Illuminate\Http\Request;

class TagController extends Controller
{
    protected TagDataProvider $tagDataProvider;
    public function __construct(TagDataProvider $tagDataProvider)
    {
    
        $this->tagDataProvider = $tagDataProvider;
      
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $pageSize = $request->pageSize ?? 20;
        $filter = trim(strip_tags($request->query('filter')));
        //Lấy danh sách chuyên mục theo cha con
        $tags = $this->tagDataProvider->paginate( $filter,$pageSize);
        $tags->appends($request->all());
        return view('pages.category.index', compact(['tags']));
    }

}
