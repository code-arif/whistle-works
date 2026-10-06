<?php

namespace App\Helpers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class Helper
{
    /**
     * Upload a file or image to a specified directory within public/uploads.
     *
     * @param  \Illuminate\Http\UploadedFile|mixed  $file
     * @param  string  $folder
     * @return string|null  Relative file path or null if invalid
     */
    public static function fileUpload($file, $folder): ?string
    {
        if (!$file->isValid()) {
            return null;
        }

        $imageName = time() . '-' . Str::random(5) . '.' . $file->getClientOriginalExtension();
        $path      = public_path('uploads/' . $folder);

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $file->move($path, $imageName);

        return 'uploads/' . $folder . '/' . $imageName;
    }

    /**
     * Delete an existing file or image from the filesystem if present.
     *
     * @param  string  $path  Absolute or relative path to file
     * @return void
     */
    public static function fileDelete(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Generate a unique slug for a given model and title string.
     *
     * @param  string  $model  Model class name
     * @param  string  $title  Raw title to slugify
     * @return string  Unique slug
     */
    public static function makeSlug($model, string $title): string
    {
        $slug = Str::slug($title);

        while ($model::where('slug', $slug)->exists()) {
            $randomString = Str::random(5);
            $slug         = Str::slug($title) . '-' . $randomString;
        }

        return $slug;
    }

    /**
     * Standardized API Success JSON Response with optional pagination metadata.
     *
     * @param  bool         $status        Request status (true/false)
     * @param  string       $message       Human-readable message
     * @param  int          $code          HTTP status code
     * @param  mixed        $data          Data payload or LengthAwarePaginator
     * @param  bool         $paginate      Whether pagination structure is included
     * @param  mixed        $paginateData  Direct paginator instance if separate
     * @return \Illuminate\Http\JsonResponse
     */
    public static function jsonResponse(bool $status, string $message, int $code, $data = null, bool $paginate = false, $paginateData = null): JsonResponse
    {
        $response = [
            'status'  => $status,
            'message' => $message,
            'code'    => $code,
        ];

        if ($paginate && !empty($paginateData)) {
            $response['data'] = $data;
            $response['pagination'] = [
                'current_page'   => $paginateData->currentPage(),
                'last_page'      => $paginateData->lastPage(),
                'per_page'       => $paginateData->perPage(),
                'total'          => $paginateData->total(),
                'first_page_url' => $paginateData->url(1),
                'last_page_url'  => $paginateData->url($paginateData->lastPage()),
                'next_page_url'  => $paginateData->nextPageUrl(),
                'prev_page_url'  => $paginateData->previousPageUrl(),
                'from'           => $paginateData->firstItem(),
                'to'             => $paginateData->lastItem(),
                'path'           => $paginateData->path(),
            ];
        } elseif ($paginate && !empty($data)) {
            $response['data'] = $data->items();
            $response['pagination'] = [
                'current_page'   => $data->currentPage(),
                'last_page'      => $data->lastPage(),
                'per_page'       => $data->perPage(),
                'total'          => $data->total(),
                'first_page_url' => $data->url(1),
                'last_page_url'  => $data->url($data->lastPage()),
                'next_page_url'  => $data->nextPageUrl(),
                'prev_page_url'  => $data->previousPageUrl(),
                'from'           => $data->firstItem(),
                'to'             => $data->lastItem(),
                'path'           => $data->path(),
            ];
        } elseif ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Standardized API Error JSON Response with validation / exception errors.
     *
     * @param  string  $message  Error explanation message
     * @param  int     $code     HTTP status code (default: 400)
     * @param  array   $errors   Detailed validation or field-level errors
     * @return \Illuminate\Http\JsonResponse
     */
    public static function jsonErrorResponse(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'status'   => false,
            'message'  => $message,
            'code'     => $code,
            't-errors' => $errors,
        ];

        return response()->json($response, $code);
    }

    /**
     * Send mobile push notification via Firebase Cloud Messaging (FCM).
     *
     * @param  string  $token       Device FCM token
     * @param  array   $notifyData  ['title' => ..., 'body' => ..., 'icon' => ...]
     * @return void
     */
    public static function sendNotifyMobile($token, $notifyData): void
    {
        try {
            $factory      = (new Factory)->withServiceAccount(storage_path(config('firebase.credentials')));
            $messaging    = $factory->createMessaging();
            $notification = Notification::create($notifyData['title'], Str::limit($notifyData['body'], 100), $notifyData['icon']);
            $message      = CloudMessage::withTarget('token', $token)->withNotification($notification);

            $messaging->send($message);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }

        return;
    }
}
