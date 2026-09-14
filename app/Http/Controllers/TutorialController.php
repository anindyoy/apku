<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TutorialController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = trim($validated['q'] ?? '');
        $topics = json_decode(file_get_contents(resource_path('content/tutorial.json')), true, 512, JSON_THROW_ON_ERROR);
        $filtered = array_filter($topics, fn (array $topic): bool => $query === '' || Str::contains(
            json_encode($topic, JSON_UNESCAPED_UNICODE), $query, ignoreCase: true,
        ));

        return view('tutorial', ['topics' => $filtered, 'total' => count($topics), 'query' => $query]);
    }
}
