@extends('layouts.ec')

@section('content')
@php
  // $v() returns the currently-live value for a field — exactly what the
  // public page is showing right now, whether EC has edited it or not.
  // Uses PageContent::get() (the same call the public views make) so the
  // admin always sees the live content in the edit form, not a blank field.
  // This means: if EC saved a value → show that saved value.
  //             if no row exists yet → show the public default fallback.
  // The defaults map mirrors every PageContent::get() call across the
  // public view files, keyed [page_key][section_key].
  $defaults = [
    'global' => [
      'site_logo'        => asset('imgs/logofinalpt.png'),
      'site_tagline'     => 'College of Industrial Technology',
      'footer_tagline'   => 'Extension Training Management & Impact Assessment Tracking System — College of Industrial Technology',
      'footer_copyright' => '© ' . now()->year . ' PAThrive – SLSU College of Industrial Technology. All rights reserved.',
      'contact_address'  => 'SLSU Main Campus, Lucban, Quezon, Philippines',
      'contact_email'    => 'cit.extension@slsu.edu.ph',
      'contact_phone'    => '(042) 540-XXXX',
    ],
    'landing' => [
      'hero_badge'             => 'College of Industrial Technology',
      'hero_heading_text'      => 'Empowering Communities Through',
      'hero_heading_highlight' => 'Skills Training',
      'hero_subheading'        => 'PAThrive is the official Extension Training Management & Impact Assessment Tracking System of the College of Industrial Technology — connecting communities with quality technical-vocational programs.',
      'courses_badge'          => 'Course Highlights',
      'courses_title'          => 'See Our Training Programs in Action',
      'courses_desc'           => 'Watch real participants and trainers from CIT extension programs — from culinary arts to automotive technology.',
      'programs_badge'         => 'Training Areas',
      'programs_title'         => 'Courses Offered',
      'programs_desc'          => 'CIT offers community-based technical-vocational training across 8 technology areas — free for qualified beneficiaries.',
      'cta_heading'            => 'Ready to Join a Training?',
      'cta_desc'               => 'Register now and start building skills for a better livelihood.',
      'cta_button_text'        => 'Register Now',
    ],
    'about' => [
      'hero_eyebrow'   => 'About PAThrive',
      'hero_heading'   => 'About the System & CIT Extension Programs',
      'hero_desc'      => "Learn about PAThrive and the College of Industrial Technology's commitment to community development through extension services.",
      'system_badge'   => 'The System',
      'system_title'   => 'What is PAThrive?',
      'system_body'    => "PAThrive is a web-based Extension Training Management and Impact Assessment Tracking System developed for the College of Industrial Technology (CIT) of Southern Luzon State University – Main Campus.\n\nThe system centralizes the management of CIT extension training programs — from planning and scheduling to participant registration, evaluation, and post-training skills utilization tracking.\n\nPAThrive enables Extension Coordinators to create and manage programs, assign Project Leaders, and generate reports — while giving participants a dedicated portal to register, attend trainings, and complete evaluations.",
      'college_badge'  => 'About the College',
      'college_title'  => 'College of Industrial Technology (CIT)',
      'college_desc'   => 'The College of Industrial Technology provides technical and vocational education that prepares individuals for industry, employment, and entrepreneurship.',
      'college_body'   => "The College of Industrial Technology (CIT) offers various specialization areas such as Culinary Technology, Apparel and Fashion Technology, Computer Technology, Information Technology, Electronics Technology, Automotive Technology, Mechanical Technology, and Print Media Technology.\n\nCIT plays a vital role in developing skilled and competent individuals by combining theoretical knowledge with hands-on training, equipping students and community members with industry-relevant competencies.\n\nIn addition to its academic functions, CIT actively participates in community development through extension services, contributing to the university's mission of promoting inclusive growth and sustainable development.",
      'programs_badge' => 'Extension Programs',
      'programs_title' => 'CIT Extension Programs',
      'programs_desc'  => "The extension programs of CIT are part of SLSU's initiatives to deliver knowledge, skills, and technical expertise to communities.",
    ],
    'contact' => [
      'hero_eyebrow'       => 'Contact Us',
      'hero_heading'       => 'Get in Touch',
      'hero_desc'          => 'Have questions about PAThrive or CIT extension programs? We\'d love to hear from you.',
      'info_section_title' => 'Contact Information',
      'info_section_sub'   => 'Reach us through any of the following channels.',
      'office_hours'       => 'Monday – Friday, 8:00 AM – 5:00 PM',
      'website'            => 'https://slsu.edu.ph',
      'facebook'           => 'https://facebook.com/',
      'message_section_title' => 'Send a Message',
      'message_section_sub'   => 'Fill out the form and we\'ll get back to you as soon as possible.',
    ],
    'trainings-public' => [
      'hero_eyebrow' => 'Training Programs',
      'hero_heading' => 'Explore CIT Extension Trainings',
      'hero_desc'    => 'Browse current and upcoming extension training activities conducted by the College of Industrial Technology.',
    ],
    'choose-role' => [
      'heading'    => 'Create Your Account',
      'subheading' => 'Choose your role to get started with PAThrive.',
    ],
    'privacy' => [
      'page_heading' => 'Privacy Policy',
      'body_html'    => '<div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">1. Information We Collect</div><p>PAThrive collects the following to operate the extension training management system:</p><ul style="margin-top:8px;padding-left:20px;display:flex;flex-direction:column;gap:6px"><li>User account information (name, email, ID number, role)</li><li>Training program data (title, schedule, area, status)</li><li>Participant information (name, ID number, enrollment, evaluation status)</li><li>Uploaded documents and files</li></ul></div><div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">2. How We Use Your Information</div><p>Collected data is used solely for managing College of Industrial Technology extension training programs, generating reports for CHED compliance, and tracking beneficiary outcomes. Data is not shared with third parties.</p></div><div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">3. Data Storage</div><p>All data is stored on College of Industrial Technology institutional servers. Uploaded files are stored in a secure directory accessible only through the system. Passwords are hashed and never stored in plain text.</p></div><div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">4. Data Retention</div><p>Training records and participant data are retained for the duration required by CHED regulations and SLSU institutional policies. Users may request data correction through the Extension Office.</p></div><div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">5. Cookies &amp; Sessions</div><p>PAThrive uses PHP sessions to maintain your login state. No third-party tracking cookies are used.</p></div><div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">6. Your Rights</div><p>Authorized users may view and update their personal information through the My Profile page. For data deletion requests or concerns, contact the CIT Extension Office.</p></div><div style="padding:16px;background:var(--blue-xsoft);border-radius:var(--radius);border:1px solid var(--blue-soft)"><p style="font-size:13px;color:var(--navy-mid)"><i class="fas fa-shield-halved"></i> For privacy concerns, contact <strong>cit.extension@slsu.edu.ph</strong> or visit the CIT Extension Office at SLSU Main Campus, Lucban, Quezon.</p></div>',
    ],
    'terms' => [
      'page_heading' => 'Terms of Use',
      'body_html'    => '<div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">1. Acceptance of Terms</div><p>By accessing and using PAThrive, you agree to be bound by these Terms of Use. This system is intended exclusively for authorized personnel of the College of Industrial Technology.</p></div><div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">2. Authorized Use</div><p>PAThrive is a web-based extension training management system. Access is granted only to Extension Coordinators and Faculty Trainers of the College of Industrial Technology. Unauthorized access or sharing of credentials is strictly prohibited.</p></div><div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">3. Data Accuracy</div><p>Users are responsible for the accuracy of data entered into the system, including training records, participant information, and evaluation results. Falsification of records is a violation of university policy.</p></div><div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">4. Confidentiality</div><p>All data within PAThrive, including participant personal information and training records, is confidential. Users must not disclose system data to unauthorized parties.</p></div><div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">5. System Availability</div><p>The College of Industrial Technology does not guarantee uninterrupted access to PAThrive. Scheduled maintenance or technical issues may temporarily affect availability.</p></div><div><div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">6. Modifications</div><p>These terms may be updated at any time. Continued use of the system after changes constitutes acceptance of the revised terms.</p></div><div style="padding:16px;background:var(--blue-xsoft);border-radius:var(--radius);border:1px solid var(--blue-soft)"><p style="font-size:13px;color:var(--navy-mid)"><i class="fas fa-circle-info"></i> For questions, contact the CIT Extension Office at <strong>cit.extension@slsu.edu.ph</strong>.</p></div>',
    ],
  ];

  // $v() returns the live value: saved DB row first, then the public-page
  // default — so the edit form always shows what's currently visible.
  $v = function (string $pageKey, string $key) use ($values, $defaults): ?string {
    $saved = $values[$pageKey][$key] ?? null;
    if ($saved !== null && $saved !== '') {
      return $saved;
    }
    return $defaults[$pageKey][$key] ?? null;
  };
@endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Manage Public Site Content</span></div>
    <h1>Manage Public Site Content</h1>
    <p>Edit the text, images, and information shown on the public-facing PAThrive website.</p>
  </div>
  <div class="page-title-actions">
    <button type="button" class="btn btn-outline" onclick="openPagePreview('home')">
      <i class="fas fa-eye"></i> View Live
    </button>
    <form method="POST" action="{{ route('ec.page-content.publish') }}" style="display:inline">
      @csrf
      <input type="hidden" name="tab" value="{{ $activeTab ?? 'pages' }}"/>
      <button type="submit" class="btn btn-primary" @if(! ($hasPendingPublish ?? false)) title="No unpublished changes" @endif>
        <i class="fas fa-upload"></i> Publish All Changes
        @if($hasPendingPublish ?? false)
          <span class="badge" style="background:#f59e0b;color:#fff;margin-left:6px;border-radius:999px;padding:1px 7px;font-size:11px">●</span>
        @endif
      </button>
    </form>
  </div>
</div>

<div class="inner-tabs">
  <button type="button" class="inner-tab {{ ($activeTab ?? 'pages') === 'pages' ? 'active' : '' }}" onclick="switchMainTab('tab-pages', this)">
    <i class="fas fa-file-lines"></i> Pages
    @if(($dirtyPages ?? collect())->except('global')->contains(true))<span class="unpublished-dot" title="Unpublished changes"></span>@endif
  </button>
  <button type="button" class="inner-tab {{ ($activeTab ?? 'pages') === 'trainings' ? 'active' : '' }}" onclick="switchMainTab('tab-trainings', this)">
    <i class="fas fa-graduation-cap"></i> Trainings
    @if(($featuredTrainings ?? collect())->contains(fn($t) => $t->hasUnpublishedFeatureChanges()))<span class="unpublished-dot" title="Unpublished changes"></span>@endif
  </button>
  <button type="button" class="inner-tab {{ ($activeTab ?? 'pages') === 'posts' ? 'active' : '' }}" onclick="switchMainTab('tab-posts', this)"><i class="fas fa-bullhorn"></i> Posts</button>
  <button type="button" class="inner-tab {{ ($activeTab ?? 'pages') === 'settings' ? 'active' : '' }}" onclick="switchMainTab('tab-settings', this)">
    <i class="fas fa-gear"></i> Site Settings
    @if(($dirtyPages ?? collect())->get('global'))<span class="unpublished-dot" title="Unpublished changes"></span>@endif
  </button>
</div>

