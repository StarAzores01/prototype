<x-public-layout>
    <div class="landing-hero landing-hero-compact">
        <div class="landing-hero-content">
            <div class="landing-hero-badge">Get in Touch</div>
            <h1>Contact Us</h1>
            <p>Have a question about our extension programs? Send us a message.</p>
        </div>
    </div>

    <section class="landing-section" style="background:var(--blue-xsoft)">
        <div style="max-width:1100px;margin:0 auto;padding:0 28px">

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:40px">
                @foreach ([
                    ['fa-location-dot', 'Address', 'College of Industrial Technology, SLSU - Main Campus, Lucban, Quezon'],
                    ['fa-envelope', 'Email', 'cit.extension@slsu.edu.ph'],
                    ['fa-phone', 'Phone', '(042) 540-XXXX'],
                    ['fa-clock', 'Office Hours', 'Monday - Friday, 8:00 AM - 5:00 PM'],
                ] as [$icon, $label, $val])
                    <div class="card">
                        <div class="card-body">
                            <div style="color:var(--blue-primary);font-size:22px;margin-bottom:8px"><i class="fa-solid {{ $icon }}"></i></div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gray-400);margin-bottom:4px">{{ $label }}</div>
                            <div style="font-size:13px;color:var(--text-primary)">{{ $val }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <h2 style="font-size:20px;font-weight:800;color:var(--text-primary);margin-bottom:16px">Send a Message</h2>

            @if (session('status'))
                <div class="alert alert-success" style="max-width:760px">
                    <i class="fa-solid fa-circle-check"></i> {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" style="max-width:760px;flex-direction:column;align-items:flex-start">
                    <div><i class="fa-solid fa-triangle-exclamation"></i> Please fix the following:</div>
                    <ul style="margin:6px 0 0 18px">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card" style="max-width:760px">
                <div class="card-body">
                    <form method="POST" action="{{ route('public.contact.submit') }}">
                        @csrf

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="name">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Your full name" value="{{ old('name') }}" required>
                            </div>
                            <div class="form-group">
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

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-envelope"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
