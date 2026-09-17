<?php

namespace App\Services\Admin;

use App\Helpers\Helper;
use App\Models\CMS;
use App\Models\OwnerInfo;
use Exception;

class CmsAboutService
{
    /**
     * Retrieve all About Page CMS sections and associated collections.
     */
    public function getAboutPageData(): array
    {
        $pageTitle = CMS::where('page', 'about')->where('section', 'page_title')->first();
        $mission = CMS::where('page', 'about')->where('section', 'mission')->first();
        $keyToExcellence = CMS::where('page', 'about')->where('section', 'key_to_excellence')->first();
        $bottomDescription = CMS::where('page', 'about')->where('section', 'bottom_description')->first();
        $gettingStarted = CMS::where('page', 'about')->where('section', 'getting_started')->where('name', 'item')->first();
        $owner = OwnerInfo::where('status', 'active')->first() ?? OwnerInfo::first();
        $featureCards = CMS::where('page', 'about')->where('section', 'features')->where('name', 'feature_card')->orderBy('order', 'asc')->get();
        $teamHeader = CMS::where('page', 'about')->where('section', 'our-team')->where('name', 'item')->first();
        $teamMembers = CMS::where('page', 'about')->where('section', 'our-team')->where('name', 'card')->orderBy('id', 'asc')->get();

        return [
            'pageTitle' => $pageTitle,
            'mission' => $mission,
            'keyToExcellence' => $keyToExcellence,
            'bottomDescription' => $bottomDescription,
            'gettingStarted' => $gettingStarted,
            'owner' => $owner ? [
                'id' => $owner->id,
                'name' => $owner->name,
                'designation' => $owner->designation,
                'experience' => $owner->experience,
                'bio' => $owner->bio,
                'image' => $owner->image ? asset($owner->image) : null,
                'image_path' => $owner->image,
                'stats' => $owner->stats ?? [
                    ['value' => '', 'label' => ''],
                    ['value' => '', 'label' => ''],
                    ['value' => '', 'label' => ''],
                ],
            ] : null,
            'featureCards' => $featureCards->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'description' => $c->description,
                    'image' => $c->image ? asset($c->image) : null,
                    'image_path' => $c->image,
                    'order' => $c->order,
                ];
            }),
            'teamHeader' => $teamHeader,
            'teamMembers' => $teamMembers->map(function ($m) {
                return [
                    'id' => $m->id,
                    'title' => $m->title, // Member Name
                    'sub_title' => $m->sub_title, // Designation/Role
                    'description' => $m->description, // Bio/Experience
                    'image' => $m->image ? asset($m->image) : null,
                    'image_path' => $m->image,
                ];
            }),
        ];
    }

    /**
     * Update Page Title section.
     */
    public function updatePageTitle(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'about', 'section' => 'page_title'],
            [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'layout' => 'page_title_v1',
                'component' => 'PageTitle',
            ]
        );
    }

    /**
     * Update Mission section.
     */
    public function updateMission(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'about', 'section' => 'mission'],
            [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'layout' => 'mission_box_v1',
                'component' => 'MissionBox',
            ]
        );
    }

    /**
     * Update Key to Excellence section.
     */
    public function updateKeyToExcellence(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'about', 'section' => 'key_to_excellence'],
            [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'layout' => 'key_excellence_v1',
                'component' => 'KeyExcellence',
            ]
        );
    }

    /**
     * Update Bottom Description section.
     */
    public function updateBottomDescription(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'about', 'section' => 'bottom_description'],
            [
                'description' => $data['description'],
                'layout' => 'description_block_v1',
                'component' => 'DescriptionBlock',
            ]
        );
    }

    /**
     * Update Getting Started section.
     */
    public function updateGettingStarted(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'about', 'section' => 'getting_started', 'name' => 'item'],
            [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]
        );
    }

    /**
     * Update or create Owner/Founder Info.
     */
    public function updateOwnerInfo(array $data, $imageFile = null): OwnerInfo
    {
        $owner = OwnerInfo::where('status', 'active')->first() ?? OwnerInfo::first();

        $stats = [
            [
                'value' => $data['stat_1_value'] ?? null,
                'label' => $data['stat_1_label'] ?? null,
            ],
            [
                'value' => $data['stat_2_value'] ?? null,
                'label' => $data['stat_2_label'] ?? null,
            ],
            [
                'value' => $data['stat_3_value'] ?? null,
                'label' => $data['stat_3_label'] ?? null,
            ],
        ];

        $payload = [
            'name' => $data['name'],
            'designation' => $data['designation'],
            'experience' => $data['experience'] ?? null,
            'bio' => $data['bio'] ?? null,
            'stats' => $stats,
            'status' => 'active',
        ];

        if ($imageFile) {
            if ($owner && $owner->image) {
                $this->deleteFile($owner->image);
            }
            $payload['image'] = Helper::fileUpload($imageFile, 'cms/about/owner');
        }

        if ($owner) {
            $owner->update($payload);
            return $owner;
        }

        return OwnerInfo::create($payload);
    }

    /**
     * Store About Feature Card.
     */
    public function storeFeatureCard(array $data, $imageFile = null): CMS
    {
        $maxOrder = CMS::where('page', 'about')->where('section', 'features')->max('order') ?? 0;

        $payload = [
            'page' => 'about',
            'section' => 'features',
            'name' => 'feature_card',
            'layout' => 'feature_card_v1',
            'component' => 'FeatureCard',
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'order' => $maxOrder + 1,
        ];

        if ($imageFile) {
            $payload['image'] = Helper::fileUpload($imageFile, 'cms/about/features');
        }

        return CMS::create($payload);
    }

    /**
     * Update About Feature Card.
     */
    public function updateFeatureCard(int $id, array $data, $imageFile = null): CMS
    {
        $card = CMS::where('page', 'about')->where('section', 'features')->where('name', 'feature_card')->findOrFail($id);

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ];

        if (isset($data['order'])) {
            $payload['order'] = (int) $data['order'];
        }

        if ($imageFile) {
            $this->deleteFile($card->image);
            $payload['image'] = Helper::fileUpload($imageFile, 'cms/about/features');
        }

        $card->update($payload);
        return $card;
    }

    /**
     * Destroy About Feature Card.
     */
    public function destroyFeatureCard(int $id): bool
    {
        $card = CMS::where('page', 'about')->where('section', 'features')->where('name', 'feature_card')->findOrFail($id);
        $this->deleteFile($card->image);
        return $card->delete();
    }

    /**
     * Update Team Header.
     */
    public function updateTeamHeader(array $data): CMS
    {
        return CMS::updateOrCreate(
            ['page' => 'about', 'section' => 'our-team', 'name' => 'item'],
            [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'layout' => 'team_header_v1',
                'component' => 'TeamHeader',
            ]
        );
    }

    /**
     * Store Team Member.
     */
    public function storeTeamMember(array $data, $imageFile = null): CMS
    {
        $payload = [
            'page' => 'about',
            'section' => 'our-team',
            'name' => 'card',
            'layout' => 'team_card_v1',
            'component' => 'TeamCard',
            'title' => $data['title'], // Name
            'sub_title' => $data['sub_title'], // Role/Designation
            'description' => $data['description'] ?? null, // Experience
        ];

        if ($imageFile) {
            $payload['image'] = Helper::fileUpload($imageFile, 'cms/about/team');
        }

        return CMS::create($payload);
    }

    /**
     * Update Team Member.
     */
    public function updateTeamMember(int $id, array $data, $imageFile = null): CMS
    {
        $member = CMS::where('page', 'about')->where('section', 'our-team')->where('name', 'card')->findOrFail($id);

        $payload = [
            'title' => $data['title'],
            'sub_title' => $data['sub_title'],
            'description' => $data['description'] ?? null,
        ];

        if ($imageFile) {
            $this->deleteFile($member->image);
            $payload['image'] = Helper::fileUpload($imageFile, 'cms/about/team');
        }

        $member->update($payload);
        return $member;
    }

    /**
     * Destroy Team Member.
     */
    public function destroyTeamMember(int $id): bool
    {
        $member = CMS::where('page', 'about')->where('section', 'our-team')->where('name', 'card')->findOrFail($id);
        $this->deleteFile($member->image);
        return $member->delete();
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
