<?php
/*
 * Wildcard Helper Classes
 * ACP - HTML Generator
 *
 * produces standard or encoded URLs, HTML anchors and images
 */

class HTMLGenerator
{
	/*
	 * default URL for links, can be set in __construct() by the plugin ACP
	 * page but can be changed in-line if needed
	 */
	public $base_url = 'index.php';

	/*
	 * allowed $_GET/$mybb->input variable names, add custom keys in
	 * __construct() or in-line
	 */
	public $allowed_url_keys = array(
		'module',
		'action',
		'mode',
		'id',
		'uid',
		'tid',
		'page',
		'my_post_key'
	);

	public $allowed_img_properties = array(
		'id',
		'name',
		'title',
		'alt',
		'style',
		'class',
		'onclick'
	);

	public $allowed_link_properties = array(
		'id',
		'name',
		'title',
		'style',
		'class',
		'onclick'
	);

	/*
	 * __construct()
	 *
	 * @param - $url - (string) - the base URL for all links and URLs
	 * @param - $extra_keys - (mixed) - a string key name or an array of key names to allow
	 *
	 * @return: n/a
	 */
	public function __construct($url = '', $extra_keys = '')
	{
		// custom base URL?
		if(trim($url))
		{
			$this->base_url = trim($url);
		}

		// custom keys?
		if($extra_keys)
		{
			if(!is_array($extra_keys))
			{
				$extra_keys = array($extra_keys);
			}
			foreach($extra_keys as $key)
			{
				$key = trim($key);
				if($key && !in_array($key, $this->allowed_url_keys))
				{
					$this->allowed_url_keys[] = $key;
				}
			}
		}
	}

	/*
	 * url()
	 *
	 * builds a URL from standard options array
	 *
	 * @param - $options - (array) keyed to standard URL options
	 * @param - $base_url - (string) overrides the default URL base if present
	 * @param - $encoded - (boolean) override URL encoded ampersand (for JS mostly)
	 * @return: (string) URL
	 */
	public function url($options = array(), $base_url = '', $encoded = true)
	{
		if($base_url && trim($base_url))
		{
			$url = $base_url;
		}
		else
		{
			$url = $this->base_url;
		}

		$amp = '&';
		if($encoded)
		{
			$amp = '&amp;';
		}
		$sep = $amp;
		if(strpos($url, '?') === false)
		{
			$sep = '?';
		}

		// check for the allowed options
		foreach((array) $this->allowed_url_keys as $item)
		{
			if(isset($options[$item]) && $options[$item])
			{
				// and add them if set
				$url .= "{$sep}{$item}={$options[$item]}";
				$sep = $amp;
			}
		}
		return $url;
	}

	/*
	 * link()
	 *
	 * builds an HTML anchor from the provided options
	 *
	 * @param - $url - (string) the address
	 * @param - $title - (string) the title of the link
	 * @param - $options - (array) options to effect the HTML output
	 * @return: (string) HTML anchor
	 */
	public function link($url = '', $caption = '', $options = '', $icon_options = array())
	{
		$properties = $this->build_property_list($options, $this->allowed_link_properties);

		if(isset($options['icon']))
		{
			$icon_img = $this->img($options['icon'], $icon_options);
			$icon_link = <<<EOF
<a href="{$url}">{$icon_img}</a>&nbsp;
EOF;
		}

		if(!$url)
		{
			$url = $this->url();
		}
		if(!isset($caption) || !$caption)
		{
			$caption = $url;
		}

		return <<<EOF
{$icon_link}<a href="{$url}"{$properties}>{$caption}</a>
EOF;
	}

	/*
	 * img()
	 *
	 * generate HTML <img> mark-up
	 *
	 * @param - $url - (string) image source attribute
	 * @param - $options - (array) a keyed array of options to be generated
	 * @return: (string) HTML image
	 */
	public function img($url, $options = array())
	{
		$properties = $this->build_property_list($options, $this->allowed_img_properties);

		return <<<EOF
<img src="{$url}"{$properties}/>
EOF;
	}

	/*
	 * build_property_list()
	 *
	 * @param - $options - (array) keyed array of properties
	 * @param - $allowed - (array) unindexed array of allowable property names
	 * @return: (string) a list of properties
	 */
	protected function build_property_list($options = array(), $allowed = array())
	{
		if(!is_array($options) || !is_array($allowed))
		{
			return false;
		}

		foreach($allowed as $key)
		{
			if(isset($options[$key]) && $options[$key])
			{
				$property_list .= <<<EOF
 {$key}="{$options[$key]}"
EOF;
			}
		}
		return $property_list;
	}
}

?>
