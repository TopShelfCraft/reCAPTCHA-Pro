<?php
namespace topshelfcraft\recaptchapro\config;

use craft\base\Model;
use craft\web\Request;

class Settings extends Model
{

	/**
	 * @var callable|bool
	 */
	public $enabled;

	/**
	 * @var callable|string
	 */
	public $expectedHostname;

	/**
	 * @var callable|string
	 */
	public $expectedAction;

	/**
	 * @var callable|float
	 */
	public $scoreThreshold = 0.7;

	/**
	 * @var bool
	 */
	public $bypassCsrfValidationIfValid = false;

	/**
	 * @var array
	 */
	public $defaultOptions = [];

	/**
	 * @var bool
	 */
	public $sendRemoteIp = true;

	/**
	 * @var string
	 */
	public $secretKey;

	/**
	 * @var string
	 */
	public $siteKey;

	/**
	 * @param array $config
	 */
	public function __construct($config = [])
	{

		/*
		 * By default, reCAPTCHA is required for front-end action requests.
		 */
//		$this->enabled = function (Request $request)
//		{
//			return $request->getIsActionRequest() && $request->getIsSiteRequest();
//		};

		parent::__construct($config);

	}

	/**
	 * @param Request $request
	 *
	 * @return bool
	 */
	public function getEnabledForRequest(Request $request)
	{

		if (is_callable($this->enabled))
		{
			return (bool) ($this->enabled)($request);
		}

		return (bool) $this->enabled;

	}

	/**
	 * @param Request $request
	 *
	 * @return string|null
	 */
	public function getExpectedAction(Request $request)
	{

		if (is_callable($this->expectedAction))
		{
			return ($this->expectedAction)($request);
		}

		return $this->expectedAction;

	}

	/**
	 * @param Request $request
	 *
	 * @return string|null
	 */
	public function getExpectedHostname(Request $request)
	{

		if (is_callable($this->expectedHostname))
		{
			return ($this->expectedHostname)($request);
		}

		return $this->expectedHostname;

	}

	/**
	 * @param Request $request
	 *
	 * @return float
	 */
	public function getScoreThreshold(Request $request)
	{

		if (is_callable($this->scoreThreshold))
		{
			return (float) ($this->scoreThreshold)($request);
		}

		return (float) $this->scoreThreshold;

	}

}
