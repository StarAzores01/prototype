@extends('layouts.ec')

@section('content')
@php
  // Maps a page_key to the public route name that actually renders it, so
  // the "View Live Page" link works — 'global' isn't a routed page (it's
  // shared footer/logo content), so it has no live-view link.
  $liveRoutes = [
    'landing'           => 'home',
    'about'             => 'about',
    'contact'           => 'contact',
    'trainings-public'  => 'trainings-public',
    'choose-role'       => 'choose-role',
    'privacy'           => 'privacy',
    'terms'             => 'terms',
  ];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Manage Public Site Content</span></div>
    <h1>Manage Public Site Content</h1>
    <p>Edit the text and images shown on the public-facing pages. Forms, buttons, and navigation are not affected — only content.</p>
  </div>
</div>

<div class="home-grid">
  @foreach($pages as $page)
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title">{{ $page['label'] }}</div>
      <div class="training-card-desc">
        {{ $page['edited'] }} of {{ $page['total'] }} section{{ $page['total'] === 1 ? '' : 's' }} customized
        @if($page['edited'] === 0)
          — showing default content
        @endif
      </div>
      <div style="background:var(--gray-100);border-radius:8px;height:8px;overflow:hidden;margin:12px 0">
        <div style="height:100%;border-radius:8px;width:{{ $page['total'] > 0 ? round($page['edited'] / $page['total'] * 100) : 0 }}%;background:#1A56DB"></div>
      </div>
      <div class="training-card-footer">
        @if(isset($liveRoutes[$page['page_key']]))
          <a href="{{ route($liveRoutes[$page['page_key']]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline">
            <i class="fas fa-arrow-up-right-from-square"></i> View Live
          </a>
        @else
          <span></span>
        @endif
        <a href="{{ route('ec.page-content.edit', $page['page_key']) }}" class="btn btn-sm btn-primary">
          <i class="fas fa-pen"></i> Edit Content
        </a>
      </div>
    </div>
  </div>
  @endforeach
</div>
@endsection
