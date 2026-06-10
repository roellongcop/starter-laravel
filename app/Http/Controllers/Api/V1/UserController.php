<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Keyset-paginated list of active users for pickers/autocomplete. Returns the
     * standard cursor envelope: { data, next_cursor, prev_cursor, has_more }.
     */
    public function available(Request $request): JsonResponse
    {
        $search = trim((string) $request->string('search'));
        $like = '%'.escape_like($search).'%';

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', like_operator(), $like)
                ->orWhere('email', like_operator(), $like)))
            ->keyset()
            ->cursorPaginate((int) $request->integer('per_page', config('keen.pagination_size')))
            ->withQueryString();

        return response()->json(cursorResponse($users, fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
        ]));
    }
}
