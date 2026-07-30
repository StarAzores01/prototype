<x-public-layout>
    <div class="landing-hero landing-hero-compact">
        <div class="landing-hero-content">
            <div class="landing-hero-badge">CIT &middot; SLSU Extension Services</div>
            <h1>Extension Training Management &amp; Impact Assessment Tracking</h1>
            <p>PAThrive centralizes the planning, delivery, and impact tracking of CIT's community extension training programs.</p>
            <div class="landing-hero-btns">
                <a href="{{ route('public.trainings') }}" class="btn btn-primary">
                    <i class="fa-solid fa-book"></i> View Training Programs
                </a>
                <a href="{{ route('public.contact') }}" class="btn btn-outline-light">
                    <i class="fa-solid fa-envelope"></i> Contact Us
                </a>
            </div>
        </div>
    </div>

    <section class="landing-section">
        <div class="landing-features">
            @foreach ([
                ['fa-book', 'Training Management', 'Extension training programs across all CIT specializations.'],
                ['fa-users', 'Beneficiary Tracking', 'Participants, attendance, and post-training outcomes in one place.'],
                ['fa-circle-check', 'Evaluation &amp; Impact', 'Feedback collection and impact assessment tracking for every training.'],
            ] as [$icon, $title, $desc])
                <div class="feature-card">
                    <div class="feature-card-icon"><i class="fa-solid {{ $icon }}"></i></div>
                    <h3>{{ $title }}</h3>
                    <p>{!! $desc !!}</p>
                </div>
            @endforeach
        </div>
    </section>
</x-public-layout>
