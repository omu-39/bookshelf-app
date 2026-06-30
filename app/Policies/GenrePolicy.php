<?php

namespace App\Policies;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GenrePolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Genre $genre): Response
    {
        return $genre->books()->doesntExist()
            ? Response::allow()
            : Response::deny('この​ジャンルには​書籍が​紐付いている​ため削除できません。​');
    }
}
