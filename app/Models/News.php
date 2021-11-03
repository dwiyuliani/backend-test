<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'description','content','status'
    ];

    public function topic()
    {
        return $this->belongsToMany(Topic::class, 'news_topic', 'news_id', 'topic_id');
    }

} 