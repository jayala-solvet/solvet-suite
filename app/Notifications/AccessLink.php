<?php

namespace App\Notifications;

use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;

class AccessLink extends ResetPassword implements ShouldBeEncrypted
{
    public function __construct(#[\SensitiveParameter] $token)
    {
        parent::__construct($token);
        $this->onQueue('security-mail')->afterCommit();
    }
}
