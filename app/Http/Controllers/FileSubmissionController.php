<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileSubmissionRequest;
use App\Http\Requests\UpdateFileSubmissionRequest;
use App\Models\FileSubmission;

class FileSubmissionController extends Controller
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
        /** @var \App\Models\FileSubmission $fileSubmission */
        $fileSubmission = FileSubmission::firstOrCreate(['user_id' => auth()->id()]);

        return view('collection-custom-property', compact('fileSubmission'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFileSubmissionRequest $request)
    {
        //
        /** @var \App\Models\FileSubmission $fileSubmission */
        $fileSubmission = FileSubmission::where('user_id', auth()->id())->first();

        $fileSubmission
            ->syncFromMediaLibraryRequest($request->mp3)
            ->withCustomProperties('extra_field')
            ->toMediaCollection('mp3');

        $fileSubmission->title = $request->title;
        $fileSubmission->save();

        // flash()->success('Your form has been submitted');

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(FileSubmission $fileSubmission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FileSubmission $fileSubmission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFileSubmissionRequest $request, FileSubmission $fileSubmission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FileSubmission $fileSubmission)
    {
        //
    }
}
