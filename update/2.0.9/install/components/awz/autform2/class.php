<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Awz\AutForm\Events;
use Awz\AutForm\Helper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Errorable;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\Security;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserTable;
use Bitrix\Main\Application;
use Bitrix\Sale\Internals\OrderPropsValueTable;

Loc::loadMessages(__FILE__);

class AwzAutFormV2Component extends CBitrixComponent implements Controllerable, Errorable
{
    const CODE_NOT_TIMEOUT = 107;

    /** @var ErrorCollection */
    protected $errorCollection;

    /** @var  Bitrix\Main\HttpRequest */
    protected $request;

    /** @var Context $context */
    protected $context;

    public $arParams = array();
    public $arResult = array();

    public $userGroups = array();

    /**
     * Ajax actions
     *
     * @return array[][]
     */
    public function configureActions(): array
    {
        return [
            'getForm' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
            ],
            'sendForm' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
            ],
        ];
    }

    /**
     * Signed params
     *
     * @return string[]
     */
    protected function listKeysSignedParameters(): array
    {
        return [
            'LOGIN_EMAIL_ACTIVE',
            'LOGIN_ACTIVE',
            'REGISTER_ACTIVE',
            'LOGIN_SMS_ACTIVE',
            'LOGIN_EMAIL_ACTIVE',
            'REGISTER_SMS_ACTIVE',
            'COUNTRY_CODE',
            'THEME',
            'LOGIN_GROUPS',
            'LOGIN_GROUPS_DEL',
            'LOGIN_SMS_GROUPS',
            'LOGIN_SMS_GROUPS_DEL',
            'LOGIN_EMAIL_GROUPS',
            'LOGIN_EMAIL_GROUPS_DEL',
            'REGISTER_GROUPS',
            'REGISTER_SMS_GROUPS',
            'PERSONAL_LINK',
            'PERSONAL_LINK_EDIT',
            'AGREEMENT',
            'FIND_TYPE',
            'SALE_PROP',
            'REGISTER_LOGIN',
            'LOGIN_REGISTER',
            'LOGIN_GROUPS_DEL2',
            'LOGIN_GROUPS_DEL3',
            'CHECK_LOGIN',
            'CHECK_EMAIL',
            'CHECK_PHONE',
            'REGISTER_ACTIVE_DSBL_CODE',
            'REGISTER_ACTIVE_SYSLOGIN',
            'REGISTER_ACTIVE_NAME',
            'REGISTER_ACTIVE_PHONE',
            'REGISTER_SMS_ACTIVE_SYSLOGIN',
            'REGISTER_SMS_ACTIVE_NAME',
            'REGISTER_ACTIVE_PSW',
            'REGISTER_SMS_ACTIVE_PSW',
            'MERGE_PE',
            'MERGE_PE_REG',
            'AGR_TITLE',
            'AGR_LINK',
            'AGR_ANCOR',
            'AGR_SET'
        ];
    }

    /**
     * Create default component params
     *
     * @param array $arParams параметры
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $this->errorCollection = new ErrorCollection();
        $this->arParams = &$arParams;

        if(!$arParams['COUNTRY_CODE'])
            $arParams['COUNTRY_CODE'] = '7';

        if(!$arParams['THEME'])
            $arParams['THEME'] = 'red';

        if($arParams['REGISTER_LOGIN']!='Y')
            $arParams['REGISTER_LOGIN'] = 'N';

        if($arParams['LOGIN_REGISTER']!='Y')
            $arParams['LOGIN_REGISTER'] = 'N';

        if($arParams['MERGE_PE']!='Y')
            $arParams['MERGE_PE'] = 'N';

        if($arParams['MERGE_PE_REG']!='Y')
            $arParams['MERGE_PE_REG'] = 'N';

        if($arParams['CHECK_LOGIN']!='Y')
            $arParams['CHECK_LOGIN'] = 'N';

        if(!$arParams['SALE_PROP'])
            $arParams['SALE_PROP'] = 'PHONE';

        if(!$arParams['FIND_TYPE'])
            $arParams['FIND_TYPE'] = 'user';

        if(!is_array($arParams['LOGIN_GROUPS']))
            $arParams['LOGIN_GROUPS'] = array();

        if(!is_array($arParams['LOGIN_GROUPS_DEL']))
            $arParams['LOGIN_GROUPS_DEL'] = array();

        if(!is_array($arParams['LOGIN_GROUPS_DEL2']))
            $arParams['LOGIN_GROUPS_DEL2'] = array();

        if(!is_array($arParams['LOGIN_SMS_GROUPS']))
            $arParams['LOGIN_SMS_GROUPS'] = array();

        if(!is_array($arParams['LOGIN_EMAIL_GROUPS']))
            $arParams['LOGIN_EMAIL_GROUPS'] = array();

        if(!is_array($arParams['REGISTER_GROUPS']))
            $arParams['REGISTER_GROUPS'] = array();

        if(!is_array($arParams['LOGIN_SMS_GROUPS_DEL']))
            $arParams['LOGIN_SMS_GROUPS_DEL'] = array();
        if(!is_array($arParams['LOGIN_EMAIL_GROUPS_DEL']))
            $arParams['LOGIN_EMAIL_GROUPS_DEL'] = array();

        if($arParams['MERGE_PE']=='Y' && $arParams['LOGIN_EMAIL_ACTIVE']=='Y' && $arParams['LOGIN_SMS_ACTIVE']=='Y'){
            $arParams['MERGE_PE'] = 'Y';
        }else{
            $arParams['MERGE_PE'] = 'N';
        }

        if($arParams['MERGE_PE_REG']=='Y' && $arParams['REGISTER_ACTIVE']=='Y' && $arParams['REGISTER_SMS_ACTIVE']=='Y'){
            $arParams['MERGE_PE_REG'] = 'Y';
        }else{
            $arParams['MERGE_PE_REG'] = 'N';
        }
        if(!$arParams['AGR_ANCOR']){
            $arParams['AGR_ANCOR'] = $arParams['AGR_LINK'];
        }
        if($arParams['AGR_TITLE']){
            $arParams['AGR_TITLE'] = str_replace(
                '#LINK#',
                '<a href="'.$arParams['AGR_LINK'].'" target="_blank">'.$arParams['AGR_ANCOR'].'</a>',
                $arParams['AGR_TITLE']
            );
        }

        return $arParams;
    }

    /**
     * Show public component
     *
     * @throws LoaderException
     */
    public function executeComponent(): void
    {
        if(!Loader::includeModule('awz.autform'))
        {
            ShowError(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_INSTALL'));
            return;
        }

        if(empty($this->arParams['REGISTER_GROUPS']) &&
            empty($this->arParams['LOGIN_SMS_GROUPS']) &&
            empty($this->arParams['LOGIN_GROUPS']))
        {
            ShowError(Loc::getMessage('AWZ_AUTFORM2_CMP_NOT_SETTINGS'));
            return;
        }

        $this->includeComponentTemplate();
    }

    public function sendFormAction(): string
    {
        $this->setTemplateName($this->request->get('COMPONENT_TEMPLATE'));

        if(!$this->checkRequireModule()) return '';

        $this->arResult['VALUES'] = [];
        $this->arResult['ERRORS'] = [];
        $this->arResult['EV_FIELDS'] = [];

        if(empty($this->arParams)){
            $this->addError(new Error(
                Loc::getMessage('AWZ_AUTFORM2_CMP_NOT_SETTINGS'), 'system'
            ));
        }

        $errors = $this->getErrors();
        foreach($errors as $err){
            $this->arResult['ERRORS'][$err->getCode()] = [$err->getMessage()];
        }
        if(!empty($errors)){
            return $this->getHtmlFormAction();
        }

        $this->setValues();
        $this->sendEvent();

        $templateName = 'success';

        if($this->arResult['VALUES']['step'] == 'code_send'){
            $templateName = 'form_auth';
        }
        if($this->arResult['VALUES']['step'] == 'active-code'){
            $templateName = 'form_auth';
        }
        if($this->arResult['VALUES']['step'] == 'active-code-register'){
            $templateName = 'form_auth';
        }
        if(isset($this->arResult['VALUES']['register']) && $this->arResult['VALUES']['register'] == 'Y'){
            if($templateName == 'form_auth'){
                $templateName = 'form_register';
            }
        }

        $errors = $this->getErrors();
        foreach($errors as $err){
            if($err->getCode() == static::CODE_NOT_TIMEOUT) continue;
            $this->arResult['ERRORS'][$err->getCode()] = [$err->getMessage()];
        }
        $errors = $this->arResult['ERRORS'];

        $this->arParams['TEMPLATE_FILE'] = $templateName;

        if(empty($errors) && $templateName){
            ob_start();
            $this->includeComponentTemplate();
            $html = ob_get_contents();
            ob_end_clean();
            return $html;
        }else{
            return $this->getFormAction();
        }

    }

    public function getFormAction(): string
    {
        $this->setTemplateName($this->request->get('COMPONENT_TEMPLATE'));

        if(!$this->checkRequireModule()) return '';

        if(!$this->arParams['autFormId']) {
            $this->arParams['autFormId'] = $this->request->get('autFormId');
        }

        $this->setValues();

        $template = 'form_auth';
        if(isset($this->arResult['VALUES']['register']) && $this->arResult['VALUES']['register'] == 'Y'){
            $template = 'form_register';
        }

        $this->arParams['TEMPLATE_FILE'] = $template;

        ob_start();
        $this->includeComponentTemplate();
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Установка значений с request
     *
     * @return void
     */
    public function setValues()
    {
        if(!isset($this->arResult['VALUES'])){
            $this->arResult['VALUES'] = [];
        }
        $activeModes = [];
        $keys = [
            'LOGIN_ACTIVE',
            'LOGIN_SMS_ACTIVE','LOGIN_EMAIL_ACTIVE',
            'REGISTER_SMS_ACTIVE','REGISTER_ACTIVE'
        ];
        foreach($keys as $v){
            if($this->arParams[$v]!='Y') continue;
            $activeModes[] = $v;
        }
        if(!empty($activeModes)){
            $currentMode = $this->request->get('mode') && in_array($this->request->get('mode'), $activeModes)
                ? $this->request->get('mode') : $activeModes[0];
            $this->arResult['VALUES']['mode'] = $currentMode;
            $this->arResult['VALUES']['step'] = $this->request->get('step');
            if(strpos($this->arResult['VALUES']['mode'], 'REGISTER_')!==false){
                $this->arResult['VALUES']['register'] = 'Y';
            }
        }
        if($this->request->get('send')=='Y'){
            $this->arResult['VALUES']['phone'] = $this->request->get('phone');
            $this->arResult['VALUES']['login'] = $this->request->get('login');
            $this->arResult['VALUES']['password'] = $this->request->get('password');
            $this->arResult['VALUES']['code'] = $this->request->get('code');
            $this->arResult['VALUES']['email'] = $this->request->get('email');
            $this->arResult['VALUES']['name'] = $this->request->get('name');
            $this->arResult['VALUES']['password2'] = $this->request->get('password2');
            $this->arResult['VALUES']['oferta'] = $this->request->get('oferta');
        }else{
            if($this->arParams['AGR_SET']=='Y'){
                $this->arResult['VALUES']['oferta'] = "Y";
            }
        }

        $event = new Event(
            'awz.autform', Events::AFTER_SET_VALUES_V2,
            array(
                'component'=>$this,
                'request'=>$this->request
            )
        );
        $event->send();

    }

    /**
     * Установка контекста и логика работы формы
     *
     * @return null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function sendEvent()
    {

        if(!$this->checkRequireModule()) return null;

        if(!$this->arResult['VALUES']['mode']){
            $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_NOT_AUTH'), 'system'));
            return null;
        }

        if($this->arParams['AGR_TITLE'] && $this->arResult['VALUES']['oferta']!='Y'){
            $this->addError(new Error(Loc::getMessage("AWZ_AUTFORM2_CMP_NOT_CHECK_AGREEMENT"), 'oferta'));
            return null;
        }

        $mode = $this->arResult['VALUES']['mode'];

        if($this->arParams['MERGE_PE']=='Y' &&
            $this->arParams['LOGIN_EMAIL_ACTIVE']=="Y" && $this->arParams['LOGIN_SMS_ACTIVE']=="Y"
        ){
            if($mode == 'LOGIN_EMAIL_ACTIVE') $mode = 'LOGIN_SMS_ACTIVE';
            if($mode == 'LOGIN_SMS_ACTIVE'){
                if(!$this->arResult['VALUES']['phone'] && $this->arResult['VALUES']['email']){
                    $this->arResult['VALUES']['phone'] = $this->arResult['VALUES']['email'];
                }
                if(!$this->arResult['VALUES']['email'] && $this->arResult['VALUES']['phone']){
                    $this->arResult['VALUES']['email'] = $this->arResult['VALUES']['phone'];
                }
                if(strpos($this->arResult['VALUES']['phone'], '@')!==false){
                    $mode = 'LOGIN_EMAIL_ACTIVE';
                }
            }
        }

        if($this->arParams['MERGE_PE_REG']=='Y' &&
            $this->arParams['REGISTER_ACTIVE']=="Y" && $this->arParams['REGISTER_SMS_ACTIVE']=="Y"
        ){
            if($mode == 'REGISTER_ACTIVE') $mode = 'REGISTER_SMS_ACTIVE';
            if($mode == 'REGISTER_SMS_ACTIVE'){
                if(!$this->arResult['VALUES']['phone'] && $this->arResult['VALUES']['email']){
                    $this->arResult['VALUES']['phone'] = $this->arResult['VALUES']['email'];
                }
                if(!$this->arResult['VALUES']['email'] && $this->arResult['VALUES']['phone']){
                    $this->arResult['VALUES']['email'] = $this->arResult['VALUES']['phone'];
                }
                if(strpos($this->arResult['VALUES']['phone'], '@')!==false){
                    $mode = 'REGISTER_ACTIVE';
                }
            }
        }

        $this->arResult['VALUES']['mode'] = $mode;

        $event = new Event(
            'awz.autform', Events::BEFORE_SET_EVENTS_V2,
            array(
                'component'=>$this,
                'request'=>$this->request
            )
        );
        $event->send();

        if(!empty($this->getErrors())){
            return null;
        }

        if($mode === 'LOGIN_ACTIVE'){
            if(!$this->arResult['VALUES']['login']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_PHONE_LOGIN'), 'login'));
            }elseif(!$this->arResult['VALUES']['password']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_PSW'), 'password'));
            }
            if(!empty($this->getErrors())){
                return null;
            }
            $authResult = $this->checkAuthAction(
                $this->arResult['VALUES']['login'],
                $this->arResult['VALUES']['password']
            );
            if($authResult && isset($authResult['user'])){
                $this->arResult['VALUES']['auth_user'] = $authResult['user'];
                $this->arResult['VALUES']['step'] = 'ok_auth';
            }
        }
        elseif($mode === 'REGISTER_ACTIVE'){
            $this->arResult['VALUES']['email'] = $this->checkEmail($this->arResult['VALUES']['email']);
            if($this->arParams['REGISTER_ACTIVE_SYSLOGIN'] == 'Y' && !$this->arResult['VALUES']['login']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_SYSLOGIN_REQ'), 'login'));
            }
            if($this->arParams['REGISTER_ACTIVE_NAME'] == 'Y' && !$this->arResult['VALUES']['name']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_NAME_REQ'), 'name'));
            }
            if($this->arParams['REGISTER_ACTIVE_PHONE'] == 'Y' && !$this->arResult['VALUES']['phone']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_PHONE_REQ'), 'phone'));
            }
            if($this->arParams['REGISTER_ACTIVE_PSW'] == 'Y' && !$this->arResult['VALUES']['password']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_PSW_REQ'), 'password'));
            }
            if($this->arParams['REGISTER_ACTIVE_PSW'] == 'Y' && !$this->arResult['VALUES']['password2']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_PSW_REQ_2'), 'password2'));
            }
            if($this->arParams['REGISTER_ACTIVE_PSW'] == 'Y' && $this->arResult['VALUES']['password'] != $this->arResult['VALUES']['password2']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_PSW_REQ_1_2'), 'password2'));
            }
            if(empty($this->arParams['REGISTER_GROUPS'])){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_NO_GROUP_REG'), 'system'));
            }
            if(!empty($this->getErrors())){
                return null;
            }
            if($this->arParams['REGISTER_ACTIVE_PSW'] == 'Y'){
                $errors = (new \CUser)->CheckPasswordAgainstPolicy($this->arResult['VALUES']['password'], \CUser::GetGroupPolicy($this->arParams['REGISTER_GROUPS']));
                if(!empty($errors)){
                    $this->addError(new Error($errors[0], 'password'));
                    return null;
                }
            }
            if($this->arParams['REGISTER_ACTIVE_DSBL_CODE'] == 'Y'){
                $this->arResult['VALUES']['step'] = 'code_send';
            }
            if($this->arResult['VALUES']['step'] == 'code_send'){
                if($this->arParams['REGISTER_ACTIVE_DSBL_CODE'] != 'Y'){
                    $this->checkCode(
                        $this->arResult['VALUES']['email'],
                        $this->arResult['VALUES']['code'],
                        $mode
                    );
                }
                if(empty($this->getErrors())){
                    $existsLogic = false;

                    if($this->arParams['REGISTER_LOGIN'] == 'Y' && $this->arParams['LOGIN_EMAIL_ACTIVE'] == 'Y') {
                        $checkUser = $this->findUserFromPhone($this->arResult['VALUES']['phone']);
                        if($checkUser){
                            $this->arResult['VALUES']['mode'] =  'LOGIN_EMAIL_ACTIVE';
                            $this->arResult['RCODE_RES'] = $this->authUserEmail($this->arResult['VALUES']['email']);
                            $existsLogic = true;
                        }
                    }

                    if(!$existsLogic)
                        $this->arResult['RCODE_RES'] = $this->register();
                }
            }else{
                $this->arResult['RCODE_RES'] = $this->getCode($mode);
            }
            $this->arResult['VALUES']['step'] =
                isset($this->arResult['RCODE_RES']['step']) ? $this->arResult['RCODE_RES']['step'] : 'code_send';
            if(is_array($this->arResult['RCODE_RES']) &&
                isset($this->arResult['RCODE_RES']['user']) &&
                $this->arResult['RCODE_RES']['user']
            ){
                $this->arResult['VALUES']['auth_user'] = $this->arResult['RCODE_RES']['user'];
                $this->arResult['VALUES']['step'] = 'ok_register';
            }
        }
        elseif($mode === 'REGISTER_SMS_ACTIVE'){
            $this->arResult['VALUES']['phone'] = $this->checkPhone($this->arResult['VALUES']['phone']);
            if($this->arParams['REGISTER_SMS_ACTIVE_SYSLOGIN'] == 'Y' && !$this->arResult['VALUES']['login']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_SYSLOGIN_REQ'), 'login'));
            }
            if($this->arParams['REGISTER_SMS_ACTIVE_NAME'] == 'Y' && !$this->arResult['VALUES']['name']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_NAME_REQ'), 'name'));
            }
            if($this->arParams['REGISTER_SMS_ACTIVE_PSW'] == 'Y' && !$this->arResult['VALUES']['password']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_PSW_REQ'), 'password'));
            }
            if($this->arParams['REGISTER_SMS_ACTIVE_PSW'] == 'Y' && !$this->arResult['VALUES']['password2']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_PSW_REQ_2'), 'password2'));
            }
            if($this->arParams['REGISTER_SMS_ACTIVE_PSW'] == 'Y' && $this->arResult['VALUES']['password'] != $this->arResult['VALUES']['password2']){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_PSW_REQ_1_2'), 'password2'));
            }
            if(empty($this->arParams['REGISTER_SMS_GROUPS'])){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_NO_GROUP_REG'), 'system'));
            }
            if(!empty($this->getErrors())){
                return null;
            }
            if($this->arParams['REGISTER_SMS_ACTIVE_PSW'] == 'Y'){
                $errors = (new \CUser)->CheckPasswordAgainstPolicy($this->arResult['VALUES']['password'], \CUser::GetGroupPolicy($this->arParams['REGISTER_GROUPS']));
                if(!empty($errors)){
                    $this->addError(new Error($errors[0], 'password'));
                    return null;
                }
            }
            if($this->arResult['VALUES']['step'] == 'code_send'){
                $this->checkCode(
                    $this->arResult['VALUES']['phone'],
                    $this->arResult['VALUES']['code'],
                    $mode
                );
                if(empty($this->getErrors())){
                    $existsLogic = false;

                    if($this->arParams['REGISTER_LOGIN'] == 'Y' && $this->arParams['LOGIN_SMS_ACTIVE'] == 'Y') {
                        $checkUser = $this->findUserFromPhone($this->arResult['VALUES']['phone']);
                        if($checkUser){
                            $this->arResult['VALUES']['mode'] =  'LOGIN_SMS_ACTIVE';
                            $this->arResult['RCODE_RES'] = $this->authUserPhone($this->arResult['VALUES']['phone']);
                            $existsLogic = true;
                        }
                    }

                    if(!$existsLogic)
                        $this->arResult['RCODE_RES'] = $this->register();
                }
            }else{
                $this->arResult['RCODE_RES'] = $this->getCode($mode);
            }
            $this->arResult['VALUES']['step'] =
                isset($this->arResult['RCODE_RES']['step']) ? $this->arResult['RCODE_RES']['step'] : 'code_send';
            if(is_array($this->arResult['RCODE_RES']) &&
                isset($this->arResult['RCODE_RES']['user']) &&
                $this->arResult['RCODE_RES']['user']
            ){
                $this->arResult['VALUES']['auth_user'] = $this->arResult['RCODE_RES']['user'];
                $this->arResult['VALUES']['step'] = 'ok_register';
            }
        }
        else if($mode == 'LOGIN_SMS_ACTIVE'){
            $this->arResult['VALUES']['phone'] = $this->checkPhone($this->arResult['VALUES']['phone']);
            if(!empty($this->getErrors())){
                return null;
            }
            if($this->arResult['VALUES']['step'] == 'code_send'){
                $this->checkCode(
                    $this->arResult['VALUES']['phone'],
                    $this->arResult['VALUES']['code'],
                    $mode
                );
                if(empty($this->getErrors())){

                    $existsLogic = false;
                    if($this->arParams['LOGIN_REGISTER'] == 'Y' && $this->arParams['REGISTER_SMS_ACTIVE'] == 'Y') {
                        $checkUser = $this->findUserFromPhone($this->arResult['VALUES']['phone']);
                        if(!$checkUser){
                            $this->arResult['VALUES']['mode'] =  'REGISTER_SMS_ACTIVE';
                            $this->arResult['RCODE_RES'] = $this->register();
                            $existsLogic = true;
                        }
                    }
                    if(!$existsLogic)
                        $this->arResult['RCODE_RES'] = $this->authUserPhone($this->arResult['VALUES']['phone']);
                }
            }else{
                $this->arResult['RCODE_RES'] = $this->getCode($mode);
            }
            $this->arResult['VALUES']['step'] =
                isset($this->arResult['RCODE_RES']['step']) ? $this->arResult['RCODE_RES']['step'] : 'code_send';
            if(is_array($this->arResult['RCODE_RES']) &&
                isset($this->arResult['RCODE_RES']['user']) &&
                $this->arResult['RCODE_RES']['user']
            ){
                $this->arResult['VALUES']['auth_user'] = $this->arResult['RCODE_RES']['user'];
                $this->arResult['VALUES']['step'] = 'ok_auth';
            }
        }
        else if($mode == 'LOGIN_EMAIL_ACTIVE'){
            $this->arResult['VALUES']['email'] = $this->checkEmail($this->arResult['VALUES']['email']);
            if(!empty($this->getErrors())){
                return null;
            }
            if($this->arResult['VALUES']['step'] == 'code_send'){
                $this->checkCode(
                    $this->arResult['VALUES']['email'],
                    $this->arResult['VALUES']['code'],
                    $mode
                );
                if(empty($this->getErrors())){

                    $existsLogic = false;
                    if($this->arParams['LOGIN_REGISTER'] == 'Y' && $this->arParams['REGISTER_ACTIVE'] == 'Y') {
                        $checkUser = $this->findUserFromEmail($this->arResult['VALUES']['email']);
                        if(!$checkUser){
                            $this->arResult['VALUES']['mode'] = 'REGISTER_ACTIVE';
                            $this->arResult['RCODE_RES'] = $this->register();
                            $existsLogic = true;
                        }
                    }
                    if(!$existsLogic)
                        $this->arResult['RCODE_RES'] = $this->authUserEmail($this->arResult['VALUES']['email']);
                }
            }else{
                $this->arResult['RCODE_RES'] = $this->getCode($mode);
            }
            $this->arResult['VALUES']['step'] =
                isset($this->arResult['RCODE_RES']['step']) ? $this->arResult['RCODE_RES']['step'] : 'code_send';
            if(is_array($this->arResult['RCODE_RES']) &&
                isset($this->arResult['RCODE_RES']['user']) &&
                $this->arResult['RCODE_RES']['user']
            ){
                $this->arResult['VALUES']['auth_user'] = $this->arResult['RCODE_RES']['user'];
                $this->arResult['VALUES']['step'] = 'ok_auth';
            }

        }
        return null;
    }

    /**
     * Регистрация пользоваптеля
     *
     * @return array|int[]|null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function register(string $mode = ''): ?array
    {

        $parameters = $this->arParams;
        $values = $this->arResult['VALUES'];

        $arFieldsUser = [];

        if(!$mode) $mode = $values['mode'];

        if($mode === 'REGISTER_ACTIVE'){

            $userId = $this->findUserFromEmail($values['email']);
            if($userId){

                if($parameters['REGISTER_LOGIN']=='Y' && $parameters['LOGIN_EMAIL_ACTIVE']=='Y'){
                    return $this->authUserEmail($values['email']);
                }

                $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_USER_REGISTER_FOUND2'));
                return null;
            }

            if($values['login']){
                $userLogin = $this->findUserFromLogin($values['login']);
                if($userLogin){
                    $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_USER_REGISTER_FOUND3'));
                    return null;
                }else{
                    $userLogin = $values['login'];
                }
            }else{
                $userLogin = $this->findUserFromLogin($values['email']);
                if($userLogin) {
                    $userLogin = 'awz_'.time().Random::getString(5);
                }else{
                    $userLogin = $values['email'];
                }
            }

            $arFieldsUser = Array(
                "LOGIN"             => $userLogin,
                "ACTIVE"            => "Y",
                "PASSWORD"          => $values['password'],
                "CONFIRM_PASSWORD"  => $values['password2'],
                "GROUP_ID"=>$parameters['REGISTER_GROUPS']
            );
            if($values['name']){
                $arFieldsUser['NAME'] = $values['name'];
            }
            if($values['email']){
                $arFieldsUser['EMAIL'] = $values['email'];
            }
            if($values['phone']){
                $arFieldsUser['PERSONAL_PHONE'] = $values['phone'];
                $arFieldsUser['PERSONAL_MOBILE'] = $values['phone'];
            }

        }
        elseif($mode === 'REGISTER_SMS_ACTIVE'){

            $userId = $this->findUserFromPhone($values['phone']);
            if($userId){

                if($parameters['REGISTER_LOGIN']=='Y' && $parameters['LOGIN_SMS_ACTIVE']=='Y'){
                    return $this->authUserPhone($values['phone']);
                }

                $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_USER_REGISTER_FOUND'));
                return null;
            }
            $userLogin = $this->findUserFromLogin($values['phone']);
            if($userLogin) {
                $userLogin = 'awz_'.time().Random::getString(5);
            }else{
                $userLogin = $values['phone'];
            }
            $arFieldsUser = Array(
                "LOGIN"             => $userLogin,
                "ACTIVE"            => "Y",
                "PASSWORD"          => $values['password'],
                "CONFIRM_PASSWORD"  => $values['password2'],
                "GROUP_ID"=>$parameters['REGISTER_SMS_GROUPS']
            );
            if($values['name']){
                $arFieldsUser['NAME'] = $values['name'];
            }
            if(!$values['email'] || (strpos($values['email'], '@')===false)){
                $emailRequired = Option::get('main', 'new_user_email_required', 'Y') === 'Y' ? true : false;
                if($emailRequired){
                    $arFieldsUser['EMAIL'] = 'awz_'.time().Random::getString(5).'@noemail.gav';
                }
            }else{
                $arFieldsUser['EMAIL'] = $values['email'];
            }
            if($values['phone']){
                $arFieldsUser['PERSONAL_PHONE'] = $values['phone'];
                $arFieldsUser['PERSONAL_MOBILE'] = $values['phone'];
            }

        }

        if(!$arFieldsUser['PASSWORD']){
            $password = Random::getStringByAlphabet(
                12,
                Random::ALPHABET_NUM|Random::ALPHABET_ALPHALOWER|Random::ALPHABET_ALPHAUPPER|Random::ALPHABET_SPECIAL,
                true
            );
            $arFieldsUser['PASSWORD'] = $password;
            $arFieldsUser['CONFIRM_PASSWORD'] = $password;
        }

        $this->arResult['REGISTER_FIELDS'] = $arFieldsUser;

        $user = new \CUser;
        $userId = $user->Add($arFieldsUser);
        if(!$userId){
            $this->addError($user->LAST_ERROR);
            return null;
        }

        global $USER;
        $USER->Authorize($userId);

        $event = new Event(
            'awz.autform', Events::AFTER_REGISTER_V2,
            array(
                'component'=>$this,
                'request'=>$this->request,
                'params'=>$parameters,
                'user'=>&$userId
            )
        );
        $event->send();

        return array(
            'user'=>$userId
        );

    }

    /**
     * Авторизация пользователя по email
     * @param string $email email
     * @return array|int[]|null
     * @throws ArgumentException
     * @throws SystemException
     */
    private function authUserEmail(string $email): ?array
    {

        $parameters = $this->arParams;
        $userId = $this->findUserFromEmail($email);
        if(!$this->checkRightGroup('LOGIN_EMAIL_GROUPS')){
            $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_ERR_GROUP_LOGIN'), 'email');
            return null;
        }
        if(!$userId){
            $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_EMAIL'), 'email');
            return null;
        }

        global $USER;
        $USER->Authorize($userId);

        $event = new Event(
            'awz.autform', Events::AFTER_AUTH_SMS,
            array(
                'component'=>$this,
                'email'=>$email,
                'user'=>&$userId,
                'request'=>$this->request,
                'params'=>$parameters
            )
        );
        $event->send();

        return array(
            'user'=>$userId
        );

    }

    /**
     * Авторизация пользователя по номеру телефона
     *
     * @param string $phone номер телефона
     * @return array|int[]|null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function authUserPhone(string $phone): ?array
    {

        $parameters = $this->arParams;
        $userId = $this->findUserFromPhone($phone);
        if(!$this->checkRightGroup('LOGIN_SMS_GROUPS')){
            $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_ERR_GROUP_LOGIN'), 'phone');
            return null;
        }
        if(!$userId){
            $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_PHONE'), 'phone');
            return null;
        }

        global $USER;
        $USER->Authorize($userId);

        $event = new Event(
            'awz.autform', Events::AFTER_AUTH_SMS,
            array(
                'component'=>$this,
                'phone'=>$phone,
                'user'=>&$userId,
                'request'=>$this->request,
                'params'=>$parameters
            )
        );
        $event->send();

        return array(
            'user'=>$userId
        );

    }

    /**
     * Ajax Проверка пароля
     *
     * @param string $phone грязный телефон
     * @param string $password
     * @return array|int[]|null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function checkAuthAction(string $phone, string $password): ?array
    {
        if(!$this->checkRequireModule()) return null;

        $parameters = $this->arParams;

        if(!$phone){
            if($parameters['CHECK_LOGIN']==='Y'){
                $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_PHONE_LOGIN'), 'login');
            }elseif($parameters['CHECK_EMAIL']==='Y'){
                $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_PHONE_LOGIN'), 'login');
            }else{
                $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_PHONE'), 'login');
            }
            return null;
        }
        if(!$password){
            $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_ERR_PASSW'), 'password');
            return null;
        }

        $userId = 0;
        if($parameters['CHECK_LOGIN']=='Y')
            $userId = $this->findUserFromLogin($phone);
        if(!$userId && ($parameters['CHECK_EMAIL']=='Y'))
            $userId = $this->findUserFromEmail($phone);
        if(!$userId && ($parameters['CHECK_PHONE']=='Y'))
            $userId = $this->findUserFromPhone($phone);

        if(!$userId){
            $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_USER_NOT_FOUND'), 'login');
            return null;
        }

        $checkAuth = $this->checkUserPassword($userId, $password);
        if(!$checkAuth){
            $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_ERR_PASSW'), 'password');
            return null;
        }

        global $USER;
        $USER->Authorize($userId);

        $event = new Event(
            'awz.autform', Events::AFTER_AUTH_PSW,
            array(
                'component'=>$this,
                'phone'=>$phone,
                'user'=>&$userId,
                'request'=>$this->request,
                'params'=>$parameters
            )
        );
        $event->send();

        return array(
            'user'=>$userId
        );
    }

    /**
     * Поиск юзера по логину
     *
     * @param string $login
     * @return int|null
     * @throws ArgumentException
     * @throws SystemException
     */
    protected function findUserFromLogin($login): ?int
    {
        $parameters = $this->arParams;

        $login = trim($login);
        $userId = 0;

        if(!$login){
            return 0;
        }

        $event = new Event(
            'awz.autform', Events::FIND_USER_FROM_LOGIN,
            array(
                'component'=>$this,
                'login'=>&$login,
                'userId'=>&$userId
            )
        );
        $event->send();

        if($userId){
            return (int) $userId;
        }

        if(!empty($this->getErrors())){
            return 0;
        }

        $filter = array(
            '=LOGIN'=>$login,
            '=ACTIVE'=>'Y'
        );

        //Application::getConnection()->startTracker();
        $main_query = new Query(UserTable::getEntity());

        if(!empty($parameters['LOGIN_GROUPS_DEL2'])){
            $main_query->registerRuntimeField(
                'UGR', array(
                         'data_type'=>'Bitrix\Main\UserGroupTable',
                         'reference'=> array('=this.ID' => 'ref.USER_ID')
                     )
            );
            $filter['=UGR.GROUP_ID'] = $parameters['LOGIN_GROUPS_DEL2'];
        }

        if($parameters['LOGIN_GROUPS_DEL3']){
            $filter['!ID'] = explode(',',$parameters['LOGIN_GROUPS_DEL3']);
            $filter['!ID'][] = false;
        }

        $main_query->setOrder(array('ID'=>'DESC'));
        $main_query->setLimit(1);
        $main_query->setFilter($filter);
        $main_query->setSelect(array('ID'));
        $rs = $main_query->exec();
        $resUsers = $rs->fetch();
        $userCandidate = 0;

        if($resUsers){
            $userCandidate = $resUsers['ID'];
        }

        //обязательно проверка групп юзера
        if($userCandidate){
            $this->userGroups = array();

            $r = UserGroupTable::getList(
                array(
                    'select'=>array('GROUP_ID'),
                    'filter'=>array('=USER_ID'=>$userCandidate)
                )
            );
            while($data = $r->fetch()){
                $this->userGroups[] = $data['GROUP_ID'];
            }
            return (int) $userCandidate;
        }

        return 0;
    }

    /**
     * Поиск юзера по email
     *
     * @param string $login
     * @return int|null
     * @throws ArgumentException
     * @throws SystemException
     */
    protected function findUserFromEmail($email): ?int
    {
        $parameters = $this->arParams;

        $email = trim($email);
        $userId = 0;

        if(!$email){
            return 0;
        }

        $event = new Event(
            'awz.autform', Events::FIND_USER_FROM_EMAIL,
            array(
                'component'=>$this,
                'email'=>&$email,
                'userId'=>&$userId
            )
        );
        $event->send();

        if($userId){
            return (int) $userId;
        }

        if(!empty($this->getErrors())){
            return 0;
        }


        $filter = array(
            '=EMAIL'=>$email,
            '=ACTIVE'=>'Y'
        );

        $main_query = new Query(UserTable::getEntity());

        if(!empty($parameters['LOGIN_GROUPS_DEL2'])){
            $main_query->registerRuntimeField(
                'UGR', array(
                    'data_type'=>'Bitrix\Main\UserGroupTable',
                    'reference'=> array('=this.ID' => 'ref.USER_ID')
                )
            );
            $filter['=UGR.GROUP_ID'] = $parameters['LOGIN_GROUPS_DEL2'];
        }

        if($parameters['LOGIN_GROUPS_DEL3']){
            $filter['!ID'] = explode(',',$parameters['LOGIN_GROUPS_DEL3']);
            $filter['!ID'][] = false;
        }

        $main_query->setOrder(array('ID'=>'DESC'));
        $main_query->setLimit(1);
        $main_query->setFilter($filter);
        $main_query->setSelect(array('ID'));
        $rs = $main_query->exec();
        $resUsers = $rs->fetch();

        $userCandidate = false;

        if($resUsers){
            $userCandidate = $resUsers['ID'];
        }

        //обязательно проверка групп юзера
        if($userCandidate){
            $this->userGroups = array();

            $r = UserGroupTable::getList(
                array(
                    'select'=>array('GROUP_ID'),
                    'filter'=>array('=USER_ID'=>$userCandidate)
                )
            );
            while($data = $r->fetch()){
                $this->userGroups[] = $data['GROUP_ID'];
            }
            return (int) $userCandidate;
        }

        return null;
    }

    /**
     * Поиск юзера по номеру телефона
     *
     * @param string $phone номер телефона
     * @return int|null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function findUserFromPhone(string $phone): ?int
    {
        $parameters = $this->arParams;

        $phone = trim($phone);
        $userId = 0;

        if(!$phone){
            return 0;
        }

        $event = new Event(
            'awz.autform', Events::FIND_USER_FROM_EMAIL,
            array(
                'component'=>$this,
                'phone'=>&$phone,
                'userId'=>&$userId
            )
        );
        $event->send();

        if($userId){
            return (int) $userId;
        }

        if(!empty($this->getErrors())){
            return 0;
        }

        $preparePhone = htmlspecialcharsEx(trim($phone));
        $phone = preg_replace('/([^0-9])/','',$phone);

        $phoneArray = Helper::getPhoneCandidates(
            $phone,
            $parameters['COUNTRY_CODE']
        );

        $event = new Event(
            'awz.autform', Events::AFTER_CREATE_PHONES,
            array(
                'preparePhone'=>$preparePhone,
                'phone'=>$phone,
                'phoneArray'=>&$phoneArray,
                'params'=>$parameters,
                'request'=>$this->request
            )
        );
        $event->send();

        $phoneFormated = array();
        if ($event->getResults()) {
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() == EventResult::SUCCESS) {
                    if($eventResultData = $eventResult->getParameters()){
                        if(!isset($eventResultData)) continue;
                        $r = $eventResultData['result'];
                        if($r instanceof Result){
                            if($r->isSuccess()){
                                $data = $r->getData();
                                if(isset($data['phoneArray'])){
                                    //если нужно прекратить применение обработчиков
                                    $phoneFormated = $data['phoneArray'];
                                    break;
                                }
                            }else{
                                foreach($r->getErrors() as $error){
                                    $this->addError($error);
                                }
                            }
                        }
                    }
                }
            }
        }
        if(!empty($phoneFormated)) $phoneArray = $phoneFormated;

        $findOrderUser = false;
        $userCandidate = false;
        if(Loader::includeModule('sale') &&
            $parameters['SALE_PROP'] && $parameters['FIND_TYPE'] &&
            strpos($parameters['FIND_TYPE'], 'order')!==false)
        {
            $filter = array(
                //'!ORD.CANCELED'=>'Y',
                '!ORD.ID'=>false,
                '!ORD.USER_ID'=>false,
                '=CODE'=>$parameters['SALE_PROP'],
                '=VALUE'=>$phoneArray
            );
            //\Bitrix\Main\Application::getConnection()->startTracker();
            $main_query = new Query(OrderPropsValueTable::getEntity());
            $main_query->registerRuntimeField(
                'ORD', array(
                    'data_type'=>'Bitrix\Sale\Internals\OrderTable',
                    'reference'=> array(
                        '=this.ORDER_ID' => 'ref.ID'
                    )
                )
            );

            if($parameters['LOGIN_GROUPS_DEL3']){
                $filter['!ORD.USER_ID'] = explode(',',$parameters['LOGIN_GROUPS_DEL3']);
                $filter['!ORD.USER_ID'][] = false;
            }
            if(!empty($parameters['LOGIN_GROUPS_DEL2'])){
                $main_query->registerRuntimeField(
                    'UGR', array(
                        'data_type'=>'Bitrix\Main\UserGroupTable',
                        'reference'=> array(
                            '=this.ORD.USER_ID' => 'ref.USER_ID'
                        )
                    )
                );
                $filter['=UGR.GROUP_ID'] = $parameters['LOGIN_GROUPS_DEL2'];
            }else{
                $main_query->registerRuntimeField(
                    'USR', array(
                        'data_type'=>'Bitrix\Main\UserTable',
                        'reference'=> array(
                            '=this.ORD.USER_ID' => 'ref.ID'
                        )
                    )
                );
            }

            $main_query->setOrder(array('ORD.ID'=>'DESC'));
            $main_query->setLimit(1);
            $main_query->setFilter($filter);
            $main_query->setSelect(array('ORD_USER_ID'=>'ORD.USER_ID'));
            $rs = $main_query->exec();
            $resUsers = $rs->fetch();
            //echo '<pre>', $resUsers, $rs->getTrackerQuery()->getSql(), '</pre>';
            //die();

            if($resUsers) $findOrderUser = $resUsers['ORD_USER_ID'];
        }

        //юзер найден в заказе, больше не ищем
        if($parameters['FIND_TYPE'] == 'orderuser' && $findOrderUser){
            $userCandidate = $findOrderUser;
        }
        //поиск только по заказу
        if($parameters['FIND_TYPE'] == 'order'){
            if($findOrderUser){
                $userCandidate = $findOrderUser;
            }else{
                return null;
            }
        }

        //продолжаем поиск стандартного битрикс юзера
        if(!$userCandidate && strpos($parameters['FIND_TYPE'], 'user')!==false)
        {
            $filter = array(
                array(
                    'LOGIC'=>'OR',
                    '=PERSONAL_PHONE'=>$phoneArray,
                    '=PERSONAL_MOBILE'=>$phoneArray,
                    '=LOGIN'=>$phoneArray
                ),
                '!LOGIN'=>false
            );

            $main_query = new Query(UserTable::getEntity());

            if(!empty($parameters['LOGIN_GROUPS_DEL2'])){
                $main_query->registerRuntimeField(
                    'UGR', array(
                        'data_type'=>'Bitrix\Main\UserGroupTable',
                        'reference'=> array('=this.ID' => 'ref.USER_ID')
                    )
                );
                $filter['=UGR.GROUP_ID'] = $parameters['LOGIN_GROUPS_DEL2'];
            }

            if($parameters['LOGIN_GROUPS_DEL3']){
                $filter['!ID'] = explode(',',$parameters['LOGIN_GROUPS_DEL3']);
                $filter['!ID'][] = false;
            }

            $main_query->setOrder(array('ID'=>'DESC'));
            $main_query->setLimit(1);
            $main_query->setFilter($filter);
            $main_query->setSelect(array('ID'));
            $rs = $main_query->exec();
            $resUsers = $rs->fetch();

            if($resUsers){
                $userCandidate = $resUsers['ID'];
            }
        }

        //если нет стандартного юзера, но найден в заказе
        if($parameters['FIND_TYPE'] == 'userorder' && $findOrderUser && !$userCandidate){
            $userCandidate = $findOrderUser;
        }

        //обязательно проверка групп юзера
        if($userCandidate){
            $this->userGroups = array();

            $r = UserGroupTable::getList(
                array(
                    'select'=>array('GROUP_ID'),
                    'filter'=>array('=USER_ID'=>$userCandidate)
                )
            );
            while($data = $r->fetch()){
                $this->userGroups[] = $data['GROUP_ID'];
            }
            return (int) $userCandidate;
        }

        return null;
    }

    /**
     * Проверка и форматирование email
     *
     * @param string $email грязный email
     * @return string отформатированный email, если вернул обработчик
     */
    private function checkEmail(string $email): string
    {
        if(!$email){
            $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_EMAIL'), 'email'));
        }else{
            $event = new Event(
                'awz.autform', Events::CHECK_EMAIL,
                array(
                    'component'=>$this,
                    'email'=>&$email
                )
            );
            $event->send();
        }

        return (string) $email;
    }

    /**
     * Проверка и форматирование номера телефона
     *
     * @param string $phone грязный номер
     * @return string отформатированный номер, если вернул обработчик
     */
    private function checkPhone(string $phone): string
    {
        if(!$phone){
            $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_PHONE'), 'phone'));
        }else{
            $event = new Event(
                'awz.autform', Events::CHECK_PHONE_V2,
                array(
                    'component'=>$this,
                    'phone'=>&$phone
                )
            );
            $event->send();
        }

        return (string) $phone;
    }

    /**
     * Проверка подключения обязательных модулей
     *
     * @return bool
     * @throws LoaderException
     */
    private function checkRequireModule(): bool
    {
        if(!Loader::includeModule('awz.autform')){
            $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_INSTALL'), 'system');
            return false;
        }
        return true;
    }

    /**
     * Генерация кода
     *
     * @param string $param email или телефон
     * @param string $mode режим формы (LOGIN_ACTIVE|LOGIN_SMS_ACTIVE|LOGIN_EMAIL_ACTIVE|REGISTER_SMS_ACTIVE|REGISTER_ACTIVE)
     * @return string|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function generateCode(string $param, string $mode): ?array
    {
        if($mode === 'REGISTER_ACTIVE'){
            if(!$param){
                $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_EMAIL'), 'email');
                return null;
            }
        }
        if($mode === 'LOGIN_SMS_ACTIVE'){
            if(!$param){
                $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_NOT_PHONE'), 'phone');
                return null;
            }
        }
        $event = new Event(
            Events::MODULE_ID, Events::GENERATE_CODE,
            array(
                'component'=>$this,
                'param'=>$param,
                'mode'=>$mode,
                'code'=>&$code
            )
        );
        $event->send();

        foreach ($event->getResults() as $evenResult)
        {
            if($resultParams = $evenResult->getParameters()){
                if($resultParams['result'] instanceof \Bitrix\Main\Result){
                    $resultData = $resultParams['result']->getData();
                    if(isset($resultData['id']) && $resultData['id']){
                        $resultData['item'] = \Awz\AutForm\CodesTable::getRowById($resultData['id']);
                    }
                    if(!$resultParams['result']->isSuccess()){
                        $this->addErrors($resultParams['result']->getErrors());
                        return $resultData;
                    }
                    if(!isset($resultData['id'])){
                        $this->addError(Loc::getMessage('AWZ_AUTFORM2_CMP_ERR_GEN'));
                    }
                    return $resultData;
                }
            }
        }

        $this->addError(Loc::getMessage('AWZ_AUTFORM2_CMP_ERR_GEN2'));
        return null;
    }

    /**
     * Проверка введенного когда на корректность
     *
     * @param string $param email или телефон
     * @param string $code код подтверждения
     * @param string $mode режим формы (LOGIN_ACTIVE|LOGIN_SMS_ACTIVE|LOGIN_EMAIL_ACTIVE|REGISTER_SMS_ACTIVE|REGISTER_ACTIVE)
     * @return array|null
     */
    protected function checkCode(string $param, string $code, string $mode): ?array
    {
        $result = new \Bitrix\Main\Result();
        $event = new Event(
            Events::MODULE_ID, Events::CHECK_CODE_V2,
            array(
                'component'=>$this,
                'param'=>$param,
                'code'=>$code,
                'mode'=>$mode,
                'result'=>$result
            )
        );
        $event->send();

        foreach ($event->getResults() as $evenResult)
        {
            if($resultParams = $evenResult->getParameters()) {
                if(isset($resultParams['result']) && $resultParams['result'] instanceof \Bitrix\Main\Result){
                    $result = $resultParams['result'];
                }
            }
        }

        if($result->isSuccess()){
            $data = $result->getData();
            if(empty($this->getErrors()) && $data['result']!='ok'){
                $this->addError(new Error(Loc::getMessage('AWZ_AUTFORM2_CMP_ERR_CHECK'), "code"));
            }
            if(!is_array($data)) $data = [];
            return $data;
        }else{
            foreach($result->getErrors() as $err){
                $this->addError($err);
            }
        }
        return null;
    }

    /**
     * Отправка кода подтверждения
     *
     * @return array|null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function getCode(string $mode = ''): ?array
    {

        if(!$this->checkRequireModule()) return null;

        if(!$mode) $mode = $this->arResult['VALUES']['mode'];

        $param = '';

        if($mode === 'REGISTER_ACTIVE'){
            $email = $this->checkEmail($this->arResult['VALUES']['email']);
            if(!empty($this->getErrors())) {
                return null;
            }
            if($this->arParams['LOGIN_EMAIL_ACTIVE'] != 'Y') {
                $userId = $this->findUserFromEmail($email);
                if ($userId) {
                    $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_USER_FOUND'), 'email');
                    return null;
                }
            }
            $param = $email;
        }
        elseif($mode === 'REGISTER_SMS_ACTIVE')
        {
            $phone = $this->checkPhone($this->arResult['VALUES']['phone']);
            if(!empty($this->getErrors())) {
                return null;
            }
            if($this->arParams['LOGIN_REGISTER'] != 'Y' || $this->arParams['LOGIN_SMS_ACTIVE'] != 'Y') {
                $userId = $this->findUserFromPhone($phone);
                if ($userId) {
                    $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_USER_FOUND'), 'phone');
                    return null;
                }
            }
            $param = $phone;
        }
        elseif($mode === 'LOGIN_SMS_ACTIVE')
        {
            $phone = $this->checkPhone($this->arResult['VALUES']['phone']);
            if(!empty($this->getErrors())) {
                return null;
            }
            if($this->arParams['LOGIN_REGISTER'] != 'Y' || $this->arParams['REGISTER_SMS_ACTIVE'] != 'Y') {
                $userId = $this->findUserFromPhone($phone);
                if(!$userId){
                    $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_USER_NOT_FOUND'), 'phone');
                    return null;
                }
            }
            $param = $phone;
        }
        elseif($mode === 'LOGIN_EMAIL_ACTIVE')
        {
            $email = $this->checkEmail($this->arResult['VALUES']['email']);
            if(!empty($this->getErrors())) {
                return null;
            }
            if($this->arParams['LOGIN_REGISTER'] != 'Y' || $this->arParams['REGISTER_ACTIVE'] != 'Y') {
                $userId = $this->findUserFromEmail($email);
                if(!$userId){
                    $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_USER_NOT_FOUND'), 'phone');
                    return null;
                }
            }
            $param = $email;
        }

        return $this->generateCode($param, $this->arResult['VALUES']['mode']);

    }

    /**
     * Проверка пароля алгоритмом битрикса
     *
     * @param int $userId
     * @param string $password
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function checkUserPassword(int $userId, string $password): bool
    {
        if(!$this->checkRightGroup('LOGIN_GROUPS')){
            $this->addError(Loc::getMessage('AWZ_AUTFORM2_MODULE_ERR_GROUP_LOGIN'), 'login');
            return false;
        }

        $main_query = new Query(UserTable::getEntity());
        if(method_exists($main_query, 'enablePrivateFields')){
            $main_query->enablePrivateFields();
        }
        $main_query->setSelect(array('PASSWORD'));
        $main_query->setFilter(array('=ID'=>$userId));
        $rs = $main_query->exec();
        $userData = $rs->fetch();

        /*$userData = UserTable::getList(array(
            'select'=>array('PASSWORD'),
            'filter'=>array('=ID'=>$userId)
        ))->fetch();*/

        if(!$userData) return false;

        if(defined("SM_VERSION") &&
            function_exists('CheckVersion') &&
            CheckVersion( '20.5.399', SM_VERSION)
        ){
            $salt = substr($userData['PASSWORD'], 0, (strlen($userData['PASSWORD']) - 32));
            $realPassword = substr($userData['PASSWORD'], -32);
            $password = md5($salt.$password);
            return ($password == $realPassword);
        }else{
            return Security\Password::equals($userData['PASSWORD'], $password);
        }
    }

    /**
     * Проверяет принадлежность последнего найденного пользователя к одной из групп
     *
     * @param string $param
     * @return bool
     */
    private function checkRightGroup(string $param): bool
    {
        $parameters = $this->arParams;

        $groups = $parameters[$param];
        $groupsDel = $parameters[$param.'_DEL'];

        if(empty($groups)) return false;
        if(empty($this->userGroups)) return false;
        foreach($this->userGroups as $groupId){
            if(in_array($groupId, $groupsDel)) return false;
        }
        foreach($this->userGroups as $groupId){
            if(in_array($groupId, $groups)) return true;
        }
        return false;
    }

    /**
     * Добавление ошибки
     *
     * @param string|Error $message
     * @param int|string $code
     */
    public function addError($message, $code=0)
    {
        if($message instanceof Error){
            $this->errorCollection[] = $message;
        }elseif(is_string($message)){
            $this->errorCollection[] = new Error($message, $code);
        }
    }

    /**
     * Добавление ошибок
     *
     * @param string[]|Error[] $errors
     */
    public function addErrors($errors)
    {
        foreach($errors as $message){
            $this->addError($message);
        }
    }

    /**
     * Массив ошибок
     *
     * Getting array of errors.
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errorCollection->toArray();
    }

    /**
     * Getting once error with the necessary code.
     *
     * @param string|int $code Code of error.
     * @return Error|null
     */
    public function getErrorByCode($code): ?Error
    {
        return $this->errorCollection->getErrorByCode($code);
    }
}

