<?php

namespace Mykholy\ReviewRateable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Reply extends Model
{
    use LogsActivity;


    /**
     * @var string
     */
    protected $table = 'replies';


    /**
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function reviewrateable()
    {
        return $this->morphTo(__FUNCTION__, 'reviewable_type', 'reviewable_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function author()
    {
        return $this->morphTo('author');
    }

    public function parent()
    {
        return $this->morphTo();
    }

    /**
     * @param Model $reviewrateable
     * @param $data
     * @param Model $author
     *
     * @return static
     */
    public static function createReply(Model $reviewrateable, array $data, Model $author, Model $parent = null)
    {
        $reply = new static();
        $reply->fill(array_merge($data, [
            'reviewrateable_id' => $reviewrateable->id,
            'reviewrateable_type' => $reviewrateable->getMorphClass(),
            'author_id' => $author->id,
            'author_type' => $author->getMorphClass(),
        ]));

        if ($parent) {
            $reply->parent_id = $parent->id;
            $reply->parent_type = $parent->getMorphClass();
        }


        $reply->save();

        return $reply;
    }

    /**
     * @param $id
     * @param $data
     *
     * @return mixed
     */
    public function updateReply($id, $data)
    {
        $reply = static::find($id);
        $reply->update($data);

        return $reply;
    }

    /**
     * @param $id
     * @param $sort
     *
     * @return mixed
     */
    public function getAllReplies($id, $sort = 'desc')
    {
        $replies = $this->select('*')
            ->where('reviewrateable_id', $id)
            ->orderBy('created_at', $sort)
            ->get();

        return $replies;
    }

    /**
     * @param $id
     * @param $sort
     *
     * @return mixed
     */
    public function getApprovedReplies($id, $sort = 'desc')
    {
        $replies = $this->select('*')
            ->where('reviewrateable_id', $id)
            ->where('approved', true)
            ->orderBy('created_at', $sort)
            ->get();

        return $replies;
    }

    /**
     * @param $id
     * @param $sort
     *
     * @return mixed
     */
    public function getNotApprovedReplies($id, $sort = 'desc')
    {
        $replies = $this->select('*')
            ->where('reviewrateable_id', $id)
            ->where('approved', false)
            ->orderBy('created_at', $sort)
            ->get();

        return $replies;
    }

    /**
     * @param $id
     * @param $limit
     * @param $sort
     *
     * @return mixed
     */
    public function getRecentReplies($id, $limit = 5, $sort = 'desc')
    {
        $replies = $this->select('*')
            ->where('reviewrateable_id', $id)
            ->where('approved', true)
            ->orderBy('created_at', $sort)
            ->limit($limit)
            ->get();

        return $replies;
    }

    /**
     * @param $id
     * @param $limit
     * @param $approved
     * @param $sort
     *
     * @return mixed
     */
    public function getRecentUserReplies($id, $limit = 5, $approved = true, $sort = 'desc')
    {
        $replies = $this->select('*')
            ->where('author_id', $id)
            ->where('approved', $approved)
            ->orderBy('created_at', $sort)
            ->limit($limit)
            ->get();

        return $replies;
    }

   

    /**
     * @param $id
     *
     * @return mixed
     */
    public function deleteReply($id)
    {
        return static::find($id)->delete();
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getUserReplies($id, $author, $sort = 'desc')
    {
        $reply = $this->where('reviewrateable_id', $id)
                ->where('author_id', $author)
                ->orderBy('id', $sort)
                ->firstOrFail();

        return $reply;
    }

     /**
     * Get the options for logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Log all attributes
            ->logOnlyDirty() // Log only the changed attributes
            ->useLogName('review'); // Optional: Set a custom log name
    }
}
