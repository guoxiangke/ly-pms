<?php

namespace App\Observers;

use App\Models\LyItem;
use App\Models\LyMeta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use getID3;
use getid3_writetags;
use FilesystemIterator;
use Illuminate\Http\File;

class LyItemObserver
{
    /**
     * Handle the LyItem "created" event.
     */
    public function created(LyItem $lyItem): void
    {
        if($lyItem->play_at) return ;
        $ymd = preg_replace('/\D+/', '', $lyItem->alias) . " 00:00:00";
        $play_at = Carbon::createFromFormat('ymd H:i:s', $ymd);
        $lyItem->update([
            'play_at' => $play_at
        ]);
        Log::debug(__CLASS__,[$lyItem->alias, $dt]);
    }

    /**
     * Handle the LyItem "updated" event.
     */
    public function updated(LyItem $lyItem): void
    {
        if($lyItem->isDirty('mp3') && $lyItem->mp3){
            $this->writeID3Tag(storage_path('/app/public/'.$lyItem->mp3), $lyItem->description);
        }
    }
    public static function writeID3Tag($tempFilePath, $description=null)
    {
        $getID3 = new getID3;
        // $thisFileInfo = $getID3->analyze($tempFilePath);
        // dd($thisFileInfo);
        $basename = basename($tempFilePath);//macs240715.mp3
        $pattern = '/^(\D+)(\d+)/';//macs240715v1.mp3
        preg_match($pattern, $basename, $matches);
        $code = $matches[1];//mattb
        $date = $matches[2];//250110
        $fileName = $code . $date . ".mp3";
        $lyMeta = LyMeta::whereCode($code)->firstOrFail();

        // https://github.com/JamesHeinrich/getID3/issues/422
        $TagData['attached_picture'][0] = [
            'data' => file_get_contents(public_path('logo.png')),
            'picturetypeid' => 3,
            'description' => 'Liangyou.png',
            'mime' => 'image/png',
        ];

        $tagwriter = new getid3_writetags;
        $tagwriter->filename       = $tempFilePath;
        $tagwriter->tagformats     = ['id3v2.3'];
        $tagwriter->tag_encoding   = 'UTF-8';
        $year = substr(date('Y'), 0, 2) . substr($date, 0, 2);//20 + 23/24 =》 2023->2024
        $dataStr = substr(date('Y'), 0, 2) . $date;

        $TagData['title'][]   = $lyMeta->name."-$dataStr";
        $TagData['copyright_message'][]   = "©良友电台";
        $TagData['album'][]   = $lyMeta->name;//"穿越圣经";
        $TagData['year'][]    = $year;//"2024";
        $TagData['comment'][] = $description??'';
        $tagwriter->tag_data = $TagData;
        $tagwriter->WriteTags();
        if($errors = $tagwriter->errors){
            return Validator::make([], [])->after(function ($validator) use($errors) {
                $validator->errors()->add('some_error', $errors);
            })->validate();
        }
    
        Storage::disk('s3')->putFileAs("/ly/audio/$code/$year/", new File($tempFilePath), $fileName);
        unlink($tempFilePath);
        // $this->deleteDirectory($tempFilePath);
    }

    // @see Spatie\TemporaryDirectory::deleteDirectory();
    
    protected function deleteDirectory(string $path): bool
    {
        try {
            if (is_link($path)) {
                return unlink($path);
            }

            if (! file_exists($path)) {
                return true;
            }

            if (! is_dir($path)) {
                return unlink($path);
            }

            foreach (new FilesystemIterator($path) as $item) {
                if (! $this->deleteDirectory((string) $item)) {
                    return false;
                }
            }

            /*
             * By forcing a php garbage collection cycle using gc_collect_cycles() we can ensure
             * that the rmdir does not fail due to files still being reserved in memory.
             */
            gc_collect_cycles();

            return rmdir($path);
        } catch (Throwable) {
            return false;
        }
    }
    /**
     * Handle the LyItem "deleted" event.
     */
    public function deleted(LyItem $lyItem): void
    {
        //
    }

    /**
     * Handle the LyItem "restored" event.
     */
    public function restored(LyItem $lyItem): void
    {
        //
    }

    /**
     * Handle the LyItem "force deleted" event.
     */
    public function forceDeleted(LyItem $lyItem): void
    {
        //
    }
}
