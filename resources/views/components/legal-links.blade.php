@if(\Illuminate\Support\Facades\Schema::hasTable('legal_documents'))
<div class="sv-footnote">@foreach(\App\Modules\Legal\Services\LegalService::current() as $doc)<a href="{{ route('legal.document',$doc->id) }}">{{ $doc->title }}</a>@if(!$loop->last) · @endif @endforeach</div>
@endif
