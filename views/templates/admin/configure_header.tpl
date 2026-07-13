{*
 * SimpleBlog Translator - configuration header
 *
 * @author    Tecnoacquisti.com
 * @copyright 2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *}

<div class="panel sbt-admin-header">
    <div class="sbt-admin-header-main">
        <img src="{$sbt_module_logo_url|escape:'html':'UTF-8'}"
             alt="{$sbt_module_display_name|escape:'html':'UTF-8'}"
             class="sbt-admin-logo" width="72">
        <div class="sbt-admin-title">
            <h2>{$sbt_module_display_name|escape:'html':'UTF-8'}</h2>
            <p>{$sbt_module_description|escape:'html':'UTF-8'}</p>
            <span class="label label-default">v{$sbt_module_version|escape:'html':'UTF-8'}</span>
            <p class="sbt-admin-status">
                <strong>{l s='SimpleBlog Translator is ready.' mod='simpleblogtranslator'}</strong><br>
                {if $sbt_has_api_key || $sbt_has_anthropic_key}
                    <span class="text-success"><i class="icon-check"></i> {l s='API key is configured.' mod='simpleblogtranslator'}</span>
                {else}
                    <span class="text-warning"><i class="icon-warning-sign"></i> {l s='Please configure your API key below before starting.' mod='simpleblogtranslator'}</span>
                {/if}
            </p>
        </div>
    </div>
    <div class="sbt-admin-header-actions">
        <a href="{$sbt_readme_url|escape:'html':'UTF-8'}" class="btn btn-default"
           target="_blank" rel="noopener noreferrer">
            <i class="icon icon-book"></i> {l s='Open README' mod='simpleblogtranslator'}
        </a>
        <a href="{$sbt_changelog_url|escape:'html':'UTF-8'}" class="btn btn-default"
           target="_blank" rel="noopener noreferrer">
            <i class="icon icon-list"></i> {l s='Open changelog' mod='simpleblogtranslator'}
        </a>
        <button type="button" id="sbt-test-api-btn" class="btn btn-default">
            <i class="icon icon-plug"></i> {l s='Test API Connection' mod='simpleblogtranslator'}
        </button>
        <a href="{$sbt_translator_url|escape:'html':'UTF-8'}" class="btn btn-primary">
            <i class="icon icon-language"></i> {l s='Open Blog Translator' mod='simpleblogtranslator'}
        </a>
        <span id="sbt-test-spinner" class="sbt-test-spinner" style="display:none;">
            <i class="icon-spinner icon-spin"></i>
        </span>
        <div id="sbt-test-result" class="sbt-test-result" style="display:none;"></div>
    </div>
</div>
