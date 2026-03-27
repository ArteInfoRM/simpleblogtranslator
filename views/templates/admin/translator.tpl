{*
 * SimpleBlog Translator – Main admin template
 *
 * @author    Custom
 * @copyright 2024 Custom
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *
 * Compatible: PrestaShop 1.7.8 - 9.x
 * NOTE: All JS config (SBT object) is injected via Media::addJsDef() in the controller.
 *       No inline <script> block here to avoid Smarty/JSON escaping conflicts.
 *}

<div class="sbt-wrap">

{* ══════════════════════════════════════════════════════════════════════
   PAGE HEADER
══════════════════════════════════════════════════════════════════════ *}
<div class="page-head clearfix" style="margin-bottom:16px;">
    <h2 class="page-title" style="margin:0 0 4px;">
        <i class="icon-language"></i>
        {l s='Blog Translator' mod='simpleblogtranslator'}
        <small>{l s='PrestaHome SimpleBlog x OpenAI' mod='simpleblogtranslator'}</small>
    </h2>
    <a href="{$sbt_config_url|escape:'html':'UTF-8'}" class="btn btn-default btn-sm">
        <i class="icon-cog"></i> {l s='API Settings' mod='simpleblogtranslator'}
    </a>
</div>

{* DB error *}
{if $sbt_db_error}
<div class="alert alert-danger">
    <i class="icon-warning-sign"></i>
    <strong>{l s='Database error:' mod='simpleblogtranslator'}</strong>
    {$sbt_db_error|escape:'html':'UTF-8'}
</div>
{/if}

