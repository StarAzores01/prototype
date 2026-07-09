<x-public-layout>
    @php
        $statusColors = [
            'scheduled' => '#6366F1',
            'ongoing' => '#1A56DB',
            'completed' => '#10B981',
        ];
    @endphp

    <div style="padding:100px 0 60px;background:linear-gradient(135deg,#09182F,#102545,#1A3A72);text-align:center">
        <div style="max-width:700px;margin:0 auto;padding:0 24px">
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.25);color:#38BDF8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.4px;padding:5px 14px;border-radius:40px;margin-bottom:20px">
                Extension Training Programs
            </div>
            <h1 style="font-size:clamp(28px,4vw,44px);font-weight:800;color:#fff;margin-bottom:16px;line-height:1.2">
                Training Programs
            </h1>
            <p style="font-size:15px;color:rgba(255,255,255,.65);line-height:1.7">
                Browse CIT's upcoming, ongoing, and completed extension training programs.
            </p>
        </div>
    </div>

    <section style="padding:56px 0;background:#F0F6FF">
        <div style="max-width:1100px;margin:0 auto;padding:0 28px">
            <h2 style="font-size:20px;font-weight:800;color:#09182F;margin-bottom:20px">Upcoming &amp; Ongoing</h2>

            @if ($upcoming->isEmpty())
                <p style="color:#64748B;font-size:14px;margin-bottom:48px">No upcoming or ongoing trainings announced right now.</p>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;margin-bottom:48px">
                    @foreach ($upcoming as $training)
                        <div style="background:#fff;border-radius:14px;border:1px solid #EEF2F7;box-shadow:0 2px 8px rgba(9,24,47,.06);overflow:hidden">
                            <div style="height:100px;background:linear-gradient(135deg,#1A56DB,#2E6BF0);display:flex;align-items:center;justify-content:center;font-size:40px">&#128218;</div>
                            <div style="padding:20px">
                                <div style="font-weight:700;font-size:15px;color:#09182F;margin-bottom:6px">{{ $training->title }}</div>
                                <div style="font-size:13px;color:#64748B;line-height:1.6;margin-bottom:12px">{{ Str::limit($training->description ?? 'No description provided.', 100) }}</div>
                                <div style="font-size:12px;color:#94A3B8;margin-bottom:10px">
                                    &#128197; {{ $training->start_date?->format('M d, Y') ?? 'Date TBA' }}
                                    @if ($training->location) &middot; &#128205; {{ $training->location }} @endif
                                </div>
                                <span style="display:inline-flex;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $statusColors[$training->status] }}22;color:{{ $statusColors[$training->status] }}">
                                    {{ ucfirst($training->status) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <h2 style="font-size:20px;font-weight:800;color:#09182F;margin-bottom:20px">Completed Trainings</h2>

            @if ($completed->isEmpty())
                <p style="color:#64748B;font-size:14px">No completed trainings to show yet.</p>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px">
                    @foreach ($completed as $training)
                        <div style="background:#fff;border-radius:14px;border:1px solid #EEF2F7;box-shadow:0 2px 8px rgba(9,24,47,.06);overflow:hidden;opacity:.9">
                            <div style="height:100px;background:linear-gradient(135deg,#6B7280,#94A3B8);display:flex;align-items:center;justify-content:center;font-size:40px">&#9989;</div>
                            <div style="padding:20px">
                                <div style="font-weight:700;font-size:15px;color:#09182F;margin-bottom:6px">{{ $training->title }}</div>
                                <div style="font-size:13px;color:#64748B;line-height:1.6;margin-bottom:12px">{{ Str::limit($training->description ?? 'No description provided.', 100) }}</div>
                                <div style="font-size:12px;color:#94A3B8;margin-bottom:10px">
                                    &#128197; {{ $training->start_date?->format('M d, Y') ?? '—' }}
                                    @if ($training->location) &middot; &#128205; {{ $training->location }} @endif
                                </div>
                                <span style="display:inline-flex;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;background:{{ $statusColors['completed'] }}22;color:{{ $statusColors['completed'] }}">
                                    Completed
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-public-layout>
