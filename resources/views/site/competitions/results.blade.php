{{--
    Public ranking, styled after the judge-mobile prototype (public_results_screen.dart):
    standalone page, dashboard palette: light content, navy header, brand red accents (dark mode optional), only the Siapgrak
    logo and name on top. Per criterion it shows each judge's weighted points
    (score × weight / 100), which add up to that judge's total, as in the prototype.
--}}
@php
    $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    // Photo slot: no photos yet, so show the number's digits (team names mostly start with "Paskibra", initials would repeat).
    $badge = fn ($participant) => preg_match('/(\d+)\D*$/', (string) $participant->competition_number, $digits)
        ? $digits[1]
        : collect(preg_split('/\s+/', trim($participant->name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    // Deterministic hue per participant, like ContestantPhoto.fallbackGradient(), kept to navy and red tones.
    $hues = [224, 352, 214, 4, 232, 344];
@endphp
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil {{ $competition->title }} · Siapgrak</title>
    <meta name="description" content="Peringkat dan nilai peserta {{ $competition->title }}.">
    @include('partials.head-fonts')
    @include('partials.head-icons')
    @vite(['resources/css/app.css'])
    <script>
        // Remembered appearance; the page still works if storage is blocked.
        try { if (localStorage.getItem('siapgrak-results-theme') === 'dark') document.documentElement.dataset.theme = 'dark'; } catch {}
    </script>
    <style>
        /* Same tones as the admin dashboard: slate-50 page, white cards, slate-200 borders, navy text, brand red accents. */
        :root, [data-theme="light"] {
            --kr-bg: #f8fafc; --kr-surface: #ffffff; --kr-surface-alt: #f8fafc; --kr-border: #e2e8f0; --kr-border-strong: #cbd5e1;
            --kr-text: #0e1838; --kr-muted: #64748b; --kr-accent: #c8102e; --kr-accent-bright: #c8102e; --kr-accent-deep: #c8102e;
            --kr-accent-soft: #fdf1f2; --kr-shadow: rgba(14, 24, 56, .04);
        }
        [data-theme="dark"] {
            --kr-bg: #08102a; --kr-surface: #0e1838; --kr-surface-alt: #13204a; --kr-border: #1f3263; --kr-border-strong: #2c4277;
            --kr-text: #f1f4fa; --kr-muted: #9aa8c7; --kr-accent: #e0283f; --kr-accent-bright: #ff4d63; --kr-accent-deep: #c8102e;
            --kr-accent-soft: rgba(200, 16, 46, .16); --kr-shadow: rgba(0, 0, 0, .25);
        }
        body { background: var(--kr-bg); color: var(--kr-text); }
        details > summary::-webkit-details-marker { display: none; }
    </style>
</head>
<body class="min-h-screen antialiased">
    <header class="bg-navy-950 text-white">
        <div class="mx-auto flex max-w-[900px] items-center justify-between px-4 py-3 md:px-5">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/siapgrak-logo.png') }}" alt="" class="h-10 w-10">
                <span class="text-lg font-semibold">Siapgrak</span>
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-[900px] px-3.5 pt-8 pb-16 md:px-5 md:pt-10">
        <p class="text-xs font-bold tracking-[0.15em] text-(--kr-accent-bright) uppercase">{{ $competition->title }}</p>
        <h1 class="mt-2 text-[28px] font-bold tracking-[-0.02em] md:text-[38px]">Hasil Penilaian</h1>
        <p class="mt-2 text-[15px] text-(--kr-muted)">Hasil resmi dari panitia.</p>

        <div class="mt-5 flex items-center gap-2.5 rounded-xl border border-(--kr-border) bg-(--kr-surface) px-3.5 py-3">
            <svg class="h-[18px] w-[18px] shrink-0 text-(--kr-accent-bright)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
            <p class="text-[13px] leading-relaxed text-(--kr-muted)">Identitas juri dirahasiakan. Ketuk kartu untuk melihat nilai per kriteria.</p>
        </div>

        @foreach ($competition->categories as $category)
            @foreach ($category->rounds as $round)
                <section class="mt-10">
                    <p class="text-xs font-bold tracking-[0.15em] text-(--kr-accent) uppercase">{{ $category->name }}</p>
                    <h2 class="mt-1 text-2xl font-bold tracking-[-0.02em] md:text-[32px]">{{ $round->name }}</h2>

                    @if (! $round->isPublished())
                        <div class="mt-5 flex flex-col items-center rounded-2xl border border-(--kr-border) bg-(--kr-surface) px-6 py-12 text-center">
                            <div class="flex h-[88px] w-[88px] items-center justify-center rounded-full border border-(--kr-accent-deep) bg-(--kr-accent-soft) text-(--kr-accent-bright)">
                                <svg class="h-11 w-11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" /></svg>
                            </div>
                            <p class="mt-5 text-xl font-bold">{{ $round->name }} masih berlangsung</p>
                            <p class="mt-2 max-w-md text-[15px] leading-relaxed text-(--kr-muted)">Juri masih melakukan penilaian. Hasil akan tampil di halaman ini setelah penilaian selesai.</p>
                        </div>
                    @else
                        @php
                            $detail = $details[$round->id];
                            $rankCounts = $round->results->countBy('rank');
                            $weights = $round->criteria->mapWithKeys(fn ($criterion) => [$criterion->id => (float) $criterion->weight / 100]);
                        @endphp
                        <p class="mt-1.5 text-[15px] text-(--kr-muted)">Diumumkan {{ $round->results_published_at->locale('id')->translatedFormat('j M Y, H.i') }} WIB</p>

                        <div class="mt-5 space-y-2.5">
                            @foreach ($round->results as $result)
                                @php
                                    $participant = $result->participant;
                                    $judgeRows = $detail['rows'][$result->competition_participant_id] ?? [];
                                    $hue = $hues[($participant->performance_order ?? $participant->id) % count($hues)];
                                @endphp
                                <details class="group rounded-[14px] border border-(--kr-border) bg-(--kr-surface) shadow-[0_6px_18px_var(--kr-shadow)] open:border-(--kr-accent-deep)">
                                    <summary class="cursor-pointer list-none p-3.5 md:p-[18px]">
                                        <div class="flex items-center">
                                            <div class="w-[34px] shrink-0 md:w-12">
                                                <p class="text-[22px] font-bold text-(--kr-accent-bright) tabular-nums md:text-[28px]">{{ $result->rank }}</p>
                                                @if ($rankCounts[$result->rank] > 1)
                                                    <p class="text-[10px] font-bold tracking-[0.08em] text-(--kr-accent-bright)">SERI</p>
                                                @endif
                                            </div>
                                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[10px] border border-(--kr-border-strong) text-xl font-bold text-white/70 md:h-[68px] md:w-[68px]"
                                                 style="background: linear-gradient(160deg, hsl({{ $hue }} 45% 28%), hsl({{ $hue }} 50% 14%))">
                                                {{ $badge($participant) }}
                                            </div>
                                            <div class="ml-3 min-w-0 flex-1 md:ml-4">
                                                @if ($participant->competition_number)
                                                    <p class="text-xs font-bold tracking-[0.06em] text-(--kr-accent)">#{{ $participant->competition_number }}</p>
                                                @endif
                                                <p class="mt-0.5 text-base leading-tight font-bold md:text-lg">{{ $participant->name }}</p>
                                                @if ($participant->institution)
                                                    <p class="text-[13px] text-(--kr-muted)">{{ $participant->institution }}</p>
                                                @endif
                                            </div>
                                            <div class="ml-2.5 shrink-0 text-right">
                                                <p class="text-xl font-bold tabular-nums md:text-2xl">{{ $number($result->weighted_score) }}</p>
                                                <p class="text-[11px] font-semibold text-(--kr-muted)">Nilai Akhir</p>
                                            </div>
                                        </div>
                                        <div class="mt-3.5 flex items-center gap-2">
                                            <div class="flex flex-1 flex-wrap gap-2">
                                                @foreach ($judgeRows as $judgeRow)
                                                    <span class="rounded-lg border border-(--kr-border) bg-(--kr-surface-alt) px-[11px] py-[7px] text-xs text-(--kr-muted)">{{ $judgeRow['label'] }}&nbsp; <span class="text-[13px] font-bold text-(--kr-text) tabular-nums">{{ $number($judgeRow['total']) }}</span></span>
                                                @endforeach
                                            </div>
                                            <svg class="h-6 w-6 shrink-0 text-(--kr-muted) transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
                                        </div>
                                    </summary>

                                    <div class="px-3.5 pb-3.5 md:px-[18px] md:pb-[18px]">
                                        <div class="overflow-x-auto rounded-[10px] border border-(--kr-border) bg-(--kr-surface-alt)">
                                            <table class="min-w-full text-[13px]">
                                                <thead>
                                                    <tr class="text-[11px] font-bold tracking-[0.06em] text-(--kr-muted) uppercase">
                                                        <th class="px-2.5 py-2.5 text-left">Kriteria</th>
                                                        @foreach ($judgeRows as $judgeRow)
                                                            <th class="px-2.5 py-2.5 text-right whitespace-nowrap">{{ $judgeRow['label'] }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($round->criteria as $criterion)
                                                        <tr class="border-t border-(--kr-border)">
                                                            <td class="px-2.5 py-2.5">{{ $criterion->name }} <span class="text-(--kr-muted)">(maks {{ $number((float) $criterion->maximum_score * $weights[$criterion->id]) }})</span></td>
                                                            @foreach ($judgeRows as $judgeRow)
                                                                <td class="px-2.5 py-2.5 text-right tabular-nums">{{ isset($judgeRow['scores'][$criterion->id]) ? $number($judgeRow['scores'][$criterion->id] * $weights[$criterion->id]) : '–' }}</td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                    <tr class="border-t border-(--kr-border) font-bold">
                                                        <td class="px-2.5 py-2.5">Total</td>
                                                        @foreach ($judgeRows as $judgeRow)
                                                            <td class="px-2.5 py-2.5 text-right tabular-nums">{{ $number($judgeRow['total']) }}</td>
                                                        @endforeach
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </details>
                            @endforeach

                            @if ($round->results->isEmpty())
                                <p class="text-[15px] text-(--kr-muted)">Belum ada peserta pada hasil ini.</p>
                            @endif
                        </div>
                    @endif
                </section>
            @endforeach
        @endforeach
    </main>

    <script>
        document.getElementById('theme-toggle').addEventListener('click', () => {
            const root = document.documentElement;
            root.dataset.theme = root.dataset.theme === 'light' ? 'dark' : 'light';
            try { localStorage.setItem('siapgrak-results-theme', root.dataset.theme); } catch {}
        });
    </script>
</body>
</html>
