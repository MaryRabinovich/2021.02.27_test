<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Drug
 * @package App\Models
 */
class Drug extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $perPage = 5;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function substances()
    {
        return $this->belongsToMany(Substance::class);
    }

    /**
     * Drugs containing only visible substances
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible(Builder $query)
    {
        $query->whereDoesntHave('substances', function(Builder $query) {
            $query->where('visible', false);
        });
    }
    
    /**
     * array of substanceIDs for the given drug,
     * used inside other functions of the model 'Drug'
     * 
     * @return Array
     */
    public function getSubstancesIdsAttribute()
    {
        return $this->substances->pluck('id')->all();
    }

    /**
     * does a drug contain only given substances
     * 
     * @return Boolean
     */
    public function hasOnly(Array $substances)
    {
        if (count(array_diff($this->substances_ids, $substances))) return false;
        else return true;
    }

    /**
     * does a drug contain all given substances
     * 
     * @return Boolean
     */
    public function hasAll(Array $substances)
    {
        if (count(array_diff($substances, $this->substances_ids))) return false;
        else return true;
    }
    
    /**
     * does a drug contain almost all given substances,
     * with given number of exceptions
     * 
     * @return Boolean
     */
    public function hasAllBut(Array $substances, $exceptionsNumber = 0)
    {
        if (count(array_diff($substances, $this->substances_ids)) == $exceptionsNumber) 
            return false;
        else return true;
    }

    /**
     * number of matched substances in the given drug
     * 
     * @return Integer
     */
    public function matched($substances)
    {
        return count(array_intersect($substances, $this->substances->pluck('id')->all()));
    } 
}
