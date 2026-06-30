<?php

namespace App\Policies;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Validation\Rules\Exists;

class GenrePolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Genre $genre): bool
    {
        return $genre->books()->doesntExist();
    }
}
