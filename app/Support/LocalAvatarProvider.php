<?php

namespace App\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

class LocalAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $words = preg_split('/\s+/u', trim($record->name));
        $letters = mb_strtoupper(mb_substr($words[0] ?? 'U', 0, 1).mb_substr($words[1] ?? '', 0, 1));
        $letters = htmlspecialchars($letters, ENT_XML1, 'UTF-8');

        return 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><rect width="80" height="80" rx="40" fill="#102F4F"/><text x="40" y="47" text-anchor="middle" font-family="Arial,sans-serif" font-size="26" fill="white">'.$letters.'</text></svg>');
    }
}
