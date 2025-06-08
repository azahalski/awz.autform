<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Awz\AutForm\CodesTable;
use Awz\AutForm\Events;
use Awz\AutForm\Helper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Entity\Query;
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
use Bitrix\Main\Service\GeoIp\Manager;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Internals\OrderPropsValueTable;

Loc::loadMessages(__FILE__);

class AwzAutFormComponent extends CBitrixComponent implements Controllerable, Errorable
{
    /** @var ErrorCollection */
    protected $errorCollection;

    /** @var  \Bitrix\Main\HttpRequest */
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
            'checkCode' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
            ],
            'checkAuth' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
            ],
            'checkRegister' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
            ],
            'getCode' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_POST
                    ]),
                    new ActionFilter\Csrf()
                ],
            ],
            'getAgreement' => [
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
            'COUNTRY_CODE',
            'THEME',
            'LOGIN_GROUPS',
            'LOGIN_GROUPS_DEL',
            'LOGIN_SMS_GROUPS',
            'LOGIN_SMS_GROUPS_DEL',
            'REGISTER_GROUPS',
            'PERSONAL_LINK',
            'AGREEMENT',
            'FIND_TYPE',
            'SALE_PROP',
            'REGISTER_LOGIN',
            'LOGIN_GROUPS_DEL2',
            'LOGIN_GROUPS_DEL3',
            'CHECK_LOGIN',
            'MODE_MESS'
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
        if(!$arParams['MODE_MESS'])
            $arParams['MODE_MESS'] = 'sms';

        if($arParams['REGISTER_LOGIN']!='Y')
            $arParams['REGISTER_LOGIN'] = 'N';

        if($arParams['CHECK_LOGIN']!='Y')
            $arParams['CHECK_LOGIN'] = 'N';

        if(!$arParams['SALE_PROP'])
            $arParams['SALE_PROP'] = 'PHONE';

        if(!$arParams['FIND_TYPE'])
            $arParams['FIND_TYPE'] = 'user';

        if(!$arParams['PERSONAL_LINK'])
            $arParams['PERSONAL_LINK'] = '/personal/';

        if(!is_array($arParams['LOGIN_GROUPS']))
            $arParams['LOGIN_GROUPS'] = array();

        if(!is_array($arParams['LOGIN_GROUPS_DEL']))
            $arParams['LOGIN_GROUPS_DEL'] = array();

        if(!is_array($arParams['LOGIN_GROUPS_DEL2']))
            $arParams['LOGIN_GROUPS_DEL2'] = array();

        if(!is_array($arParams['LOGIN_SMS_GROUPS']))
            $arParams['LOGIN_SMS_GROUPS'] = array();

        if(!is_array($arParams['REGISTER_GROUPS']))
            $arParams['REGISTER_GROUPS'] = array();

        if(!is_array($arParams['LOGIN_SMS_GROUPS_DEL']))
            $arParams['LOGIN_SMS_GROUPS_DEL'] = array();

        if(empty($arParams['LOGIN_SMS_GROUPS']) && empty($arParams['REGISTER_GROUPS']))
            $arParams['REGISTER_LOGIN'] = 'N';

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
            ShowError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_INSTALL'));
            return;
        }

        if(empty($this->arParams['REGISTER_GROUPS']) &&
            empty($this->arParams['LOGIN_SMS_GROUPS']) &&
            empty($this->arParams['LOGIN_GROUPS']))
        {
            ShowError(Loc::getMessage('AWZ_AUTFORM_CMP_NOT_SETTINGS'));
            return;
        }

        $this->includeComponentTemplate();
    }

    /**
     * Добавление ошибки
     *
     * @param string|Error $message
     * @param int $code
     */
    public function addError($message, int $code=0)
    {
        if($message instanceof Error){
            $this->errorCollection[] = $message;
        }elseif(is_string($message)){
            $this->errorCollection[] = new Error($message, $code);
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
        $parameters = $this->arParams;

        if($parameters['AGREEMENT'] && $this->request->get('AGREEMENT')!='Y'){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_CMP_NOT_CHECK_AGREEMENT'));
            return null;
        }

        if(!$phone){
            if($parameters['CHECK_LOGIN']==='Y'){
                $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_PHONE_LOGIN'));
            }else{
                $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_PHONE'));
            }
            return null;
        }
        if(!$password){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_ERR_PASSW'));
            return null;
        }

        if(!Loader::includeModule('awz.autform')){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_INSTALL'));
            return null;
        }

        $userId = $this->findUserFromLogin($phone);

        if(!$userId){
            $phone = $this->checkPhone($phone);

            if(!empty($this->getErrors())) {
                if($parameters['CHECK_LOGIN']==='Y'){
                    $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_LOGIN'));
                }
                return null;
            }

            $userId = $this->findUserFromPhone($phone);

            if(!$userId){
                $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_USER_NOT_FOUND'));
                return null;
            }
        }

        if(!$this->checkRightGroup('LOGIN_GROUPS')){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_ERR_GROUP_LOGIN'));
            return null;
        }

        $checkAuth = $this->checkUserPassword($userId, $password);
        if(!$checkAuth){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_ERR_PASSW'));
            return null;
        }

        global $USER;
        $USER->Authorize($userId);

        $event = new Event(
            'awz.autform', Events::AFTER_AUTH_PSW,
            array(
                'phone'=>$phone,
                'user'=>$userId,
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
     * Ajax Проверка кода и регистрация с авто-авторизацией
     *
     * @param string $phone грязный телефон
     * @param string $code
     * @return array|null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function checkRegisterAction(string $phone, string $code): ?array
    {
        $parameters = $this->arParams;

        if(empty($parameters['REGISTER_GROUPS'])){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_CMP_NOT_REGISTER'));
            return null;
        }

        $rsGroups = CGroup::GetList("c_sort", "asc", array());
        while($arGroups = $rsGroups->Fetch()){
            if(!in_array($arGroups['ID'], $parameters['REGISTER_GROUPS']))
                continue;
            $arGroups["NAME"] = CUtil::translit(
                $arGroups["NAME"],'ru',
                array("replace_space"=>"","replace_other"=>"")
            );
            if(strpos($arGroups["NAME"], 'admin')!==false){
                $this->addError(Loc::getMessage('AWZ_AUTFORM_CMP_NOT_REGISTER_ADM'));
                return null;
            }
        }

        if($parameters['AGREEMENT'] && $this->request->get('AGREEMENT')!='Y'){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_CMP_NOT_CHECK_AGREEMENT'));
            return null;
        }

        $code = trim(preg_replace('/([^0-9])/is','',$code));

        if(!$phone){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_PHONE'));
            return null;
        }

        if(!$code){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_SMSCODE'));
            return null;
        }

        if(!Loader::includeModule('awz.autform')){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_INSTALL'));
            return null;
        }

        $phone = $this->checkPhone($phone);

        if(!empty($this->getErrors())) {
            return null;
        }

        $userId = $this->findUserFromPhone($phone);

        if($userId){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_USER_REGISTER_FOUND'));
            return null;
        }

        $curDate = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());

        $checkRes = CodesTable::getList(array(
            'select'=>array('*'),
            'filter'=>array(
                '=PHONE'=>$phone,
                '>EXPIRED_DATE'=>$curDate,
            ),
            'order'=>array(
                'ID'=>'DESC'
            )
        ))->fetch();

        if(!$checkRes){
            if($parameters['MODE_MESS']=='call'){
                $this->addError(Loc::getMessage('AWZ_AUTFORM_CODE_NOT_FOUND'));
            }else{
                $this->addError(Loc::getMessage('AWZ_AUTFORM_CODE_NOT_FOUND_CALL'));
            }
            return null;
        }

        $maxCount = intval(Option::get('awz.autform', 'MAX_CHECK', '3', ''));

        // required!!! not delete!!! max rate attack (default bitrix session is locked)
        if(bitrix_sessid() != $checkRes['PRM']['csrf']){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_TOKEN_EXPIRED'));
            return null;
        }

        if($maxCount <= intval($checkRes['PRM']['count'])){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_CODE_MAX_CHECK_LIMIT'));
            return null;
        }

        $event = new Event(
            'awz.autform', Events::CHECK_CODE,
            array(
                'phone'=>$phone,
                'code'=>$code,
                'entityRow'=>$checkRes,
                'request'=>$this->request
            )
        );
        $event->send();

        $result = array();
        if ($event->getResults()) {
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() == EventResult::SUCCESS) {
                    if($eventResultData = $eventResult->getParameters()){
                        if(!isset($eventResultData['result'])) continue;
                        $r = $eventResultData['result'];
                        if($r instanceof Result){
                            if($r->isSuccess()){
                                $result = $r->getData();
                            }else{
                                foreach($r->getErrors() as $error){
                                    $this->addError($error);
                                }
                                return null;
                            }
                            break;
                        }
                    }
                }
            }
        }

        $checkErr = $checkRes['CODE'] != $code;
        if(!empty($result)){
            $checkErr = $result['status'] != Helper::CHECK_OK;
        }

        if($checkErr){
            $checkRes['PRM']['count'] = intval($checkRes['PRM']['count']) + 1;
            CodesTable::update(array('ID'=>$checkRes['ID']), array('PRM'=>$checkRes['PRM']));
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_CODE_ERR'));
            return null;
        }else{

            //регистрация юзера
            $pass = Random::getStringByAlphabet(
                12,
                Random::ALPHABET_NUM|Random::ALPHABET_ALPHALOWER|Random::ALPHABET_ALPHAUPPER|Random::ALPHABET_SPECIAL,
                true
            );
            $user = new CUser;

            $arFieldsUser = Array(
                "LOGIN"             => $phone,
                "ACTIVE"            => "Y",
                "PASSWORD"          => $pass,
                "CONFIRM_PASSWORD"  => $pass,
                "GROUP_ID"=>$parameters['REGISTER_GROUPS'],
                "PERSONAL_PHONE"=>$phone,
                "PERSONAL_MOBILE"=>$phone
            );

            $emailRequired = Option::get('main', 'new_user_email_required') === 'Y' ? 'Y' : 'N';
            if($emailRequired == 'Y'){
                $arFieldsUser['EMAIL'] = time().Random::getString(5).'@noemail.gav';
            }

            // add Guest ID
            $userId = $user->Add($arFieldsUser);
            if(!$userId){
                $this->addError($user->LAST_ERROR);
                return null;
            }

            global $USER;
            $USER->Authorize($userId);

            $event = new Event(
                'awz.autform', Events::AFTER_AUTH_SMS,
                array(
                    'phone'=>$phone,
                    'user'=>$userId,
                    'request'=>$this->request,
                    'params'=>$parameters
                )
            );
            $event->send();

            return array(
                'user'=>$userId
            );

        }
    }


    /**
     * Ajax проверка кода
     *
     * @param string $phone грязный телефон
     * @param string $code
     * @return array|null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function checkCodeAction(string $phone, string $code): ?array
    {
        $parameters = $this->arParams;

        if($parameters['AGREEMENT'] && $this->request->get('AGREEMENT')!='Y'){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_CMP_NOT_CHECK_AGREEMENT'));
            return null;
        }

        $code = trim(preg_replace('/([^0-9])/is','',$code));

        if(!$phone){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_PHONE'));
            return null;
        }

        if(!$code){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_SMSCODE'));
            return null;
        }

        if(!Loader::includeModule('awz.autform')){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_INSTALL'));
            return null;
        }

        $phone = $this->checkPhone($phone);

        if(!empty($this->getErrors())) {
            return null;
        }

        $userId = $this->findUserFromPhone($phone);

        if(!$userId){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_USER_NOT_FOUND'));
            return null;
        }

        $curDate = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());

        $checkRes = CodesTable::getList(array(
            'select'=>array('*'),
            'filter'=>array(
             '=PHONE'=>$phone,
             '>EXPIRED_DATE'=>$curDate,
            ),
            'order'=>array(
                'ID'=>'DESC'
            )
        ))->fetch();

        if(!$checkRes){
            if($parameters['MODE_MESS']=='call'){
                $this->addError(Loc::getMessage('AWZ_AUTFORM_CODE_NOT_FOUND_CALL'));
            }else{
                $this->addError(Loc::getMessage('AWZ_AUTFORM_CODE_NOT_FOUND'));
            }
            return null;
        }

        $maxCount = intval(Option::get('awz.autform', 'MAX_CHECK', '3', ''));

        // required!!! not delete!!! max rate attack (default bitrix session is locked)
        if(bitrix_sessid() != $checkRes['PRM']['csrf']){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_TOKEN_EXPIRED'));
            return null;
        }

        if($maxCount <= intval($checkRes['PRM']['count'])){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_CODE_MAX_CHECK_LIMIT'));
            return null;
        }

        $event = new Event(
            'awz.autform', Events::CHECK_CODE,
            array(
                'phone'=>$phone,
                'code'=>$code,
                'entityRow'=>$checkRes,
                'request'=>$this->request
            )
        );
        $event->send();

        $result = array();
        if ($event->getResults()) {
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() == EventResult::SUCCESS) {
                    if($eventResultData = $eventResult->getParameters()){
                        if(!isset($eventResultData['result'])) continue;
                        $r = $eventResultData['result'];
                        if($r instanceof Result){
                            if($r->isSuccess()){
                                $result = $r->getData();
                            }else{
                                foreach($r->getErrors() as $error){
                                    $this->addError($error);
                                }
                                return null;
                            }
                            break;
                        }
                    }
                }
            }
        }

        $checkErr = $checkRes['CODE'] != $code;
        if(!empty($result)){
            $checkErr = $result['status'] != Helper::CHECK_OK;
        }

        if($checkErr){
            $checkRes['PRM']['count'] = intval($checkRes['PRM']['count']) + 1;
            CodesTable::update(array('ID'=>$checkRes['ID']), array('PRM'=>$checkRes['PRM']));
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_CODE_ERR'));
            return null;
        }else{

            global $USER;
            $USER->Authorize($userId);

            $event = new Event(
                'awz.autform', Events::AFTER_AUTH_SMS,
                array(
                    'phone'=>$phone,
                    'user'=>$userId,
                    'request'=>$this->request,
                    'params'=>$parameters
                )
            );
            $event->send();

            return array(
                'user'=>$userId
            );

        }
    }


    /**
     * Генерация и отправка кода
     *
     * @param string $phone
     * @param string $fmode
     * @return array|null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getCodeAction(string $phone, string $fmode): ?array
    {
        $parameters = $this->arParams;

        if($parameters['AGREEMENT'] && $this->request->get('AGREEMENT')!='Y'){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_CMP_NOT_CHECK_AGREEMENT'));
            return null;
        }

        if(!$phone){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_PHONE'));
            return null;
        }

        if(!Loader::includeModule('awz.autform')){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_INSTALL'));
            return null;
        }

        $phone = $this->checkPhone($phone);

        if(!empty($this->getErrors())) {
            return null;
        }

        $userId = $this->findUserFromPhone($phone);

        $registerMode = strpos($fmode, 'register')!==false;

        if($registerMode){
            if($userId){

                if(!$this->checkRightGroup('LOGIN_SMS_GROUPS')){
                    $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_USER_REGISTER_FOUND'));
                    return null;
                }else{
                    $registerMode = false;
                }

            }
        }
        if(!$registerMode){
            if(!$userId){
                if($parameters['REGISTER_LOGIN'] == 'Y'){
                    $registerMode = true;
                }else{
                    $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_USER_NOT_FOUND'));
                    return null;
                }
            }else{
                if(!$this->checkRightGroup('LOGIN_SMS_GROUPS')){
                    $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_ERR_GROUP_LOGIN_SMS'));
                    return null;
                }
            }

        }

        $code = $this->generateCode($phone);

        if(!$code){
            if($this->getErrorByCode(105)){
                return array(
                    'phone'=>$phone,
                    'step'=>'active-code'.($registerMode ? '-register' : '')
                );
            }
            return null;
        }

        $startCode = $code;
        $event = new Event(
            'awz.autform', Events::SEND_SMS_CODE,
            array(
                'phone'=>$phone,
                'user'=>$userId,
                'code'=>&$code,
                'params'=>$parameters,
                'request'=>$this->request
            )
        );
        $event->send();

        $result = array();
        if ($event->getResults()) {
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() == EventResult::SUCCESS) {
                    if($eventResultData = $eventResult->getParameters()){
                        if(!isset($eventResultData['result'])) continue;
                        $r = $eventResultData['result'];
                        if($r instanceof Result){
                            if($r->isSuccess()){
                                $result = $r->getData();
                            }else{
                                foreach($r->getErrors() as $error){
                                    $this->addError($error);
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }

        if(!empty($this->getErrors())) {
            $this->deleteCode($phone, $startCode);
            return null;
        }

        if(empty($result)){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_EVENT',array('#EVENT_NAME#'=> Events::SEND_SMS_CODE)));
            $this->deleteCode($phone, $startCode);
            return null;
        }

        if($startCode != $code){
            $this->updateCode($phone, $startCode, $code);
        }

        if(!isset($result['phone'])){
            $result['phone'] = $phone;
        }

        if($registerMode && $userId){
            $result['step'] = 'active-code';
        }elseif($registerMode && !$userId){
            $result['step'] = 'active-code-register';
        }else{
            $result['step'] = 'active-code';
        }

        return $result;

    }


    /**
     * Текст соглашения
     *
     * @return array|null
     */
    public function getAgreementAction(): ?array
    {
        $parameters = $this->arParams;

        $agreement = $parameters['AGREEMENT'];

        if(!$agreement){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_CMP_NOT_AGREMENT'));
            return null;
        }

        $agreementOb = new Agreement($agreement);
        if (!$agreementOb->isExist() || !$agreementOb->isActive())
        {
            $this->addError(Loc::getMessage('AWZ_AUTFORM_CMP_NOT_AGREMENT'));
            return null;
        }

        return array(
            'text'=>$agreementOb->getText()
        );
    }

    /**
     * Проверка пароля алгоритмом битрикса
     *
     * @param $userId
     * @param $password
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function checkUserPassword($userId, $password): bool
    {
        if(!$this->checkRightGroup('LOGIN_GROUPS')){
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

        if(!$login){
            return null;
        }

        $event = new Event(
            'awz.autform', Events::FIND_USER_FROM_LOGIN,
            array(
                'login'=>$login,
                'params'=>$parameters,
                'request'=>$this->request
            )
        );
        $event->send();

        $findUser = 0;
        if ($event->getResults()) {
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() == EventResult::SUCCESS) {
                    if($eventResultData = $eventResult->getParameters()){
                        if(!isset($eventResultData['result'])) continue;
                        $r = $eventResultData['result'];
                        if($r instanceof Result){
                            if($r->isSuccess()){
                                $data = $r->getData();
                                if(isset($data['user'])){
                                    $findUser = $data['user'];
                                }
                            }else{
                                foreach($r->getErrors() as $error){
                                    $this->addError($error);
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }
        if($findUser){
            return (int) $findUser;
        }
        if(!empty($this->getErrors())){
            return null;
        }

        $filter = array(
            array(
                'LOGIC'=>'OR',
                '=LOGIN'=>$login
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
     * @param $phone
     * @return int|null
     * @throws ArgumentException
     * @throws LoaderException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function findUserFromPhone($phone): ?int
    {
        $parameters = $this->arParams;

        $preparePhone = htmlspecialcharsEx(trim($phone));
        $phone = preg_replace('/([^0-9])/','',$phone);

        $event = new Event(
            'awz.autform', Events::FIND_USER,
            array(
                'preparePhone'=>$preparePhone,
                'phone'=>$phone,
                'params'=>$parameters,
                'request'=>$this->request
            )
        );
        $event->send();

        $findUser = 0;
        if ($event->getResults()) {
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() == EventResult::SUCCESS) {
                    if($eventResultData = $eventResult->getParameters()){
                        if(!isset($eventResultData['result'])) continue;
                        $r = $eventResultData['result'];
                        if($r instanceof Result){
                            if($r->isSuccess()){
                                $data = $r->getData();
                                if(isset($data['user'])){
                                    $findUser = $data['user'];
                                }
                            }else{
                                foreach($r->getErrors() as $error){
                                    $this->addError($error);
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }
        if($findUser){
            return (int) $findUser;
        }
        if(!empty($this->getErrors())){
            return null;
        }

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
     * Проверка и форматирование номера телефона
     *
     * @param string $phone грязный номер
     * @return string отформатированный номер, если вернул обработчик
     */
    private function checkPhone(string $phone): string
    {
        $parameters = $this->arParams;

        $event = new Event(
            'awz.autform', Events::CHECK_PHONE,
            array(
                'phone'=>&$phone,
                'params'=>$parameters,
                'request'=>$this->request
            )
        );
        $event->send();

        $phoneFormated = '';
        if ($event->getResults()) {
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() == EventResult::SUCCESS) {
                    if($eventResultData = $eventResult->getParameters()){
                        if(!isset($eventResultData['result'])) continue;
                        $r = $eventResultData['result'];
                        if($r instanceof Result){
                            if($r->isSuccess()){
                                $data = $r->getData();
                                //если нужно прекратить применение обработчиков
                                if(isset($data['phone'])){
                                    $phoneFormated = (string) $data['phone'];
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
        if($phoneFormated) $phone = $phoneFormated;
        return (string) $phone;
    }

    /**
     * Удаление кода
     *
     * @param string $phone форматированный номер
     * @param string $code код
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function deleteCode(string $phone, string $code): void
    {
        $checkRes = CodesTable::getList(array(
            'select'=>array('ID'),
            'filter'=>array(
                '=PHONE'=>$phone,
                '=CODE'=>$code
            )
        ));
        while($data = $checkRes->fetch()){
            CodesTable::delete($data);
        }
    }

    /**
     * Замена кода
     *
     * @param string $phone форматированный номер
     * @param string $oldCode старый код
     * @param string $newCode новый код
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function updateCode(string $phone, string $oldCode, string $newCode): void
    {
        $checkRes = CodesTable::getList(array(
            'select'=>array('ID'),
            'filter'=>array(
                '=PHONE'=>$phone,
                '=CODE'=>$oldCode
            )
        ));
        while($data = $checkRes->fetch()){
            CodesTable::update($data, array('CODE'=>$newCode));
        }
    }

    /**
     * Генерация кода и запись в базу
     *
     * @param string $phone форматированный номер
     * @return string|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function generateCode(string $phone): ?string
    {
        $parameters = $this->arParams;

        if(!$phone){
            $this->addError(Loc::getMessage('AWZ_AUTFORM_MODULE_NOT_PHONE'));
            return null;
        }

        $maxTime = intval(Option::get('awz.autform', 'MAX_TIME', '10', '')) * 60;
        $curDate = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());
        $expiredDate = \Bitrix\Main\Type\DateTime::createFromTimestamp(time()+$maxTime);

        $checkRes = CodesTable::getList(array(
            'select'=>array('*'),
            'filter'=>array(
                '=PHONE'=>$phone,
                '>EXPIRED_DATE'=>$curDate
            )
        ));
        if($data = $checkRes->fetch()){
            if($parameters['MODE_MESS']=='call'){
                $this->addError(Loc::getMessage('AWZ_AUTFORM_CODE_NOT_EXPIRED_CALL', array('#DATE#'=>$data['EXPIRED_DATE']->toString())), 105);
            }else{
                $this->addError(Loc::getMessage('AWZ_AUTFORM_CODE_NOT_EXPIRED', array('#DATE#'=>$data['EXPIRED_DATE']->toString())), 105);
            }
            return null;
        }

        $ip = Manager::getRealIp();
        $checkBanResult = Helper::checkLimits($phone, $ip);

        if(!$checkBanResult->isSuccess()) {
            foreach($checkBanResult->getErrors() as $err){
                $this->addError($err);
            }
            return null;
        }

        $code = Random::getStringByCharsets(6, '123456789');
        $r = CodesTable::add(array(
            'PHONE'=>$phone,
            'CREATE_DATE'=>$curDate,
            'EXPIRED_DATE'=>$expiredDate,
            'IP_STR'=>$ip,
            'PRM'=>array(
                'count'=>0,
                'csrf'=>bitrix_sessid()
            ),
            'CODE'=>$code
        ));

        if($r->isSuccess()){
            return $code;
        }else{
            foreach($r->getErrors() as $error){
                $this->addError($error);
            }
        }
        return null;
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
}

