<x-public-layout>
    @php
        $badgeClass = [
            'scheduled' => 'badge-proposed',
            'ongoing' => 'badge-ongoing',
            'completed' => 'badge-completed',
        ];
    @endphp

    <div class="landing-hero landing-hero-compact">
        <div class="landing-hero-content">
            <div class="landing-hero-badge">Extension Training Programs</div>
            <h1>Training Programs</h1>
            <p>Browse CIT's upcoming, ongoing, and completed extension training programs.</p>
        </div>
    </div>

    <section class="landing-section" style="background:var(--blue-xsoft)">
        <div style="max-width:1100px;margin:0 auto;padding:0 28px">
            <h2 style="font-size:20px;font-weight:800;color:var(--text-primary);margin-bottom:20px">Upcoming &amp; Ongoing</h2>

            @if ($upcoming->isEmpty())
                <p style="color:var(--gray-600);font-size:14px;margin-bottom:48px">No upcoming or ongoing trainings announced right now.</p>
            @else
                <div class="home-grid" style="margin-bottom:48px">
                    @foreach ($upcoming as $training)
                        <div class="training-card">
                            <div class="training-card-img"><i class="fa-solid fa-book"></i></div>
                            <div class="training-card-body">
                                <div class="training-card-title">{{ $training->title }}</div>
                                <div class="training-card-desc">{{ Str::limit($training->description ?? 'No description provided.', 100) }}</div>
                                <div class="training-card-meta">
                                    <span><i class="fa-solid fa-calendar"></i> {{ $training->start_date?->format('M d, Y') ?? 'Date TBA' }}</span>
                                    @if ($training->location)
                                        <span><i class="fa-solid fa-location-dot"></i> {{ $training->location }}</span>
                                    @endif
                                </div>
                                <div class="training-card-footer">
                                    <span class="badge {{ $badgeClass[$training->status] ?? 'badge-proposed' }}">{{ ucfirst($training->status) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <h2 style="font-size:20px;font-weight:800;color:var(--text-primary);margin-bottom:20px">Completed Trainings</h2>

            @if ($completed->isEmpty())
                <p style="color:var(--gray-600);font-size:14px">No completed trainings to show yet.</p>
            @else
                <div class="home-grid">
                    @foreach ($completed as $training)
                        <div class="training-card" style="opacity:.9">
                            <div class="training-card-img"><i class="fa-solid fa-circle-check"></i></div>
                            <div class="training-card-body">
                                <div class="training-card-title">{{ $training->title }}</div>
                                <div class="training-card-desc">{{ Str::limit($training->description ?? 'No description provided.', 100) }}</div>
                                <div class="training-card-meta">
                                    <span><i class="fa-solid fa-calendar"></i> {{ $training->start_date?->format('M d, Y') ?? '—' }}</span>
                                    @if ($training->location)
                                        <span><i class="fa-solid fa-location-dot"></i> {{ $training->location }}</span>
                                    @endif
                                </div>
                                <div class="training-card-footer">
                                    <span class="badge badge-completed">Completed</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-public-layout>
