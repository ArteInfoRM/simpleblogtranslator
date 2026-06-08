<?php
/**
 * SimpleBlog Translator - Admin Controller
 *
 * @author    Custom
 * @copyright 2024 Custom
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminSimpleBlogTranslatorController extends ModuleAdminController
{
    /** Allowed per-page values */
    const PER_PAGE_OPTIONS = [20, 50, 100];

    /** Stores the last API error message for surfacing in AJAX responses */
    private $lastApiError = '';

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->meta_title = 'Blog Translator';
    }

    public function initContent()
    {
        // AJAX handler exits before the normal page render.
        if (Tools::getValue('ajax') && Tools::getValue('action')) {
            $this->handleAjax();
        }

        $moduleUri = $this->module->getPathUri();
        $this->addCSS($moduleUri . 'views/css/simpleblogtranslator.css');
        $this->addJS($moduleUri . 'views/js/simpleblogtranslator.js?v=1.0.4');

        $sourceLangId = (int) Tools::getValue('source_lang', (int) Configuration::get('PS_LANG_DEFAULT'));
        $targetLangs = Tools::getValue('target_langs', []);
        $translateContent = (bool) Tools::getValue('translate_content', false);
        $page = max(1, (int) Tools::getValue('p', 1));
        $perPage = (int) Tools::getValue('per_page', 50);
        if (!in_array($perPage, self::PER_PAGE_OPTIONS)) {
            $perPage = 50;
        }

        // Search / filter / sort
        $searchTitle = trim(Tools::getValue('search_title', ''));
        $filterActive = Tools::getValue('filter_active', '');   // '', '1', '0'
        $orderBy = Tools::getValue('order_by', 'date_desc');
        $allowedOrder = ['id_asc', 'id_desc', 'date_asc', 'date_desc'];
        if (!in_array($orderBy, $allowedOrder)) {
            $orderBy = 'date_desc';
        }

        $allLanguages = Language::getLanguages(true);
        $postsData = $this->getBlogPosts($sourceLangId, $page, $perPage, $searchTitle, $filterActive, $orderBy);
        $total = $postsData['total'];
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        $winStart = max(1, $page - 2);
        $winEnd = min($totalPages, $page + 2);
        $paginationPages = range($winStart, $winEnd);

        $langMap = [];
        foreach ($allLanguages as $l) {
            $langMap[(int) $l['id_lang']] = $l['name'];
        }

        $baseUrl = $this->context->link->getAdminLink('AdminSimpleBlogTranslator');

        // Pagination base URL built in PHP - avoids Smarty 3 {assign} with embedded vars
        $paginationBaseUrl = $baseUrl
            . '&amp;per_page=' . $perPage
            . '&amp;source_lang=' . $sourceLangId;

        // -- JS config via Media::addJsDef (official PS way) ---------------
        // Must be called BEFORE parent::initContent() which flushes the JS header
        Media::addJsDef([
            'SBT' => [
                'ajaxUrl' => $baseUrl,
                'token' => $this->token,
                'baseUrl' => $baseUrl,
                'langMap' => $langMap,
                'sourceLangId' => $sourceLangId,
                'perPage' => $perPage,
                'page' => $page,
                'i18n' => [
                    'translating' => $this->module->l('Translating...'),
                    'complete' => $this->module->l('Translation complete!'),
                    'stopped' => $this->module->l('Translation stopped.'),
                    'selectTargets' => $this->module->l('Select at least one target language.'),
                    'selectPosts' => $this->module->l('Select at least one article.'),
                    'operations' => $this->module->l('operations'),
                    'articles' => $this->module->l('articles'),
                ],
            ],
        ]);

        // -- Smarty assigns BEFORE parent::initContent() -------------------
        // parent::initContent() calls renderView() which uses these vars,
        // then assigns the result to 'content' in the Smarty layout context.
        // Any assign done AFTER parent() would be too late.
        $this->context->smarty->assign([
            'sbt_languages' => $allLanguages,
            'sbt_source_lang_id' => $sourceLangId,
            'sbt_target_langs' => array_map('intval', (array) $targetLangs),
            'sbt_translate_content' => $translateContent,
            'sbt_posts' => $postsData['posts'],
            'sbt_db_error' => isset($postsData['error']) ? $postsData['error'] : null,
            'sbt_total' => $total,
            'sbt_showing_from' => ($page - 1) * $perPage + 1,
            'sbt_showing_to' => min($page * $perPage, $total),
            'sbt_page' => $page,
            'sbt_per_page' => $perPage,
            'sbt_total_pages' => $totalPages,
            'sbt_pagination_pages' => $paginationPages,
            'sbt_win_start' => $winStart,
            'sbt_win_end' => $winEnd,
            'sbt_pagination_base_url' => $paginationBaseUrl,
            'sbt_base_url' => $baseUrl,
            'sbt_token' => $this->token,
            'sbt_config_url' => $this->context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                ['configure' => 'simpleblogtranslator']
            ),
            'sbt_per_page_options' => self::PER_PAGE_OPTIONS,
            // search / filter / sort
            'sbt_search_title' => $searchTitle,
            'sbt_filter_active' => $filterActive,
            'sbt_order_by' => $orderBy,
        ]);

        // Tell PS 1.7 to call renderView() instead of the default renderList()
        $this->display = 'view';

        // Call parent LAST - it invokes renderView() and assigns result to layout
        parent::initContent();
    }

    /* ================================================================== */
    /* renderView - called by parent::initContent(), result goes to layout  */
    /* ================================================================== */

    public function renderView()
    {
        $tplPath = _PS_MODULE_DIR_ . 'simpleblogtranslator/views/templates/admin/translator.tpl';

        if (!file_exists($tplPath)) {
            return '<div class="alert alert-danger">'
                . '<strong>SimpleBlog Translator:</strong> Template file not found at: '
                . htmlspecialchars($tplPath)
                . '</div>';
        }

        try {
            return $this->context->smarty->fetch($tplPath);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'SimpleBlogTranslator template error: ' . $e->getMessage(),
                3,
                null,
                'Module',
                0
            );

            return '<div class="alert alert-danger">'
                . '<strong>SimpleBlog Translator - Template Error:</strong> '
                . htmlspecialchars($e->getMessage())
                . '</div>';
        }
    }

    /* ================================================================== */
    /* Data retrieval                                                       */
    /* ================================================================== */

    private function getBlogPosts($idLang, $page, $perPage, $searchTitle = '', $filterActive = '', $orderBy = 'date_desc')
    {
        $offset = ($page - 1) * $perPage;
        $db = Db::getInstance();

        try {
            // Build WHERE clauses for search/filter
            $where = [];
            if ($filterActive !== '') {
                $where[] = 'p.`active` = ' . (int) $filterActive;
            }
            if ($searchTitle !== '') {
                $where[] = 'pl.`title` LIKE "%' . pSQL($searchTitle) . '%"';
            }
            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            // ORDER BY
            $orderMap = [
                'id_asc' => 'p.`id_simpleblog_post` ASC',
                'id_desc' => 'p.`id_simpleblog_post` DESC',
                'date_asc' => 'p.`date_add` ASC',
                'date_desc' => 'p.`date_add` DESC',
            ];
            $orderSql = $orderMap[$orderBy];

            $total = (int) $db->getValue(
                'SELECT COUNT(*)
                 FROM `' . _DB_PREFIX_ . 'simpleblog_post` p
                 LEFT JOIN `' . _DB_PREFIX_ . 'simpleblog_post_lang` pl
                     ON p.`id_simpleblog_post` = pl.`id_simpleblog_post`
                     AND pl.`id_lang` = ' . (int) $idLang . '
                 ' . $whereSql
            );

            if ($total === 0) {
                return ['posts' => [], 'total' => 0];
            }

            $posts = $db->executeS('
                SELECT
                    p.`id_simpleblog_post`,
                    p.`id_simpleblog_category`,
                    p.`active`,
                    DATE_FORMAT(COALESCE(p.`date_add`, NOW()), "%d/%m/%Y") AS `date_formatted`,
                    COALESCE(pl.`title`, "") AS `title`,
                    pl.`id_lang` AS `has_source_lang`
                FROM `' . _DB_PREFIX_ . 'simpleblog_post` p
                LEFT JOIN `' . _DB_PREFIX_ . 'simpleblog_post_lang` pl
                    ON p.`id_simpleblog_post` = pl.`id_simpleblog_post`
                    AND pl.`id_lang` = ' . (int) $idLang . '
                ' . $whereSql . '
                ORDER BY ' . $orderSql . '
                LIMIT ' . (int) $offset . ', ' . (int) $perPage
            );

            if (empty($posts)) {
                return ['posts' => [], 'total' => $total];
            }

            $postIds = implode(',', array_map('intval', array_column($posts, 'id_simpleblog_post')));

            $transRows = $db->executeS('
                SELECT `id_simpleblog_post`, `id_lang`
                FROM `' . _DB_PREFIX_ . 'simpleblog_post_lang`
                WHERE `id_simpleblog_post` IN (' . $postIds . ')
                AND `title` != ""
            ');

            $transMap = [];
            foreach ((array) $transRows as $t) {
                $transMap[(int) $t['id_simpleblog_post']][] = (int) $t['id_lang'];
            }

            $catsMap = $this->getPostCategories($postIds);

            foreach ($posts as &$post) {
                $pid = (int) $post['id_simpleblog_post'];
                $post['translated_langs'] = isset($transMap[$pid]) ? $transMap[$pid] : [];
                $post['categories'] = isset($catsMap[$pid]) ? $catsMap[$pid] : [];
                // Detect Elementor/JSON content in PHP - more reliable than SQL IF()
                $post['content_is_json'] = $this->detectJsonContent($postIds, (int) $idLang, $pid);
            }
            unset($post);

            return ['posts' => $posts, 'total' => $total];
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'SimpleBlogTranslator getBlogPosts error: ' . $e->getMessage(),
                3,
                null,
                'Module',
                0
            );

            return ['posts' => [], 'total' => 0, 'error' => $e->getMessage()];
        }
    }

    private function detectJsonContent($postIdsCsv, $idLang, $idPost)
    {
        // We pre-fetch all short_content snippets for the current page in one query
        // to avoid N+1. Results are cached in a static map per request.
        static $cache = [];
        $cacheKey = $idLang . '_' . implode(',', (array) $postIdsCsv);
        if (!isset($cache[$cacheKey])) {
            $cache[$cacheKey] = [];
            try {
                $rows = Db::getInstance()->executeS('
                    SELECT `id_simpleblog_post`, LEFT(TRIM(`content`), 1) AS `first_char`
                    FROM `' . _DB_PREFIX_ . 'simpleblog_post_lang`
                    WHERE `id_simpleblog_post` IN (' . $postIdsCsv . ')
                    AND `id_lang` = ' . (int) $idLang . '
                    AND `content` IS NOT NULL
                    AND `content` != ""
                ');
                foreach ((array) $rows as $r) {
                    $firstChar = $r['first_char'];
                    $cache[$cacheKey][(int) $r['id_simpleblog_post']] = in_array($firstChar, ['{', '[']);
                }
            } catch (Exception $e) {
                // fail silently
            }
        }
        return isset($cache[$cacheKey][$idPost]) ? (bool) $cache[$cacheKey][$idPost] : false;
    }

    private function getPostCategories($postIdsCsv)
    {
        $map = [];
        $idLang = (int) $this->context->language->id;

        if (empty($postIdsCsv)) {
            return $map;
        }

        try {
            // In this SimpleBlog schema the category FK lives directly on simpleblog_post,
            // there is no simpleblog_post_category pivot table.
            $rows = Db::getInstance()->executeS('
                SELECT p.`id_simpleblog_post`, cl.`name`
                FROM `' . _DB_PREFIX_ . 'simpleblog_post` p
                INNER JOIN `' . _DB_PREFIX_ . 'simpleblog_category_lang` cl
                    ON p.`id_simpleblog_category` = cl.`id_simpleblog_category`
                    AND cl.`id_lang` = ' . $idLang . '
                WHERE p.`id_simpleblog_post` IN (' . $postIdsCsv . ')
                AND cl.`name` != ""
            ');

            foreach ((array) $rows as $r) {
                $map[(int) $r['id_simpleblog_post']][] = $r['name'];
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'SimpleBlogTranslator getPostCategories error: ' . $e->getMessage(),
                3, null, 'Module', 0
            );
        }

        return $map;
    }

    /* ================================================================== */
    /* Debug helper                                                         */
    /* ================================================================== */

    /**
     * Write a debug message to PS logs if debug mode is enabled.
     */
    private function dbg($message)
    {
        if ((bool) Configuration::get('SIMPLEBLOGTRANSLATOR_DEBUG')) {
            PrestaShopLogger::addLog(
                '[SBT DEBUG] ' . $message,
                1,
                null,
                'Module',
                0
            );
        }
    }

    /* ================================================================== */
    /* AJAX dispatcher                                                      */
    /* ================================================================== */

    private function handleAjax()
    {
        header('Content-Type: application/json; charset=utf-8');

        $action = Tools::getValue('action');

        switch ($action) {
            case 'translate':
                echo json_encode($this->processTranslation());
                break;
            case 'test_api':
                echo json_encode($this->processTestApi());
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
        }

        exit;
    }

    /* ================================================================== */
    /* Translation logic                                                    */
    /* ================================================================== */

    private function processTranslation()
    {
        $idPost = (int) Tools::getValue('id_post');
        $sourceLangId = (int) Tools::getValue('source_lang_id');
        $targetLangId = (int) Tools::getValue('target_lang_id');
        $translateContent = (bool) Tools::getValue('translate_content', false);
        $regenMeta = (bool) Tools::getValue('regen_meta', false);

        $this->dbg("processTranslation called - post=$idPost src=$sourceLangId tgt=$targetLangId regen=$regenMeta translate_content=$translateContent");

        if (!$idPost || !$sourceLangId || !$targetLangId) {
            return ['success' => false, 'message' => 'Missing parameters'];
        }

        $provider = Configuration::get('SIMPLEBLOGTRANSLATOR_PROVIDER') ?: 'openai';
        if ($provider === 'anthropic') {
            $apiKey = Configuration::get('SIMPLEBLOGTRANSLATOR_ANTHROPIC_API_KEY');
            if (!$apiKey) {
                return ['success' => false, 'message' => 'Anthropic API key not configured. Go to module settings.'];
            }
        } else {
            $apiKey = Configuration::get('SIMPLEBLOGTRANSLATOR_API_KEY');
            if (!$apiKey) {
                return ['success' => false, 'message' => 'OpenAI API key not configured. Go to module settings.'];
            }
        }

        $source = Db::getInstance()->getRow('
            SELECT * FROM `' . _DB_PREFIX_ . 'simpleblog_post_lang`
            WHERE `id_simpleblog_post` = ' . $idPost . '
            AND `id_lang` = ' . $sourceLangId
        );

        if (!$source) {
            return [
                'success' => false,
                'message' => 'No source row found for post #' . $idPost . ' in lang #' . $sourceLangId,
            ];
        }

        // Route to dedicated meta regeneration flow (allowed even when source == target lang)
        if ($regenMeta) {
            return $this->processRegenMeta($idPost, $sourceLangId, $targetLangId, $source, $apiKey);
        }

        // For normal translation, source and target must differ
        if ($sourceLangId === $targetLangId) {
            return ['success' => false, 'message' => 'Source and target language are the same'];
        }

        $defaultModel = ($provider === 'anthropic') ? 'claude-haiku-4-5-20251001' : 'gpt-4o-mini';
        $model = Configuration::get('SIMPLEBLOGTRANSLATOR_MODEL') ?: $defaultModel;
        $temperature = (float) (Configuration::get('SIMPLEBLOGTRANSLATOR_TEMPERATURE') ?: 0);
        $phrase = Configuration::get('SIMPLEBLOGTRANSLATOR_PHRASE')
            ?: 'Translate from {from_lang} to {to_lang}. Preserve HTML. Return only the translation.';

        $sourceLang = new Language($sourceLangId);
        $targetLang = new Language($targetLangId);

        $fieldsToTranslate = ['title', 'meta_title', 'meta_description', 'short_content'];
        if ($translateContent) {
            $fieldsToTranslate[] = 'content';
        }

        $translated = [];
        foreach ($fieldsToTranslate as $field) {
            $value = isset($source[$field]) ? trim((string) $source[$field]) : '';
            if ($value === '') {
                $translated[$field] = '';
                continue;
            }
            if ($provider === 'anthropic') {
                $result = $this->callAnthropic($apiKey, $model, $temperature, $phrase, $value, $sourceLang->name, $targetLang->name);
            } else {
                $result = $this->callOpenAI($apiKey, $model, $temperature, $phrase, $value, $sourceLang->name, $targetLang->name);
            }
            if ($result === false) {
                $detail = $this->lastApiError !== '' ? ': ' . $this->lastApiError : '';
                return [
                    'success' => false,
                    'message' => 'AI API error on field "' . $field . '" for post #' . $idPost . $detail,
                ];
            }
            $translated[$field] = trim($result);
        }

        $translatedTitle = isset($translated['title']) ? $translated['title'] : '';
        $translated['link_rewrite'] = !empty($translatedTitle)
            ? Tools::str2url($translatedTitle)
            : Tools::str2url(isset($source['title']) ? $source['title'] : 'post-' . $idPost);

        $row = [
            'title' => pSQL(isset($translated['title']) ? $translated['title'] : ''),
            'meta_title' => pSQL(isset($translated['meta_title']) ? $translated['meta_title'] : ''),
            'meta_description' => pSQL(
                isset($translated['meta_description']) ? $translated['meta_description'] : ''
            ),
            'short_content' => pSQL(isset($translated['short_content']) ? $translated['short_content'] : '', true),
            'link_rewrite' => pSQL($translated['link_rewrite']),
            // content: translated only if requested.
            // New records without translation get empty string.
            // Existing records: key is removed below in the UPDATE block so value is preserved.
            'content' => $translateContent
                ? pSQL(isset($translated['content']) ? $translated['content'] : '', true)
                : '',
            'meta_keywords' => pSQL(isset($source['meta_keywords']) ? $source['meta_keywords'] : ''),
            'canonical' => pSQL(isset($source['canonical']) ? $source['canonical'] : '', true),
            'video_code' => pSQL(isset($source['video_code']) ? $source['video_code'] : '', true),
            'external_url' => pSQL(isset($source['external_url']) ? $source['external_url'] : '', true),
        ];

        $exists = (bool) Db::getInstance()->getValue('
            SELECT `id_simpleblog_post`
            FROM `' . _DB_PREFIX_ . 'simpleblog_post_lang`
            WHERE `id_simpleblog_post` = ' . $idPost . '
            AND `id_lang` = ' . $targetLangId
        );

        if ($exists) {
            // When not translating content, remove it from UPDATE so existing value is preserved
            if (!$translateContent) {
                unset($row['content']);
            }
            $ok = Db::getInstance()->update(
                'simpleblog_post_lang',
                $row,
                '`id_simpleblog_post` = ' . $idPost . ' AND `id_lang` = ' . $targetLangId
            );
        } else {
            $row['id_simpleblog_post'] = $idPost;
            $row['id_lang'] = $targetLangId;
            $ok = Db::getInstance()->insert('simpleblog_post_lang', $row);
        }

        if (!$ok) {
            return ['success' => false, 'message' => 'DB error saving translation for post #' . $idPost];
        }

        return [
            'success' => true,
            'message' => 'OK',
            'data' => [
                'post_id' => $idPost,
                'target_lang_id' => $targetLangId,
                'target_lang_name' => $targetLang->name,
                'title' => $translatedTitle,
            ],
        ];
    }

    /* ================================================================== */
    /* OpenAI API call                                                      */
    /* ================================================================== */

    /**
     * @param string $apiKey
     * @param string $model
     * @param float $temperature
     * @param string $phraseTemplate
     * @param string $content
     * @param string $fromLang
     * @param string $toLang
     *
     * @return string|false
     */
    /* ================================================================== */
    /* Meta regeneration                                                    */
    /* ================================================================== */

    /**
     * Regenerate meta_title + meta_description (+ link_rewrite) for a post/lang
     * using AI, based on the source post content.
     * Works for both source language (rewrite in same lang) and target langs.
     */
    private function processRegenMeta($idPost, $sourceLangId, $targetLangId, $source, $apiKey)
    {
        $provider = Configuration::get('SIMPLEBLOGTRANSLATOR_PROVIDER') ?: 'openai';
        $defaultModel = ($provider === 'anthropic') ? 'claude-haiku-4-5-20251001' : 'gpt-4o-mini';
        $model = Configuration::get('SIMPLEBLOGTRANSLATOR_MODEL') ?: $defaultModel;
        $temperature = (float) (Configuration::get('SIMPLEBLOGTRANSLATOR_TEMPERATURE') ?: 0);
        $targetLang = new Language($targetLangId);

        // If target == source we regenerate in the source language itself
        $langName = $targetLang->name;

        // Build clean text using only title and short_content to avoid Elementor JSON noise.
        $this->dbg('processRegenMeta - post=' . $idPost . ' src=' . $sourceLangId . ' tgt=' . $targetLangId . ' lang=' . $targetLang->name);

        $title = trim(strip_tags(isset($source['title'])         ? $source['title']         : ''));
        $shortContent = trim(strip_tags(isset($source['short_content']) ? $source['short_content'] : ''));

        // Fallback: if short_content is also empty or JSON, use first 400 chars of content stripped
        if ($shortContent === '' || (isset($shortContent[0]) && in_array($shortContent[0], ['{', '[']))) {
            $rawBody = isset($source['content']) ? $source['content'] : '';
            $shortContent = mb_substr(trim(strip_tags($rawBody)), 0, 400);
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'No title available for post #' . $idPost . ' to generate meta'];
        }

        $prompt = $this->buildSeoPrompt($title, $shortContent, $langName);

        $this->dbg('Prompt built - title=' . mb_substr($title, 0, 60) . ' | shortContent=' . mb_substr($shortContent, 0, 60) . ' | lang=' . $langName);
        $this->dbg('Full prompt sent to API: ' . mb_substr($prompt, 0, 400));

        if ($provider === 'anthropic') {
            $raw = $this->callAnthropicRaw($apiKey, $model, $temperature, $prompt);
        } else {
            $raw = $this->callOpenAIRaw($apiKey, $model, $temperature, $prompt);
        }
        if ($raw === false) {
            $detail = $this->lastApiError !== '' ? ': ' . $this->lastApiError : '';
            return ['success' => false, 'message' => 'AI API error during meta regeneration for post #' . $idPost . $detail];
        }

        $this->dbg('Raw API response for post ' . $idPost . ': ' . mb_substr($raw, 0, 500));

        // Parse JSON response and strip markdown fences if present.
        $clean = trim(preg_replace('/```(?:json)?\s*|\s*```/s', '', trim($raw)));
        $this->dbg('Cleaned JSON string: ' . mb_substr($clean, 0, 300));
        $meta = json_decode($clean, true);

        $this->dbg('json_decode result: ' . print_r($meta, true));

        if (!is_array($meta) || !isset($meta['meta_title'], $meta['meta_description'])) {
            PrestaShopLogger::addLog(
                'SimpleBlogTranslator regen JSON parse error post #' . $idPost
                . ' raw: ' . substr($raw, 0, 300),
                3, null, 'Module', 0
            );
            return [
                'success' => false,
                'message' => 'AI returned unexpected format for post #' . $idPost . ': ' . substr($raw, 0, 200),
            ];
        }

        $metaTitle = mb_substr(trim((string) $meta['meta_title']), 0, 255);
        $metaDescription = mb_substr(trim((string) $meta['meta_description']), 0, 255);

        if ($metaTitle === '' || $metaDescription === '') {
            PrestaShopLogger::addLog(
                'SimpleBlogTranslator regen returned empty meta for post #' . $idPost
                . ' raw: ' . substr($raw, 0, 300),
                2, null, 'Module', 0
            );
            return ['success' => false, 'message' => 'AI returned empty meta fields for post #' . $idPost];
        }

        // Check if target lang row exists
        $exists = (bool) Db::getInstance()->getValue(
            'SELECT `id_simpleblog_post`
             FROM `' . _DB_PREFIX_ . 'simpleblog_post_lang`
             WHERE `id_simpleblog_post` = ' . $idPost . '
             AND `id_lang` = ' . $targetLangId
        );

        $row = [
            'meta_title' => pSQL($metaTitle),
            'meta_description' => pSQL($metaDescription),
        ];

        if ($exists) {
            $ok = Db::getInstance()->update(
                'simpleblog_post_lang',
                $row,
                '`id_simpleblog_post` = ' . $idPost . ' AND `id_lang` = ' . $targetLangId
            );
        } else {
            // New row copies all fields from source and overrides meta fields.
            $newRow = [
                'id_simpleblog_post' => $idPost,
                'id_lang' => $targetLangId,
                'title' => pSQL(isset($source['title'])         ? $source['title']         : ''),
                'meta_title' => pSQL($metaTitle),
                'meta_description' => pSQL($metaDescription),
                'meta_keywords' => pSQL(isset($source['meta_keywords']) ? $source['meta_keywords'] : ''),
                'canonical' => pSQL(isset($source['canonical'])     ? $source['canonical']     : '', true),
                'short_content' => pSQL(isset($source['short_content']) ? $source['short_content'] : '', true),
                'content' => '',
                'video_code' => pSQL(isset($source['video_code'])    ? $source['video_code']    : '', true),
                'external_url' => pSQL(isset($source['external_url'])  ? $source['external_url']  : '', true),
                'link_rewrite' => pSQL(isset($source['link_rewrite'])  ? $source['link_rewrite']  : ''),
            ];
            $ok = Db::getInstance()->insert('simpleblog_post_lang', $newRow);
        }

        $this->dbg('DB save result for post ' . $idPost . ' lang ' . $targetLangId . ': ' . ($ok ? 'OK' : 'FAILED'));

        if (!$ok) {
            return ['success' => false, 'message' => 'DB error saving meta for post #' . $idPost];
        }

        $this->dbg('Meta saved - meta_title=' . mb_substr($metaTitle, 0, 80) . ' | meta_desc=' . mb_substr($metaDescription, 0, 80));

        return [
            'success' => true,
            'message' => 'OK',
            'data' => [
                'post_id' => $idPost,
                'target_lang_id' => $targetLangId,
                'target_lang_name' => $targetLang->name,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
            ],
        ];
    }

    /**
     * Build the SEO meta generation system prompt.
     */
    private function buildSeoPrompt($title, $shortContent, $langName)
    {
        $articleText = 'Title: ' . $title;
        if ($shortContent !== '') {
            $articleText .= "\nSummary: " . $shortContent;
        }

        return 'You are an expert SEO copywriter. '
            . 'Based on the article below, write an SEO-optimised meta_title and meta_description in ' . $langName . '. '
            . 'Rules: '
            . 'meta_title: 50-60 characters, include the main keyword, no brand suffix. '
            . 'meta_description: 140-160 characters, summarise the value, end with a call to action. '
            . 'Respond with ONLY a JSON object with keys "meta_title" and "meta_description". '
            . 'No markdown, no explanation, no code fences. '
            . "\n\nArticle:\n" . $articleText;
    }

    /**
     * Direct OpenAI call without a system/user split.
     *
     * @return string|false
     */
    private function callOpenAIRaw($apiKey, $model, $temperature, $userPrompt)
    {
        $payloadArr = [
            'model' => $model,
            'max_completion_tokens' => 300,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        if ($this->openAiModelSupportsTemperature($model)) {
            $payloadArr['temperature'] = $temperature;
        }

        $payload = json_encode($payloadArr);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || !$response) {
            $this->lastApiError = 'cURL error: ' . $curlError;
            PrestaShopLogger::addLog('SimpleBlogTranslator cURL error (regen): ' . $curlError, 3, null, 'Module', 0);
            return false;
        }
        $this->dbg('callOpenAIRaw HTTP status: ' . $httpCode . ' | response length: ' . strlen((string) $response));
        if ($httpCode !== 200) {
            $decoded = json_decode($response, true);
            $err = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'HTTP ' . $httpCode;
            $this->lastApiError = $err;
            PrestaShopLogger::addLog('SimpleBlogTranslator API error (regen): ' . $err, 3, null, 'Module', 0);
            $this->dbg('API error body: ' . mb_substr((string) $response, 0, 400));
            return false;
        }

        $decoded = json_decode($response, true);
        return isset($decoded['choices'][0]['message']['content'])
            ? $decoded['choices'][0]['message']['content']
            : false;
    }

    /* ================================================================== */
    /* API connection test                                                  */
    /* ================================================================== */

    private function processTestApi()
    {
        $provider = Configuration::get('SIMPLEBLOGTRANSLATOR_PROVIDER') ?: 'openai';

        if ($provider === 'anthropic') {
            $apiKey = Configuration::get('SIMPLEBLOGTRANSLATOR_ANTHROPIC_API_KEY');
            if (!$apiKey) {
                return ['success' => false, 'message' => 'Anthropic API key not configured.'];
            }
        } else {
            $apiKey = Configuration::get('SIMPLEBLOGTRANSLATOR_API_KEY');
            if (!$apiKey) {
                return ['success' => false, 'message' => 'OpenAI API key not configured.'];
            }
        }

        $defaultModel = ($provider === 'anthropic') ? 'claude-haiku-4-5-20251001' : 'gpt-4o-mini';
        $model = Configuration::get('SIMPLEBLOGTRANSLATOR_MODEL') ?: $defaultModel;

        if ($provider === 'anthropic') {
            return $this->testAnthropic($apiKey, $model, $provider);
        }

        return $this->testOpenAI($apiKey, $model, $provider);
    }

    private function testOpenAI($apiKey, $model, $provider = 'openai')
    {
        $prefix = '[Provider: ' . $provider . ' | Model: ' . $model . '] ';
        // Use GET /v1/models - no tokens consumed
        $ch = curl_init('https://api.openai.com/v1/models');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || !$response) {
            return ['success' => false, 'message' => $prefix . 'Connection error: ' . $curlError];
        }

        $decoded = json_decode($response, true);

        if ($httpCode !== 200) {
            $errMsg = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'HTTP ' . $httpCode;
            return ['success' => false, 'message' => $prefix . 'HTTP ' . $httpCode . ' - ' . $errMsg];
        }

        $available = [];
        if (!empty($decoded['data']) && is_array($decoded['data'])) {
            foreach ($decoded['data'] as $m) {
                if (isset($m['id']) && strpos($m['id'], 'gpt') !== false) {
                    $available[] = $m['id'];
                }
            }
            sort($available);
        }

        $configuredOk = in_array($model, $available);
        $availableStr = implode(', ', $available);

        if (!$configuredOk) {
            return [
                'success' => false,
                'message' => $prefix . 'API key valid but this model is not in your OpenAI account. '
                    . 'Select one of the available models and save before translating. '
                    . 'Available: ' . ($availableStr ?: '(none)'),
            ];
        }

        $debugSuffix = (bool) Configuration::get('SIMPLEBLOGTRANSLATOR_DEBUG')
            ? ' Available: ' . ($availableStr ?: '(none)')
            : '';

        return [
            'success' => true,
            'message' => $prefix . 'OpenAI connection OK.' . $debugSuffix,
        ];
    }

    private function testAnthropic($apiKey, $model, $provider = 'anthropic')
    {
        $prefix = '[Provider: ' . $provider . ' | Model: ' . $model . '] ';
        // Use GET /v1/models - no tokens consumed, returns the list of accessible models
        $ch = curl_init('https://api.anthropic.com/v1/models?limit=20');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || !$response) {
            return ['success' => false, 'message' => $prefix . 'Connection error: ' . $curlError];
        }

        $decoded = json_decode($response, true);

        if ($httpCode !== 200) {
            $errMsg = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'HTTP ' . $httpCode;
            return ['success' => false, 'message' => $prefix . 'HTTP ' . $httpCode . ' - ' . $errMsg];
        }

        // Extract model IDs available to this key
        $available = [];
        if (!empty($decoded['data']) && is_array($decoded['data'])) {
            foreach ($decoded['data'] as $m) {
                if (isset($m['id'])) {
                    $available[] = $m['id'];
                }
            }
        }

        $configuredOk = in_array($model, $available);
        $availableStr = implode(', ', $available);

        if (empty($available)) {
            return [
                'success' => false,
                'message' => $prefix . 'API key valid but no models returned. '
                    . 'Ensure billing is set up at console.anthropic.com.',
            ];
        }

        if (!$configuredOk) {
            return [
                'success' => false,
                'message' => $prefix . 'API key valid but this model is not accessible. '
                    . 'Select one of the available models and save before translating. '
                    . 'Available: ' . $availableStr,
            ];
        }

        $debugSuffix = (bool) Configuration::get('SIMPLEBLOGTRANSLATOR_DEBUG')
            ? ' Available: ' . ($availableStr ?: '(none)')
            : '';

        return [
            'success' => true,
            'message' => $prefix . 'Anthropic connection OK.' . $debugSuffix,
        ];
    }

    /* ================================================================== */
    /* Anthropic API calls                                                  */
    /* ================================================================== */

    /**
     * @param string $apiKey
     * @param string $model
     * @param float  $temperature
     * @param string $phraseTemplate
     * @param string $content
     * @param string $fromLang
     * @param string $toLang
     *
     * @return string|false
     */
    private function callAnthropic($apiKey, $model, $temperature, $phraseTemplate, $content, $fromLang, $toLang)
    {
        $systemPrompt = str_replace(
            ['[from_lang]', '[to_lang]'],
            [$fromLang, $toLang],
            $phraseTemplate
        );

        $payload = json_encode([
            'model' => $model,
            'max_tokens' => 4096,
            'temperature' => min((float) $temperature, 1.0),
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $content],
            ],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 180,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $this->dbg('callAnthropic HTTP status: ' . $httpCode . ' | response length: ' . strlen((string) $response));

        if ($curlError || !$response) {
            $this->lastApiError = 'cURL error: ' . $curlError;
            PrestaShopLogger::addLog('SimpleBlogTranslator cURL error (Anthropic): ' . $curlError, 3, null, 'Module', 0);

            return false;
        }

        if ($httpCode !== 200) {
            $decoded = json_decode($response, true);
            $errMsg = isset($decoded['error']['message']) ? $decoded['error']['message'] : ('HTTP ' . $httpCode);
            $this->lastApiError = $errMsg;
            PrestaShopLogger::addLog('SimpleBlogTranslator Anthropic API error: ' . $errMsg, 3, null, 'Module', 0);
            $this->dbg('Anthropic API error body: ' . mb_substr((string) $response, 0, 400));

            return false;
        }

        $decoded = json_decode($response, true);

        return isset($decoded['content'][0]['text']) ? $decoded['content'][0]['text'] : false;
    }

    /**
     * Direct Anthropic call without system/user split - used for structured generation (meta regen).
     *
     * @return string|false
     */
    private function callAnthropicRaw($apiKey, $model, $temperature, $userPrompt)
    {
        $payload = json_encode([
            'model' => $model,
            'max_tokens' => 300,
            'temperature' => min((float) $temperature, 1.0),
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $this->dbg('callAnthropicRaw HTTP status: ' . $httpCode . ' | response length: ' . strlen((string) $response));

        if ($curlError || !$response) {
            $this->lastApiError = 'cURL error: ' . $curlError;
            PrestaShopLogger::addLog('SimpleBlogTranslator cURL error (Anthropic regen): ' . $curlError, 3, null, 'Module', 0);

            return false;
        }

        if ($httpCode !== 200) {
            $decoded = json_decode($response, true);
            $err = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'HTTP ' . $httpCode;
            $this->lastApiError = $err;
            PrestaShopLogger::addLog('SimpleBlogTranslator Anthropic API error (regen): ' . $err, 3, null, 'Module', 0);
            $this->dbg('Anthropic API error body: ' . mb_substr((string) $response, 0, 400));

            return false;
        }

        $decoded = json_decode($response, true);

        return isset($decoded['content'][0]['text']) ? $decoded['content'][0]['text'] : false;
    }

    /* ================================================================== */
    /* OpenAI API calls                                                     */
    /* ================================================================== */

    private function callOpenAI($apiKey, $model, $temperature, $phraseTemplate, $content, $fromLang, $toLang)
    {
        $systemPrompt = str_replace(
            ['[from_lang]', '[to_lang]'],
            [$fromLang, $toLang],
            $phraseTemplate
        );

        $payloadArr = [
            'model' => $model,
            'max_completion_tokens' => 4096,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $content],
            ],
        ];

        if ($this->openAiModelSupportsTemperature($model)) {
            $payloadArr['temperature'] = $temperature;
        }

        $payload = json_encode($payloadArr);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 180,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || !$response) {
            $this->lastApiError = 'cURL error: ' . $curlError;
            PrestaShopLogger::addLog('SimpleBlogTranslator cURL error: ' . $curlError, 3, null, 'Module', 0);

            return false;
        }

        if ($httpCode !== 200) {
            $decoded = json_decode($response, true);
            $errMsg = isset($decoded['error']['message']) ? $decoded['error']['message'] : ('HTTP ' . $httpCode);
            $this->lastApiError = $errMsg;
            PrestaShopLogger::addLog('SimpleBlogTranslator API error: ' . $errMsg, 3, null, 'Module', 0);

            return false;
        }

        $decoded = json_decode($response, true);

        return isset($decoded['choices'][0]['message']['content'])
            ? $decoded['choices'][0]['message']['content']
            : false;
    }

    private function openAiModelSupportsTemperature($model)
    {
        $noTempPrefixes = ['o1', 'o3', 'o4', 'gpt-5.5'];

        foreach ($noTempPrefixes as $prefix) {
            if (strpos($model, $prefix) === 0) {
                return false;
            }
        }

        return true;
    }
}
