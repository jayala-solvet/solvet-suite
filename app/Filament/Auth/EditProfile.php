<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;

class EditProfile extends \Filament\Auth\Pages\EditProfile
{
    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()->disabled()->dehydrated(false);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->minLength(12)->maxLength(128);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('currentPassword')->label('Contraseña actual')->password()->currentPassword()->required(fn ($get) => (bool) $get('password'))->dehydrated(false);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['email'],$data['role'],$data['is_active']);

        return parent::handleRecordUpdate($record, $data);
    }
}
