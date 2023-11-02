<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBibleItemRequest;
use App\Http\Requests\UpdateBibleItemRequest;
use App\Models\BibleItem;

class BibleItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBibleItemRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(BibleItem $bibleItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BibleItem $bibleItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBibleItemRequest $request, BibleItem $bibleItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BibleItem $bibleItem)
    {
        //
    }
}
