<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Http\Controllers;

use Illuminate\Http\Response;
use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsBackend\Services\Social\SocialGuides;
use Modules\CmsBackend\Support\ProjectContext;

/**
 * "Hướng dẫn" of the administration: the guides for every project and those of the current project, and each one shown as it is.
 * The page of a guide holds the document in a sandboxed frame (no script can run in it), and the document itself is served with a
 * restrictive policy too.
 */
class SocialGuideController extends SocialBaseController
{
    public function __construct(SocialPublicationDataProvider $data, private SocialGuides $guides)
    {
        parent::__construct($data);
        $this->middleware($this->requires(PermissionsEnum::SOCIAL_PUBLICATION_VIEW));
    }

    public function index()
    {
        $projectKey = $this->guides->projectKey();

        return view('cmsbackend::screens.pages.truyen-thong.guides', [
            'common' => $this->guides->forScope(SocialGuides::SCOPE_COMMON),
            'project' => $this->guides->forScope(SocialGuides::SCOPE_PROJECT),
            'projectLabel' => $projectKey !== null && ProjectContext::exists($projectKey) ? ProjectContext::label($projectKey) : null,
        ]);
    }

    public function show(string $scope, string $file)
    {
        $guide = $this->guides->find($scope, $file);
        abort_if($guide === null, 404);

        return view('cmsbackend::screens.pages.truyen-thong.guide', ['guide' => $guide]);
    }

    /** The document itself, for the frame and for "open in a new tab". */
    public function raw(string $scope, string $file): Response
    {
        $html = $this->guides->html($scope, $file);
        abort_if($html === null, 404);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            // No script, no form, no plugin, nothing loaded from another site except pictures over https; links may open a new window.
            'Content-Security-Policy' => "sandbox allow-popups allow-popups-to-escape-sandbox; default-src 'none'; style-src 'unsafe-inline'; img-src data: https:; font-src data:; form-action 'none'",
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
