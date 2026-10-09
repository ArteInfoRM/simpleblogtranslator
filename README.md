# SimpleBlog Translator

PrestaShop module that translates **PrestaHome SimpleBlog** articles using the **OpenAI** or **Anthropic** AI API.

---

## Features

- Translate article title, meta title, meta description, excerpt and full content
- Supports curated **OpenAI** (GPT-6 and GPT-5.6) and **Anthropic** (Claude Haiku, Sonnet, Opus, and Fable) models
- AI-powered **SEO meta regeneration** (meta title + meta description) for any language
- Bulk translation queue with real-time progress log and stop button
- Provider/model switcher with live compatibility filter in the back office
- Branded configuration header with Blog Translator, README, changelog, and API-test actions
- Built-in **API connection test** — lists accessible models for the saved key
- Debug mode → detailed logs in PrestaShop logger
- Customisable system prompt (supports `[from_lang]` / `[to_lang]` placeholders)
- Compatible with PrestaShop **1.7.8 → 9.x**, PHP **8.1+**

---

## Requirements

| Dependency | Version |
|---|---|
| PrestaShop | 1.7.8 → **9.x** (tested on PS9) |
| PHP | ≥ 8.1 |
| PHP cURL | Required for OpenAI and Anthropic API requests |
| SimpleBlog for PrestaShop | any recent version |
| OpenAI API key **or** Anthropic API key | — |

> **Anthropic keys** must be created at [console.anthropic.com](https://console.anthropic.com) (billing required for Claude 4.x models).
> **OpenAI keys** are available at [platform.openai.com](https://platform.openai.com).

---

## Installation

1. Download or clone this repository.
2. Compress the `simpleblogtranslator/` folder as a ZIP.
3. In the PrestaShop back office go to **Modules → Module Manager → Upload a module** and upload the ZIP.
4. Click **Configure** on the module card.

---

## Configuration

| Setting | Description |
|---|---|
| **AI Provider** | `openai` or `anthropic` |
| **OpenAI API Key** | Secret key starting with `sk-…` |
| **Anthropic API Key** | Secret key starting with `sk-ant-…` |
| **Model** | Provider-filtered dropdown (only compatible models shown) |
| **Debug Mode** | Writes verbose logs to PrestaShop logger |
| **Translation Phrase** | System prompt — must contain `[from_lang]` and `[to_lang]` |

Use the **Test API Connection** button to verify the saved key and model before translating.

---

## Usage

1. Open **Blog Translator** from the module configuration page or the sidebar menu.
2. Select the **source language** and filter/search articles as needed.
3. Choose one or more **target languages**.
4. Optionally enable **Translate full content** and/or **Regenerate SEO meta**.
5. Select articles (checkbox or *Select all*) and click **Translate**.

The queue runs sequentially. Progress, per-article status and any errors are shown in the live log. Click **Stop** to abort after the current article.

---

## Supported models

### OpenAI
`gpt-6-luna` · `gpt-6.1-sol` · `gpt-6-astra`

### Anthropic
`claude-haiku-5-5` · `claude-sonnet-5-5` · `claude-opus-5-5` · `claude-fable-5-1`

Configurations without a saved model use `gpt-6-luna` for OpenAI or
`claude-haiku-5-5` for Anthropic. Previously offered GPT-6 Sol, GPT-5.6,
GPT-5.5, GPT-5.4, Claude Sonnet 5, Opus 5, Opus 4.8, Sonnet 4.6, Haiku 4.5,
Fable 5, and Opus 4.7 selections remain visible only while already configured
in the shop. Model availability depends on the provider account.

Both providers allow up to 16,000 output tokens for translation and 4,096
for SEO regeneration to leave room for reasoning and longer translated content.
These are request ceilings, not guaranteed output lengths. Responses stopped
by token limits, context exhaustion, or provider refusal/filtering are rejected
before their content is used. The API connection test checks model catalog
access rather than generating content.

---

## Security and release documentation

- [Security policy](SECURITY.md) — private vulnerability reporting and supported release line.
- [Third-party notices](THIRD-PARTY-NOTICES.md) — packaged dependencies and external services.
- [CRA readiness note](CRA.md) — release-facing scope and security information; it is not a declaration of conformity.

---

## License

Academic Free License 3.0 — see [LICENSE](LICENSE).

---

## Credits

Developed by [Tecnoacquisti.com](https://www.tecnoacquisti.com) — PrestaShop modules, consulting and hosting.
