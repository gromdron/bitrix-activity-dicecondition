<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Bizproc;
use \Bitrix\Main\Localization\Loc;

class CBPDiceCondition
	extends CBPActivityCondition
{
	public $number = 6;

	public function __construct( $activityData )
	{
		$this->number = $activityData['Number'];
	}

	/**
	 * @param  CBPActivity $ownerActivity
	 * @return bool If success `true` otherwise `false`
	 */
	public function Evaluate( CBPActivity $ownerActivity )
	{
		try
		{
			$random = rand(1, 6);

			$this->writeToTrackingService(
				$ownerActivity, 
				Loc::getMessage('DICE_ACTIVITY_ROLL', [
					'#EXPECTED#' => $this->number,
					'#GOT#' => $random
				]), 
				0, 
				\CBPTrackingType::Report
			);

			return $random==$this->number;
		}
		catch( \Throwable $e )
		{
			$this->writeToTrackingService(
				$ownerActivity, 
				$e->getMessage(), 
				0, 
				\CBPTrackingType::Error
			);
		}

		return false;
	}

	/**
	 * Polyfill: Write to process log
	 * 
	 * @param  CBPActivity       $ownerActivity     Owner activity
	 * @param  string            $message           Log message
	 * @param  integer           $modifiedBy        Log by
	 * @param  \CBPTrackingType $trackingType       Log level
	 * @return void
	 */
	protected function writeToTrackingService( CBPActivity $ownerActivity, $message = "", $modifiedBy = 0, $trackingType = -1)
	{
		$trackingService = $ownerActivity->workflow->GetService("TrackingService");
		if ($trackingType < 0)
			$trackingType = \CBPTrackingType::Custom;

		$trackingService->Write(
			$ownerActivity->GetWorkflowInstanceId(),
			$trackingType,
			$ownerActivity->getName(),
			$ownerActivity->executionStatus,
			$ownerActivity->executionResult,
			($ownerActivity->IsPropertyExists("Title") ? $ownerActivity->Title : ""),
			$message,
			$modifiedBy
		);
	}

	/**
	 * Return rendered visual file of activity
	 * 
	 * @param array  $documentType         Document type [<module>, <entity>, <sub code>]
	 * @param array  $arWorkflowTemplate   Workflow template array
	 * @param array  $arWorkflowParameters Workflow parameters
	 * @param array  $arWorkflowVariables  Workflow variables
	 * @param array  $defaultValue         Default variables (first execution)
	 * @param array  $arCurrentValues      After apply form
	 * @param string $formName             HTML form tag name
	 * @param array  $popupWindow          Popup window data
	 * @param string $currentSiteId        Current website code
	 * @param array  $arWorkflowConstants  Workflow constants
	 */
	public static function GetPropertiesDialog(
		$documentType,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$defaultValue,
		$arCurrentValues = null,
		$formName,
		$popupWindow,
		$currentSiteId,
		$arWorkflowConstants
	)
	{
		$runtime = \CBPRuntime::GetRuntime();

		if ( !is_array($arCurrentValues) )
		{
			$arCurrentValues = [
				"Number" => $defaultValue['Number']
			];
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			[
				"arCurrentValues" => $arCurrentValues
			]
		);
	}

	/**
	 * Validate values
	 * 
	 * @param array                        $values All data from activity
	 * @param CBPWorkflowTemplateUser|null $user   Who edit current template
	 * @return array  List of errors
	 */
	public static function ValidateProperties($values = null, CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		$values = is_array($values)? $values : [];

		$values['Number'] = array_key_exists('Number', $values)
			? intval($value['Number'])
			: 0
			;

		if ( $values['Number'] > 6 || $values['Number'] < 1 )
		{
			$arErrors[] = [
				"code" => "",
				"message" => Loc::getMessage("DICE_ACTIVITY_INCORRECT_NUMBER"),
			];
		}

		return array_merge(
			$arErrors,
			parent::ValidateProperties($values, $user)
		);
	}

	/**
	 * Return dialog values or null if error
	 * 
	 * @param string     $documentType         Document type [<module>, <entity>, <sub code>]
	 * @param array      $arWorkflowTemplate   Array with workflow template
	 * @param array      $arWorkflowParameters Array with workflow parameters
	 * @param array      $arWorkflowVariables  Array with workflow variables
	 * @param array|null $arCurrentValues      null or array with values
	 * @param array      &$arErrors            array
	 * @param array      $arWorkflowConstants  Array with workflow constants
	 */
	public static function GetPropertiesDialogValues(
		$documentType,
		$arWorkflowTemplate,
		$arWorkflowParameters,
		$arWorkflowVariables,
		$arCurrentValues,
		&$arErrors,
		$arWorkflowConstants
	)
	{
		$arErrors = array();

		if (
			!array_key_exists("Number", $arCurrentValues)
			|| $arCurrentValues["Number"] == ''
			|| $arCurrentValues["Number"] > 6
			|| $arCurrentValues["Number"] < 1
		)
		{
			$arErrors[] = array(
				"code" => "",
				"message" => Loc::getMessage("DICE_ACTIVITY_INCORRECT_NUMBER"),
			);
			return null;
		}

		$arErrors = self::ValidateProperties(
			$arCurrentValues,
			new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
		);
		
		if (count($arErrors) > 0)
			return null;

		return $arCurrentValues;
	}
}