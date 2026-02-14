@props([
    'title' => '',
    'icon' => 'file',
    'showRefresh' => false,
    'refreshId' => 'btnCardRefresh',
    'reloadIconId' => 'reloadIcon',
    'showUserAvatar' => false,
    'avatarSize' => 32,
])

@php
    $authUser = $showUserAvatar ? Auth::user() : null;
    $authName = $authUser?->name ?? 'User';

    $avatarUrl = null;
    if ($showUserAvatar && $authUser) {
        $raw = $authUser?->photo;
        $avatarUrl = $authUser && isset($authUser->photo_url) ? $authUser->photo_url : null;

        if (!$avatarUrl) {
            if (!empty($raw)) {
                $key = 'upload/user_images/' . ltrim((string) $raw, '/');
                if ((bool) env('AWS_SIGNED_URLS', false)) {
                    try {
                        $disk = \Illuminate\Support\Facades\Storage::disk('s3');
                        if (is_callable([$disk, 'temporaryUrl'])) {
                            $avatarUrl = (string) call_user_func([$disk, 'temporaryUrl'], $key, now()->addMinutes((int) env('AWS_SIGNED_URL_TTL', 60)));
                        } else {
                            $avatarUrl = (string) url($key);
                        }
                    } catch (\Throwable $e) {
                        $avatarUrl = (string) url($key);
                    }
                } else {
                    $base = (string) (config('filesystems.disks.s3.url') ?? '');
                    $avatarUrl = $base !== '' ? rtrim($base, '/') . '/' . ltrim($key, '/') : (string) url($key);
                }
            } else {
                $avatarUrl = (string) url('upload/no_image.jpg');
            }
        }
    }
@endphp

<div class="card flex-grow-1 d-flex flex-column" style="flex:1 1 auto;min-height:0;position:relative;">
    @if($title)
        <div class="card-header d-flex align-items-center justify-content-between py-2" style="min-height:48px;">
            <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                @if(Str::startsWith($icon, 'fa'))
                    <i class="{{ $icon }} me-2" style="font-size:18px; width:18px; text-align:center;"></i>
                @else
                    <i data-feather="{{ $icon }}" class="me-2" style="width:18px;height:18px;"></i>
                @endif
                <span class="d-inline-block">{{ $title }}</span>
                @if($showRefresh)
                    <button type="button" class="btn btn-outline-success btn-sm p-0 ms-2" id="{{ $refreshId }}" title="Reload Data" aria-label="Reload" style="width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;">
                        <i data-feather="refresh-ccw" id="{{ $reloadIconId }}" style="width:16px;height:16px;"></i>
                    </button>
                @endif
            </h5>

            <div class="d-flex align-items-center gap-2">
                {{ $actions ?? '' }}

                @if($showUserAvatar && $avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $authName }}" class="rounded-circle" style="width:{{ $avatarSize }}px;height:{{ $avatarSize }}px;object-fit:cover;" title="{{ $authName }}" />
                @endif
            </div>
        </div>
    @endif

    <div class="card-body">
        {{ $slot }}
    </div>
</div>