<style>
  .unpublished-dot { display:inline-block; width:8px; height:8px; border-radius:50%; background:#f59e0b; margin-left:5px; vertical-align:middle; }
</style>

<!-- ══════════════════════════════════════
     TAB: PAGES
══════════════════════════════════════ -->
<div class="inner-tab-panel {{ ($activeTab ?? 'pages') === 'pages' ? 'active' : '' }}" id="tab-pages">
  <div class="home-grid">
    @foreach ([
      ['home',              'Home Page',      'Hero section, introduction, featured trainings, and call-to-action.'],
      ['about',             'About Page',     'About PAThrive, CIT information, mission, vision, and extension programs.'],
      ['trainings-public',  'Trainings Page', 'Overview of all featured training activities offered by CIT.'],
      ['contact',           'Contact Page',   'Contact information, office hours, social links, and inquiry form.'],
      ['privacy',           'Privacy Policy', 'Data privacy policy and user rights information for the public site.'],
      ['terms',             'Terms of Use',   'Terms and conditions for visitors using the PAThrive public website.'],
    ] as [$routeName, $title, $desc])
    <div class="training-card">
      <div class="training-card-body">
        <div class="training-card-title">
          {{ $title }}
          @if(($dirtyPages ?? collect())->get($routeName))
            <span class="unpublished-dot" title="Unpublished changes"></span>
          @endif
        </div>
        <div class="training-card-desc">{{ $desc }}</div>
        <div class="training-card-footer">
          <button type="button" class="btn btn-sm btn-outline" onclick="openPagePreview('{{ $routeName }}')">
            <i class="fas fa-eye"></i> View Live
          </button>
          <button type="button" class="btn btn-sm btn-primary" onclick="openModal('edit-{{ $routeName }}')">
            <i class="fas fa-pen"></i> Edit Content
          </button>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</div>

<!-- ══════════════════════════════════════
     TAB: TRAININGS
══════════════════════════════════════ -->
<style>
/* ── Trainings tab responsive table ─────────────────────────────── */
@media (max-width: 700px) {
  /* Hide the desktop table header on mobile */
  #tab-trainings .data-table thead { display: none; }

  /* Each row becomes a card */
  #tab-trainings .data-table,
  #tab-trainings .data-table tbody,
  #tab-trainings .data-table tr,
  #tab-trainings .data-table td { display: block; width: 100%; box-sizing: border-box; }

  #tab-trainings .data-table tr {
    border-bottom: 1px solid var(--gray-100);
    padding: 14px 16px;
    position: relative;
    display: grid;
    grid-template-columns: 50px 1fr;
    grid-template-rows: auto auto auto auto;
    column-gap: 12px;
  }
  #tab-trainings .data-table tr:last-child { border-bottom: none; }

  /* Image spans rows 1–2 (aligns with name + desc) */
  #tab-trainings .tc-img-cell {
    grid-column: 1; grid-row: 1 / 3;
    display: flex; align-items: flex-start; padding-top: 2px;
  }
  /* Name */
  #tab-trainings .tc-name-cell {
    grid-column: 2; grid-row: 1;
    font-size: 14px; font-weight: 700; color: var(--text-heading);
    padding: 0; border: none; align-self: center;
  }
  /* Description */
  #tab-trainings .tc-desc-cell {
    grid-column: 2; grid-row: 2;
    font-size: 12.5px; color: var(--gray-500); line-height: 1.5;
    padding: 4px 0 0; border: none;
  }
  /* Program + Status on same row */
  #tab-trainings .tc-meta-cell {
    grid-column: 1 / -1; grid-row: 3;
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    font-size: 12px; color: var(--gray-500);
    padding: 8px 0 0; border: none;
  }
  /* Actions */
  #tab-trainings .tc-actions-cell {
    grid-column: 1 / -1; grid-row: 4;
    padding: 8px 0 0; border: none;
  }
  #tab-trainings .tc-actions-cell .action-btns { justify-content: flex-start; }

  /* Empty state spans full row */
  #tab-trainings .data-table tr td[colspan] {
    grid-column: 1 / -1; grid-row: 1;
    display: block; text-align: center;
    padding: 28px 0 !important;
  }
}
</style>
<div class="inner-tab-panel {{ ($activeTab ?? 'pages') === 'trainings' ? 'active' : '' }}" id="tab-trainings">
  <div class="page-header" style="margin-bottom:16px">
    <div class="page-header-left">
      <div style="font-size:15px;font-weight:700;color:var(--text-heading)">Trainings</div>
      <p>Feature real Activities (from the system's Activities/Trainings) with a photo — they show up as "Courses Offered" on the Home page and "Training Programs" on the public Trainings page.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openFeatureActivityModal()">
      <i class="fas fa-star"></i> Feature Activity
    </button>
  </div>

  <div class="card" style="overflow:hidden">
    <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:64px">Photo</th>
          <th>Activity</th>
          <th>Area</th>
          <th>Description</th>
          <th style="width:200px">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($featuredTrainings as $t)
        <tr>
          {{-- Photo --}}
          <td class="tc-img-cell">
            @if($t->featured_image)
              <img src="{{ $t->featured_image }}" alt="{{ $t->title }}" style="width:44px;height:34px;border-radius:6px;object-fit:cover;display:block"/>
            @else
              <div style="width:44px;height:34px;border-radius:6px;background:var(--blue-soft);display:flex;align-items:center;justify-content:center;color:var(--blue-primary)"><i class="fas fa-image"></i></div>
            @endif
          </td>
          {{-- Activity --}}
          <td class="tc-name-cell"><strong>{{ $t->title }}</strong>@if($t->hasUnpublishedFeatureChanges())<span class="unpublished-dot" title="Unpublished changes"></span>@endif</td>
          {{-- Area (free text, whatever the EC typed) --}}
          <td class="tc-meta-cell" style="white-space:nowrap">{{ $t->area }}</td>
          {{-- Description --}}
          <td class="tc-desc-cell" style="color:var(--gray-500);max-width:260px">{{ \Illuminate\Support\Str::limit($t->description, 90) }}</td>
          {{-- Actions --}}
          <td class="tc-actions-cell">
            <div class="action-btns">
              <button type="button" class="btn btn-sm btn-outline" onclick="openChangeImageModal({{ $t->id }}, {{ Illuminate\Support\Js::from($t->title) }})" title="Change Image"><i class="fas fa-camera"></i></button>
              <form method="POST" action="{{ route('ec.page-content.trainings.store') }}" style="display:inline" onsubmit="return confirm('Remove &quot;{{ $t->title }}&quot; from the featured list? The activity itself is kept — this only unfeatures it.')">
                @csrf
                <input type="hidden" name="tab" value="trainings"/>
                <input type="hidden" name="action" value="unfeature"/>
                <input type="hidden" name="id" value="{{ $t->id }}"/>
                <button type="submit" class="btn btn-sm btn-danger" title="Unfeature"><i class="fas fa-star-half-stroke"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:30px">No activities featured yet.</td></tr>
        @endforelse
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     TAB: POSTS
══════════════════════════════════════ -->
<style>
.posts-filter-pills { display:flex; gap:8px; margin-bottom:18px; flex-wrap:wrap; }
.posts-filter-pill {
  padding:7px 16px; border-radius:20px; font-size:13px; font-weight:600;
  background:var(--gray-50,#F5F7FA); color:var(--gray-500); border:1px solid var(--gray-100);
  cursor:pointer; transition:all .18s; user-select:none;
}
.posts-filter-pill.active { background:var(--blue-primary); color:#fff; border-color:var(--blue-primary); }
.post-row-card {
  display:flex; align-items:center; gap:16px; padding:16px 18px;
  border:1px solid var(--gray-100); border-radius:12px; background:#fff; margin-bottom:12px;
}
.post-row-icon {
  width:46px; height:46px; border-radius:10px; flex-shrink:0; overflow:hidden;
  background:var(--blue-soft); color:var(--blue-primary);
  display:flex; align-items:center; justify-content:center; font-size:18px;
}
.post-row-icon img { width:100%; height:100%; object-fit:cover; display:block; }
.post-row-body { flex:1; min-width:0; }
.post-row-title { font-size:14.5px; font-weight:700; color:var(--text-heading); margin-bottom:3px; }
.post-row-desc { font-size:12.5px; color:var(--gray-500); line-height:1.5; margin-bottom:8px;
  overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
.post-row-meta { display:flex; align-items:center; gap:16px; flex-wrap:wrap; font-size:12px; color:var(--gray-400); }
.post-row-meta span { display:inline-flex; align-items:center; gap:5px; }
.post-row-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }
@media (max-width:700px) {
  .post-row-card { flex-wrap:wrap; }
  .post-row-actions { width:100%; justify-content:flex-start; }
}
</style>
<div class="inner-tab-panel {{ ($activeTab ?? 'pages') === 'posts' ? 'active' : '' }}" id="tab-posts">
  <div class="page-header" style="margin-bottom:16px">
    <div class="page-header-left">
      <div style="font-size:15px;font-weight:700;color:var(--text-heading)">Posts &amp; Announcements</div>
      <p>Create and manage announcements shown on the public landing page.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openCreatePostModal()">
      <i class="fas fa-plus"></i> Create Post
    </button>
  </div>

  @php
    $postsTotal     = $posts->count();
    $postsPublished = $posts->where('status', 'published')->count();
    $postsDraft     = $posts->where('status', 'draft')->count();
    $postsViews     = $posts->sum('views');
  @endphp
  <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:20px">
    <div class="stat-card">
      <div class="stat-icon navy"><i class="fas fa-file-lines"></i></div>
      <div class="stat-body"><div class="stat-value">{{ $postsTotal }}</div><div class="stat-label">Total Posts</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
      <div class="stat-body"><div class="stat-value">{{ $postsPublished }}</div><div class="stat-label">Published</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="fas fa-pen"></i></div>
      <div class="stat-body"><div class="stat-value">{{ $postsDraft }}</div><div class="stat-label">Draft</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fas fa-eye"></i></div>
      <div class="stat-body"><div class="stat-value">{{ number_format($postsViews) }}</div><div class="stat-label">Total Views</div></div>
    </div>
  </div>

  <div class="posts-filter-pills">
    <div class="posts-filter-pill active" data-filter="all" onclick="filterPosts('all', this)">All Posts</div>
    <div class="posts-filter-pill" data-filter="published" onclick="filterPosts('published', this)">Published</div>
    <div class="posts-filter-pill" data-filter="draft" onclick="filterPosts('draft', this)">Drafts</div>
  </div>

  <div id="posts-list">
    @forelse($posts as $post)
    <div class="post-row-card" data-status="{{ $post->status }}">
      <div class="post-row-icon">
        @if($post->image)
          <img src="{{ $post->image }}" alt="{{ $post->title }}"/>
        @else
          <i class="fas fa-bullhorn"></i>
        @endif
      </div>
      <div class="post-row-body">
        <div class="post-row-title">{{ $post->title }}</div>
        <div class="post-row-desc">{{ $post->excerpt(160) }}</div>
        <div class="post-row-meta">
          <span><i class="fas fa-calendar"></i> {{ ($post->published_at ?? $post->created_at)?->format('M d, Y') }}</span>
          <span><i class="fas fa-user"></i> {{ $post->author->full_name ?? 'Extension Coordinator' }}</span>
          <span><i class="fas fa-eye"></i> {{ number_format($post->views) }}</span>
          <span class="badge {{ $post->status === 'published' ? 'badge-active' : 'badge-pending' }}">
            {{ $post->status === 'published' ? 'Published' : 'Draft' }}
          </span>
        </div>
      </div>
      <div class="post-row-actions">
        <button type="button" class="btn btn-sm btn-outline" onclick="openEditPostModal({{ $post->toJson() }})" title="Edit"><i class="fas fa-pen"></i> Edit</button>
        @if($post->status === 'published')
          <a href="{{ route('public.posts.show', $post) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline" title="Preview"><i class="fas fa-eye"></i> Preview</a>
        @else
          <form method="POST" action="{{ route('ec.page-content.posts.store') }}" style="display:inline">
            @csrf
            <input type="hidden" name="tab" value="posts"/>
            <input type="hidden" name="action" value="publish"/>
            <input type="hidden" name="id" value="{{ $post->id }}"/>
            <button type="submit" class="btn btn-sm btn-primary" title="Publish"><i class="fas fa-upload"></i> Publish</button>
          </form>
        @endif
        <form method="POST" action="{{ route('ec.page-content.posts.store') }}" style="display:inline" onsubmit="return confirm('Delete &quot;{{ $post->title }}&quot;? This cannot be undone.')">
          @csrf
          <input type="hidden" name="tab" value="posts"/>
          <input type="hidden" name="action" value="delete"/>
          <input type="hidden" name="id" value="{{ $post->id }}"/>
          <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
        </form>
      </div>
    </div>
    @empty
    <div class="card" style="text-align:center;color:var(--gray-400);padding:30px">No posts yet — click "Create Post" to publish your first announcement.</div>
    @endforelse
  </div>
</div>

<!-- ══════════════════════════════════════
     TAB: SITE SETTINGS
══════════════════════════════════════ -->
<div class="inner-tab-panel {{ ($activeTab ?? 'pages') === 'settings' ? 'active' : '' }}" id="tab-settings">

  <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
    <button type="button" class="btn btn-primary btn-sm settings-subtab" onclick="switchSettingsTab('set-branding', this)">Branding</button>
    <button type="button" class="btn btn-outline btn-sm settings-subtab" onclick="switchSettingsTab('set-footer', this)">Footer</button>
  </div>

  <!-- Branding -->
  <div class="settings-subpanel" id="set-branding" style="display:block">
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Site Branding</div>
          <div class="card-subtitle">Logo and tagline shown across the public site</div>
        </div>
      </div>
      <form method="POST" action="{{ route('ec.page-content.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="tab" value="settings"/>
        <div class="card-body">
          @include('ec.page-content._field', ['pageKey'=>'global','key'=>'site_tagline','label'=>'Tagline (shown under the logo)','type'=>'text','rows'=>1,'fieldValue'=>$v('global','site_tagline')])
          @include('ec.page-content._field', ['pageKey'=>'global','key'=>'footer_tagline','label'=>'Site Description','type'=>'text','fieldValue'=>$v('global','footer_tagline')])
          @include('ec.page-content._field', ['pageKey'=>'global','key'=>'site_logo','label'=>'Logo','type'=>'image','fieldValue'=>$v('global','site_logo')])
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--gray-100)">
          <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Branding</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <div class="settings-subpanel" id="set-footer" style="display:none">
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Footer Content</div>
          <div class="card-subtitle">Description, contact details, and copyright text shown in the site footer</div>
        </div>
      </div>
      <form method="POST" action="{{ route('ec.page-content.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="tab" value="settings"/>
        <div class="card-body">
          @include('ec.page-content._field', ['pageKey'=>'global','key'=>'footer_tagline','label'=>'Footer Description','type'=>'text','fieldValue'=>$v('global','footer_tagline')])
          <div class="form-row">
            @include('ec.page-content._field', ['pageKey'=>'global','key'=>'contact_address','label'=>'Address','type'=>'text','rows'=>2,'fieldValue'=>$v('global','contact_address')])
            @include('ec.page-content._field', ['pageKey'=>'global','key'=>'contact_email','label'=>'Email','type'=>'text','rows'=>1,'fieldValue'=>$v('global','contact_email')])
          </div>
          @include('ec.page-content._field', ['pageKey'=>'global','key'=>'contact_phone','label'=>'Phone','type'=>'text','rows'=>1,'fieldValue'=>$v('global','contact_phone')])
          @include('ec.page-content._field', ['pageKey'=>'global','key'=>'footer_copyright','label'=>'Copyright Text','type'=>'text','rows'=>1,'hint'=>'Leave blank to use the default "© '.now()->year.' PAThrive – SLSU College of Industrial Technology. All rights reserved."','fieldValue'=>$v('global','footer_copyright')])
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--gray-100)">
          <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Footer</button>
        </div>
      </form>
    </div>
  </div>


</div><!-- /tab-settings -->

<!-- ══════════════════════════════════════
     MODAL: EDIT HOME PAGE
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-edit-home">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <h2><i class="fas fa-house"></i> Edit Home Page</h2>
      <button class="modal-close" onclick="closeModal('edit-home')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:18px">
        <button type="button" class="btn btn-primary btn-sm home-subtab" onclick="switchHomeTab('home-hero', this)">Hero</button>
        <button type="button" class="btn btn-outline btn-sm home-subtab" onclick="switchHomeTab('home-intro', this)">Introduction</button>
        <button type="button" class="btn btn-outline btn-sm home-subtab" onclick="switchHomeTab('home-video', this)">Video Section</button>
        <button type="button" class="btn btn-outline btn-sm home-subtab" onclick="switchHomeTab('home-courses', this)">Courses</button>
        <button type="button" class="btn btn-outline btn-sm home-subtab" onclick="switchHomeTab('home-cta', this)">Call to Action</button>
      </div>

      <form id="homeContentForm" method="POST" action="{{ route('ec.page-content.update') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="tab" value="pages"/>
        <div id="home-hero" class="home-subpanel" style="display:block">
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'hero_badge','label'=>'Hero Eyebrow Badge','type'=>'text','rows'=>1,'fieldValue'=>$v('landing','hero_badge')])
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'hero_heading_text','label'=>'Main Headline','type'=>'text','rows'=>2,'fieldValue'=>$v('landing','hero_heading_text')])
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'hero_heading_highlight','label'=>'Highlighted Word(s) (shown in blue)','type'=>'text','rows'=>1,'fieldValue'=>$v('landing','hero_heading_highlight')])
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'hero_subheading','label'=>'Description','type'=>'text','fieldValue'=>$v('landing','hero_subheading')])
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'hero_image','label'=>'Hero Background Image','type'=>'image','fieldValue'=>$v('landing','hero_image')])
        </div>
        <div id="home-intro" class="home-subpanel" style="display:none">
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'courses_badge','label'=>'Section Label','type'=>'text','rows'=>1,'fieldValue'=>$v('landing','courses_badge')])
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'courses_title','label'=>'Section Heading','type'=>'text','rows'=>1,'fieldValue'=>$v('landing','courses_title')])
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'courses_desc','label'=>'Description','type'=>'text','fieldValue'=>$v('landing','courses_desc')])
        </div>
        <div id="home-courses" class="home-subpanel" style="display:none">
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'programs_badge','label'=>'Section Label','type'=>'text','rows'=>1,'fieldValue'=>$v('landing','programs_badge')])
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'programs_title','label'=>'Section Heading','type'=>'text','rows'=>1,'fieldValue'=>$v('landing','programs_title')])
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'programs_desc','label'=>'Description','type'=>'text','fieldValue'=>$v('landing','programs_desc')])
          <div class="form-hint">The training cards themselves (photo/name/description) are managed in the <strong>Trainings</strong> tab.</div>
        </div>
        <div id="home-cta" class="home-subpanel" style="display:none">
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'cta_heading','label'=>'Heading','type'=>'text','rows'=>1,'fieldValue'=>$v('landing','cta_heading')])
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'cta_desc','label'=>'Description','type'=>'text','fieldValue'=>$v('landing','cta_desc')])
          @include('ec.page-content._field', ['pageKey'=>'landing','key'=>'cta_button_text','label'=>'Button Text','type'=>'text','rows'=>1,'fieldValue'=>$v('landing','cta_button_text')])
        </div>
      </form>

      <div id="home-video" class="home-subpanel" style="display:none">
        <div class="form-hint" style="margin-bottom:12px">Saves immediately — separate from the rest of this page's content, and also editable from the Dashboard.</div>
        <form method="POST" action="{{ route('ec.dashboard.store') }}">
          @csrf
          <input type="hidden" name="action" value="upload_homepage_video"/>
          <div class="form-group">
            <label class="form-label">Video Caption</label>
            <input type="text" name="video_title" class="form-control" value="{{ old('video_title', $homepageVideo?->video_title) }}" placeholder="e.g. SLSU March"/>
          </div>
          <div class="form-group">
            <label class="form-label">Video Link</label>
            <input type="url" name="video_url" class="form-control" value="{{ old('video_url', $homepageVideo?->video_url) }}" placeholder="https://..."/>
            <div class="form-hint">YouTube, Vimeo, or a direct video file link plays inline; any other link (Drive, Facebook, etc.) opens in a new tab.</div>
          </div>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Save Video</button>
        </form>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" onclick="closeModal('edit-home')">Cancel</button>
      <button type="submit" form="homeContentForm" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     MODAL: EDIT ABOUT PAGE
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-edit-about">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <h2><i class="fas fa-circle-info"></i> Edit About Page</h2>
      <button class="modal-close" onclick="closeModal('edit-about')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.page-content.update') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="tab" value="pages"/>
      <div class="modal-body">
        <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:18px">
          <button type="button" class="btn btn-primary btn-sm about-subtab" onclick="switchAboutTab('ab-hero', this)">Hero</button>
          <button type="button" class="btn btn-outline btn-sm about-subtab" onclick="switchAboutTab('ab-system', this)">What is PAThrive?</button>
          <button type="button" class="btn btn-outline btn-sm about-subtab" onclick="switchAboutTab('ab-cit', this)">About the College</button>
          <button type="button" class="btn btn-outline btn-sm about-subtab" onclick="switchAboutTab('ab-programs', this)">Extension Programs</button>
        </div>
        <div id="ab-hero" class="about-subpanel" style="display:block">
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'hero_eyebrow','label'=>'Pill Label','type'=>'text','rows'=>1,'fieldValue'=>$v('about','hero_eyebrow')])
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'hero_heading','label'=>'Page Heading','type'=>'text','rows'=>1,'fieldValue'=>$v('about','hero_heading')])
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'hero_desc','label'=>'Description','type'=>'text','fieldValue'=>$v('about','hero_desc')])
        </div>
        <div id="ab-system" class="about-subpanel" style="display:none">
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'system_badge','label'=>'Section Label','type'=>'text','rows'=>1,'fieldValue'=>$v('about','system_badge')])
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'system_title','label'=>'Heading','type'=>'text','rows'=>1,'fieldValue'=>$v('about','system_title')])
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'system_body','label'=>'Body Text','type'=>'text','rows'=>6,'fieldValue'=>$v('about','system_body')])
        </div>
        <div id="ab-cit" class="about-subpanel" style="display:none">
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'college_badge','label'=>'Section Label','type'=>'text','rows'=>1,'fieldValue'=>$v('about','college_badge')])
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'college_title','label'=>'Heading','type'=>'text','rows'=>1,'fieldValue'=>$v('about','college_title')])
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'college_desc','label'=>'Subheading / Description','type'=>'text','fieldValue'=>$v('about','college_desc')])
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'college_body','label'=>'Body Text','type'=>'text','rows'=>5,'fieldValue'=>$v('about','college_body')])
        </div>
        <div id="ab-programs" class="about-subpanel" style="display:none">
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'programs_badge','label'=>'Section Label','type'=>'text','rows'=>1,'fieldValue'=>$v('about','programs_badge')])
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'programs_title','label'=>'Heading','type'=>'text','rows'=>1,'fieldValue'=>$v('about','programs_title')])
          @include('ec.page-content._field', ['pageKey'=>'about','key'=>'programs_desc','label'=>'Description','type'=>'text','fieldValue'=>$v('about','programs_desc')])
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('edit-about')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════
     MODAL: EDIT TRAININGS PAGE
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-edit-trainings-public">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-graduation-cap"></i> Edit Trainings Page</h2>
      <button class="modal-close" onclick="closeModal('edit-trainings-public')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.page-content.update') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="tab" value="pages"/>
      <div class="modal-body">
        <div class="form-hint" style="margin-bottom:16px">Edits the hero and intro text of the public Trainings page. Training category cards are managed in the <strong>Trainings</strong> tab.</div>
        @include('ec.page-content._field', ['pageKey'=>'trainings-public','key'=>'hero_eyebrow','label'=>'Pill Label','type'=>'text','rows'=>1,'fieldValue'=>$v('trainings-public','hero_eyebrow')])
        @include('ec.page-content._field', ['pageKey'=>'trainings-public','key'=>'hero_heading','label'=>'Page Heading','type'=>'text','rows'=>1,'fieldValue'=>$v('trainings-public','hero_heading')])
        @include('ec.page-content._field', ['pageKey'=>'trainings-public','key'=>'hero_desc','label'=>'Description','type'=>'text','fieldValue'=>$v('trainings-public','hero_desc')])
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('edit-trainings-public')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════
     MODAL: EDIT CONTACT PAGE
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-edit-contact">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <h2><i class="fas fa-envelope"></i> Edit Contact Page</h2>
      <button class="modal-close" onclick="closeModal('edit-contact')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.page-content.update') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="tab" value="pages"/>
      <div class="modal-body">
        @include('ec.page-content._field', ['pageKey'=>'contact','key'=>'hero_eyebrow','label'=>'Pill Label','type'=>'text','rows'=>1,'fieldValue'=>$v('contact','hero_eyebrow')])
        @include('ec.page-content._field', ['pageKey'=>'contact','key'=>'hero_heading','label'=>'Page Heading','type'=>'text','rows'=>1,'fieldValue'=>$v('contact','hero_heading')])
        @include('ec.page-content._field', ['pageKey'=>'contact','key'=>'hero_desc','label'=>'Description','type'=>'text','fieldValue'=>$v('contact','hero_desc')])

        <hr class="divider"/>
        <div class="section-row-title" style="font-size:13px;font-weight:700;margin-bottom:10px">Contact Info Section</div>
        <div class="form-row">
          @include('ec.page-content._field', ['pageKey'=>'contact','key'=>'info_section_title','label'=>'Section Title','type'=>'text','rows'=>1,'fieldValue'=>$v('contact','info_section_title')])
          @include('ec.page-content._field', ['pageKey'=>'contact','key'=>'info_section_sub','label'=>'Section Subtitle','type'=>'text','rows'=>1,'fieldValue'=>$v('contact','info_section_sub')])
        </div>

        <hr class="divider"/>
        <div class="section-row-title" style="font-size:13px;font-weight:700;margin-bottom:10px">Contact Cards</div>
        <div class="form-row">
          @include('ec.page-content._field', ['pageKey'=>'global','key'=>'contact_address','label'=>'Address','type'=>'text','rows'=>2,'fieldValue'=>$v('global','contact_address')])
          @include('ec.page-content._field', ['pageKey'=>'global','key'=>'contact_email','label'=>'Email','type'=>'text','rows'=>1,'fieldValue'=>$v('global','contact_email')])
        </div>
        <div class="form-row">
          @include('ec.page-content._field', ['pageKey'=>'global','key'=>'contact_phone','label'=>'Phone','type'=>'text','rows'=>1,'fieldValue'=>$v('global','contact_phone')])
          @include('ec.page-content._field', ['pageKey'=>'contact','key'=>'office_hours','label'=>'Office Hours','type'=>'text','rows'=>1,'fieldValue'=>$v('contact','office_hours')])
        </div>
        <div class="form-row">
          @include('ec.page-content._field', ['pageKey'=>'contact','key'=>'website','label'=>'Website','type'=>'text','rows'=>1,'fieldValue'=>$v('contact','website')])
          @include('ec.page-content._field', ['pageKey'=>'contact','key'=>'facebook','label'=>'Facebook','type'=>'text','rows'=>1,'fieldValue'=>$v('contact','facebook')])
        </div>

        <hr class="divider"/>
        <div class="section-row-title" style="font-size:13px;font-weight:700;margin-bottom:10px">Contact Form</div>
        <div class="form-row">
          @include('ec.page-content._field', ['pageKey'=>'contact','key'=>'message_section_title','label'=>'Form Heading','type'=>'text','rows'=>1,'fieldValue'=>$v('contact','message_section_title')])
          @include('ec.page-content._field', ['pageKey'=>'contact','key'=>'message_section_sub','label'=>'Form Description','type'=>'text','rows'=>1,'fieldValue'=>$v('contact','message_section_sub')])
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('edit-contact')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════
     MODALS: EDIT PRIVACY / TERMS (shared layout, same shape)
