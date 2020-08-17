<?php
namespace topshelfcraft\recaptchapro\components;

use Craft;
use craft\base\Component;
use craft\helpers\Template;
use craft\web\View;
use topshelfcraft\recaptchapro\RecaptchaPro;

class Recaptcha2 extends Component
{

	public function render($options = [])
	{

		$settings = RecaptchaPro::getInstance()->getSettings();

		$options = array_merge($settings->defaultOptions, $options);

		if ($options['registerJs'] ?? true)
		{
			Craft::$app->view->registerJsFile('https://www.google.com/recaptcha/api.js');
		}

		$vars = [
			'id' => 'gRecaptchaContainer',
			'options' => array_merge(['siteKey' => $settings->siteKey], $options)
		];

		$html = Craft::$app->view->renderTemplate(
			'recaptcha-pro/_v2',
			$vars,
			View::TEMPLATE_MODE_CP
		);

		return Template::raw($html);

	}

}
