<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class FaviconController extends Controller
{
    public function show()
    {
        $path = resource_path('images/favicon-template.svg');

        if (!file_exists($path)) {
            abort(404);
        }

        $content = file_get_contents($path);
        $color = config('services.theme.color', '#38b1c9'); // Default or configured color

        // Validate hex color to prevent injection, though config should be safe
        // Support 8-digit hex by stripping alpha
        if (preg_match('/^#([a-fA-F0-9]{6})[a-fA-F0-9]{0,2}$/', $color, $matches)) {
            $color = '#' . $matches[1];
        } else {
            $color = '#38b1c9';
        }

        $content = str_replace('THEME_COLOR_PLACEHOLDER', $color, $content);

        return Response::make($content, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
