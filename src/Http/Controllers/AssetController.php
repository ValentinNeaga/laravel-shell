<?php

namespace Jakyeru\LaravelShell\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    /**
     * The assets that may be served, mapped to their path and content type.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    protected array $assets = [
        'xterm.css' => ['css/xterm.css', 'text/css'],
        'xterm.js' => ['js/xterm.js', 'application/javascript'],
        'xterm.js.map' => ['js/xterm.js.map', 'application/json'],
    ];

    /**
     * Serve the asset.
     */
    public function serve(Request $request, string $asset): BinaryFileResponse
    {
        abort_unless(isset($this->assets[$asset]), 404);

        [$path, $contentType] = $this->assets[$asset];

        return response()->file(__DIR__."/../../resources/{$path}", [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
