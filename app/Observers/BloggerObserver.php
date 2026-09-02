<?php

namespace App\Observers;

use App\Models\Blogger;
use Illuminate\Support\Facades\Cache;

class BloggerObserver
{
    public function saved(Blogger $blogger): void
    {
        $this->forgetLanding($blogger);
    }

    public function deleted(Blogger $blogger): void
    {
        $this->forgetLanding($blogger);
    }

    public function restored(Blogger $blogger): void
    {
        $this->forgetLanding($blogger);
    }

    private function forgetLanding(Blogger $blogger): void
    {
        Cache::forget('landing:blogger:'.$blogger->getKey());
        Cache::forget('landing:v2:blogger:'.$blogger->getKey());
        Cache::forget('landing:v3:blogger:'.$blogger->getKey());
    }
}
