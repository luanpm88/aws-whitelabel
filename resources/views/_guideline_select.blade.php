{{-- Zone-pick guideline panel — sibling of the domain dropdown on select-domain page --}}

<div class="mc-card" style="background:var(--color-bg-subtle, #f7f8fa);">
    <div style="padding:var(--space-5);">
        <h3 style="margin:0 0 var(--space-3) 0;font-size:var(--text-base);display:flex;align-items:center;gap:var(--space-2);">
            <span class="material-symbols-rounded" aria-hidden="true" style="color:var(--color-primary);">help_outline</span>
            {{ trans('awswhitelabel::messages.guideline_select.title') }}
        </h3>

        {{-- What this zone is used for --}}
        <div style="margin-bottom:var(--space-4);">
            <h4 style="margin:0 0 var(--space-2) 0;font-size:var(--text-sm);font-weight:600;">
                {{ trans('awswhitelabel::messages.guideline_select.what.heading') }}
            </h4>
            <p style="margin:0;font-size:var(--text-sm);color:var(--color-text-muted);line-height:1.55;">
                {{ trans('awswhitelabel::messages.guideline_select.what.body') }}
            </p>
        </div>

        {{-- DNS chain explained visually --}}
        <div style="margin-bottom:var(--space-4);">
            <h4 style="margin:0 0 var(--space-2) 0;font-size:var(--text-sm);font-weight:600;">
                {{ trans('awswhitelabel::messages.guideline_select.chain.heading') }}
            </h4>
            <p style="margin:0 0 var(--space-2) 0;font-size:var(--text-sm);color:var(--color-text-muted);line-height:1.55;">
                {{ trans('awswhitelabel::messages.guideline_select.chain.body') }}
            </p>
            <pre style="margin:0;padding:var(--space-3);background:var(--color-bg-emphasis, #1f2933);color:#e6edf3;border-radius:6px;font-family:ui-monospace,'SF Mono',Menlo,monospace;font-size:11px;line-height:1.5;overflow-x:auto;"><code>{{ trans('awswhitelabel::messages.guideline_select.chain.example') }}</code></pre>
        </div>

        {{-- Picking criteria --}}
        <div style="margin-bottom:var(--space-4);">
            <h4 style="margin:0 0 var(--space-2) 0;font-size:var(--text-sm);font-weight:600;">
                {{ trans('awswhitelabel::messages.guideline_select.criteria.heading') }}
            </h4>
            <ul style="margin:0;padding-left:var(--space-4);font-size:var(--text-sm);color:var(--color-text-muted);line-height:1.7;">
                <li>{{ trans('awswhitelabel::messages.guideline_select.criteria.c1') }}</li>
                <li>{{ trans('awswhitelabel::messages.guideline_select.criteria.c2') }}</li>
                <li>{{ trans('awswhitelabel::messages.guideline_select.criteria.c3') }}</li>
            </ul>
        </div>

        {{-- NS delegation reminder --}}
        <div>
            <h4 style="margin:0 0 var(--space-2) 0;font-size:var(--text-sm);font-weight:600;">
                {{ trans('awswhitelabel::messages.guideline_select.ns.heading') }}
            </h4>
            <p style="margin:0;font-size:var(--text-sm);color:var(--color-text-muted);line-height:1.55;">
                {{ trans('awswhitelabel::messages.guideline_select.ns.body') }}
            </p>
        </div>
    </div>
</div>
