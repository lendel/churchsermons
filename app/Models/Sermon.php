<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Intervention\Image\ImageManagerStatic as Image;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMedia;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;

class Sermon extends Model implements HasMedia
{
    use LogsActivity;
    use Sluggable;
    use HasMediaTrait;
    use SluggableScopeHelpers;

    protected static $logAttributes = ['title', 'preacher'];

    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function service()
    {
        return $this->belongsTo('App\Service');
    }

    protected $fillable = [
        'title', 'preacher', 'service_id', 'category_id', 'datepreached', 'status', 'filename', 'size', 'type', 'imageurl', 'slug',
    ];

    protected $hidden = [
        'id', 'created_at', 'updated_at', 'deleted_at',
    ];

    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'title',
            ],
        ];
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getdatepreachedAttribute($value)
    {
        return Carbon::parse($value)->format('d F Y');
    }

    public static function createUploadsFolder()
    {
        $folder = 'uploads/';
        if (!file_exists(public_path($folder))) {
            mkdir(public_path($folder), @755, true);
        }

        return public_path($folder);
    }

    public static function saveSermonImage($request)
    {
        $folder = self::createUploadsFolder();

        $sermonImage = request()->file('sermonImage');
        $fileName = uniqid().'.'.$sermonImage->getClientOriginalExtension();

        $savedFile = Image::make($sermonImage)
            ->resize(400, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->save(public_path($folder).$fileName);

        return public_path($folder).$fileName;
    }

    public function removeMediaFromSermon()
    {
        $image = $this->getMedia();
        $image[0]->delete();
    }

    public function addMediaToSermon($path)
    {
        if (!file_exists($path)) {
            return false;
        }
        $this->addMedia($path)->toMediaCollection('sermon_image');

        return true;
    }

    public static function addImageUrlToSermon($slug)
    {
        $sermon = self::findBySlug($slug);
        if ($sermon->imageurl === null) {
            $image = $sermon->getMedia('sermon_image');
            $sermon->imageurl = $image[0]->getUrl();
            $sermon->save();

            return true;
        } else {
            return false;
        }
    }

    public static function addImageUrlToUpdatedSermon($slug)
    {
        $sermon = self::findBySlug($slug);
        $image = $sermon->getMedia('sermon_image');
        $sermon->imageurl = $image[0]->getUrl();
        $sermon->save();

        return true;
    }

    public static function sermonCategoryFilters()
    {
        return Category::latest('created_at')->get();
    }

    public static function sermonServiceFilters()
    {
        return Service::latest('created_at')->get();
    }
}
