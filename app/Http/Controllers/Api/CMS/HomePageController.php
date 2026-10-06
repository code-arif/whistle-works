<?php

namespace App\Http\Controllers\Api\CMS;

use App\Http\Controllers\Controller;
use App\Services\Api\CMS\HomePageService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HomePageController extends Controller
{
    use ApiResponse;

    protected HomePageService $homePageService;

    public function __construct(HomePageService $homePageService)
    {
        parent::__construct();
        $this->homePageService = $homePageService;
    }

    /**
     * Get home page all CMS data.
     *
     * @return JsonResponse
     */
    public function home(): JsonResponse
    {
        try {
            $data = $this->homePageService->getHomeData();

            return $this->success($data, 'Home data retrieved successfully');
        } catch (Exception $e) {
            Log::error('Error fetching Home CMS data: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->error([], 'An error occurred while fetching home data', 500);
        }
    }
}
