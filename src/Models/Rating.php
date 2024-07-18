<?php

namespace Mykholy\ReviewRateable\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Mykholy\ReviewRateable\Models\Reply;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;

class Rating extends Model
{
    use LogsActivity;


    /**
     * @var string
     */
    protected $table = 'reviews';

    /**
     * @var string
     */
    protected $rating;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

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

    /**
     * Define the relationship with replies.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function replies()
    {
        return $this->morphMany(Reply::class, 'reviewrateable');
    }

    /**
     * @param array $data
     * @param Model $author
     * @param Model|null $parent
     *
     * 
     */
    public function reply(array $data, Model $author, Model $parent = null)
    {
        return Reply::createReply($this, $data, $author, $parent);
    }

    /**
     * @param Model $reviewrateable
     * @param $data
     * @param Model $author
     *
     * @return static
     */
    public function createRating(Model $reviewrateable, $data, Model $author)
    {
        $rating = new static();
        $rating->fill(array_merge($data, [
            'author_id' => $author->id,
            'author_type' => $author->getMorphClass(),
        ]));

        $reviewrateable->ratings()->save($rating);

        return $rating;
    }

    /**
     * @param $id
     * @param $data
     *
     * @return mixed
     */
    public function updateRating($id, $data)
    {
        $rating = static::find($id);
        $rating->update($data);

        return $rating;
    }

    /**
     * @param $id
     * @param $sort
     *
     * @return mixed
     */
    public function getAllRatings($id, $sort = 'desc')
    {
        $rating = $this->select('*')
            ->where('reviewrateable_id', $id)
            ->orderBy('created_at', $sort)
            ->get();

        return $rating;
    }

    /**
     * @param $id
     * @param $sort
     *
     * @return mixed
     */
    public function getApprovedRatings($id, $sort = 'desc')
    {
        $rating = $this->select('*')
            ->where('reviewrateable_id', $id)
            ->where('approved', true)
            ->orderBy('created_at', $sort)
            ->get();

        return $rating;
    }

    /**
     * @param $id
     * @param $sort
     *
     * @return mixed
     */
    public function getNotApprovedRatings($id, $sort = 'desc')
    {
        $rating = $this->select('*')
            ->where('reviewrateable_id', $id)
            ->where('approved', false)
            ->orderBy('created_at', $sort)
            ->get();

        return $rating;
    }

    /**
     * @param $id
     * @param $limit
     * @param $sort
     *
     * @return mixed
     */
    public function getRecentRatings($id, $limit = 5, $sort = 'desc')
    {
        $rating = $this->select('*')
            ->where('reviewrateable_id', $id)
            ->where('approved', true)
            ->orderBy('created_at', $sort)
            ->limit($limit)
            ->get();

        return $rating;
    }

    /**
     * @param $id
     * @param $limit
     * @param $approved
     * @param $sort
     *
     * @return mixed
     */
    public function getRecentUserRatings($id, $limit = 5, $approved = true, $sort = 'desc')
    {
        $rating = $this->select('*')
            ->where('author_id', $id)
            ->where('approved', $approved)
            ->orderBy('created_at', $sort)
            ->limit($limit)
            ->get();

        return $rating;
    }

    /**
     * @param $rating
     * @param $type
     * @param $approved
     * @param $sort
     *
     * @return mixed
     */
    public function getCollectionByAverageRating($rating, $type = 'rating', $approved = true, $sort = 'asc')
    {
        $this->rating = $rating;
        $this->type = $type;

        $ratings = $this->whereHasMorph('reviewrateable', '*', function (Builder $query) {
            return $query->groupBy('reviewrateable_id')
                ->havingRaw('AVG(' . $this->type . ')  >= ' . $this->rating);
        })->where('approved', $approved)
            ->orderBy($type, $sort)->get();

        // ddd($ratings);
        return $ratings;
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function deleteRating($id)
    {
        return static::find($id)->delete();
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getUserRatings($id, $author, $sort = 'desc')
    {
        $rating = $this->where('reviewrateable_id', $id)
            ->where('author_id', $author)
            ->orderBy('id', $sort)
            ->firstOrFail();

        return $rating;
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
