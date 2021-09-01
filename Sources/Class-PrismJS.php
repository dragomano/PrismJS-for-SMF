<?php

/**
 * Class-PrismJS.php
 *
 * @package PrismJS for SMF
 * @link https://github.com/dragomano/prismjs-for-smf
 * @author Bugo https://dragomano.ru/mods/prismjs-for-smf
 * @copyright 2021 Bugo
 * @license https://opensource.org/licenses/MIT MIT
 *
 * @version 0.2
 */

if (!defined('SMF'))
	die('Hacking attempt...');

class PrismJS
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

	/**
	 * @return void
	 */
	public function hooks()
	{
		add_integration_function('integrate_load_theme', __CLASS__ . '::loadTheme#', false, __FILE__);
		add_integration_function('integrate_bbc_codes', __CLASS__ . '::bbcCodes#', false, __FILE__);
		add_integration_function('integrate_post_parsebbc', __CLASS__ . '::postParseBbc#', false, __FILE__);
		add_integration_function('integrate_admin_areas', __CLASS__ . '::adminAreas#', false, __FILE__);
		add_integration_function('integrate_admin_search', __CLASS__ . '::adminSearch#', false, __FILE__);
		add_integration_function('integrate_modify_modifications', __CLASS__ . '::modifyModifications#', false, __FILE__);
		add_integration_function('integrate_credits', __CLASS__ . '::credits#', false, __FILE__);
	}

	/**
	 * @return void
	 */
	public function loadTheme()
	{
		global $modSettings, $context;

		loadLanguage('PrismJS/');

		if (empty($modSettings['prism_js_enable']) || in_array($context['current_action'], array('helpadmin', 'printpage')))
			return;

		loadCssFile(
			'https://cdn.jsdelivr.net/npm/prismjs@1/themes/prism' . (!empty($modSettings['prism_js_style']) ? '-' . $modSettings['prism_js_style'] : '') . '.css',
			array(
				'external' => true
			)
		);
		loadCssFile('prismjs.css');
		loadJavaScriptFile('https://cdn.jsdelivr.net/npm/prismjs@1/components/prism-core.min.js', array('external' => true));
		loadJavaScriptFile('https://cdn.jsdelivr.net/npm/prismjs@1/plugins/autoloader/prism-autoloader.min.js', array('external' => true));

		if (!empty($modSettings['prism_js_line_numbers'])) {
			loadCssFile('https://cdn.jsdelivr.net/npm/prismjs@1/plugins/line-numbers/prism-line-numbers.css', array('external' => true));
			loadJavaScriptFile('https://cdn.jsdelivr.net/npm/prismjs@1/plugins/line-numbers/prism-line-numbers.min.js', array('external' => true));
		}

		if (!empty($modSettings['prism_js_copy_btn'])) {
			loadCssFile('https://cdn.jsdelivr.net/npm/prismjs@1/plugins/toolbar/prism-toolbar.css', array('external' => true));
			loadJavaScriptFile('https://cdn.jsdelivr.net/npm/prismjs@1/plugins/toolbar/prism-toolbar.min.js', array('external' => true));
			loadJavaScriptFile('https://cdn.jsdelivr.net/npm/prismjs@1/plugins/copy-to-clipboard/prism-copy-to-clipboard.min.js', array('external' => true));
		}
	}

	/**
	 * @param array $codes
	 * @return void
	 */
	public function bbcCodes(&$codes)
	{
		global $modSettings, $txt;

		if (SMF === 'BACKGROUND' || empty($modSettings['prism_js_enable']))
			return;

		$disabled = [];
		if (!empty($modSettings['disabledBBC'])) {
			foreach (explode(',', $modSettings['disabledBBC']) as $tag)
				$disabled[$tag] = true;
		}

		if (isset($disabled['code']))
			return;

		foreach ($codes as $tag => $dump) {
			if ($dump['tag'] == 'code')
				unset($codes[$tag]);
		}

		if (!empty($txt['prism_js_op_copy']) && !empty($txt['prism_js_op_copy_alt']) && !empty($txt['prism_js_op_copied'])) {
			$labels = ' data-prismjs-copy="' . $txt['prism_js_op_copy'] . '" data-prismjs-copy-error="' . $txt['prism_js_op_copy_alt'] . '" data-prismjs-copy-success="' . $txt['prism_js_op_copied'] . '"';
		}

		if (!empty($modSettings['prism_js_fontsize'])) {
			$fontSize = ' style="font-size: ' . $modSettings['prism_js_fontsize'] . '"';
		}

		if (!empty($modSettings['prism_js_line_numbers'])) {
			$lineNumbers = ' class="line-numbers"';
		}

		if (!empty($modSettings['prism_js_default_lang'])) {
			$defaultLang = $modSettings['prism_js_default_lang'];
		}

		$codes[] = 	array(
			'tag' => 'code',
			'type' => 'unparsed_content',
			'content' => '<figure' . ($labels ?? '') . ' class="block_code"' . ($fontSize ?? '') . '><pre' . ($lineNumbers ?? '') . '><code class="language-' . ($defaultLang ?? 'php') . '">$1</code></pre></figure>',
			'block_level' => true
		);

		$codes[] = array(
			'tag' => 'code',
			'type' => 'unparsed_equals_content',
			'content' => '<figure' . ($labels ?? '') . ' class="block_code"' . ($fontSize ?? '') . '><figcaption class="codeheader">' . $txt['code'] . ': $2</figcaption><pre' . ($lineNumbers ?? '') . '><code class="language-$2">$1</code></pre></figure>',
			'block_level' => true
		);
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public function postParseBbc(&$message)
	{
		global $modSettings;

		if (empty($modSettings['prism_js_enable']) || strpos($message, '<pre') === false)
			return;

		$message = preg_replace_callback('~<pre(.*?)>(.*?)<\/pre>~si', function ($matches) {
			return str_replace('<br>', "\n", $matches[0]);
		}, $message);
	}

	/**
	 * @param array $admin_areas
	 * @return void
	 */
	public function adminAreas(&$admin_areas)
	{
		global $txt;

		$admin_areas['config']['areas']['modsettings']['subsections']['prismjs'] = array($txt['prism_js_title']);
	}

	/**
	 * @param array $language_files
	 * @param array $include_files
	 * @param array $settings_search
	 * @return void
	 */
	public function adminSearch(&$language_files, &$include_files, &$settings_search)
	{
		$settings_search[] = array(array($this, 'settings'), 'area=modsettings;sa=prismjs');
	}

	/**
	 * @param array $subActions
	 * @return void
	 */
	public function modifyModifications(&$subActions)
	{
		$subActions['prismjs'] = array($this, 'settings');
	}

	/**
	 * @return array|void
	 */
	public function settings($return_config = false)
	{
		global $context, $txt, $scripturl, $modSettings, $settings;

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

	/**
	 * @return void
	 */
	public function credits()
	{
		global $modSettings, $context;

		if (empty($modSettings['prism_js_enable']))
			return;

		$link = $context['user']['language'] == 'russian' ? 'https://dragomano.ru/mods/prismjs-for-smf' : 'https://github.com/dragomano/PrismJS-for-SMF';

		$context['credits_modifications'][] = '<a href="' . $link . '" target="_blank" rel="noopener">PrismJS for SMF</a> &copy; 2021, Bugo';
	}
}

/**
 * @return void
 */
function template_callback_prism_js_example()
{
	global $settings, $txt;

	if (file_exists($settings['default_theme_dir'] . '/css/admin.css'))	{
		$file = file_get_contents($settings['default_theme_dir'] . '/css/admin.css');
		$file = parse_bbc('[code=css]' . $file . '[/code]');

		echo '</dl><strong>' . $txt['prism_js_example'] . '</strong>' . $file . '<dl><dt></dt><dd></dd>';
	}
}
