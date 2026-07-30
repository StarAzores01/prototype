<x-public-layout>
    <div class="landing-hero landing-hero-compact">
        <div class="landing-hero-content">
            <div class="landing-hero-badge">About PAThrive</div>
            <h1>About the System &amp; CIT Extension Programs</h1>
            <p>Learn about PAThrive and the College of Industrial Technology's commitment to community development through extension services.</p>
        </div>
    </div>

    <section class="landing-section">
        <div style="max-width:1100px;margin:0 auto;padding:0 28px">
            <h2 style="font-size:clamp(22px,3vw,32px);font-weight:800;color:var(--text-primary);margin-bottom:16px">What is PAThrive?</h2>
            <p style="font-size:15px;color:var(--gray-600);line-height:1.75;margin-bottom:14px;max-width:760px">
                <strong>PAThrive</strong> is a web-based Extension Training Management and Impact Assessment Tracking System developed for the College of Industrial Technology (CIT) of Southern Luzon State University.
            </p>
            <p style="font-size:15px;color:var(--gray-600);line-height:1.75;max-width:760px">
                The system centralizes the management of CIT extension training programs - from scheduling and participant registration to evaluation and post-training impact tracking.
            </p>
        </div>
    </section>

    <section class="landing-section" style="background:var(--blue-xsoft)">
        <h2 class="landing-section-title">College of Industrial Technology (CIT)</h2>
        <div class="landing-features">
            @foreach ([
                ['fa-graduation-cap', 'Technical &amp; Vocational Education', 'Eight specialization areas aligned with industry needs.'],
                ['fa-screwdriver-wrench', 'Hands-On Training', 'Combining theory with practical skills for workforce readiness.'],
                ['fa-seedling', 'Community Development', 'Contributing to inclusive growth through extension service programs.'],
                ['fa-chart-column', 'CHED Compliance', 'All programs documented and reported per CHED requirements.'],
            ] as [$icon, $title, $desc])
                <div class="feature-card">
                    <div class="feature-card-icon"><i class="fa-solid {{ $icon }}"></i></div>
                    <h3>{!! $title !!}</h3>
                    <p>{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>
</x-public-layout>
