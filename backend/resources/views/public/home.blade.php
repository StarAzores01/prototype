<x-public-layout>
    <div style="padding:120px 0 64px;background:linear-gradient(135deg,#09182F 0%,#102545 50%,#1A3A72 100%);text-align:center">
        <div style="max-width:700px;margin:0 auto;padding:0 24px">
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.25);color:#38BDF8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.4px;padding:5px 14px;border-radius:40px;margin-bottom:20px">
                CIT &middot; SLSU Extension Services
            </div>
            <h1 style="font-size:clamp(28px,4vw,44px);font-weight:800;color:#fff;margin-bottom:16px;line-height:1.2">
                Extension Training Management &amp; Impact Assessment Tracking
            </h1>
            <p style="font-size:15px;color:rgba(255,255,255,.65);line-height:1.7;margin-bottom:32px">
                PAThrive centralizes the planning, delivery, and impact tracking of CIT's community extension training programs.
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
                <a href="{{ route('public.trainings') }}" style="display:inline-flex;align-items:center;gap:8px;padding:11px 24px;border-radius:10px;background:linear-gradient(135deg,#1A56DB,#2E6BF0);color:#fff;font-size:13.5px;font-weight:700;text-decoration:none">
                    &#128218; View Training Programs
                </a>
                <a href="{{ route('public.contact') }}" style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:10px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.85);font-size:13.5px;font-weight:600;text-decoration:none">
                    &#9993; Contact Us
                </a>
            </div>
        </div>
    </div>

    <section style="padding:64px 0;background:#fff">
        <div style="max-width:1100px;margin:0 auto;padding:0 28px">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px">
                @foreach ([
                    ['&#128218;', 'Training Management', 'Extension training programs across all CIT specializations.'],
                    ['&#128101;', 'Beneficiary Tracking', 'Participants, attendance, and post-training outcomes in one place.'],
                    ['&#9989;', 'Evaluation &amp; Impact', 'Feedback collection and impact assessment tracking for every training.'],
                ] as [$icon, $title, $desc])
                    <div style="background:#fff;border:1px solid #EEF2F7;border-radius:14px;padding:24px;box-shadow:0 2px 8px rgba(9,24,47,.06)">
                        <div style="font-size:28px;margin-bottom:10px">{!! $icon !!}</div>
                        <div style="font-weight:700;font-size:15px;color:#09182F;margin-bottom:6px">{{ $title }}</div>
                        <div style="font-size:13px;color:#64748B;line-height:1.6">{!! $desc !!}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-public-layout>
