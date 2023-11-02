<?php

namespace App\Http\Controllers\Blade;

// use App\Http\Requests\StoreBladeCollectionCustomPropertyRequest;
use App\Models\FileSubmission;

class FileCustomPropertyController
{
    public function create()
    {
        /** @var \App\Models\FileSubmission $fileSubmission */
        $fileSubmission = FileSubmission::firstOrCreate(['id' => 1]);

        return view('collection-custom-property', compact('fileSubmission'));
    }

// StoreBladeCollectionCustomPropertyRequest 
    public function store($request)
    {
        /** @var \App\Models\FileSubmission $fileSubmission */
        $fileSubmission = FileSubmission::first();

        $fileSubmission
            ->syncFromMediaLibraryRequest($request->files)
            ->withCustomProperties('extra_field')
            ->toMediaCollection('files');

        $fileSubmission->name = $request->name;
        $fileSubmission->save();

        flash()->success('Your form has been submitted');

        return back();
    }
}