{* ══════════════════════════════════════════════════════════════════════
   TRANSLATION OPTIONS PANEL
══════════════════════════════════════════════════════════════════════ *}
<div class="panel" id="sbt-options-panel">
    <div class="panel-heading">
        <i class="icon-cog"></i> {l s='Translation Options' mod='simpleblogtranslator'}
    </div>
    <div class="panel-body">
        <form id="sbt-filter-form" method="get">
            <input type="hidden" name="controller" value="AdminSimpleBlogTranslator">
            <input type="hidden" name="token" value="{$sbt_token|escape:'html':'UTF-8'}">

            <div class="row">

                {* Source language *}
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="control-label">
                            {l s='Source Language' mod='simpleblogtranslator'}
                        </label>
                        <select name="source_lang" id="sbt-source-lang" class="form-control">
                            {foreach $sbt_languages as $lang}
                                <option value="{$lang.id_lang|intval}"
                                    {if $lang.id_lang == $sbt_source_lang_id}selected="selected"{/if}>
                                    {$lang.name|escape:'html':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </div>

                {* Target languages *}
                <div class="col-md-5">
                    <div class="form-group">
                        <label class="control-label">
                            {l s='Target Languages' mod='simpleblogtranslator'}
                        </label>
                        <select name="target_langs[]" id="sbt-target-langs"
                                class="form-control" multiple="multiple" size="5">
                            {foreach $sbt_languages as $lang}
                                {if $lang.id_lang != $sbt_source_lang_id}
                                <option value="{$lang.id_lang|intval}"
                                    {if in_array($lang.id_lang, $sbt_target_langs)}selected="selected"{/if}>
                                    {$lang.name|escape:'html':'UTF-8'}
                                </option>
                                {/if}
                            {/foreach}
                        </select>
                        <p class="help-block">
                            <i class="icon-info-sign"></i>
                            {l s='Ctrl+Click (Win) or Cmd+Click (Mac) to select multiple' mod='simpleblogtranslator'}
                        </p>
                    </div>
                </div>

                {* Content option *}
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label">
                            {l s='Additional Options' mod='simpleblogtranslator'}
                        </label>
                        <div class="sbt-option-box">

                            {* Option 1: Translate full content *}
                            <label class="sbt-checkbox-label" id="sbt-opt-content-wrap">
                                <input type="checkbox" id="sbt-translate-content"
                                       name="translate_content" value="1"
                                       {if $sbt_translate_content}checked="checked"{/if}>
                                <strong>{l s='Translate full Content' mod='simpleblogtranslator'}</strong>
                            </label>
                            <p class="help-block sbt-warning-note" id="sbt-opt-content-note">
                                <i class="icon-exclamation-triangle text-warning"></i>
                                {l s='Enable ONLY if the article body was NOT built with Elementor.' mod='simpleblogtranslator'}
                            </p>

                            <hr class="sbt-opt-divider">

                            {* Option 2: Regenerate SEO meta *}
                            <label class="sbt-checkbox-label">
                                <input type="checkbox" id="sbt-regen-meta"
                                       name="regen_meta" value="1"
                                       {if isset($sbt_regen_meta) && $sbt_regen_meta}checked="checked"{/if}>
                                <strong>{l s='Regenerate Meta SEO (AI)' mod='simpleblogtranslator'}</strong>
                            </label>
                            <p class="help-block text-muted sbt-opt-regen-note">
                                <i class="icon-magic"></i>
                                {l s='AI generates SEO-optimised Meta Title and Meta Description from article content for each selected language (including source). When active, normal translation is skipped.' mod='simpleblogtranslator'}
                            </p>

                            <p class="help-block text-muted" style="margin-top:8px;font-size:11px;">
                                {l s='Standard fields always translated: Title, Short Content, Meta Title, Meta Description, Friendly URL.' mod='simpleblogtranslator'}
                            </p>
                        </div>
                    </div>
                </div>

            </div>{* /row *}
        </form>
    </div>
</div>

{* ══════════════════════════════════════════════════════════════════════
   PROGRESS BAR
══════════════════════════════════════════════════════════════════════ *}
<div id="sbt-progress-panel" class="panel" style="display:none;">
    <div class="panel-body">
        <div class="sbt-progress-header clearfix">
            <strong id="sbt-progress-label">{l s='Translating...' mod='simpleblogtranslator'}</strong>
            <span id="sbt-progress-count" class="text-muted" style="margin-left:8px;"></span>
            <button id="sbt-stop-btn" class="btn btn-danger btn-xs pull-right">
                <i class="icon-stop"></i> {l s='Stop' mod='simpleblogtranslator'}
            </button>
        </div>
        <div class="progress sbt-progressbar-wrap">
            <div id="sbt-progress-bar"
                 class="progress-bar progress-bar-striped active"
                 role="progressbar"
                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                 style="width:0%;min-width:24px;">0%</div>
        </div>
        <div id="sbt-progress-log"></div>
    </div>
</div>

{* Alert container *}
<div id="sbt-alert-container" style="display:none;"></div>

{* ══════════════════════════════════════════════════════════════════════
   ARTICLES GRID
══════════════════════════════════════════════════════════════════════ *}
<div class="panel">
    <div class="panel-heading clearfix">
        <div class="sbt-panel-title-left">
            <i class="icon-list"></i>
            {l s='Blog Articles' mod='simpleblogtranslator'}
            <span class="badge" style="background:#5bc0de;margin-left:6px;">{$sbt_total|intval}</span>
        </div>
        <div class="sbt-panel-title-right">
            <span class="text-muted" style="font-size:12px;margin-right:4px;">
                {l s='Show' mod='simpleblogtranslator'}
            </span>
            <select id="sbt-per-page" class="form-control input-sm sbt-perpage-select">
                {foreach $sbt_per_page_options as $opt}
                    <option value="{$opt|intval}" {if $opt == $sbt_per_page}selected="selected"{/if}>
                        {$opt|intval}
                    </option>
                {/foreach}
            </select>
            <span class="text-muted" style="font-size:12px;margin:0 10px 0 4px;">
                {l s='per page' mod='simpleblogtranslator'}
            </span>
            &nbsp;
            <button id="sbt-select-all-btn" class="btn btn-default btn-sm">
                <i class="icon-check-square-o"></i> {l s='All' mod='simpleblogtranslator'}
            </button>
            <button id="sbt-deselect-all-btn" class="btn btn-default btn-sm">
                <i class="icon-square-o"></i> {l s='None' mod='simpleblogtranslator'}
            </button>
        </div>
    </div>

    {* ── Search / Filter / Sort bar ──────────────────────────────────────── *}
    <div class="sbt-search-bar">
        <form method="get" id="sbt-search-form" class="sbt-search-form">
            <input type="hidden" name="controller"   value="AdminSimpleBlogTranslator">
            <input type="hidden" name="token"        value="{$sbt_token|escape:'html':'UTF-8'}">
            <input type="hidden" name="source_lang"  value="{$sbt_source_lang_id|intval}">
            <input type="hidden" name="per_page"     value="{$sbt_per_page|intval}">
            <input type="hidden" name="p"            value="1">

            <div class="sbt-search-inner">

                {* Title search *}
                <div class="sbt-search-field">
                    <div class="input-group input-group-sm">
                        <span class="input-group-addon"><i class="icon-search"></i></span>
                        <input type="text"
                               name="search_title"
                               class="form-control"
                               placeholder="{l s='Search by title...' mod='simpleblogtranslator'}"
                               value="{$sbt_search_title|escape:'html':'UTF-8'}">
                    </div>
                </div>

                {* Active filter *}
                <div class="sbt-search-field sbt-search-field--sm">
                    <select name="filter_active" class="form-control input-sm">
                        <option value=""  {if $sbt_filter_active === ''}selected="selected"{/if}>
                            {l s='All statuses' mod='simpleblogtranslator'}
                        </option>
                        <option value="1" {if $sbt_filter_active === '1'}selected="selected"{/if}>
                            {l s='Active only' mod='simpleblogtranslator'}
                        </option>
                        <option value="0" {if $sbt_filter_active === '0'}selected="selected"{/if}>
                            {l s='Inactive only' mod='simpleblogtranslator'}
                        </option>
                    </select>
                </div>

                {* Sort order *}
                <div class="sbt-search-field sbt-search-field--sm">
                    <select name="order_by" class="form-control input-sm">
                        <option value="date_desc" {if $sbt_order_by == 'date_desc'}selected="selected"{/if}>
                            {l s='Date (newest)' mod='simpleblogtranslator'}
                        </option>
                        <option value="date_asc"  {if $sbt_order_by == 'date_asc'}selected="selected"{/if}>
                            {l s='Date (oldest)' mod='simpleblogtranslator'}
                        </option>
                        <option value="id_desc"   {if $sbt_order_by == 'id_desc'}selected="selected"{/if}>
                            {l s='ID (desc)' mod='simpleblogtranslator'}
                        </option>
                        <option value="id_asc"    {if $sbt_order_by == 'id_asc'}selected="selected"{/if}>
                            {l s='ID (asc)' mod='simpleblogtranslator'}
                        </option>
                    </select>
                </div>

                {* Buttons *}
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="icon-search"></i> {l s='Filter' mod='simpleblogtranslator'}
                </button>
                {if $sbt_search_title != '' || $sbt_filter_active !== '' || $sbt_order_by != 'date_desc'}
                <a href="{$sbt_base_url|escape:'html':'UTF-8'}&amp;source_lang={$sbt_source_lang_id|intval}&amp;per_page={$sbt_per_page|intval}"
                   class="btn btn-default btn-sm">
                    <i class="icon-times"></i> {l s='Reset' mod='simpleblogtranslator'}
                </a>
                {/if}

            </div>
        </form>
    </div>

    {if $sbt_posts|@count > 0}

    <div class="table-responsive">
        <table class="table sbt-table" id="sbt-posts-table">
            <thead>
                <tr>
                    <th class="sbt-col-check">
                        <input type="checkbox" id="sbt-check-all">
                    </th>
                    <th class="sbt-col-id sbt-sortable">
                        <a href="{$sbt_pagination_base_url}&amp;p=1&amp;order_by={if $sbt_order_by == 'id_desc'}id_asc{else}id_desc{/if}">
                            ID
                            {if $sbt_order_by == 'id_asc'}<i class="icon-caret-up"></i>
                            {elseif $sbt_order_by == 'id_desc'}<i class="icon-caret-down"></i>
                            {else}<i class="icon-sort text-muted"></i>{/if}
                        </a>
                    </th>
                    <th class="sbt-col-title sbt-sortable">
                        {l s='Title' mod='simpleblogtranslator'}
                    </th>
                    <th class="sbt-col-cat">{l s='Category' mod='simpleblogtranslator'}</th>
                    <th class="sbt-col-date">{l s='Published' mod='simpleblogtranslator'}</th>
                    <th class="sbt-col-langs">{l s='Translated into' mod='simpleblogtranslator'}</th>
                    <th class="sbt-col-elementor text-center">{l s='Content' mod='simpleblogtranslator'}</th>
                    <th class="sbt-col-status text-center">{l s='Active' mod='simpleblogtranslator'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $sbt_posts as $post}
                <tr data-post-id="{$post.id_simpleblog_post|intval}"
                    class="sbt-post-row{if !$post.has_source_lang} sbt-no-source{/if}">

                    <td class="sbt-col-check">
                        <input type="checkbox" class="sbt-post-check"
                               value="{$post.id_simpleblog_post|intval}"
                               {if !$post.has_source_lang}disabled="disabled"{/if}>
                    </td>

                    <td class="sbt-col-id">
                        <span class="text-muted" style="font-size:11px;">
                            #{$post.id_simpleblog_post|intval}
                        </span>
                    </td>

                    <td class="sbt-col-title">
                        <span class="sbt-title">
                            {if $post.title != ''}
                                {$post.title|escape:'html':'UTF-8'|truncate:90:'...'}
                            {else}
                                <em class="text-muted">
                                    {l s='(no title in source language)' mod='simpleblogtranslator'}
                                </em>
                            {/if}
                        </span>
                        {if !$post.has_source_lang}
                            <span class="label label-warning" style="font-size:10px;">
                                {l s='missing source' mod='simpleblogtranslator'}
                            </span>
                        {/if}
                    </td>

                    <td class="sbt-col-cat">
                        {if $post.categories|@count > 0}
                            {foreach $post.categories as $cat}
                                <span class="label label-default sbt-cat-label"
                                      title="{$cat|escape:'html':'UTF-8'}">
                                    {$cat|escape:'html':'UTF-8'}
                                </span>
                            {/foreach}
                        {else}
                            <span class="text-muted">-</span>
                        {/if}
                    </td>

                    <td class="sbt-col-date">
                        <span class="text-muted sbt-date">
                            {$post.date_formatted|escape:'html':'UTF-8'}
                        </span>
                    </td>

                    <td class="sbt-col-langs" id="sbt-langs-{$post.id_simpleblog_post|intval}">
                        {foreach $post.translated_langs as $lid}
                            <span class="sbt-lang-badge" data-lang-id="{$lid|intval}">
                                {$lid|intval}
                            </span>
                        {/foreach}
                    </td>

                    <td class="sbt-col-elementor text-center">
                        {if $post.content_is_json}
                            <span class="label sbt-label-elementor" title="{l s='Content built with Elementor (JSON) – will not be translated' mod='simpleblogtranslator'}">
                                Elementor
                            </span>
                        {elseif $post.has_source_lang}
                            <span class="label label-default sbt-label-html" title="{l s='Standard HTML content – can be translated' mod='simpleblogtranslator'}">
                                HTML
                            </span>
                        {else}
                            <span class="text-muted">-</span>
                        {/if}
                    </td>

                    <td class="sbt-col-status text-center">
                        {if $post.active}
                            <span class="label label-success"><i class="icon-check"></i></span>
                        {else}
                            <span class="label label-danger"><i class="icon-times"></i></span>
                        {/if}
                    </td>

                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>{* /table-responsive *}

    {* ── Pagination ─────────────────────────────────────────────────────── *}
    {* sbt_pagination_base_url is built entirely in PHP (controller)        *}
    {* to avoid Smarty 3 {assign} with embedded variable interpolation      *}
    {if $sbt_total_pages > 1}
    <div class="panel-footer">
        <div class="row">
            <div class="col-sm-5 sbt-pagination-info">
                {l s='Showing' mod='simpleblogtranslator'}
                <strong>{$sbt_showing_from|intval}</strong>
                {l s='to' mod='simpleblogtranslator'}
                <strong>{$sbt_showing_to|intval}</strong>
                {l s='of' mod='simpleblogtranslator'}
                <strong>{$sbt_total|intval}</strong>
                {l s='entries' mod='simpleblogtranslator'}
            </div>
            <div class="col-sm-7">
                <ul class="pagination sbt-pagination pull-right">

                    {* First *}
                    <li{if $sbt_page <= 1} class="disabled"{/if}>
                        <a href="{$sbt_pagination_base_url}&amp;p=1" aria-label="First">
                            <i class="icon-step-backward"></i>
                        </a>
                    </li>

                    {* Prev *}
                    <li{if $sbt_page <= 1} class="disabled"{/if}>
                        {if $sbt_page > 1}
                            <a href="{$sbt_pagination_base_url}&amp;p={math equation='x-1' x=$sbt_page}">
                                <i class="icon-chevron-left"></i>
                            </a>
                        {else}
                            <a href="{$sbt_pagination_base_url}&amp;p=1">
                                <i class="icon-chevron-left"></i>
                            </a>
                        {/if}
                    </li>

                    {* Ellipsis before window *}
                    {if $sbt_win_start > 1}
                        <li><a href="{$sbt_pagination_base_url}&amp;p=1">1</a></li>
                        {if $sbt_win_start > 2}
                            <li class="disabled"><a href="#">...</a></li>
                        {/if}
                    {/if}

                    {* Page buttons *}
                    {foreach $sbt_pagination_pages as $ppage}
                        <li{if $ppage == $sbt_page} class="active"{/if}>
                            <a href="{$sbt_pagination_base_url}&amp;p={$ppage|intval}">
                                {$ppage|intval}
                            </a>
                        </li>
                    {/foreach}

                    {* Ellipsis after window *}
                    {if $sbt_win_end < $sbt_total_pages}
                        {if $sbt_win_end < $sbt_total_pages - 1}
                            <li class="disabled"><a href="#">...</a></li>
                        {/if}
                        <li>
                            <a href="{$sbt_pagination_base_url}&amp;p={$sbt_total_pages|intval}">
                                {$sbt_total_pages|intval}
                            </a>
                        </li>
                    {/if}

                    {* Next *}
                    <li{if $sbt_page >= $sbt_total_pages} class="disabled"{/if}>
                        {if $sbt_page < $sbt_total_pages}
                            <a href="{$sbt_pagination_base_url}&amp;p={math equation='x+1' x=$sbt_page}">
                                <i class="icon-chevron-right"></i>
                            </a>
                        {else}
                            <a href="{$sbt_pagination_base_url}&amp;p={$sbt_total_pages|intval}">
                                <i class="icon-chevron-right"></i>
                            </a>
                        {/if}
                    </li>

                    {* Last *}
                    <li{if $sbt_page >= $sbt_total_pages} class="disabled"{/if}>
                        <a href="{$sbt_pagination_base_url}&amp;p={$sbt_total_pages|intval}" aria-label="Last">
                            <i class="icon-step-forward"></i>
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </div>
    {/if}

    {else}
    <div class="panel-body">
        <div class="alert alert-warning">
            <i class="icon-warning-sign"></i>
            {l s='No blog articles found. Make sure the SimpleBlog module by PrestaHome is installed and has published posts.' mod='simpleblogtranslator'}
        </div>
    </div>
    {/if}

</div>{* /panel *}

</div>{* /sbt-wrap *}

{* ══════════════════════════════════════════════════════════════════════
   FIXED BOTTOM ACTION BAR
══════════════════════════════════════════════════════════════════════ *}
<div id="sbt-action-bar">
    <div class="sbt-action-inner">
        <div class="sbt-action-info">
            <span id="sbt-sel-count">
                <i class="icon-file-text-o"></i>
                <strong>0</strong> {l s='articles' mod='simpleblogtranslator'}
            </span>
            <span class="sbt-sep">x</span>
            <span id="sbt-lang-count">
                <i class="icon-language"></i>
                <strong>0</strong> {l s='languages' mod='simpleblogtranslator'}
            </span>
            <span id="sbt-ops-badge" class="sbt-ops-badge" style="display:none;"></span>
        </div>
        <div class="sbt-action-btn">
            <button id="sbt-translate-btn" class="btn btn-primary btn-lg" disabled="disabled">
                <i class="icon-language"></i>
                &nbsp;{l s='Start Translation' mod='simpleblogtranslator'}
            </button>
        </div>
    </div>
</div>
