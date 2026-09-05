@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Contact Messages</span></div>
    <h1>Contact Messages</h1>
    <p>Messages sent by the public via the Contact page</p>
  </div>
  @if($unread->count() > 0)
  <a href="{{ route('ec.messages') }}?mark_all=1" class="btn btn-outline" onclick="return confirm('Mark all messages as read?')">
    <i class="fas fa-check"></i> Mark All Read
  </a>
  @endif
</div>

@if($messages->isEmpty())
<div class="empty-state">
  <div class="empty-icon"><i class="fas fa-envelope"></i></div>
  <div class="empty-title">No messages yet</div>
  <div class="empty-sub">When someone sends a message through the contact form, it will appear here.</div>
</div>
@else
<div class="card" style="padding:0;overflow:hidden">
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:32px"></th>
        <th>Name</th>
        <th>Email</th>
        <th>Subject</th>
        <th>Date</th>
        <th style="width:120px">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($messages as $msg)
      <tr style="{{ !$msg->is_read ? 'background:rgba(59,130,246,.12);font-weight:600' : '' }}">
        <td style="text-align:center">
          @if(!$msg->is_read)
          <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#1A56DB"></span>
          @endif
        </td>
        <td>{{ $msg->name }}</td>
        <td><a href="mailto:{{ $msg->email }}" style="color:var(--blue-primary)">{{ $msg->email }}</a></td>
        <td>
          <span style="cursor:pointer;color:var(--text-heading)" onclick="toggleMsg({{ $msg->id }})">
            {{ $msg->subject }}
          </span>
          <div id="msg-{{ $msg->id }}" style="display:none;margin-top:8px;padding:12px;background:var(--gray-50);border-radius:8px;font-size:13px;color:var(--gray-700);font-weight:400;white-space:pre-wrap;border:1px solid var(--gray-200)">
            {{ $msg->message }}
          </div>
        </td>
        <td style="white-space:nowrap;font-size:12px;color:var(--gray-500)">{{ $msg->created_at->format('M d, Y g:i A') }}</td>
        <td>
          <div style="display:flex;gap:6px">
            @if(!$msg->is_read)
            <a href="{{ route('ec.messages') }}?read={{ $msg->id }}" class="btn btn-sm btn-outline" title="Mark as read"><i class="fas fa-check"></i></a>
            @endif
            <a href="{{ route('ec.messages') }}?delete={{ $msg->id }}" class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this message?')" title="Delete"><i class="fas fa-trash"></i></a>
          </div>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

<script>
function toggleMsg(id) {
  const el = document.getElementById('msg-' + id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
@endsection
