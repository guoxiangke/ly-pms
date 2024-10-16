<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Metable\Metable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Tags\HasTags;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Laravel\Scout\Searchable;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\hasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Http\File;
use App;
use getID3;
use getid3_writetags;
use Illuminate\Support\Facades\Log;

class LyMeta extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Metable;
    use HasTags;

    // use Searchable;
    // use LogsActivity;
    protected static $recordEvents = ['updated','deleted'];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()
            ->logOnlyDirty()
            ;
    }

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'begin_at', 'end_at', 'unpublished_at'];
    // https://laracasts.com/discuss/channels/nova/nova-datetime-field-must-cast-to-datetime-in-eloquent-model
    protected $casts = [
        'begin_at' => 'date',
        'end_at' => 'date',
        'unpublished_at' => 'date',
        'lts_first_play_at' => 'date',
    ];
    protected $appends = [
        'cover',
        'category',
    ];


    public function announcers()
    {
        return $this->belongsToMany(Announcer::class, 'announcer_has_programs' , 'ly_meta_id', 'announcer_id');
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(Make::class);
    }


    public function scopeActive($query)
    {
        return $query->whereNull('end_at')
                ->Orwhere('end_at', '>', now());
    }


    public function scopeNotLts($query)
    {
        return $query->whereNot('code','like','lts%');
    }


    // append programs API的category
    // +category : "生活智慧"
    public function getCategoryAttribute(){
        return $this->tags()->firstOrNew(['name'=>'noTagName'])->name;
    }

    /**
     * Get the cover.
     */
    protected function cover(): Attribute
    {
        $domain =  config('pms.cloudfront_domain');
        $cover = "{$domain}/ly/image/cover/{$this->code}.jpg";
        return Attribute::make(
            get: fn () => $cover,
        );
    }

    // 前台显示，不包含 明后天的节目 @see LyItem::addGlobalScope('ancient'
    public function ly_items($order = "DESC"): HasMany
    {
        return $this->HasMany(LyItem::class)
            ->whereBetween('play_at', [now()->subDays($this->max_list_count), now()])
            ->orderBy('alias', $order);
    }

    public function getMaxListCountAttribute(){
        return $maxCounts = $this->counts_max_list??31;
    }

    // $lyMeta->isLts
    public function isLts(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::startsWith($this->code, 'lts'),
        );
    }

    // if($this->isLts)
    public function lts_items($order = "DESC")
    {
        return LtsItem::with('lts_meta')->whereBetween('play_at', [now()->subDays($this->max_list_count), now()])->orderBy('play_at', $order)->get()->filter(fn($ltsItem) => $ltsItem->lts_meta->ly_meta_id == $this->id);

    }

    public function ltsItems()
    {
        return $this
            ->hasManyThrough(LtsItem::class, LtsMeta::class)
            ->withoutGlobalScopes();
    }

    public function ltsMetas()
    {
        return $this->hasMany(LtsMeta::class);
    }
    
    // Call to undefined method App\Models\LyMeta::lyItems()
    public function lyitems($order = "DESC"): HasMany
    {
        return $this->ly_items($order);
    }

    // 后台显示，包含 明后天的及31天以外的节目
    public function ly_items_with_future(): HasMany
    {
        return $this->HasMany(LyItem::class)
            ->withoutGlobalScopes()
            ->orderBy('alias', 'DESC');
    }

    public function getLtsFirstPlayAttribute(){
        return $this->getMeta('lts_first_play');
    }

    public function getLtsFirstPlayAtAttribute(){
        return \DateTime::createFromFormat("Y-m-d", $this->getMeta('lts_first_play_at','2000-01-01'));
    }

    //
    public static function writeID3TagAndSync2S3($tempFilePath, $description=null)
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
            'data' => file_get_contents(public_path('Liangyou.png')),
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
            return Log::error(__FILE__,[__LINE__, $tempFilePath, $fileName, $errors]);
        }

        if(App::isLocal()){
            Storage::putFileAs("/ly/audio/$year/$code/", new File($tempFilePath), $fileName);
        }else{
           Storage::disk('s3')->putFileAs("/ly/audio/$year/$code/", new File($tempFilePath), $fileName);
        }
        unlink($tempFilePath);
        rmdir(dirname($tempFilePath));
        rmdir(dirname(dirname($tempFilePath) . ".remove"));
        // @see Spatie\TemporaryDirectory::deleteDirectory();
        // static::deleteDirectory($tempFilePath);
    }

    public static function getLtsTags($code)
    {
        $tags = [];
        switch ($code) {
            case 'ltsnp':
                $tags[] = '启航课程';
                $tags[] = '专题特辑';
                break;
            case 'ltstpa1':
            case 'ltstpa2':
                $tags[] = '普及本科';
                $tags[] = '专题特辑';
                break;
            case 'ltstpb1':
            case 'ltstpb2':
                $tags[] = '普及进深';
                break;

            default:
                // code...
                break;
        }
        // if($local != 'en') app()->setLocale('en'); 
        // $options = LtsMeta::withAnyTags($tags, 'lts')->pluck('name','id')->toArray();
        return $tags;
    }

    // hasItemToday()
    public function hasItemByDate($date = null)
    {
        $playAt = $date??now();
        $schedule = explode(",", $this->rrule_by_day);
        return in_array(Str::upper($playAt->minDayName), $schedule);
    }

}
