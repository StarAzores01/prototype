<x-public-layout>
    <div style="padding:100px 0 60px;background:linear-gradient(135deg,#09182F,#102545,#1A3A72);text-align:center">
        <div style="max-width:700px;margin:0 auto;padding:0 24px">
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.25);color:#38BDF8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.4px;padding:5px 14px;border-radius:40px;margin-bottom:20px">
                About PAThrive
            </div>
            <h1 style="font-size:clamp(28px,4vw,44px);font-weight:800;color:#fff;margin-bottom:16px;line-height:1.2">
                About the System &amp; CIT Extension Programs
            </h1>
            <p style="font-size:15px;color:rgba(255,255,255,.65);line-height:1.7">
                Learn about PAThrive and the College of Industrial Technology's commitment to community development through extension services.
            </p>
        </div>
    </div>

    <section style="padding:64px 0;background:#fff">
        <div style="max-width:1100px;margin:0 auto;padding:0 28px">
            <h2 style="font-size:clamp(22px,3vw,32px);font-weight:800;color:#09182F;margin-bottom:16px">What is PAThrive?</h2>
            <p style="font-size:15px;color:#64748B;line-height:1.75;margin-bottom:14px;max-width:760px">
                <strong>PAThrive</strong> is a web-based Extension Training Management and Impact Assessment Tracking System developed for the College of Industrial Technology (CIT) of Southern Luzon State University.
            </p>
            <p style="font-size:15px;color:#64748B;line-height:1.75;max-width:760px">
                The system centralizes the management of CIT extension training programs - from scheduling and participant registration to evaluation and post-training impact tracking.
            </p>
        </div>
    </section>

    <section style="padding:64px 0;background:#F0F6FF">
        <div style="max-width:1100px;margin:0 auto;padding:0 28px">
            <h2 style="font-size:clamp(22px,3vw,32px);font-weight:800;color:#09182F;margin-bottom:24px;text-align:center">College of Industrial Technology (CIT)</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px">
                @foreach ([
                    ['&#127891;', 'Technical &amp; Vocational Education', 'Eight specialization areas aligned with industry needs.'],
                    ['&#128736;', 'Hands-On Training', 'Combining theory with practical skills for workforce readiness.'],
                    ['&#127793;', 'Community Development', 'Contributing to inclusive growth through extension service programs.'],
                    ['&#128202;', 'CHED Compliance', 'All programs documented and reported per CHED requirements.'],
                ] as [$icon, $title, $desc])
                    <div style="background:#fff;border-radius:14px;padding:24px;border:1px solid #EEF2F7;box-shadow:0 2px 8px rgba(9,24,47,.06)">
                        <div style="font-size:28px;margin-bottom:10px">{!! $icon !!}</div>
                        <div style="font-weight:700;font-size:14px;color:#09182F;margin-bottom:6px">{!! $title !!}</div>
                        <div style="font-size:13px;color:#64748B;line-height:1.6">{{ $desc }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-public-layout>
