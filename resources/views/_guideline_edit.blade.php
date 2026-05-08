{{-- Setup guideline panel — sibling of the credentials form on edit-key page --}}
@php
    $iamPolicy = <<<'JSON'
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "route53:ListHostedZones",
                "route53:GetHostedZone",
                "route53:ListResourceRecordSets",
                "route53:ChangeResourceRecordSets"
            ],
            "Resource": "*"
        }
    ]
}
JSON;
@endphp

<div class="mc-card" style="background:var(--color-bg-subtle, #f7f8fa);">
    <div style="padding:var(--space-5);">
        <h3 style="margin:0 0 var(--space-3) 0;font-size:var(--text-base);display:flex;align-items:center;gap:var(--space-2);">
            <span class="material-symbols-rounded" aria-hidden="true" style="color:var(--color-primary);">help_outline</span>
            {{ trans('awswhitelabel::messages.guideline.title') }}
        </h3>

        {{-- Why Route53 --}}
        <div style="margin-bottom:var(--space-4);">
            <h4 style="margin:0 0 var(--space-2) 0;font-size:var(--text-sm);font-weight:600;">
                {{ trans('awswhitelabel::messages.guideline.why_route53.heading') }}
            </h4>
            <p style="margin:0;font-size:var(--text-sm);color:var(--color-text-muted);line-height:1.55;">
                {{ trans('awswhitelabel::messages.guideline.why_route53.body') }}
            </p>
        </div>

        {{-- Quick setup steps --}}
        <div style="margin-bottom:var(--space-4);">
            <h4 style="margin:0 0 var(--space-2) 0;font-size:var(--text-sm);font-weight:600;">
                {{ trans('awswhitelabel::messages.guideline.steps.heading') }}
            </h4>
            <ol style="margin:0;padding-left:var(--space-4);font-size:var(--text-sm);color:var(--color-text-muted);line-height:1.7;">
                <li>{{ trans('awswhitelabel::messages.guideline.steps.s1') }}</li>
                <li>{{ trans('awswhitelabel::messages.guideline.steps.s2') }}</li>
                <li>{{ trans('awswhitelabel::messages.guideline.steps.s3') }}</li>
                <li>{{ trans('awswhitelabel::messages.guideline.steps.s4') }}</li>
                <li>{{ trans('awswhitelabel::messages.guideline.steps.s5') }}</li>
            </ol>
        </div>

        {{-- IAM policy --}}
        <div>
            <h4 style="margin:0 0 var(--space-2) 0;font-size:var(--text-sm);font-weight:600;">
                {{ trans('awswhitelabel::messages.guideline.iam.heading') }}
            </h4>
            <p style="margin:0 0 var(--space-2) 0;font-size:var(--text-sm);color:var(--color-text-muted);line-height:1.55;">
                {{ trans('awswhitelabel::messages.guideline.iam.body') }}
            </p>
            <div style="position:relative;">
                <pre id="awswl-iam-policy" style="margin:0;padding:var(--space-3);background:var(--color-bg-emphasis, #1f2933);color:#e6edf3;border-radius:6px;font-family:ui-monospace,'SF Mono',Menlo,monospace;font-size:11px;line-height:1.5;overflow-x:auto;"><code>{{ $iamPolicy }}</code></pre>
                <button type="button" class="mc-btn mc-btn-ghost mc-btn-sm" id="awswl-copy-iam"
                    style="position:absolute;top:8px;right:8px;color:#e6edf3;background:rgba(255,255,255,0.08);">
                    <span class="material-symbols-rounded" aria-hidden="true" style="font-size:14px;">content_copy</span>
                    {{ trans('awswhitelabel::messages.guideline.iam.copy') }}
                </button>
            </div>
            <p style="margin:var(--space-2) 0 0 0;font-size:var(--text-xs);color:var(--color-text-muted);line-height:1.55;">
                {!! trans('awswhitelabel::messages.guideline.iam.scope_hint') !!}
            </p>
        </div>
    </div>
</div>

{{-- Copy-button handler. Placed inline (not in @section('scripts')) so the
     partial is self-contained — same `<script>` runs whether included from
     edit.blade.php or anywhere else. The button + pre exist above by the
     time this script executes. --}}
<script>
(function() {
    var btn = document.getElementById('awswl-copy-iam');
    var pre = document.getElementById('awswl-iam-policy');
    if (!btn || !pre) return;

    btn.addEventListener('click', function() {
        var text = pre.querySelector('code').textContent;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                var orig = btn.innerHTML;
                btn.innerHTML = '<span class="material-symbols-rounded" aria-hidden="true" style="font-size:14px;">check</span> {{ trans('awswhitelabel::messages.guideline.iam.copied') }}';
                setTimeout(function() { btn.innerHTML = orig; }, 1500);
            });
        }
    });
})();
</script>
