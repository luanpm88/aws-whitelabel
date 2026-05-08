<?php

return [
    'aws_whitelabel_plugin' => 'AWS Whitelabel Plugin',
    'version' => 'Version',
    'plugin.setting.updated' => 'The setting was updated',

    // Status badges
    'status.active' => 'ACTIVE',
    'status.inactive' => 'INACTIVE',

    // Wizard
    'wizard.step' => 'Step :n of :total',

    // Edit credentials screen
    'edit.heading' => 'Configure AWS credentials',
    'edit.intro' => 'Provide IAM credentials with permission to manage Route53 hosted zones. The plugin uses these to UPSERT proxy CNAMEs into your brand zone whenever a customer verifies a DKIM domain.',
    'edit.existing_creds' => 'Credentials currently saved: :key — submitting will replace them.',
    'edit.secret_replace_hint' => 'Enter to replace existing secret',
    'edit.save_and_connect' => 'Save & Connect',
    'edit.back_to_plugins' => 'Back to Plugins',

    // Select brand domain screen
    'select.heading' => 'Choose brand domain',
    'select.intro' => 'Pick a Route53 hosted zone in your AWS account to use as the brand domain. Customer DKIM CNAMEs will read `*.dkim.{your-brand}` instead of `*.dkim.amazonses.com`.',
    'select.label' => 'Brand domain (Route53 hosted zone)',
    'select.help' => 'The hosted zone must be authoritative for this domain — verify NS records point to Route53 before activating.',
    'select.current_zone' => 'Currently selected zone: :zone',
    'select.no_zones' => 'No hosted zones found in this AWS account. Re-check the IAM credentials are for the correct account.',
    'select.save' => 'Save & Activate',
    'select.ns_warning_title' => '⚠ Brand domain is not delegated to Route53',
    'select.ns_warning_body' => 'The currently configured brand domain :domain has no public NS records pointing to its Route53 hosted zone. Proxy CNAMEs we write into Route53 will be invisible to public resolvers — customer DKIM verification will fail with no diagnostic. Update the registrar to delegate this domain to Route53 before relying on the whitelabel.',
    'select.ns_warning_diag' => 'Show NS comparison',
    'select.ns_route53' => 'Authoritative NS in Route53 hosted zone',
    'select.ns_public' => 'NS records resolved from public DNS',
    'select.ns_public_empty' => '(no NS records resolved — domain may not exist publicly yet)',

    // Summary (index) screen
    'summary.heading' => 'AWS Whitelabel — Summary',
    'summary.aws_key_label' => 'AWS Access Key ID',
    'summary.brand_label' => 'Brand domain',
    'summary.zone_label' => 'Route53 hosted zone',

    // Action buttons
    'action.test_connection' => 'Test Connection',
    'action.testing' => 'Testing…',
    'action.edit_credentials' => 'Edit Credentials',
    'action.change_domain' => 'Change Brand Domain',
    'action.disconnect' => 'Disconnect',

    // Disconnect modal
    'disconnect.title' => 'Disconnect AWS Whitelabel',
    'disconnect.warning' => 'This wipes the saved AWS credentials and brand-domain configuration, and disables the plugin. Customer DKIM records will revert to showing `amazonses.com`. Continue?',
    'disconnect.confirm' => 'Yes, disconnect',

    // Chip (fixed-positioned status badge on sending-server edit pages)
    'chip.title' => 'AWS Whitelabel status — click for details',
    'chip.active' => 'Whitelabelled · :brand',
    'chip.inactive' => 'Not whitelabelled',

    // Status modal (opened by clicking the chip)
    'modal.active.title' => 'AWS SES is whitelabelled',
    'modal.active.intro' => 'Customer-facing DKIM CNAMEs are rewritten to point at your brand domain :brand (Route53 zone :zone). No AWS-related strings appear to your users.',
    'modal.active.applies_to' => 'This sending server (type: :type) participates in the rewrite when its customers verify DKIM domains.',
    'modal.inactive.title' => 'AWS Whitelabel is not active',
    'modal.inactive.intro' => 'When enabled, customer DKIM CNAMEs read `*.dkim.{your-brand}` instead of `*.dkim.amazonses.com` — AWS branding is hidden.',
    'modal.inactive.cta_hint' => 'Open plugin settings to provide AWS credentials and pick a Route53 hosted zone.',

    'modal.btn.settings' => 'Open Plugin Settings',
    'modal.btn.configure' => 'Configure Plugin',
    'modal.btn.close' => 'Close',

    // Customer-facing info chip + modal (DNS records page)
    'info_chip.title' => 'About these DKIM records — click for details',
    'info_chip.label' => 'DKIM via :brand',
    'info_modal.title' => 'About your DKIM CNAME records',
    'info_modal.intro' => 'The DKIM CNAME records below for :domain point at :brand — your sender\'s brand domain — instead of a generic third-party hostname. The DNS chain still resolves correctly on the back end; this brand-domain layer is normal and required for delivery.',
    'info_modal.note' => 'Add the records as shown to your DNS panel — TTL 600 is fine — and verification will complete once DNS has propagated.',

    // Guideline panel — edit credentials page
    'guideline.title' => 'Setup checklist',
    'guideline.why_route53.heading' => 'Why must the brand domain be on Route53?',
    'guideline.why_route53.body' => 'The plugin programmatically creates proxy CNAME records (`*.dkim.{your-brand}` → `*.dkim.amazonses.com`) every time a customer verifies a DKIM domain. Only AWS Route53 exposes a stable API for this from inside an Acelle install — generic registrars (GoDaddy, Cloudflare, Namecheap, …) either lack the API or require per-vendor integrations the plugin does not implement. Therefore the brand domain MUST be hosted in a Route53 hosted zone.',
    'guideline.steps.heading' => 'Quick setup',
    'guideline.steps.s1' => 'Register your brand domain (e.g. `mybrand.com`) at any registrar.',
    'guideline.steps.s2' => 'Create a hosted zone in AWS Route53 for that brand domain.',
    'guideline.steps.s3' => 'Update the registrar to delegate the domain to Route53 (point the registrar NS records at the four NS values from Route53\'s DelegationSet).',
    'guideline.steps.s4' => 'Create an IAM user in AWS with the policy below attached.',
    'guideline.steps.s5' => 'Generate an access key for that IAM user, then paste the key + secret into the form on the left.',
    'guideline.iam.heading' => 'IAM policy for the AWS access key',
    'guideline.iam.body' => 'The IAM user / role behind the access key needs these four Route53 actions. The plugin never touches IAM, S3, EC2, or any other AWS service.',
    'guideline.iam.copy' => 'Copy',
    'guideline.iam.copied' => 'Copied',
    'guideline.iam.scope_hint' => '<code>Resource: "*"</code> covers any hosted zone in the account. To tighten, replace it with <code>arn:aws:route53:::hostedzone/Z…</code> for the specific brand zone — the <code>ListHostedZones</code> action still needs <code>"*"</code> since it is a list-level call.',

    // Guideline panel — select zone page
    'guideline_select.title' => 'About the brand zone',
    'guideline_select.what.heading' => 'What this zone is used for',
    'guideline_select.what.body' => 'The zone you pick will host one CNAME record per customer DKIM token (e.g. `xxx.dkim.{your-brand}`) which forwards to the matching `xxx.dkim.amazonses.com`. Customer DNS chains resolve through this zone; AWS branding never appears on the customer side.',
    'guideline_select.chain.heading' => 'How the DNS chain resolves',
    'guideline_select.chain.body' => 'Customer adds one CNAME at THEIR DNS provider; that CNAME points at your brand zone (here in Route53); your brand zone\'s CNAME points at amazonses.com. Three hops, no AWS string visible to the customer:',
    'guideline_select.chain.example' => "[customer DNS]   xxx._domainkey.customer.com  CNAME  xxx.dkim.your-brand.com\n[your Route53]   xxx.dkim.your-brand.com      CNAME  xxx.dkim.amazonses.com\n[AWS]            xxx.dkim.amazonses.com       CNAME  <DKIM key>",
    'guideline_select.criteria.heading' => 'Picking the right zone',
    'guideline_select.criteria.c1' => 'Pick a domain you fully control — not a customer\'s domain, not a marketing landing-page domain you might churn.',
    'guideline_select.criteria.c2' => 'Subdomain is fine and recommended (e.g. `mail.yourbrand.com`) — keeps the proxy zone separate from your main site\'s DNS.',
    'guideline_select.criteria.c3' => 'Switching the brand zone later wipes the old zone\'s proxy CNAMEs automatically — but customer DNS pointing at the old zone breaks until they update their CNAMEs to the new brand. Pick once, change rarely.',
    'guideline_select.ns.heading' => 'NS delegation must be live',
    'guideline_select.ns.body' => 'The brand domain\'s registrar must delegate to Route53 (NS records at the registrar match Route53\'s hosted-zone NS). If you see an "NS not delegated" warning above, fix that at the registrar before saving — otherwise the proxy CNAMEs we write are invisible to the public internet, and every customer DKIM verification will silently fail.',
];
