<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }

    public function isEmpty(): bool
    {
        return Genre::exists();
    }

    public function books_count(): int
    {
        return $this->books()->count();
    }
}
