# CRA readiness note

This document records release-facing security information for SimpleBlog
Translator (`simpleblogtranslator`) version 1.1.4. It is not an EU Cyber
Resilience Act declaration of conformity and does not replace the technical
documentation, risk assessment, vulnerability-handling process, or retention
records that may be required for a specific distribution.

The module source, metadata, and release documentation use the Academic Free
License 3.0 (`AFL-3.0`). Third-party notices remain separate from this module
license.

## Product scope

The module translates merchant-selected PrestaHome SimpleBlog article fields
and can regenerate SEO metadata. It runs in the PrestaShop back office and
sends the selected source text, configured translation instructions, model
identifier, and language names to the OpenAI or Anthropic API selected by the
merchant.

The merchant selects the provider, account, model, source and target languages,
and whether to translate full content or regenerate metadata. The module does
not bundle an AI provider SDK or model.

## Security controls in this release

- API keys are displayed masked in the configuration form and a blank or masked
  submission preserves the stored key.
- Provider and model values are limited to server-side allowlists before they
  are persisted or used for an API request.
- Provider API calls use HTTPS with TLS certificate verification and explicit
  connection and response timeouts.
- The translation workflow is initiated from the PrestaShop back office and
  uses the native controller token supplied to its AJAX requests.
- Debug logging is optional. Disable it in production unless diagnostics are
  needed, and review PrestaShop logs according to the merchant's data policy.

## Dependencies and vulnerability reporting

The release inventory is recorded in [sbom.cdx.json](sbom.cdx.json) and
[THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md). Report suspected
vulnerabilities according to [SECURITY.md](SECURITY.md).

## Release evidence

The source repository records the release code, changelog, upgrade scripts, and
generated documentation. ZIP installation or upgrade testing on a clean
environment and marketplace-validator execution remain manual release controls
and are not asserted by this document.
