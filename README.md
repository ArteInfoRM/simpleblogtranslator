# SimpleBlog Translator

PrestaShop module that translates **PrestaHome SimpleBlog** articles using the **OpenAI** or **Anthropic** AI API.

---

## Features

- Translate article title, meta title, meta description, excerpt and full content
- Supports **OpenAI** (GPT-4o, GPT-4.1, GPT-5 …) and **Anthropic** (Claude Haiku, Sonnet, Opus)
- AI-powered **SEO meta regeneration** (meta title + meta description) for any language
- Bulk translation queue with real-time progress log and stop button
- Provider/model switcher with live compatibility filter in the back office
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
| **Temperature** | 0 = deterministic, 1 = balanced, 2 = creative (OpenAI only; clamped to 1 for Anthropic) |
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
`gpt-5.5` · `gpt-5.5-pro` · `gpt-5.4` · `gpt-5.4-mini` · `gpt-5.4-nano` · `gpt-5` · `gpt-5-mini` · `gpt-4.1` · `gpt-4.1-mini` *(recommended)* · `gpt-4.1-nano` · `gpt-4o` · `gpt-4o-mini`

### Anthropic
`claude-opus-4-8` · `claude-opus-4-7` · `claude-haiku-4-5-20251001` *(recommended)* · `claude-sonnet-4-5-20250929` · `claude-sonnet-4-6` · `claude-opus-4-6`

---

## License

Academic Free License 3.0 — see [LICENSE](LICENSE).

---

## Credits

Developed by [Tecnoacquisti.com](https://www.tecnoacquisti.com) — PrestaShop modules, consulting and hosting.
