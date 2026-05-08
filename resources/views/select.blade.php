@extends('refactor.layouts.admin')

@section('title', trans('awswhitelabel::messages.aws_whitelabel_plugin'))

@section('page-header')
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title">{{ $plugin->title }}</h1>
            <p class="mc-page-subtitle">{{ $plugin->description }}</p>
        </div>
    </div>
@endsection

@section('content')
@if (session('alert-error'))
    <div class="mc-alert mc-alert-danger" style="margin-bottom:var(--space-3)">
        <span class="material-symbols-rounded" aria-hidden="true">error</span>
        {{ session('alert-error') }}
    </div>
@endif

<div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:var(--space-5);align-items:start;">

<div class="mc-card">
    <div style="padding:var(--space-5);">
        @if ($step)
            <p style="margin:0 0 var(--space-3) 0;color:var(--color-text-muted);font-size:var(--text-sm);">
                <strong>{{ trans('awswhitelabel::messages.wizard.step', ['n' => $step, 'total' => 2]) }}</strong>
            </p>
        @endif

        <h2 style="margin:0 0 var(--space-2) 0;font-size:var(--text-lg);">{{ trans('awswhitelabel::messages.select.heading') }}</h2>
        <p style="color:var(--color-text-muted);margin-bottom:var(--space-4);">{{ trans('awswhitelabel::messages.select.intro') }}</p>

        @if (!empty($delegation) && !$delegation['delegated'])
            <div class="mc-alert mc-alert-warning" style="margin-bottom:var(--space-4);">
                <span class="material-symbols-rounded" aria-hidden="true">warning</span>
                <div>
                    <strong>{{ trans('awswhitelabel::messages.select.ns_warning_title') }}</strong>
                    <p style="margin:var(--space-2) 0 0 0;">{!! trans('awswhitelabel::messages.select.ns_warning_body', ['domain' => '<code>'.e($currentZone).'</code>']) !!}</p>
                    <details style="margin-top:var(--space-2);">
                        <summary style="cursor:pointer;color:var(--color-text-muted);font-size:var(--text-sm);">{{ trans('awswhitelabel::messages.select.ns_warning_diag') }}</summary>
                        <div style="margin-top:var(--space-2);font-size:var(--text-sm);">
                            <strong>{{ trans('awswhitelabel::messages.select.ns_route53') }}:</strong>
                            <ul style="margin:var(--space-1) 0 var(--space-2) var(--space-3);padding:0;">
                                @foreach ($delegation['route53_ns'] as $ns)
                                    <li><code>{{ $ns }}</code></li>
                                @endforeach
                            </ul>
                            <strong>{{ trans('awswhitelabel::messages.select.ns_public') }}:</strong>
                            @if (empty($delegation['public_ns']))
                                <p style="margin:var(--space-1) 0 0 0;color:var(--color-text-muted);">{{ trans('awswhitelabel::messages.select.ns_public_empty') }}</p>
                            @else
                                <ul style="margin:var(--space-1) 0 0 var(--space-3);padding:0;">
                                    @foreach ($delegation['public_ns'] as $ns)
                                        <li><code>{{ $ns }}</code></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </details>
                </div>
            </div>
        @endif

        @if (empty($domains))
            <div class="mc-alert mc-alert-warning" style="margin-bottom:var(--space-3);">
                <span class="material-symbols-rounded" aria-hidden="true">warning</span>
                {{ trans('awswhitelabel::messages.select.no_zones') }}
            </div>
            <a href="{{ action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@editKey') }}" class="mc-btn mc-btn-default">
                {{ trans('awswhitelabel::messages.action.edit_credentials') }}
            </a>
        @else
            <form method="POST" action="{{ action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@saveDomain') }}">
                @csrf

                @if ($currentZone)
                    <p style="color:var(--color-text-muted);font-size:var(--text-sm);margin:0 0 var(--space-2) 0;">
                        {{ trans('awswhitelabel::messages.select.current_zone', ['zone' => $currentZone]) }}
                    </p>
                @endif

                <div style="margin-bottom:var(--space-5);">
                    <label for="awswl-domain" class="mc-form-label">{{ trans('awswhitelabel::messages.select.label') }}</label>
                    <select name="domain" id="awswl-domain" class="mc-form-input" required>
                        @foreach ($domains as $domain)
                            <option value="{{ $domain['name'] }}|{{ $domain['zone'] }}" @if ($currentZone === $domain['zone']) selected @endif>
                                {{ $domain['name'] }} (Zone: {{ $domain['zone'] }})
                            </option>
                        @endforeach
                    </select>
                    <p style="color:var(--color-text-muted);font-size:var(--text-sm);margin:var(--space-1) 0 0 0;">
                        {{ trans('awswhitelabel::messages.select.help') }}
                    </p>
                </div>

                <div style="display:flex;gap:var(--space-2);">
                    <button type="submit" class="mc-btn mc-btn-primary">
                        <span class="material-symbols-rounded" aria-hidden="true">check</span>
                        {{ trans('awswhitelabel::messages.select.save') }}
                    </button>
                    <a href="{{ action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@index') }}" class="mc-btn mc-btn-ghost">
                        {{ trans('messages.cancel') }}
                    </a>
                </div>
            </form>
        @endif
    </div>
</div>

    {{-- RIGHT: zone-pick guideline --}}
    @include('awswhitelabel::_guideline_select')

</div>
@endsection
