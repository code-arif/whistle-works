<?php

namespace App\Http\Controllers\Api\CMS;

use App\Http\Controllers\Controller;
use App\Services\Api\CMS\AboutPageService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AboutPageController extends Controller
{
    use ApiResponse;

    protected AboutPageService $aboutPageService;

    public function __construct(AboutPageService $aboutPageService)
    {
        parent::__construct();
        $this->aboutPageService = $aboutPageService;
    }

    /**
     * Get about page all CMS data.
     *
     * @return JsonResponse
     */
    public function about(): JsonResponse
    {
        try {
            $data = $this->aboutPageService->getAboutData();

            return $this->success('About data retrieved successfully', $data);
        } catch (Exception $e) {
            Log::error('Error fetching About CMS data: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->error([], 'An error occurred while fetching about data', 500);
        }
    }
}
