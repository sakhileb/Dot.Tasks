<x-mail::layout>
{{-- Header --}}
{{-- No <img> logo here: this platform has no real logo asset today (the codebase inherited
Dot.Sheet's logo file, tracked separately for a dedicated rebrand fix — see wiki.md) — using a
styled text wordmark rather than propagating the wrong logo into another surface. --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}<br>
{{ __('AI-assisted task execution for the Dot Ecosystem.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
