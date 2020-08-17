<?php
namespace topshelfcraft\recaptchapro;

use Craft;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\web\Application as WebApplication;
use craft\web\Controller;
use craft\web\Request;
use craft\web\twig\variables\CraftVariable;
use ReCaptcha\ReCaptcha;
use topshelfcraft\recaptchapro\components\Recaptcha2;
use topshelfcraft\recaptchapro\components\Recaptcha3;
use topshelfcraft\recaptchapro\config\Settings;
use yii\base\ActionEvent;
use yii\base\Event;
use yii\web\BadRequestHttpException;

/**
 * Module to encapsulate reCAPTCHA Pro functionality.
 *
 * This class will be available throughout the system via:
 * `Craft::$app->getModule('recaptcha-pro')`
 *
 * @see http://www.yiiframework.com/doc-2.0/guide-structure-modules.html
 *
 * @property Recaptcha2 $v2
 * @property Recaptcha3 $v3
 *
 * @method Settings getSettings()
 *
 */
class RecaptchaPro extends Plugin
{

	const RECAPTCHA_RESPONSE_PARAMETER = 'g-recaptcha-response';

	/**
	 * @var bool
	 */
	public $hasCpSettings = false;

	/**
	 * @var bool
	 */
	public $hasCpSection = false;

	/**
	 * @var string
	 */
	public $schemaVersion = '0.0.0.0';


	/*
     * Public methods
     * ===========================================================================
     */

	public function __construct($id, $parent = null, array $config = [])
	{

		$config['components'] = [
			'v2' => Recaptcha2::class,
			'v3' => Recaptcha3::class,
		];

		parent::__construct($id, $parent, $config);

	}

	/**
	 * Initializes the module.
	 */
	public function init()
	{

		Craft::setAlias('@recaptcha-pro', __DIR__);
		parent::init();

		$this->_registerEventHandlers();
		$this->_attachVariableGlobal();

		// Register controllers via namespace map

		if (Craft::$app instanceof ConsoleApplication)
		{
			$this->controllerNamespace = 'topshelfcraft\\recaptchapro\\controllers\\console';
		}
		if (Craft::$app instanceof WebApplication)
		{
			$this->controllerNamespace = 'topshelfcraft\\recaptchapro\\controllers\\web';
		}

	}

	/**
	 * @param Request $request
	 *
	 * @return bool
	 *
	 * @throws BadRequestHttpException if token param is not in the request.
	 */
	public function validateRequest(Request $request)
	{

		$token = $request->getRequiredParam(self::RECAPTCHA_RESPONSE_PARAMETER);

		$settings = $this->getSettings();

		$recaptcha = new ReCaptcha($settings->secretKey);

		if ($hostname = $settings->getExpectedHostname($request))
		{
			$recaptcha->setExpectedHostname($hostname);
		}

		if ($action = $settings->getExpectedAction($request))
		{
			$recaptcha->setExpectedAction($action);
		}

		$remoteIp = $settings->sendRemoteIp ? $request->getRemoteIP() : null;

		$response = $recaptcha
			->setScoreThreshold($settings->getScoreThreshold($request))
			->verify($token, $remoteIp);

		// TODO: Custom exception depending on error codes?
		// TODO: Event before/after validate?

		Craft::dd($response);

		return $response->isSuccess();

	}

	/**
	 * @param ActionEvent $event
	 *
	 * @throws BadRequestHttpException
	 */
	public function handleBeforeActionEvent(ActionEvent $event)
	{

		/** @var Controller $controller */
		$controller = $event->sender;
		$request = Craft::$app->getRequest();

		// Skip validation for "safe" methods (https://tools.ietf.org/html/rfc2616#section-9.1.1)
		if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true))
		{
			return;
		}

		$settings = $this->getSettings();

		// Bypass if the plugin is not enabled, or if we are already dealing with an exception.
		if (!$settings->getEnabledForRequest($request) || Craft::$app->getErrorHandler()->exception !== null)
		{
			return;
		}

		// Interrupt the Action if the provided token is not valid.
		if (!$this->validateRequest($request))
		{
			throw new BadRequestHttpException(Craft::t('yii', 'Unable to verify your data submission.'));
		}

		// Bypass Yii's internal CSRF validation if reCAPTCHA has already vouched for the request.
		if ($settings->bypassCsrfValidationIfValid)
		{
			$controller->enableCsrfValidation = false;
		}

	}

	/*
     * Protected methods
     * ===========================================================================
     */

	/**
	 * Creates and returns the model used to store the plugin’s settings.
	 *
	 * @return Settings|null
	 */
	protected function createSettingsModel()
	{
		return new Settings();
	}

	/*
     * Private methods
     * ===========================================================================
     */

	/**
	 * Makes the plugin instance available to Twig via the `craft.recaptcha` variable.
	 */
	private function _attachVariableGlobal()
	{

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			function (Event $event) {
				/** @var CraftVariable $variable **/
				$variable = $event->sender;
				$variable->set('recaptcha', $this);
			}
		);

	}

	/**
	 * Registers handlers for various Event hooks
	 */
	private function _registerEventHandlers()
	{

		/*
		 * The handler event is attached to craft\web\Controller, rather than a base controller,
		 * because everything downstream assumes the request is a craft\web\Request.
		 */
		Event::on(
			Controller::class,
			Controller::EVENT_BEFORE_ACTION,
			[$this, 'handleBeforeActionEvent']
		);

	}

}
