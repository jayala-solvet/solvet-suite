<?php

namespace App\Modules\Legal\Services;

use App\Models\User;
use App\Modules\Access\Services\AccessService;
use App\Modules\Core\Models\Company;
use App\Modules\Legal\Models\LegalDocument;
use App\Modules\Legal\Models\LegalEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LegalService
{
    public const KINDS = ['terms' => 'Términos de uso', 'privacy' => 'Privacidad', 'cookies' => 'Cookies y almacenamiento local', 'optional' => 'Consentimiento opcional'];

    public static function template(?string $kind): string
    {
        if (! $kind || ! array_key_exists($kind, self::KINDS)) {
            return '';
        }
        $company = Company::first();

        return strtr(file_get_contents(resource_path("legal/$kind.txt")), ['[RESPONSABLE]' => $company?->name ?? '[RESPONSABLE]', '[RUC]' => $company?->tax_id ?? '[RUC]', '[DOMICILIO]' => $company?->address ?? '[DOMICILIO]', '[CONTACTO]' => $company?->email ?? '[CONTACTO]']);
    }

    public function draft(User $actor, array $data, string $password): LegalDocument
    {
        AccessService::authorize($actor, 'legal.manage');
        AccessService::reauthenticate($actor, $password);
        $data = Validator::make($data, ['kind' => 'required|in:terms,privacy,cookies,optional', 'purpose_key' => 'nullable|regex:/^[a-z][a-z0-9_-]{0,79}$/', 'title' => 'required|string|max:200', 'version' => 'required|string|max:50', 'body' => 'required|string|min:100|max:30000'])->validate();
        $data['purpose_key'] = $data['kind'] === 'optional' ? ($data['purpose_key'] ?? 'optional') : $data['kind'];
        Validator::make($data, ['version' => Rule::unique('legal_documents')->where('kind', $data['kind'])->where('purpose_key', $data['purpose_key'])])->validate();
        $data['effect'] = match ($data['kind']) {
            'terms' => 'contract','optional' => 'optional',default => 'notice'
        };
        $data['sha256'] = hash('sha256', $data['body']);
        $data['controller_snapshot'] = Company::first()?->only(['name', 'tax_id', 'address', 'email']) ?? [];
        $record = LegalDocument::create($data + ['created_by' => $actor->id]);
        AccessService::audit($actor, 'legal.drafted', 'legal_document', $record->id);

        return $record;
    }

    public function publish(User $actor, LegalDocument $document, string $reference, bool $reviewed, string $password): void
    {
        AccessService::authorize($actor, 'legal.manage');
        AccessService::reauthenticate($actor, $password);
        Validator::make(['reference' => $reference, 'reviewed' => $reviewed], ['reference' => 'required|string|min:10|max:1000', 'reviewed' => 'accepted'])->validate();
        DB::transaction(function () use ($actor, $document, $reference) {
            $document = LegalDocument::lockForUpdate()->findOrFail($document->id);
            if ($document->published_at) {
                return;
            }
            foreach (['name', 'tax_id', 'address', 'email'] as $key) {
                if (empty($document->controller_snapshot[$key])) {
                    throw ValidationException::withMessages(['operation' => 'Faltan identidad, RUC, domicilio o correo confirmados en la copia de Mi empresa. Creá otra versión con datos completos.']);
                }
            }
            if (preg_match('/\[(RESPONSABLE|RUC|DOMICILIO|CONTACTO|COMPLETAR|PENDIENTE)[^\]]*\]/u', $document->body)) {
                throw ValidationException::withMessages(['operation' => 'Completá los campos pendientes antes de publicar.']);
            }
            $document->update(['published_at' => now(), 'review_reference' => $reference]);
            AccessService::audit($actor, 'legal.published', 'legal_document', $document->id, ['sha256' => $document->sha256, 'review_reference' => $reference]);
        });
    }

    public static function current()
    {
        return LegalDocument::whereNotNull('published_at')->orderByDesc('id')->get()->unique(fn ($doc) => $doc->kind.':'.$doc->purpose_key)->values();
    }

    public function record(User $user, int $documentId, string $action): void
    {
        abort_unless($user->fresh()?->is_active && ! $user->fresh()?->suspended_at, 403);
        DB::transaction(function () use ($user, $documentId, $action) {
            User::lockForUpdate()->findOrFail($user->id);
            $doc = LegalDocument::whereNotNull('published_at')->findOrFail($documentId);
            abort_unless(hash_equals($doc->sha256, hash('sha256', $doc->body)), 409);
            $expected = match ($doc->effect) {
                'contract' => 'accepted','optional' => 'consented',default => 'acknowledged'
            };
            abort_unless($action === $expected || ($doc->effect === 'optional' && $action === 'withdrawn'), 422);
            $last = LegalEvent::where('user_id', $user->id)->where('legal_document_id', $doc->id)->latest('id')->first();
            if ($last?->action === $action) {
                return;
            }
            if ($action === 'withdrawn' && $last?->action !== 'consented') {
                abort(422);
            }
            LegalEvent::create(['user_id' => $user->id, 'legal_document_id' => $doc->id, 'action' => $action, 'document_sha256' => $doc->sha256, 'purpose' => $doc->title]);
            AccessService::audit($user, 'legal.'.$action, 'legal_document', $doc->id, ['sha256' => $doc->sha256]);
        });
    }
}
