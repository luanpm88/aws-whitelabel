{{-- AWS Whitelabel — status chip + modal injected via layout.body.before_close --}}
@php
    $data = $plugin->getData();
    $brand = $configured ? ($data['domain'] ?? '') : null;
    $zone = $configured ? ($data['zone'] ?? '') : null;
    $settingsUrl = action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@index');
@endphp

<button type="button"
    class="awswl-chip {{ $configured ? 'awswl-chip--active' : 'awswl-chip--inactive' }}"
    data-bs-toggle="modal"
    data-bs-target="#awswl-status-modal"
    title="{{ trans('awswhitelabel::messages.chip.title') }}">
    @if ($configured)
        <span class="awswl-chip__icon">✓</span>
        <span class="awswl-chip__label">{{ trans('awswhitelabel::messages.chip.active', ['brand' => $brand]) }}</span>
    @else
        <span class="awswl-chip__icon">⚠</span>
        <span class="awswl-chip__label">{{ trans('awswhitelabel::messages.chip.inactive') }}</span>
    @endif
</button>

<div class="modal fade" id="awswl-status-modal" tabindex="-1" aria-labelledby="awswl-status-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="awswl-status-modal-title">
                    @if ($configured)
                        {{ trans('awswhitelabel::messages.modal.active.title') }}
                    @else
                        {{ trans('awswhitelabel::messages.modal.inactive.title') }}
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($configured)
                    <p>{!! trans('awswhitelabel::messages.modal.active.intro', [
                        'brand' => '<strong>'.e($brand).'</strong>',
                        'zone' => '<code>'.e($zone).'</code>',
                    ]) !!}</p>
                    <p class="text-muted small mb-0">
                        {{ trans('awswhitelabel::messages.modal.active.applies_to', ['type' => $server->type]) }}
                    </p>
                @else
                    <p>{{ trans('awswhitelabel::messages.modal.inactive.intro') }}</p>
                    <p class="text-muted small mb-0">
                        {{ trans('awswhitelabel::messages.modal.inactive.cta_hint') }}
                    </p>
                @endif
            </div>
            <div class="modal-footer">
                <a href="{{ $settingsUrl }}" class="btn btn-primary">
                    @if ($configured)
                        {{ trans('awswhitelabel::messages.modal.btn.settings') }}
                    @else
                        {{ trans('awswhitelabel::messages.modal.btn.configure') }}
                    @endif
                </a>
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">
                    {{ trans('awswhitelabel::messages.modal.btn.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
