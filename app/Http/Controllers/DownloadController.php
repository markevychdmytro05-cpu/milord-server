<?php

namespace App\Http\Controllers;

use App\Models\Release;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    public function index(): View
    {
        return view('public.downloads', ['releases' => Release::latestPerTarget()]);
    }

    public function start(string $target): RedirectResponse
    {
        [$platform, $arch] = explode('-', $target);
        $release = Release::latestFor($platform, $arch);

        abort_unless($release && $release->fileExists(), 404);

        return redirect()->to($release->temporaryUrl(10));
    }

    public function file(Release $release): BinaryFileResponse
    {
        abort_unless($release->is_active && $release->fileExists(), 404);

        $release->increment('downloads_count');

        return response()->download(Storage::disk(Release::DISK)->path($release->file_path), $release->file_name, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
