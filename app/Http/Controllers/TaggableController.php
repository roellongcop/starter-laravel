<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesDataTags;
use App\Models\Asset;
use App\Models\Form;
use App\Models\Project;
use App\Models\ReferenceFile;
use App\Models\Requirement;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Generic inline data-tag sync for any taggable resource, powering the reusable
 * <TagEditor> chip. The resource is resolved by type + token (inactive rows
 * included so tags stay editable), authorized via its own policy's `update`
 * ability, and its tags re-scoped to its organization by syncDataTags (a tag
 * from another organization is silently dropped). Returns the fresh tag chips
 * (name + colour) so the editor updates in place without a page reload.
 */
class TaggableController extends Controller
{
    use ResolvesDataTags;

    public function sync(Request $request, string $type, string $token): JsonResponse
    {
        $model = match ($type) {
            'projects' => Project::withInactive()->firstWhere('token', $token),
            'assets' => Asset::withInactive()->firstWhere('token', $token),
            'forms' => Form::withInactive()->firstWhere('token', $token),
            'reference-files' => ReferenceFile::withInactive()->firstWhere('token', $token),
            'tasks' => Task::withInactive()->firstWhere('token', $token),
            'requirements' => Requirement::withInactive()->firstWhere('token', $token),
            default => null,
        };

        abort_if($model === null, 404);

        $this->authorize('update', $model);

        $validated = $request->validate([
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'exists:data_tags,token'],
        ]);

        $model->syncDataTags($validated['tags'] ?? []);

        return response()->json([
            'tags' => $this->serializeTags($model->tags()->get()),
        ]);
    }
}