══════════════════════════════════════ -->
@foreach ([
  ['privacy', 'Privacy Policy'],
  ['terms',   'Terms of Use'],
] as [$legalKey, $legalTitle])
<div class="modal-overlay" id="modal-edit-{{ $legalKey }}">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-file-lines"></i> Edit {{ $legalTitle }}</h2>
      <button class="modal-close" onclick="closeModal('edit-{{ $legalKey }}')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.page-content.update') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="tab" value="pages"/>
      <div class="modal-body">
        @include('ec.page-content._field', ['pageKey'=>$legalKey,'key'=>'page_heading','label'=>'Page Heading','type'=>'text','rows'=>1,'fieldValue'=>$v($legalKey,'page_heading')])
        @include('ec.page-content._field', ['pageKey'=>$legalKey,'key'=>'body_html','label'=>'Content','type'=>'text','rows'=>10,'fieldValue'=>$v($legalKey,'body_html'),'extraClass'=>'policy-body-editor'])
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('edit-{{ $legalKey }}')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endforeach

<!-- ══════════════════════════════════════
     MODAL: FEATURE ACTIVITY
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-feature-activity">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-star"></i> Feature Activity</h2>
      <button class="modal-close" onclick="closeModal('feature-activity')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body" style="padding-bottom:0">
      <div class="posts-filter-pills" style="margin-bottom:18px">
        <span class="posts-filter-pill active" id="feature-mode-existing" onclick="switchFeatureMode('existing')">Pick Existing Activity</span>
        <span class="posts-filter-pill" id="feature-mode-new" onclick="switchFeatureMode('new')">Add New Activity</span>
      </div>
    </div>

    <!-- Pick an existing Activity -->
    <form method="POST" action="{{ route('ec.page-content.trainings.store') }}" enctype="multipart/form-data" id="feature-existing-form">
      @csrf
      <input type="hidden" name="tab" value="trainings"/>
      <input type="hidden" name="action" value="feature_existing"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Activity <span style="color:var(--red)">*</span></label>
          <select name="training_id" class="form-control" required>
            <option value="" disabled selected>Select an activity…</option>
            @forelse($availableTrainings as $av)
              <option value="{{ $av->id }}">{{ $av->title }} @if($av->area)&mdash; {{ $av->area }}@endif</option>
            @empty
              <option value="" disabled>No unfeatured activities available</option>
            @endforelse
          </select>
          <div class="form-hint">Only activities not already featured are listed. Create one first under Manage Trainings if it isn't here yet.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Photo</label>
          <input type="file" name="featured_image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp"/>
          <div class="form-hint">JPG, PNG, GIF, or WEBP — max 5 MB. Optional — you can add it later via "Change Image".</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('feature-activity')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-star"></i> Feature Activity</button>
      </div>
    </form>

    <!-- Quick-add a brand new Activity and feature it -->
    <form method="POST" action="{{ route('ec.page-content.trainings.store') }}" enctype="multipart/form-data" id="feature-new-form" style="display:none">
      @csrf
      <input type="hidden" name="tab" value="trainings"/>
      <input type="hidden" name="action" value="feature_new"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Activity Title <span style="color:var(--red)">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Basic Pastry Making" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Area / Specialization <span style="color:var(--red)">*</span></label>
          <input type="text" name="area" class="form-control" placeholder="e.g. Culinary Technology" required/>
          <div class="form-hint">Free text — type whatever specialization fits, it isn't limited to a fixed list.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Program <span style="color:var(--red)">*</span></label>
          <select name="program_id" class="form-control" required>
            <option value="" disabled selected>Select a program…</option>
            @foreach($programsForFeature as $p)
              <option value="{{ $p->id }}">{{ $p->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Short Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Brief description shown on the public website..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Photo</label>
          <input type="file" name="featured_image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp"/>
          <div class="form-hint">JPG, PNG, GIF, or WEBP — max 5 MB. Optional — you can add it later via "Change Image".</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('feature-activity')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-star"></i> Create &amp; Feature</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════
     MODAL: CHANGE TRAINING IMAGE
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-change-training-image">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2 id="change_training_image_title"><i class="fas fa-camera"></i> Change Image</h2>
      <button class="modal-close" onclick="closeModal('change-training-image')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.page-content.trainings.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="tab" value="trainings"/>
      <input type="hidden" name="action" value="update_image"/>
      <input type="hidden" name="id" id="change_training_image_id"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">New Image <span style="color:var(--red)">*</span></label>
          <input type="file" name="featured_image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp" required/>
          <div class="form-hint">JPG, PNG, GIF, or WEBP — max 5 MB.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('change-training-image')">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="pathriveRequestImageUpload(this.form, { title: 'Upload this image?' })"><i class="fas fa-check"></i> Save Image</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════
     MODAL: CREATE POST
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-create-post">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h2><i class="fas fa-bullhorn"></i> Create Post</h2>
      <button class="modal-close" onclick="closeModal('create-post')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.page-content.posts.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="tab" value="posts"/>
      <input type="hidden" name="action" value="add"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Enrollment Now Open for Culinary Batch 5" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="5" placeholder="Write the announcement content shown to visitors..." required></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Image (optional)</label>
          <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp"/>
          <div class="form-hint">JPG, PNG, GIF, or WEBP — max 5 MB.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control" style="max-width:220px">
            <option value="draft">Save as Draft</option>
            <option value="published">Publish Now</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('create-post')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Post</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════
     MODAL: EDIT POST
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-edit-post">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h2><i class="fas fa-pen"></i> Edit Post</h2>
      <button class="modal-close" onclick="closeModal('edit-post')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.page-content.posts.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="tab" value="posts"/>
      <input type="hidden" name="action" value="update"/>
      <input type="hidden" name="id" id="edit_post_id"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" name="title" id="edit_post_title" class="form-control" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="edit_post_description" class="form-control" rows="5" required></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Replace Image (optional)</label>
          <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp"/>
          <div class="form-hint">Leave blank to keep the current image. JPG, PNG, GIF, or WEBP — max 5 MB.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="edit_post_status" class="form-control" style="max-width:220px">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('edit-post')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
/**
 * Maps between the inner-tab panel ids used in the DOM and the short
 * "tab" query-string key controllers redirect back to after a save
 * (?tab=pages|trainings|posts|settings), so every write action lands the
 * EC back on the same tab they were editing instead of the default one.
 */
var _panelIdByTabKey = { 'pages': 'tab-pages', 'trainings': 'tab-trainings', 'posts': 'tab-posts', 'settings': 'tab-settings' };
var _tabKeyByPanelId = { 'tab-pages': 'pages', 'tab-trainings': 'trainings', 'tab-posts': 'posts', 'tab-settings': 'settings' };

function switchMainTab(panelId, btn, skipUrlUpdate) {
  document.querySelectorAll('.inner-tab').forEach(function (t) { t.classList.remove('active'); });
  document.querySelectorAll('.inner-tab-panel').forEach(function (p) { p.classList.remove('active'); });
  if (btn) {
    btn.classList.add('active');
  } else {
    var fallbackBtn = document.querySelector('.inner-tab[onclick*="' + panelId + '"]');
    if (fallbackBtn) fallbackBtn.classList.add('active');
  }
  document.getElementById(panelId)?.classList.add('active');
  // keep View Live preview in sync with the active tab's page
  var tabPageMap = { 'tab-pages': 'home', 'tab-trainings': 'trainings-public', 'tab-posts': 'home', 'tab-settings': 'home' };
  window._activePreviewPage = tabPageMap[panelId] || 'home';

  if (!skipUrlUpdate) {
    var tabKey = _tabKeyByPanelId[panelId];
    if (tabKey && window.history && window.history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.set('tab', tabKey);
      window.history.replaceState(null, '', url);
    }
  }
}

/**
 * On load, re-open whichever tab the "tab" query param names (set by
 * every controller redirect after a save) so the EC stays on the tab
 * they were working in instead of always landing back on Pages.
 */
function openTabFromQueryString() {
  var params = new URLSearchParams(window.location.search);
  var tabKey = params.get('tab');
  var panelId = tabKey && _panelIdByTabKey[tabKey];
  if (!panelId) return;
  var btn = document.querySelector('.inner-tab[onclick*="' + panelId + '"]');
  switchMainTab(panelId, btn, true);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', openTabFromQueryString);
} else {
  openTabFromQueryString();
}

function openCreatePostModal() {
  openModal('create-post');
}

function openEditPostModal(post) {
  document.getElementById('edit_post_id').value = post.id;
  document.getElementById('edit_post_title').value = post.title || '';
  document.getElementById('edit_post_description').value = post.description || '';
  document.getElementById('edit_post_status').value = post.status || 'draft';
  openModal('edit-post');
}

function filterPosts(status, pill) {
  document.querySelectorAll('.posts-filter-pill').forEach(function (p) { p.classList.remove('active'); });
  pill.classList.add('active');
  document.querySelectorAll('#posts-list .post-row-card').forEach(function (card) {
    card.style.display = (status === 'all' || card.dataset.status === status) ? '' : 'none';
  });
}

function switchSettingsTab(panelId, btn) {
  document.querySelectorAll('.settings-subtab').forEach(function (b) { b.classList.remove('btn-primary'); b.classList.add('btn-outline'); });
  btn.classList.remove('btn-outline'); btn.classList.add('btn-primary');
  document.querySelectorAll('.settings-subpanel').forEach(function (p) { p.style.display = 'none'; });
  var panel = document.getElementById(panelId);
  if (panel) panel.style.display = 'block';
}

function switchHomeTab(panelId, btn) {
  document.querySelectorAll('.home-subtab').forEach(function (b) { b.classList.remove('btn-primary'); b.classList.add('btn-outline'); });
  btn.classList.remove('btn-outline'); btn.classList.add('btn-primary');
  document.querySelectorAll('.home-subpanel').forEach(function (p) { p.style.display = 'none'; });
  var panel = document.getElementById(panelId);
  if (panel) panel.style.display = 'block';
}

function switchAboutTab(panelId, btn) {
  document.querySelectorAll('.about-subtab').forEach(function (b) { b.classList.remove('btn-primary'); b.classList.add('btn-outline'); });
  btn.classList.remove('btn-outline'); btn.classList.add('btn-primary');
  document.querySelectorAll('.about-subpanel').forEach(function (p) { p.style.display = 'none'; });
  var panel = document.getElementById(panelId);
  if (panel) panel.style.display = 'block';
}

function openFeatureActivityModal() {
  switchFeatureMode('existing');
  openModal('feature-activity');
}

function switchFeatureMode(mode) {
  var existingForm = document.getElementById('feature-existing-form');
  var newForm = document.getElementById('feature-new-form');
  var existingPill = document.getElementById('feature-mode-existing');
  var newPill = document.getElementById('feature-mode-new');
  if (!existingForm || !newForm) return;
  var showNew = mode === 'new';
  existingForm.style.display = showNew ? 'none' : '';
  newForm.style.display = showNew ? '' : 'none';
  existingPill.classList.toggle('active', !showNew);
  newPill.classList.toggle('active', showNew);
}

function openChangeImageModal(id, activityName) {
  document.getElementById('change_training_image_id').value = id;
  document.getElementById('change_training_image_title').innerHTML = '<i class="fas fa-camera"></i> Change Image — ' + activityName;
  openModal('change-training-image');
}

/* ── Page preview modal ─────────────────────────────────────────────────
   Opens the live public page inside a floating browser-chrome panel so
   the admin can preview any page without leaving the current admin screen.
   The panel shows a simulated nav bar with clickable page links.
   ────────────────────────────────────────────────────────────────────── */
/* ?ec_preview=1 flips every draft-aware read (PageContent::get(),
   Training::scopeVisibleOnPublicSite()/display_featured_image — see
   App\Support\PagePreview) over to EC's own unpublished draft instead of
   what's actually live, so "View Live" shows what she's about to publish.
   The flag only has any effect for an authenticated extension_coordinator
   — nobody else can use it to see draft content. */
var _pageUrls = {
  'home':              @json(route('home', ['ec_preview' => 1])),
  'about':             @json(route('about', ['ec_preview' => 1])),
  'trainings-public':  @json(route('trainings-public', ['ec_preview' => 1])),
  'contact':           @json(route('contact', ['ec_preview' => 1])),
  'privacy':           @json(route('privacy', ['ec_preview' => 1])),
  'terms':             @json(route('terms', ['ec_preview' => 1])),
};
var _pageUrlLabels = {
  'home':              'pathrive / home',
  'about':             'pathrive / about',
  'trainings-public':  'pathrive / trainings',
  'contact':           'pathrive / contact',
  'privacy':           'pathrive / privacy-policy',
  'terms':             'pathrive / terms-of-use',
};
window._activePreviewPage = 'home';

function _setPreviewNavActive(key) {
  var urlBar = document.getElementById('page-preview-url-bar');
  if (urlBar) urlBar.textContent = _pageUrlLabels[key] || 'pathrive';
}

function openPagePreview(pageKey) {
  var key = pageKey || window._activePreviewPage || 'home';
  window._activePreviewPage = key;
  var url    = _pageUrls[key] || _pageUrls['home'];
  var overlay = document.getElementById('modal-page-preview');
  var iframe  = document.getElementById('page-preview-iframe');
  iframe.src = url;
  overlay.classList.add('open');
  _setPreviewNavActive(key);
}

function switchPreviewPage(pageKey) {
  window._activePreviewPage = pageKey;
  var iframe = document.getElementById('page-preview-iframe');
  iframe.src = _pageUrls[pageKey] || _pageUrls['home'];
  _setPreviewNavActive(pageKey);
}

function closePagePreview() {
  var overlay = document.getElementById('modal-page-preview');
  var iframe  = document.getElementById('page-preview-iframe');
  overlay.classList.remove('open');
  iframe.src = 'about:blank';
}

document.getElementById('modal-page-preview')?.addEventListener('click', function (e) {
  if (e.target === this) closePagePreview();
});

// "Publish All Changes" is now a real form POST to ec.page-content.publish
// (see the page header above) — it no longer guesses at and clicks
// whatever save button happens to be visible in the active tab.
</script>

<!-- ══════════════════════════════════════
     MODAL: PAGE PREVIEW (View Live)
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-page-preview" style="z-index:600;padding:12px;align-items:center;justify-content:center">
  <div id="page-preview-panel" style="
    display:flex;flex-direction:column;
    width:min(960px,calc(100vw - 24px));height:calc(100vh - 24px);
    background:#fff;border-radius:12px;
    box-shadow:0 24px 64px rgba(0,0,0,.4);
    overflow:hidden;animation:slideUp .22s ease;
    border:1px solid rgba(0,0,0,.12);flex-shrink:0;
  ">
    <!-- Traffic-light title bar -->
    <div style="
      display:flex;align-items:center;justify-content:space-between;
      padding:10px 14px 10px 14px;
      background:#1e1e1e;border-radius:12px 12px 0 0;flex-shrink:0;gap:10px
    ">
      <div style="display:flex;align-items:center;gap:6px">
        <span style="width:12px;height:12px;border-radius:50%;background:#FF5F57;display:inline-block;cursor:pointer" onclick="closePagePreview()" title="Close"></span>
        <span style="width:12px;height:12px;border-radius:50%;background:#FEBC2E;display:inline-block"></span>
        <span style="width:12px;height:12px;border-radius:50%;background:#28C840;display:inline-block"></span>
      </div>
      <div style="flex:1;text-align:center;min-width:0">
        <span id="page-preview-url-bar" style="
          display:inline-block;font-size:11.5px;color:rgba(255,255,255,.55);
          background:rgba(255,255,255,.1);border-radius:6px;
          padding:3px 12px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap
        ">pathrive.test</span>
      </div>
      <button type="button" onclick="closePagePreview()" style="
        background:rgba(255,255,255,.12);border:none;border-radius:6px;
        color:rgba(255,255,255,.8);font-size:12px;font-weight:600;
        padding:4px 10px;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px
      ">
        <i class="fas fa-xmark"></i> Close
      </button>
    </div>

    <!-- Scrollable iframe content — nav is hidden via injected CSS on load -->
    <iframe id="page-preview-iframe" src="about:blank"
      style="flex:1;width:100%;border:none;display:block;background:#fff"
      title="Live page preview"
      sandbox="allow-same-origin allow-scripts allow-forms"
      onload="(function(f){try{var s=f.contentDocument.createElement('style');s.textContent='nav,#lpNav,.lp-nav,.nav{display:none!important}body,html{padding-top:0!important;margin-top:0!important}.lp-hero,.hero,section:first-of-type{padding-top:40px!important}';f.contentDocument.head.appendChild(s);}catch(e){}})(this)"
    ></iframe>
  </div>
</div>

{{-- ══════════════════════════════════════
     TINYMCE — rich text editor for policy body fields
     Only active on textareas with the .policy-body-editor class (privacy & terms modals).
     Uses the free CDN build (no API key required for self-hosted usage).
     On form submit TinyMCE syncs its HTML back to the hidden textarea automatically,
     so the controller receives and saves clean HTML just like any other text field.
══════════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function initPolicyEditors() {
  // Run init after the DOM is ready. The modals are already in the DOM (hidden),
  // so TinyMCE can bind to the textarea elements immediately.
  function doInit() {
    tinymce.init({
      selector: 'textarea.policy-body-editor',

      // No toolbar branding / powered-by footer
      branding: false,
      promotion: false,

      // Plugins needed for a clean policy editor
      plugins: 'lists link autoresize',
      toolbar: 'bold italic underline | bullist numlist | link | removeformat',

      // Match the modal's look — minimal chrome
      menubar: false,
      statusbar: false,

      // autoresize keeps the editor from being a fixed-height box
      min_height: 280,
      max_height: 520,
      autoresize_bottom_margin: 0,

      // Keep the editor content styles close to what the public pages render
      content_style: [
        "body { font-family: 'Poppins', sans-serif; font-size: 14px;",
        "       color: #334155; line-height: 1.9; margin: 12px; }",
        "p { margin: 0 0 10px; }",
        "ul, ol { padding-left: 20px; margin: 8px 0 10px; }",
        "li { margin-bottom: 4px; }",
        "strong { font-weight: 700; color: #09182F; }",
      ].join(' '),

      // When the containing modal opens, refresh the editor layout so it
      // renders at the correct width (modals start hidden / display:none).
      setup: function(editor) {
        editor.on('init', function() {
          // Find the closest modal-overlay ancestor and watch for it becoming visible
          var overlay = editor.getElement().closest('.modal-overlay');
          if (!overlay) return;

          var observer = new MutationObserver(function() {
            if (overlay.classList.contains('active')) {
              // Give the browser one frame to apply display styles, then refresh
              requestAnimationFrame(function() { editor.execCommand('mceAutoResize'); });
            }
          });
          observer.observe(overlay, { attributes: true, attributeFilter: ['class'] });
        });
      },
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', doInit);
  } else {
    doInit();
  }
})();
</script>

@endsection
