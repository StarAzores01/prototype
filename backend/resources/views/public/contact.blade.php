<x-public-layout>
    <div style="padding:100px 0 60px;background:linear-gradient(135deg,#09182F,#102545,#1A3A72);text-align:center">
        <div style="max-width:700px;margin:0 auto;padding:0 24px">
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.25);color:#38BDF8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.4px;padding:5px 14px;border-radius:40px;margin-bottom:20px">
                Get in Touch
            </div>
            <h1 style="font-size:clamp(28px,4vw,44px);font-weight:800;color:#fff;margin-bottom:16px;line-height:1.2">Contact Us</h1>
            <p style="font-size:15px;color:rgba(255,255,255,.65);line-height:1.7">
                Have a question about our extension programs? Send us a message.
            </p>
        </div>
    </div>

    <section style="padding:56px 0;background:#F0F6FF">
        <div style="max-width:1100px;margin:0 auto;padding:0 28px">

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:40px">
                @foreach ([
                    ['&#128205;', 'Address', 'College of Industrial Technology, SLSU - Main Campus, Lucban, Quezon'],
                    ['&#9993;', 'Email', 'cit.extension@slsu.edu.ph'],
                    ['&#128222;', 'Phone', '(042) 540-XXXX'],
                    ['&#128336;', 'Office Hours', 'Monday - Friday, 8:00 AM - 5:00 PM'],
                ] as [$icon, $label, $val])
                    <div style="background:#fff;border-radius:14px;padding:20px;border:1px solid #EEF2F7;box-shadow:0 2px 8px rgba(9,24,47,.06)">
                        <div style="font-size:22px;margin-bottom:8px">{!! $icon !!}</div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94A3B8;margin-bottom:4px">{{ $label }}</div>
                        <div style="font-size:13px;color:#334155">{{ $val }}</div>
                    </div>
                @endforeach
            </div>

            <h2 style="font-size:20px;font-weight:800;color:#09182F;margin-bottom:16px">Send a Message</h2>

            @if (session('status'))
                <div style="padding:13px 18px;border-radius:10px;font-size:13.5px;font-weight:500;margin-bottom:24px;max-width:760px;background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0">
                    &#10003; {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div style="padding:13px 18px;border-radius:10px;font-size:13.5px;font-weight:500;margin-bottom:24px;max-width:760px;background:#FEF2F2;color:#991B1B;border:1px solid #FECACA">
                    &#9888;
                    <ul style="margin:6px 0 0 18px">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('public.contact.submit') }}" style="background:#fff;border-radius:20px;border:1px solid #E8EEF8;padding:36px 40px;max-width:760px">
                @csrf

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label" for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Your full name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com" value="{{ old('email') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="What is this about?" value="{{ old('subject') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">Message</label>
                    <textarea id="message" name="message" class="form-control" rows="5" placeholder="Write your message here..." required>{{ old('message') }}</textarea>
                </div>

                <button type="submit" style="display:inline-flex;align-items:center;gap:8px;padding:11px 28px;border-radius:10px;background:linear-gradient(135deg,#1A56DB,#2E6BF0);color:#fff;font-family:'Poppins',sans-serif;font-size:13.5px;font-weight:700;border:none;cursor:pointer">
                    &#9993; Send Message
                </button>
            </form>
        </div>
    </section>
</x-public-layout>
