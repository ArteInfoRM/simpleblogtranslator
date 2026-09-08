# Third-party notices

## Included components

No third-party library, SDK, or vendored dependency is included in this module
release. The module communicates with provider APIs through PHP cURL and uses
PrestaShop facilities supplied by the merchant's installation.

## Runtime dependencies and services

PrestaShop, Smarty, PrestaHome SimpleBlog, the PHP runtime, and the PHP cURL
extension are supplied by the merchant's environment and are not redistributed
by this package.

The module sends merchant-selected SimpleBlog article text and translation
instructions to either OpenAI or Anthropic, as selected and configured by the
merchant. Neither provider SDK nor model is bundled in this package. Provider
terms, available models, data handling, and retention settings are determined
by the merchant's provider account and configuration.
