# Changelog

All notable changes to **SimpleBlog Translator** are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.1.1] — 2026-06-08

### Changed
- Model selector aligned with the current text-generation models used by Art Blog Creator: added `gpt-5.5`, `gpt-5.5-pro`, `claude-opus-4-8`, and `claude-opus-4-7`.

---

## [1.1.0] — 2026-03-27

### Added
- **Anthropic API support** — translate with Claude Haiku, Sonnet and Opus alongside OpenAI models
- **Provider selector** in configuration (OpenAI / Anthropic)
- **Anthropic API Key** field (masked, preserved on re-save)
- **API Connection Test** button — calls `/v1/models` on the saved provider (zero token cost) and reports which models are accessible for the key
- **Model compatibility filter** — the Model dropdown dynamically hides models incompatible with the selected provider
- `copyright.tpl` credits panel on configuration page (consistent with other Tecnoacquisti modules)
- `shop_base_url` Smarty variable passed to admin templates

### Changed
- Module description updated to mention both OpenAI and Anthropic
- Model list extended with full date-suffixed Anthropic IDs (`claude-haiku-4-5-20251001`, `claude-sonnet-4-5-20250929`, `claude-opus-4-5-20251101`) and Claude 4.6 aliases
- API error messages now include the raw provider error text (surfaced in AJAX response via `$lastApiError`)
- Anthropic temperature clamped to 1.0 (API maximum)
- Form legend renamed from "OpenAI API Configuration" to "AI API Configuration"
- Default fallback model for Anthropic provider changed to `claude-haiku-4-5-20251001`

### Fixed
- Generic "AI API error on field" message replaced by the actual provider error (e.g. model not found, quota exceeded)

---

## [1.0.0] — 2026-01-15

### Added
- Initial release
- Translate SimpleBlog articles via OpenAI Chat Completions API
- Supported fields: `title`, `meta_title`, `meta_description`, `short_content`, `content` (optional)
- AI-powered SEO meta regeneration (`meta_title` + `meta_description`) for any language
- Bulk translation queue with real-time progress bar, per-article log and Stop button
- Source language selector, title search, active filter, sort order and per-page options
- Select all / deselect all controls
- Translated language badges displayed inline on article rows
- Customisable system prompt with `[from_lang]` / `[to_lang]` placeholders
- Temperature and Debug Mode settings
- Automatic tab placement under the SimpleBlog parent menu entry
- `fixTabParent` hook to correct tab position without reinstalling
- PrestaShop 1.7.8 → 9.x compatibility
