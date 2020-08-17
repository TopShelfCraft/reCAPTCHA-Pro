<?php
namespace topshelfcraft\recaptchapro\components;

use Craft;
use craft\base\Component;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\web\View;
use topshelfcraft\recaptchapro\RecaptchaPro;

class Recaptcha3 extends Component
{

	public function render($options = [])
	{

		$settings = RecaptchaPro::getInstance()->getSettings();

		$options = array_merge($settings->defaultOptions, $options);

		if ($options['registerJs'] ?? true)
		{
			Craft::$app->view->registerJsFile(
				UrlHelper::url('https://www.google.com/recaptcha/api.js'),
				['render' => $settings->siteKey]
			);
		}

	}

}
