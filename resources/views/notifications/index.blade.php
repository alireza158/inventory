@extends('layouts.app')
@section('content')
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">آلارم‌ها</h5>
    <div class="btn-group btn-group-sm">
      <a href="{{ route('notifications.index', ['filter'=>'all']) }}" class="btn btn-outline-secondary @if($filter==='all') active @endif">همه</a>
      <a href="{{ route('notifications.index', ['filter'=>'unread']) }}" class="btn btn-outline-secondary @if($filter==='unread') active @endif">خوانده‌نشده</a>
      <a href="{{ route('notifications.index', ['filter'=>'read']) }}" class="btn btn-outline-secondary @if($filter==='read') active @endif">خوانده‌شده</a>
    </div>
  </div>
  <div class="card">
    <div class="list-group list-group-flush">
      @forelse($notifications as $n)
        <a href="{{ route('notifications.open', $n->id) }}" class="list-group-item list-group-item-action @if(is_null($n->read_at)) fw-bold @endif">
          <div class="d-flex justify-content-between"><span>{{ $n->title }}</span><small>{{ $n->created_at?->diffForHumans() }}</small></div>
          <div class="small text-muted">{{ $n->message }}</div>
          <span class="badge bg-light text-dark border">{{ $n->type }}</span>
          <span class="badge bg-{{ $n->level === 'danger' ? 'danger' : ($n->level === 'warning' ? 'warning text-dark' : ($n->level === 'success' ? 'success' : 'info text-dark')) }}">{{ $n->level }}</span>
        </a>
      @empty
        <div class="p-3 text-muted">آلارمی وجود ندارد.</div>
      @endforelse
    </div>
  </div>
  <div class="mt-3">{{ $notifications->links() }}</div>
</div>
@endsection
