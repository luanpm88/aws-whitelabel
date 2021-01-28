# AWS Whitelabel Plugin

## Prequisites
A domain registered at AWS Route53 is required.
It is used as your brand (brand.com for example)

## How it works
Create CNAME records chains to redirect [token]._domainkey.example.com to [token].dkim.brand.com and then to [token].dkim.amazonses.com. As a result, "amazonses.com" is hidden from users
