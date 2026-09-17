<?php

namespace App\Services\Admin;

use App\Helpers\Helper;
use App\Models\CMS;
use App\Models\Slider;
use App\Models\Testimonials;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class CmsHomeService
{
    /**
     * Retrieve all Home Page CMS sections and associated collections.
     */
    public function getHomePageData(): array
    {
        $hero = CMS::where('page', 'home')->where('section', 'hero')->where('name', 'item')->first();
        $trainingCamp = CMS::where('page', 'home')->where('section', 'training-camp')->where('name', 'item')->first();
        $operations = CMS::where('page', 'home')->where('section', 'operations')->where('name', 'item')->first();
        $partnerHeader = CMS::where('page', 'home')->where('section', 'partner')->where('name', 'item')->first();
        $sliders = Slider::orderBy('order', 'asc')->get();
        $featuresHeader = CMS::where('page', 'home')->where('section', 'features')->where('name', 'item')->first();
        $featuresCards = CMS::where('page', 'home')->where('section', 'features')->where('name', 'card')->orderBy('id', 'asc')->get();
        $testimonialHeader = CMS::where('page', 'home')->where('section', 'testimonial')->where('name', 'item')->first();
        $testimonials = Testimonials::latest('id')->get();

        return [
            'hero' => $hero,
            'trainingCamp' => $trainingCamp,
            'operations' => $operations,
            'partnerHeader' => $partnerHeader,
            'sliders' => $sliders->map(function ($s) {
                return [
                    'id' => $s->id,
                    'image' => $s->image ? asset($s->image) : null,
                    'image_path' => $s->image,
                    'link' => $s->link,
                    'status' => (bool) $s->status,
                    'order' => $s->order,
                ];
            }),
            'featuresHeader' => $featuresHeader,
            'featuresCards' => $featuresCards->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'description' => $c->description,
                    'image' => $c->image ? asset($c->image) : null,
                    'image_path' => $c->image,
                ];
            }),
            'testimonialHeader' => $testimonialHeader,
            'testimonials' => $testimonials->map(function ($t) {
                return [
                    'id' => $t->id,
                    'author_name' => $t->author_name,
                    'designation' => $t->designation,
                    'review_text' => $t->review_text,
                    'author_avatar' => $t->author_avatar ? asset($t->author_avatar) : null,
                    'avatar_path' => $t->author_avatar,
                ];
            }),
        ];
    }

    /**
     * Update Hero section.
     */
    public function updateHero(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'home', 'section' => 'hero', 'name' => 'item'],
            [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
            ]
        );
    }

    /**
     * Update Training Camp section.
     */
    public function updateTrainingCamp(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'home', 'section' => 'training-camp', 'name' => 'item'],
            [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
            ]
        );
    }

    /**
     * Update Operations section.
     */
    public function updateOperations(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'home', 'section' => 'operations', 'name' => 'item'],
            [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
            ]
        );
    }

    /**
     * Update Partner header title.
     */
    public function updatePartnerHeader(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'home', 'section' => 'partner', 'name' => 'item'],
            [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
            ]
        );
    }

    /**
     * Store new slider / partner logo.
     */
    public function storeSlider(array $data, $imageFile): Slider
    {
        $payload = [
            'link' => $data['link'] ?? null,
            'status' => isset($data['status']) ? (bool) $data['status'] : true,
            'order' => (Slider::max('order') ?? 0) + 1,
        ];

        if ($imageFile) {
            $payload['image'] = Helper::fileUpload($imageFile, 'sliders');
        }

        return Slider::create($payload);
    }

    /**
     * Update existing slider / partner logo.
     */
    public function updateSlider(int $id, array $data, $imageFile = null): Slider
    {
        $slider = Slider::findOrFail($id);

        $payload = [
            'link' => $data['link'] ?? $slider->link,
            'status' => isset($data['status']) ? (bool) $data['status'] : $slider->status,
        ];

        if ($imageFile) {
            $this->deleteFile($slider->image);
            $payload['image'] = Helper::fileUpload($imageFile, 'sliders');
        }

        $slider->update($payload);
        return $slider;
    }

    /**
     * Toggle slider active status.
     */
    public function toggleSliderStatus(int $id): bool
    {
        $slider = Slider::findOrFail($id);
        $slider->status = !$slider->status;
        return $slider->save();
    }

    /**
     * Delete slider / partner logo.
     */
    public function destroySlider(int $id): bool
    {
        $slider = Slider::findOrFail($id);
        $this->deleteFile($slider->image);
        return $slider->delete();
    }

    /**
     * Update Feature section header.
     */
    public function updateFeatureHeader(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'home', 'section' => 'features', 'name' => 'item'],
            [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
            ]
        );
    }

    /**
     * Store Feature Card.
     */
    public function storeFeatureCard(array $data, $imageFile = null): CMS
    {
        $payload = [
            'page' => 'home',
            'section' => 'features',
            'name' => 'card',
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ];

        if ($imageFile) {
            $payload['image'] = Helper::fileUpload($imageFile, 'cms/home/features');
        }

        return CMS::create($payload);
    }

    /**
     * Update Feature Card.
     */
    public function updateFeatureCard(int $id, array $data, $imageFile = null): CMS
    {
        $card = CMS::where('page', 'home')->where('section', 'features')->where('name', 'card')->findOrFail($id);

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ];

        if ($imageFile) {
            $this->deleteFile($card->image);
            $payload['image'] = Helper::fileUpload($imageFile, 'cms/home/features');
        }

        $card->update($payload);
        return $card;
    }

    /**
     * Destroy Feature Card.
     */
    public function destroyFeatureCard(int $id): bool
    {
        $card = CMS::where('page', 'home')->where('section', 'features')->where('name', 'card')->findOrFail($id);
        $this->deleteFile($card->image);
        return $card->delete();
    }

    /**
     * Update Testimonial section header.
     */
    public function updateTestimonialHeader(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'home', 'section' => 'testimonial', 'name' => 'item'],
            [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
            ]
        );
    }

    /**
     * Store Testimonial review.
     */
    public function storeTestimonialCard(array $data, $avatarFile = null): Testimonials
    {
        $payload = [
            'author_name' => $data['author_name'],
            'designation' => $data['designation'] ?? null,
            'review_text' => $data['review_text'] ?? null,
        ];

        if ($avatarFile) {
            $payload['author_avatar'] = Helper::fileUpload($avatarFile, 'testimonial/images');
        }

        return Testimonials::create($payload);
    }

    /**
     * Update Testimonial review.
     */
    public function updateTestimonialCard(int $id, array $data, $avatarFile = null): Testimonials
    {
        $review = Testimonials::findOrFail($id);

        $payload = [
            'author_name' => $data['author_name'],
            'designation' => $data['designation'] ?? null,
            'review_text' => $data['review_text'] ?? null,
        ];

        if ($avatarFile) {
            $this->deleteFile($review->author_avatar);
            $payload['author_avatar'] = Helper::fileUpload($avatarFile, 'testimonial/images');
        }

        $review->update($payload);
        return $review;
    }

    /**
     * Destroy Testimonial review.
     */
    public function destroyTestimonialCard(int $id): bool
    {
        $review = Testimonials::findOrFail($id);
        $this->deleteFile($review->author_avatar);
        return $review->delete();
    }

    /**
     * Safe file removal helper.
     */
    protected function deleteFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        $fullPath = public_path($path);
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}
