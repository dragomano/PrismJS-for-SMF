<?php

/**
 * Class-PrismJS.php
 *
 * @package PrismJS for SMF
 * @link https://github.com/dragomano/prismjs-for-smf
 * @author Bugo https://dragomano.ru/mods/prismjs-for-smf
 * @copyright 2021-2022 Bugo
 * @license https://opensource.org/licenses/MIT MIT
 *
 * @version 0.4
 */

if (!defined('SMF'))
	die('Hacking attempt...');

final class PrismJS
{
	private const STYLE_SET = [
		''               => 'Default',
		'coy'            => 'Coy',
		'dark'           => 'Dark',
		'funky'          => 'Funky',
		'okaidia'        => 'Okaidia',
		'solarizedlight' => 'SolarizedLight',
		'tomorrow'       => 'Tomorrow',
		'twilight'       => 'Twilight'
	];

	private const FONTSIZE_SET = [
		'x-small' => 'x-small',
		'small'   => 'small',
		'medium'  => 'medium',
		'large'   => 'large',
		'x-large' => 'x-large'
	];

	public function hooks()
	{
		add_integration_function('integrate_pre_css_output', __CLASS__ . '::preCssOutput#', false, __FILE__);
		add_integration_function('integrate_load_theme', __CLASS__ . '::loadTheme#', false, __FILE__);
		add_integration_function('integrate_bbc_codes', __CLASS__ . '::bbcCodes#', false, __FILE__);
		add_integration_function('integrate_post_parsebbc', __CLASS__ . '::postParseBbc#', false, __FILE__);
		add_integration_function('integrate_admin_areas', __CLASS__ . '::adminAreas#', false, __FILE__);
		add_integration_function('integrate_admin_search', __CLASS__ . '::adminSearch#', false, __FILE__);
		add_integration_function('integrate_modify_modifications', __CLASS__ . '::modifyModifications#', false, __FILE__);
		add_integration_function('integrate_credits', __CLASS__ . '::credits#', false, __FILE__);
	}

	/**
	 * @hook integrate_pre_css_output
	 */
	public function preCssOutput()
	{
		global $modSettings;

		if (! $this->shouldItWork())
			return;

		echo "\n\t" . '<link rel="preload" href="https://cdn.jsdelivr.net/npm/prismjs@1/themes/prism' . (!empty($modSettings['prism_js_style']) ? '-' . $modSettings['prism_js_style'] : '') . '.min.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';

		if (!empty($modSettings['prism_js_line_numbers']))
			echo "\n\t" . '<link rel="preload" href="https://cdn.jsdelivr.net/npm/prismjs@1/plugins/line-numbers/prism-line-numbers.min.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';

		if (!empty($modSettings['prism_js_copy_btn']))
			echo "\n\t" . '<link rel="preload" href="https://cdn.jsdelivr.net/npm/prismjs@1/plugins/toolbar/prism-toolbar.min.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
	}

	public function loadTheme()
	{
		global $modSettings, $context;

		loadLanguage('PrismJS/');

		if (! $this->shouldItWork())
			return;

		$options = ['external' => true, 'defer' => true];

		loadCSSFile('prismjs.css');
		loadJavaScriptFile('https://cdn.jsdelivr.net/npm/prismjs@1/components/prism-core.min.js', $options);
		loadJavaScriptFile('https://cdn.jsdelivr.net/npm/prismjs@1/plugins/autoloader/prism-autoloader.min.js', $options);

		if (!empty($modSettings['prism_js_line_numbers'])) {
			loadJavaScriptFile('https://cdn.jsdelivr.net/npm/prismjs@1/plugins/line-numbers/prism-line-numbers.min.js', $options);
		}

		if (!empty($modSettings['prism_js_copy_btn'])) {
			loadJavaScriptFile('https://cdn.jsdelivr.net/npm/prismjs@1/plugins/toolbar/prism-toolbar.min.js', $options);
			loadJavaScriptFile('https://cdn.jsdelivr.net/npm/prismjs@1/plugins/copy-to-clipboard/prism-copy-to-clipboard.min.js', $options);
		}
	}

