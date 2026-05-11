<?php

namespace App\Filament\Resources\Society\PostResource\Pages;

use App\Filament\Resources\Society\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
