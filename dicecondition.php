<?php

// phpcs:disable PSR1.Files.SideEffects

declare(strict_types=1);

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use CBPActivity;
use CBPRuntime;
use CBPTrackingType;
use CBPWorkflowTemplateUser;
use Bitrix\Bizproc;
use Bitrix\Main\Localization\Loc;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class CBPDiceCondition extends CBPActivityCondition
{
    public $number = 6;

    public function __construct($activityData)
    {
        $this->number = $activityData['Number'];
    }

    /**
     * @param  CBPActivity $ownerActivity
     * @return bool If success `true` otherwise `false`
     */
    public function evaluate(CBPActivity $ownerActivity)
    {
        try {
            $random = rand(1, 6);

            $this->writeToTrackingService(
                $ownerActivity,
                Loc::getMessage('DICE_ACTIVITY_ROLL', [
                    '#EXPECTED#' => $this->number,
                    '#GOT#' => $random
                ]),
                0,
                CBPTrackingType::Report
            );

            return $random == $this->number;
        } catch (\Throwable $e) {
            $this->writeToTrackingService(
                $ownerActivity,
                $e->getMessage(),
                0,
                CBPTrackingType::Error
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
    protected function writeToTrackingService(
        CBPActivity $ownerActivity,
        $message = "",
        $modifiedBy = 0,
        $trackingType = -1
    ) {
        $trackingService = $ownerActivity->workflow->GetService("TrackingService");
        if ($trackingType < 0) {
            $trackingType = CBPTrackingType::Custom;
        }

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
     * @param    array          $documentType          The document type
     * @param    array          $arWorkflowTemplate    The archive workflow template
     * @param    array          $arWorkflowParameters  The archive workflow parameters
     * @param    array          $arWorkflowVariables   The archive workflow variables
     * @param    mixed          $defaultValue          The default value
     * @param    array          $arCurrentValues       The archive current values
     * @param    string         $formName              The form name
     * @param    CJSPopup|null  $popupWindow           The popup window
     * @param    string         $currentSiteId         The current site identifier
     * @param    array          $arWorkflowConstants   The archive workflow constants
     *
     * @return string
     */
    public static function getPropertiesDialog(
        $documentType,
        $arWorkflowTemplate,
        $arWorkflowParameters,
        $arWorkflowVariables,
        $defaultValue,
        $arCurrentValues = null,
        $formName = "",
        ?CJSPopup $popupWindow = null,
        $currentSiteId = "",
        $arWorkflowConstants = []
    ) {
        $runtime = CBPRuntime::GetRuntime();

        if (!is_array($arCurrentValues)) {
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
    public static function validateProperties($values = null, CBPWorkflowTemplateUser $user = null)
    {
        $arErrors = [];

        $values = is_array($values) ? $values : [];

        $values['Number'] = array_key_exists('Number', $values)
            ? intval($values['Number'])
            : 0
            ;

        if ($values['Number'] > 6 || $values['Number'] < 1) {
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
    public static function getPropertiesDialogValues(
        $documentType,
        $arWorkflowTemplate,
        $arWorkflowParameters,
        $arWorkflowVariables,
        $arCurrentValues,
        &$arErrors,
        $arWorkflowConstants = []
    ) {
        $arErrors = [];

        if (!array_key_exists("Number", $arCurrentValues)) {
            $arErrors[] = [
                "code" => "",
                "message" => Loc::getMessage("DICE_ACTIVITY_INCORRECT_NUMBER"),
            ];
            return null;
        }

        $arErrors = self::validateProperties(
            $arCurrentValues,
            new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser)
        );

        if (count($arErrors) > 0) {
            return null;
        }

        return $arCurrentValues;
    }
}
