@props(['url'])

@php
    $baseUrl = rtrim((string) ($url ?: config('app.url') ?: ''), '/');
    $logoUrl = $baseUrl !== '' ? $baseUrl.'/images/bu-logo.png' : null;
@endphp

<tr>
<td class="header" style="padding: 24px 0 12px; text-align: center;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@if($logoUrl)
<img src="{{ $logoUrl }}" alt="Baliuag University" style="display:block; margin:0 auto 8px; width:56px; height:56px; object-fit:contain;">
@endif
<div style="font-size: 15px; font-weight: 700; color: #0f6b37; line-height: 1.2;">
Baliuag University
</div>
<div style="font-size: 14px; font-weight: 600; color: #111827; line-height: 1.2; margin-top: 2px;">
Faculty Reclassification
</div>
</a>
</td>
</tr>
