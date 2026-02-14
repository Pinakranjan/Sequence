<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImageBankController extends Controller
{
    /**
     * Base path for image bank storage.
     */
    private const IMAGE_PATH = 'upload/imagebank';

    /**
     * Allowed image extensions.
     */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'svg'];

    /**
     * Render the Image Bank page.
     */
    public function index()
    {
        return view('admin.utility.imagebank');
    }

    /**
     * List all images in the image bank (JSON).
     */
    public function list()
    {
        $path = public_path(self::IMAGE_PATH);

        // Ensure directory exists
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $files = File::files($path);
        $images = [];

        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());
            if (in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $filename = $file->getFilename();
                $images[] = [
                    'filename' => $filename,
                    'url' => asset(self::IMAGE_PATH . '/' . $filename),
                    'size' => $file->getSize(),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        // Sort by modified date descending (newest first)
        usort($images, fn($a, $b) => strcmp($b['modified'], $a['modified']));

        return response()->json($images);
    }

    /**
     * Upload a new image.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,svg|max:200', // 200KB max
        ], [
            'image.required' => 'Please select an image to upload.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Allowed formats: JPG, JPEG, PNG, GIF, SVG.',
            'image.max' => 'Maximum file size is 200KB.',
        ]);

        $path = public_path(self::IMAGE_PATH);

        // Ensure directory exists
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $file = $request->file('image');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension());

        // Sanitize filename
        $safeName = Str::slug($originalName, '_');
        if (empty($safeName)) {
            $safeName = 'image';
        }

        // Add timestamp to avoid conflicts
        $filename = $safeName . '_' . time() . '.' . $extension;

        // Move the file
        $file->move($path, $filename);

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully.',
            'filename' => $filename,
            'url' => asset(self::IMAGE_PATH . '/' . $filename),
            'alert-type' => 'success',
        ]);
    }

    /**
     * Delete an image.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'filename' => 'required|string|max:255',
        ]);

        $filename = basename($request->input('filename')); // Prevent directory traversal
        $filepath = public_path(self::IMAGE_PATH . '/' . $filename);

        if (!File::exists($filepath)) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found.',
                'alert-type' => 'error',
            ], 404);
        }

        File::delete($filepath);

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.',
            'alert-type' => 'success',
        ]);
    }
}
