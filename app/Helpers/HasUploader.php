<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait HasUploader
{
    private function upload(Request $request, $input, $oldFile = null, $disk = null)
    {
        $file = $request->file($input);
        $ext = $file->getClientOriginalExtension();
        $filename = now()->timestamp . '-' . rand(1, 1000) . '.' . $ext;

        $path = 'upload/' . date('y') . '/' . date('m') . '/';
        $filePath = $path . $filename;

        if ($oldFile) {
            if (file_exists(public_path($oldFile))) {
                Storage::disk($disk ?? 'frontcms_uploads')->delete($oldFile);
            }
        }

        Storage::disk($disk ?? 'frontcms_uploads')->put($filePath, file_get_contents($file));
        return $filePath;
    }

    private function uploadWithFileName(Request $request, $input, $oldFile = null, $disk = null)
    {
        $file = $request->file($input);
        $filename = $file->getClientOriginalName();

        $path = 'files/';
        $filePath = $path . $filename;

        if ($oldFile) {
            if (file_exists(public_path($oldFile))) {
                Storage::disk($disk ?? 'frontcms_uploads')->delete($oldFile);
            }
        }

        Storage::disk($disk ?? 'frontcms_uploads')->put($filePath, file_get_contents($file));
        return $filePath;
    }

    private function multipleUpload(Request $request, $input, $oldFiles = [], $disk = null)
    {
        $uploadedFiles = [];
        $files = $request->file($input);

        // Return old files if no new files are uploaded
        if (empty($files)) {
            return $oldFiles;
        }

        foreach ($files as $file) {
            $ext = $file->getClientOriginalExtension();
            $filename = now()->timestamp . '_' . uniqid() . '.' . $ext;

            $path = 'upload/' . date('y') . '/' . date('m') . '/';
            $filePath = $path . $filename;

            foreach ($oldFiles as $oldFile) {
                if (file_exists(public_path($oldFile))) {
                    Storage::disk($disk ?? 'frontcms_uploads')->delete($oldFile);
                }
            }

            Storage::disk($disk ?? 'frontcms_uploads')->put($filePath, file_get_contents($file));
            $uploadedFiles[] = $filePath;
        }

        return $uploadedFiles;
    }

}