	public function bbcCodes(array &$codes)
	{
		global $txt, $modSettings;

		if (! $this->shouldItWork())
			return;

		if (!empty($txt['prism_js_op_copy']) && !empty($txt['prism_js_op_copy_alt']) && !empty($txt['prism_js_op_copied'])) {
			$labels = ' data-prismjs-copy="' . $txt['prism_js_op_copy'] . '" data-prismjs-copy-error="' . $txt['prism_js_op_copy_alt'] . '" data-prismjs-copy-success="' . $txt['prism_js_op_copied'] . '"';
		}

		if (!empty($modSettings['prism_js_fontsize'])) {
			$fontSize = ' style="font-size: ' . $modSettings['prism_js_fontsize'] . '"';
		}

		if (!empty($modSettings['prism_js_line_numbers'])) {
			$lineNumbers = ' class="line-numbers"';
		}

		$defaultLang = $modSettings['prism_js_default_lang'] ?: 'php';

		$codes = array_filter($codes, function ($code) {
			return $code['tag'] !== 'code';
		});

		$codes[] = array(
			'tag' => 'code',
			'type' => 'unparsed_content',
			'content' => '<figure' . ($labels ?? '') . ' class="block_code"' . ($fontSize ?? '') . '><figcaption class="codeheader">' . $txt['code'] . ': ' . $defaultLang . '</figcaption><pre' . ($lineNumbers ?? '') . '><code class="language-' . $defaultLang . '">$1</code></pre></figure>',
			'block_level' => true
		);

		$codes[] = array(
			'tag' => 'code',
			'type' => 'unparsed_equals_content',
			'content' => '<figure' . ($labels ?? '') . ' class="block_code"' . ($fontSize ?? '') . '><figcaption class="codeheader">' . $txt['code'] . ': $2</figcaption><pre' . ($lineNumbers ?? '') . '><code class="language-$2">$1</code></pre></figure>',
			'block_level' => true
		);
	}

	public function postParseBbc(string &$message)
	{
		if (! $this->shouldItWork() || strpos($message, '<pre') === false)
			return;

		$message = preg_replace_callback('~<pre(.*?)>(.*?)<\/pre>~si', function ($matches) {
			return str_replace('<br>', PHP_EOL, $matches[0]);
		}, $message);
	}

	public function adminAreas(array &$admin_areas)
	{
		global $txt;

		$admin_areas['config']['areas']['modsettings']['subsections']['prismjs'] = array($txt['prism_js_title']);
	}

	public function adminSearch(array &$language_files, array &$include_files, array &$settings_search)
	{
		$settings_search[] = array(array($this, 'settings'), 'area=modsettings;sa=prismjs');
	}

	public function modifyModifications(array &$subActions)
	{
		$subActions['prismjs'] = array($this, 'settings');
	}

	/**
	 * @return array|void
	 */
	public function settings($return_config = false)
	{
		global $context, $txt, $scripturl, $modSettings;

		$context['page_title']     = $txt['prism_js_title'];
		$context['settings_title'] = $txt['prism_js_settings'];
		$context['post_url']       = $scripturl . '?action=admin;area=modsettings;save;sa=prismjs';
		$context[$context['admin_menu_name']]['tab_data']['tabs']['prismjs'] = array('description' => $txt['prism_js_desc']);

		$addSettings = [];
		if (!isset($modSettings['prism_js_fontsize']))
			$addSettings['prism_js_fontsize'] = 'medium';
		if (!isset($modSettings['prism_js_default_lang']))
			$addSettings['prism_js_default_lang'] = 'php';
		if (!empty($addSettings))
			updateSettings($addSettings);

		$config_vars = array(
			array('check', 'prism_js_enable'),
			array('select', 'prism_js_style', self::STYLE_SET),
			array('select', 'prism_js_fontsize', self::FONTSIZE_SET),
			array('check', 'prism_js_line_numbers'),
			array('check', 'prism_js_copy_btn'),
			array('text', 'prism_js_default_lang'),
		);

		if (!empty($modSettings['prism_js_enable']) && function_exists('file_get_contents')) {
			$config_vars[] = array('callback', 'prism_js_example');
			$config_vars[] = '<br>';
		}

		if ($return_config)
			return $config_vars;

		// Saving?
		if (isset($_GET['save'])) {
			checkSession();

			$save_vars = $config_vars;
			saveDBSettings($save_vars);

			redirectexit('action=admin;area=modsettings;sa=prismjs');
		}

		prepareDBSettingContext($config_vars);
	}

	public function credits()
	{
		global $modSettings, $context;

		if (empty($modSettings['prism_js_enable']))
			return;

		$context['credits_modifications'][] = '<a href="https://github.com/dragomano/PrismJS-for-SMF" target="_blank" rel="noopener">PrismJS for SMF</a> &copy; 2021-2022, Bugo';
	}

	private function shouldItWork(): bool
	{
		global $modSettings, $context;

		if (SMF === 'BACKGROUND' || SMF === 'SSI' || empty($modSettings['enableBBC']) || empty($modSettings['prism_js_enable']))
			return false;

		if (in_array($context['current_action'], array('helpadmin', 'printpage')) || $context['current_subaction'] === 'showoperations')
			return false;

		return empty($modSettings['disabledBBC']) || !in_array('code', explode(',', $modSettings['disabledBBC']));
	}
}

function template_callback_prism_js_example()
{
	global $settings, $txt;

	if (file_exists($settings['default_theme_dir'] . '/css/admin.css'))	{
		$file = file_get_contents($settings['default_theme_dir'] . '/css/admin.css');
		$file = parse_bbc('[code=css]' . $file . '[/code]');

		echo '</dl><strong>' . $txt['prism_js_example'] . '</strong>' . $file . '<dl><dt></dt><dd></dd>';
	}
}
