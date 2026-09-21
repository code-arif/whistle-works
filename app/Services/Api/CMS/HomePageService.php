<?php

namespace App\Services\Api\CMS;

use App\Http\Resources\HomePagePartnerResource;
use App\Models\CMS;
use App\Models\Slider;
use App\Models\Testimonials;

class HomePageService
{
    /**
     * Retrieve and format all Home page CMS data.
     *
     * @return array
     */
    public function getHomeData(): array
    {
        $hero = CMS::where('page', 'home')
            ->where('section', 'hero')
            ->get();

        $trainingCamp = CMS::where('page', 'home')
            ->where('section', 'training-camp')
            ->get();

        $partners = CMS::where('page', 'home')
            ->where('section', 'partner')
            ->where('name', 'item')
            ->get();

        $sliderData = Slider::where('status', true)->get();

        $features = CMS::where('page', 'home')
            ->where('section', 'features')
            ->where('name', 'item')
            ->get();

        $featuresItem = CMS::where('page', 'home')
            ->where('section', 'features')
            ->where('name', 'card')
            ->get();

        $operations = CMS::where('page', 'home')
            ->where('section', 'operations')
            ->where('name', 'item')
            ->get();

        $testimonial = CMS::where('page', 'home')
            ->where('section', 'testimonial')
            ->where('name', 'item')
            ->get();

        $testimonialCard = Testimonials::get();

        return [
            'hero' => $hero->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'description' => $item->description,
                ];
            }),
            'training_camp' => $trainingCamp->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'description' => $item->description,
                ];
            }),
            'partners' => $partners->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'title' => $item->title,
                ];
            }),
            'partners_logo' => HomePagePartnerResource::collection($sliderData),
            'features' => $features->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'description' => $item->description,
                ];
            }),
            'features_item' => $featuresItem->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'description' => $item->description,
                    'image'       => $item->image ? asset($item->image) : null,
                ];
            }),
            'operations' => $operations->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'description' => $item->description,
                ];
            }),
            'testimonial' => $testimonial->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'description' => $item->description,
                ];
            }),
            'testimonial_card' => $testimonialCard->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'author_name'   => $item->author_name,
                    'review_text'   => $item->review_text,
                    'designation'   => $item->designation,
                    'author_avatar' => $item->author_avatar ? asset($item->author_avatar) : null,
                ];
            }),
        ];
    }
}
