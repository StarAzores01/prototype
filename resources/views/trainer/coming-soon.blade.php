@extends('layouts.trainer')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>{{ ucfirst(str_replace('_', ' ', $activePage)) }}</span></div>
    <h1>{{ ucfirst(str_replace('_', ' ', $activePage)) }}</h1>
    <p>This module hasn't been converted from the original PHP prototype yet.</p>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:40px;text-align:center;color:var(--gray-400)">
    &#128736; This page is being migrated to Laravel next.
  </div>
</div>
@endsection
