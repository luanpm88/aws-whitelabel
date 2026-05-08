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

    {{-- LEFT: credentials form --}}
    <div class="mc-card">
        <div style="padding:var(--space-5);">
            @if ($step)
                <p style="margin:0 0 var(--space-3) 0;color:var(--color-text-muted);font-size:var(--text-sm);">
                    <strong>{{ trans('awswhitelabel::messages.wizard.step', ['n' => $step, 'total' => 2]) }}</strong>
                </p>
            @endif

            <h2 style="margin:0 0 var(--space-2) 0;font-size:var(--text-lg);">{{ trans('awswhitelabel::messages.edit.heading') }}</h2>
            <p style="color:var(--color-text-muted);margin-bottom:var(--space-4);">{{ trans('awswhitelabel::messages.edit.intro') }}</p>

            @if ($hasExistingCreds)
                <div class="mc-alert mc-alert-info" style="margin-bottom:var(--space-4)">
                    <span class="material-symbols-rounded" aria-hidden="true">info</span>
                    {{ trans('awswhitelabel::messages.edit.existing_creds', ['key' => $maskedKey]) }}
                </div>
            @endif

            <form method="POST" action="{{ action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@saveKey') }}">
                @csrf

                <div style="margin-bottom:var(--space-4);">
                    <label for="awswl-aws-key" class="mc-form-label">AWS Access Key ID</label>
                    <input type="text" class="mc-form-input" id="awswl-aws-key" name="aws_key" required autocomplete="off" placeholder="AKIA…">
                </div>

                <div style="margin-bottom:var(--space-5);">
                    <label for="awswl-aws-secret" class="mc-form-label">AWS Secret Access Key</label>
                    <input type="password" class="mc-form-input" id="awswl-aws-secret" name="aws_secret" required autocomplete="off" placeholder="{{ $hasExistingCreds ? trans('awswhitelabel::messages.edit.secret_replace_hint') : '' }}">
                </div>

                <div style="display:flex;gap:var(--space-2);">
                    <button type="submit" class="mc-btn mc-btn-primary">
                        <span class="material-symbols-rounded" aria-hidden="true">cloud_done</span>
                        {{ trans('awswhitelabel::messages.edit.save_and_connect') }}
                    </button>
                    <a href="{{ url('rui/admin/plugins') }}" class="mc-btn mc-btn-ghost">
                        {{ trans('awswhitelabel::messages.edit.back_to_plugins') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- RIGHT: setup checklist + IAM policy guideline --}}
    @include('awswhitelabel::_guideline_edit')

</div>
@endsection
