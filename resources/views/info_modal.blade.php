{{-- AWS Whitelabel — customer-facing info chip + modal on DNS records page --}}
@php
    $data = $plugin->getData();
    $brand = $data['domain'] ?? '';
@endphp

<button type="button"
    class="awswl-chip awswl-chip--info"
    data-bs-toggle="modal"
    data-bs-target="#awswl-info-modal"
    title="{{ trans('awswhitelabel::messages.info_chip.title') }}">
    <span class="awswl-chip__icon">ⓘ</span>
    <span class="awswl-chip__label">{{ trans('awswhitelabel::messages.info_chip.label', ['brand' => $brand]) }}</span>
</button>

<div class="modal fade" id="awswl-info-modal" tabindex="-1" aria-labelledby="awswl-info-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="awswl-info-modal-title">
                    {{ trans('awswhitelabel::messages.info_modal.title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{!! trans('awswhitelabel::messages.info_modal.intro', [
                    'brand' => '<strong>'.e($brand).'</strong>',
                    'domain' => '<code>'.e($identity->value).'</code>',
                ]) !!}</p>
                <p class="text-muted small mb-0">
                    {{ trans('awswhitelabel::messages.info_modal.note') }}
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">
                    {{ trans('awswhitelabel::messages.modal.btn.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
