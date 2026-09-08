<?php
/**
 * SimpleBlog Translator
 *
 * @author    Tecnoacquisti.com
 * @copyright 2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @version   1.1.4
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class SimpleBlogTranslator extends Module
{
    const CFG = 'SIMPLEBLOGTRANSLATOR_';

    public function __construct()
    {
        $this->name = 'simpleblogtranslator';
        $this->tab = 'administration';
        $this->version = '1.1.4';
        $this->author = 'Tecnoacquisti.com';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('SimpleBlog Translator');
        $this->description = $this->l('Translate PrestaHome Blog articles using OpenAI or Anthropic AI API');
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => _PS_VERSION_];
    }

    /* ------------------------------------------------------------------ */
    /* Install / Uninstall                                                  */
    /* ------------------------------------------------------------------ */

    public function install()
    {
        return parent::install()
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->installTab()
            && $this->setDefaults();
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallTab();
    }

    private function setDefaults()
    {
        Configuration::updateValue(self::CFG . 'PROVIDER', 'openai');
        Configuration::updateValue(self::CFG . 'MODEL', 'gpt-5.4-mini');
        Configuration::updateValue(self::CFG . 'ANTHROPIC_API_KEY', '');
        Configuration::updateValue(self::CFG . 'DEBUG', '0');
        Configuration::updateValue(
            self::CFG . 'PHRASE',
            'Translate this content from [from_lang] to [to_lang]. '
            . 'Preserve the HTML structure exactly and do not add or remove any content. '
            . 'Return only the translated content without any explanation or preamble.'
        );

        return true;
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = true;
        $tab->class_name = 'AdminSimpleBlogTranslator';
        $tab->module = $this->name;

        $tab->id_parent = $this->findBlogParentTabId();

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Blog Translator';
        }

        return (bool) $tab->add();
    }

    private function uninstallTab()
    {
        $idTab = (int) Db::getInstance()->getValue(
            'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "AdminSimpleBlogTranslator"'
        );
        if ($idTab) {
            $tab = new Tab($idTab);

            return (bool) $tab->delete();
        }

        return true;
    }

    /**
     * Find the parent tab ID for "Blog for PrestaShop" (simpleblog module).
     * Falls back to AdminCatalog, then -1.
     *
     * @return int
     */
    private function findBlogParentTabId()
    {
        $db = Db::getInstance();

        // Use "Improve > Modules" - a native PS tab present in all versions 1.7.x to 9.x
        // class_name is AdminParentModulesSf on 1.7+ (falls back to AdminModules on older builds)
        foreach (['AdminParentModulesSf', 'AdminModules'] as $className) {
            $id = (int) $db->getValue(
                'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "' . pSQL($className) . '"'
            );
            if ($id) {
                return $id;
            }
        }

        return -1;
    }

    /**
     * Move the translator tab under Blog for PrestaShop if it is not already there.
     * Called automatically on each config page save so a reinstall is not needed.
     */
    private function fixTabParent()
    {
        $idTab = (int) Db::getInstance()->getValue(
            'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "AdminSimpleBlogTranslator"'
        );
        if (!$idTab) {
            return;
        }

        $correctParent = $this->findBlogParentTabId();
        $currentParent = (int) Db::getInstance()->getValue(
            'SELECT `id_parent` FROM `' . _DB_PREFIX_ . 'tab` WHERE `id_tab` = ' . $idTab
        );

        if ($currentParent !== $correctParent) {
            Db::getInstance()->update(
                'tab',
                ['id_parent' => $correctParent],
                '`id_tab` = ' . $idTab
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /* Module configuration page                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Hook called on every BO page load - keeps the tab under Blog for PrestaShop
     * even if this module was installed before simpleblog.
     */
    public function hookActionAdminControllerSetMedia()
    {
        $this->fixTabParent();

        if ((string) Tools::getValue('configure') === $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/simpleblogtranslator.css');
            $this->context->controller->addCSS($this->_path . 'views/css/configure.css');
        }
    }

    public function getContent()
    {
        // Auto-fix tab position in case module was installed before simpleblog
        $this->fixTabParent();

        $output = '';

        if (Tools::isSubmit('submit_sbt_config')) {
            $output .= $this->processConfigForm();
        }

        $translatorUrl = $this->context->link->getAdminLink('AdminSimpleBlogTranslator');
        $savedKey = (string) Configuration::get(self::CFG . 'API_KEY');
        $savedAnthropicKey = (string) Configuration::get(self::CFG . 'ANTHROPIC_API_KEY');
        $useSsl = (bool) Configuration::get('PS_SSL_ENABLED_EVERYWHERE') || (bool) Configuration::get('PS_SSL_ENABLED');

        $this->context->smarty->assign([
            'sbt_translator_url' => $translatorUrl,
            'sbt_has_api_key' => $savedKey !== '',
            'sbt_has_anthropic_key' => $savedAnthropicKey !== '',
            'sbt_ajax_url' => $this->context->link->getAdminLink('AdminSimpleBlogTranslator', false),
            'sbt_ajax_token' => Tools::getAdminTokenLite('AdminSimpleBlogTranslator'),
            'shop_base_url' => $this->context->link->getBaseLink((int) $this->context->shop->id, $useSsl),
            'sbt_module_display_name' => $this->displayName,
            'sbt_module_description' => $this->description,
            'sbt_module_version' => $this->version,
            'sbt_module_logo_url' => $this->_path . 'logo.png',
            'sbt_readme_url' => $this->_path . 'documentation/README.html',
            'sbt_changelog_url' => $this->_path . 'documentation/CHANGELOG.html',
        ]);

        $headerPath = _PS_MODULE_DIR_ . $this->name . '/views/templates/admin/configure_header.tpl';
        $tplPath = _PS_MODULE_DIR_ . $this->name . '/views/templates/admin/configure.tpl';
        $header = $this->context->smarty->fetch($headerPath);
        $btnHtml = $this->context->smarty->fetch($tplPath);
        $copyright = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/copyright.tpl');

        return $header . $output . $this->renderConfigForm() . $btnHtml . $copyright;
    }

    private function processConfigForm()
    {
        $errors = [];

        $provider = Tools::getValue(self::CFG . 'PROVIDER', 'openai');
        $apiKey = trim(Tools::getValue(self::CFG . 'API_KEY', ''));
        $anthropicKey = trim(Tools::getValue(self::CFG . 'ANTHROPIC_API_KEY', ''));
        $model = Tools::getValue(self::CFG . 'MODEL', 'gpt-5.4-mini');
        $phrase = trim(Tools::getValue(self::CFG . 'PHRASE', ''));

        // If a key field was left blank or masked, keep the existing saved key
        if ($apiKey === '' || strpos($apiKey, '•') !== false) {
            $apiKey = (string) Configuration::get(self::CFG . 'API_KEY');
        }
        if ($anthropicKey === '' || strpos($anthropicKey, '•') !== false) {
            $anthropicKey = (string) Configuration::get(self::CFG . 'ANTHROPIC_API_KEY');
        }

        if ($provider === 'anthropic' && empty($anthropicKey)) {
            $errors[] = $this->l('Anthropic API Key is required when Anthropic is selected as provider.');
        } elseif ($provider !== 'anthropic' && empty($apiKey)) {
            $errors[] = $this->l('OpenAI API Key is required when OpenAI is selected as provider.');
        }
        if (!in_array($provider, ['openai', 'anthropic'])) {
            $errors[] = $this->l('Invalid provider selected.');
        }
        if (!$this->isModelAvailableForProvider($model, $provider)) {
            $errors[] = $this->l('Invalid model selected for the selected provider.');
        }
        if (strpos($phrase, '[from_lang]') === false || strpos($phrase, '[to_lang]') === false) {
            $errors[] = $this->l('The translation phrase must contain [from_lang] and [to_lang] placeholders.');
        }

        if (!empty($errors)) {
            return $this->displayError(implode('<br>', $errors));
        }

        Configuration::updateValue(self::CFG . 'PROVIDER', $provider);
        Configuration::updateValue(self::CFG . 'API_KEY', $apiKey);
        Configuration::updateValue(self::CFG . 'ANTHROPIC_API_KEY', $anthropicKey);
        Configuration::updateValue(self::CFG . 'MODEL', $model);
        Configuration::updateValue(self::CFG . 'PHRASE', $phrase);
        Configuration::updateValue(self::CFG . 'DEBUG', Tools::getValue(self::CFG . 'DEBUG', 0) ? '1' : '0');

        return $this->displayConfirmation($this->l('Settings saved successfully.'));
    }

    private function renderConfigForm()
    {
        $providers = [
            ['id' => 'openai',    'name' => 'OpenAI'],
            ['id' => 'anthropic', 'name' => 'Anthropic'],
        ];

        $models = [
            ['id' => 'gpt-6-astra',              'name' => '[OpenAI] GPT-6 Astra'],
            ['id' => 'gpt-5.6-luna',               'name' => '[OpenAI] GPT-5.6 Luna'],
            ['id' => 'gpt-5.6-sol',                'name' => '[OpenAI] GPT-5.6 Sol'],
            ['id' => 'gpt-5.6-terra',              'name' => '[OpenAI] GPT-5.6 Terra'],
            ['id' => 'gpt-5.5',                    'name' => '[OpenAI] GPT-5.5'],
            ['id' => 'gpt-5.5-pro',                'name' => '[OpenAI] GPT-5.5 Pro'],
            ['id' => 'gpt-5.4',                    'name' => '[OpenAI] GPT-5.4'],
            ['id' => 'gpt-5.4-mini',               'name' => '[OpenAI] GPT-5.4 mini'],
            ['id' => 'claude-sonnet-5',            'name' => '[Anthropic] Claude Sonnet 5'],
            ['id' => 'claude-fable-5',             'name' => '[Anthropic] Claude Fable 5'],
            ['id' => 'claude-opus-5',              'name' => '[Anthropic] Claude Opus 5'],
            ['id' => 'claude-opus-4-8',            'name' => '[Anthropic] Claude Opus 4.8'],
            ['id' => 'claude-sonnet-4-6',          'name' => '[Anthropic] Claude Sonnet 4.6'],
            ['id' => 'claude-haiku-4-5-20251001',  'name' => '[Anthropic] Claude Haiku 4.5'],
        ];

        $savedKey = (string) Configuration::get(self::CFG . 'API_KEY');
        $maskedKey = '';
        if ($savedKey !== '') {
            $maskedKey = str_repeat('•', max(0, strlen($savedKey) - 4)) . substr($savedKey, -4);
        }

        $savedAnthropicKey = (string) Configuration::get(self::CFG . 'ANTHROPIC_API_KEY');
        $maskedAnthropicKey = '';
        if ($savedAnthropicKey !== '') {
            $maskedAnthropicKey = str_repeat('•', max(0, strlen($savedAnthropicKey) - 4)) . substr($savedAnthropicKey, -4);
        }

        $fieldsForm = [[
            'form' => [
                'legend' => ['title' => $this->l('AI API Configuration'), 'icon' => 'icon-cogs'],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('AI Provider'),
                        'name' => self::CFG . 'PROVIDER',
                        'options' => ['query' => $providers, 'id' => 'id', 'name' => 'name'],
                        'desc' => $this->l('Select which AI provider to use for translations.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('OpenAI API Key'),
                        'name' => self::CFG . 'API_KEY',
                        'desc' => $this->l(
                            'Your OpenAI secret key (starts with sk-…). '
                            . 'Shown masked. Leave unchanged to keep the existing key. '
                            . 'Paste a new key to replace it.'
                        ),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Anthropic API Key'),
                        'name' => self::CFG . 'ANTHROPIC_API_KEY',
                        'desc' => $this->l(
                            'Your Anthropic secret key (starts with sk-ant-…). '
                            . 'Shown masked. Leave unchanged to keep the existing key. '
                            . 'Paste a new key to replace it.'
                        ),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Model'),
                        'name' => self::CFG . 'MODEL',
                        'options' => ['query' => $models, 'id' => 'id', 'name' => 'name'],
                        'desc' => $this->l('Select a model matching the chosen provider. For Anthropic, start with Haiku (accessible on all tiers).'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Debug Mode'),
                        'name' => self::CFG . 'DEBUG',
                        'desc' => $this->l(
                            'Write detailed debug info to PrestaShop logs (Logger). '
                            . 'Disable in production.'
                        ),
                        'values' => [
                            ['id' => 'debug_on',  'value' => 1, 'label' => $this->l('Enabled')],
                            ['id' => 'debug_off', 'value' => 0, 'label' => $this->l('Disabled')],
                        ],
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Translation Phrase (System Prompt)'),
                        'name' => self::CFG . 'PHRASE',
                        'rows' => 4,
                        'cols' => 80,
                        'desc' => $this->l('System prompt sent to the AI. Must contain [from_lang] and [to_lang].'),
                    ],
                ],
                'submit' => ['title' => $this->l('Save'), 'class' => 'btn btn-default pull-right'],
            ],
        ]];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit_sbt_config';
        $helper->currentIndex = $this->context->link->getAdminLink(
            'AdminModules',
            false,
            [],
            ['configure' => $this->name]
        );
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $cfgPhrase = Configuration::get(self::CFG . 'PHRASE');
        $helper->fields_value = [
            self::CFG . 'PROVIDER' => Configuration::get(self::CFG . 'PROVIDER') ?: 'openai',
            self::CFG . 'API_KEY' => $maskedKey,
            self::CFG . 'ANTHROPIC_API_KEY' => $maskedAnthropicKey,
            self::CFG . 'MODEL' => Configuration::get(self::CFG . 'MODEL') ?: 'gpt-5.4-mini',
            self::CFG . 'DEBUG' => (int) Configuration::get(self::CFG . 'DEBUG'),
            self::CFG . 'PHRASE' => $cfgPhrase ?: '',
        ];

        return $helper->generateForm($fieldsForm);
    }

    /**
     * Check that a model belongs to the selected provider.
     *
     * @param string $model Model identifier submitted by the merchant
     * @param string $provider Selected AI provider
     *
     * @return bool
     */
    private function isModelAvailableForProvider($model, $provider)
    {
        $models = [
            'openai' => [
                'gpt-6-astra',
                'gpt-5.6-luna',
                'gpt-5.6-sol',
                'gpt-5.6-terra',
                'gpt-5.5',
                'gpt-5.5-pro',
                'gpt-5.4',
                'gpt-5.4-mini',
            ],
            'anthropic' => [
                'claude-sonnet-5',
                'claude-fable-5',
                'claude-opus-5',
                'claude-opus-4-8',
                'claude-sonnet-4-6',
                'claude-haiku-4-5-20251001',
            ],
        ];

        return is_string($provider)
            && isset($models[$provider])
            && in_array($model, $models[$provider], true);
    }
}
