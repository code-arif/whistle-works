<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Enums\PageEnum;
use App\Http\Controllers\Controller;
use App\Models\CMS;
use App\Models\Post;
use App\Models\Product;
use App\Models\SocialLink;
use Modules\Portfolio\Models\Project;
use Modules\Portfolio\Models\Type;

class HomeController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }
}
