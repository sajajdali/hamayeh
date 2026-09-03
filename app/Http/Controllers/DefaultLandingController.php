<?php

namespace App\Http\Controllers;

use App\Models\Blogger;
use Illuminate\Http\Response;

class DefaultLandingController extends Controller
{
    public function __invoke(ReferenceDesignController $referenceDesign): Response
    {
        $defaultBlogger = Blogger::query()->where('code', 'a0')->firstOrFail();

        return $referenceDesign->landing($defaultBlogger);
    }
}
