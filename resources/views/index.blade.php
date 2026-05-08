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
@if (session('alert-success'))
    <div class="mc-alert mc-alert-success" style="margin-bottom:var(--space-3)">
        <span class="material-symbols-rounded" aria-hidden="true">check_circle</span>
        {{ session('alert-success') }}
    </div>
@endif
@if (session('alert-error'))
    <div class="mc-alert mc-alert-danger" style="margin-bottom:var(--space-3)">
        <span class="material-symbols-rounded" aria-hidden="true">error</span>
        {{ session('alert-error') }}
    </div>
@endif
@if (session('alert-warning'))
    <div class="mc-alert mc-alert-warning" style="margin-bottom:var(--space-3)">
        <span class="material-symbols-rounded" aria-hidden="true">warning</span>
        {{ session('alert-warning') }}
    </div>
@endif

<div class="mc-card" style="max-width:760px;">
    <div style="padding:var(--space-5);">
        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4);">
            @if ($plugin->isActive())
                <span class="mc-badge mc-badge-success">{{ trans('awswhitelabel::messages.status.active') }}</span>
            @else
                <span class="mc-badge mc-badge-default">{{ trans('awswhitelabel::messages.status.inactive') }}</span>
            @endif
            <h2 style="margin:0;font-size:var(--text-lg);">{{ trans('awswhitelabel::messages.summary.heading') }}</h2>
        </div>

        <dl style="display:grid;grid-template-columns:max-content 1fr;column-gap:var(--space-4);row-gap:var(--space-2);margin-bottom:var(--space-4);">
            <dt style="color:var(--color-text-muted);">{{ trans('awswhitelabel::messages.summary.aws_key_label') }}</dt>
            <dd style="margin:0;"><code>{{ $maskedKey }}</code></dd>

            <dt style="color:var(--color-text-muted);">{{ trans('awswhitelabel::messages.summary.brand_label') }}</dt>
            <dd style="margin:0;">{{ $data['domain'] }}</dd>

            <dt style="color:var(--color-text-muted);">{{ trans('awswhitelabel::messages.summary.zone_label') }}</dt>
            <dd style="margin:0;"><code>{{ $data['zone'] }}</code></dd>
        </dl>

        <div id="awswl-test-result" class="mc-alert" style="display:none;margin-bottom:var(--space-3);"></div>

        <div style="display:flex;flex-wrap:wrap;gap:var(--space-2);">
            <button type="button" id="awswl-test-btn" class="mc-btn mc-btn-default">
                <span class="material-symbols-rounded" aria-hidden="true">network_check</span>
                {{ trans('awswhitelabel::messages.action.test_connection') }}
            </button>
            <a href="{{ action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@editKey') }}" class="mc-btn mc-btn-ghost">
                {{ trans('awswhitelabel::messages.action.edit_credentials') }}
            </a>
            <a href="{{ action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@selectDomain') }}" class="mc-btn mc-btn-ghost">
                {{ trans('awswhitelabel::messages.action.change_domain') }}
            </a>
            <button type="button" class="mc-btn mc-btn-ghost" id="awswl-disconnect-btn" style="color:var(--color-danger);margin-left:auto;">
                {{ trans('awswhitelabel::messages.action.disconnect') }}
            </button>
        </div>
    </div>
</div>

{{-- Disconnect confirmation modal --}}
<div class="modal fade" id="awswl-disconnect-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ trans('awswhitelabel::messages.disconnect.title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ trans('awswhitelabel::messages.disconnect.warning') }}</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="{{ action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@disconnect') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="mc-btn mc-btn-danger">
                        {{ trans('awswhitelabel::messages.disconnect.confirm') }}
                    </button>
                </form>
                <button type="button" class="mc-btn mc-btn-ghost" data-bs-dismiss="modal">
                    {{ trans('messages.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    var testBtn = document.getElementById('awswl-test-btn');
    var resultBox = document.getElementById('awswl-test-result');
    var disconnectBtn = document.getElementById('awswl-disconnect-btn');

    if (testBtn) {
        testBtn.addEventListener('click', function() {
            testBtn.disabled = true;
            testBtn.dataset.origLabel = testBtn.dataset.origLabel || testBtn.textContent.trim();
            testBtn.textContent = '{{ trans('awswhitelabel::messages.action.testing') }}';
            resultBox.style.display = 'none';

            var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('{{ action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@testConnection') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
            }).then(function(r) {
                return r.json().then(function(json) { return { ok: r.ok, json: json }; });
            }).then(function(res) {
                resultBox.className = 'mc-alert ' + (res.ok ? 'mc-alert-success' : 'mc-alert-danger');
                resultBox.textContent = res.json.message || 'Unknown response';
                resultBox.style.display = '';
            }).catch(function(err) {
                resultBox.className = 'mc-alert mc-alert-danger';
                resultBox.textContent = 'Network error: ' + err.message;
                resultBox.style.display = '';
            }).finally(function() {
                testBtn.disabled = false;
                testBtn.textContent = testBtn.dataset.origLabel;
            });
        });
    }

    if (disconnectBtn) {
        disconnectBtn.addEventListener('click', function() {
            var modalEl = document.getElementById('awswl-disconnect-modal');
            if (window.bootstrap && window.bootstrap.Modal) {
                new window.bootstrap.Modal(modalEl).show();
            }
        });
    }
})();
</script>
@endsection
