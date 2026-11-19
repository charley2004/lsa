{{-- The XML declaration is echoed from PHP rather than written literally:
     a literal "<?xml" opening line is parsed as a PHP open tag on servers with
     short_open_tag=On, which is what made production's /sitemap.xml return 500. --}}
{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $u)
    <url>
        <loc>{{ $u['loc'] ?? route($u['route']) }}</loc>
@if ($u['lastmod'])
        <lastmod>{{ \Illuminate\Support\Carbon::parse($u['lastmod'])->toAtomString() }}</lastmod>
@endif
        <changefreq>{{ $u['changefreq'] }}</changefreq>
        <priority>{{ $u['priority'] }}</priority>
    </url>
@endforeach
</urlset>
