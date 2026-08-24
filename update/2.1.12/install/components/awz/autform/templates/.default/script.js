'use strict';

function AwzAutFormComponent(){}

AwzAutFormComponent.prototype = {
    modeOptions: {
        //вход по паролю с смс
        login: {
            show: ['buttons-aut','group-password'],
            hide: ['buttons-checkcode', 'buttons-getcode', 'buttons-register', 'group-smscode'],
            title: 'AWZ_AUTFORM_TMPL_TITLE_AUTH',
            active_link: 'login'
        },
        //вход по паролю без смс
        loginnosms: {
            show: ['buttons-aut','group-password'],
            hide: ['buttons-checkcode', 'buttons-getcode', 'buttons-register', 'group-smscode', 'group-smslink'],
            title: 'AWZ_AUTFORM_TMPL_TITLE_AUTH',
            active_link: 'login'
        },
        //вход по смс (выслать код)
        loginsms: {
            show: ['buttons-getcode'],
            hide: ['buttons-checkcode', 'buttons-aut','group-password', 'buttons-register', 'group-smscode'],
            title: 'AWZ_AUTFORM_TMPL_TITLE_AUTHSMS',
            active_link: 'loginsms'
        },
        //подтверждение кода в смс (проверка кода)
        logincode: {
            show: ['buttons-checkcode','group-smscode'],
            hide: ['buttons-aut', 'buttons-getcode', 'buttons-register', 'group-password'],
            title: 'AWZ_AUTFORM_TMPL_TITLE_AUTHSMS',
            active_link: 'loginsms'
        },
        //регистрация по смс (выслать код)
        register: {
            show: ['buttons-getcode'],
            hide: ['buttons-aut', 'buttons-register','buttons-checkcode', 'group-password','group-smscode'],
            title: 'AWZ_AUTFORM_TMPL_TITLE_REGISTER',
            active_link: 'register'
        },
        //подтверждение регистрации (проверка кода)
        registercode: {
            show: ['buttons-register','group-smscode'],
            hide: ['buttons-aut', 'buttons-getcode','buttons-checkcode', 'group-password'],
            title: 'AWZ_AUTFORM_TMPL_TITLE_REGISTER',
            active_link: 'register'
        }
    },
    activate: function (options) {
        if(typeof options !== 'object') options = {};
        this.theme = (!!options.theme ? options.theme : false) || 'red';
        this.hiddenReg = (!!options.hiddenReg ? (options.hiddenReg==='Y') : false);
        this.checkLogin = (!!options.checkLogin ? (options.checkLogin==='Y') : false);
        this.AGREEMENT = (!!options.AGREEMENT ? options.AGREEMENT : false) || '';
        this.lang = (!!options.lang ? options.lang : false) || {};
        if(typeof this.lang !==  'object') this.lang = {};
        this.ajaxTimer = 100;
        this.debug = false;
        this.autForm = BX(this.autFormId);
        if(this.hiddenReg){
            this.modeOptions.register.title = this.modeOptions.loginsms.title;
            this.modeOptions.registercode.title = this.modeOptions.loginsms.title;
            this.modeOptions.register.active_link = this.modeOptions.loginsms.active_link;
            this.modeOptions.registercode.active_link = this.modeOptions.loginsms.active_link;
        }
        this.modes = (!!options.modes ? options.modes : false) || [];
        this.setMode((!!options.mode ? options.mode : false) || 'login');
        this.initHandlers();
    },
    setMode: function(mode){
        if(!this.modeOptions.hasOwnProperty(mode)) return;
        this.mode = mode;
        this.checkHidden();
    },
    loc: function(code){
        return this.lang.hasOwnProperty(code) ? this.lang[code] : code;
    },
    initHandlers: function(){
        BX.bind(BX(this.autFormId+'_lnk'), 'click', BX.proxy(this.open, this));
    },
    handlerKeydown: function(e){
        if(!e) return;
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();

            if(e.target.name === 'awz-password'){
                this.checkAuthAction();
            }else if(this.mode){
                if(this.mode === 'logincode'){
                    this.checkCodeAction();
                }else if(this.mode === 'registercode'){
                    this.checkRegisterAction();
                }else if(this.mode === 'loginsms'){
                    this.getCodeAction();
                }else if(this.mode === 'register'){
                    this.getCodeAction();
                }
            }

            return false;
        }
    },
    open: function(e){
        if(!!e)
            e.preventDefault();
        this.createPopup();
        BX.append(this.loginForm(), BX(this.autFormId+'_ac'));
        this.checkHidden();
        this.popupSize();
    },
    close: function(e){
        if(!!e)
            e.preventDefault();

        if(!!BX(this.autFormId+'_content_agreement')){
            BX.removeClass(BX(this.autFormId+'_form'),'awz-autform-hide');
            BX(this.autFormId+'_content_agreement').remove();
            this.popupSize();
            return;
        }

        BX(this.autFormId+'_modal').remove();
    },
    checkHidden: function(){

        if(!this.mode) return;

        if(BX(this.autFormId+'_link_login'))
            BX.removeClass(BX(this.autFormId+'_link_login'), 'active');
        if(BX(this.autFormId+'_link_loginsms'))
            BX.removeClass(BX(this.autFormId+'_link_loginsms'), 'active');
        if(BX(this.autFormId+'_link_register'))
            BX.removeClass(BX(this.autFormId+'_link_register'), 'active');

        var k;
        for (k in this.modeOptions[this.mode].show){
            BX.removeClass(BX('awz-autform-form-'+this.modeOptions[this.mode].show[k]),'awz-autform-hide');
        }
        for (k in this.modeOptions[this.mode].hide){
            BX.addClass(BX('awz-autform-form-'+this.modeOptions[this.mode].hide[k]),'awz-autform-hide');
        }

        //console.log(this.loc(this.modeOptions[this.mode].title));
        if(BX(this.autFormId+'_title'))
            BX.adjust(BX(this.autFormId+'_title'), {html: this.loc(this.modeOptions[this.mode].title)});

        if(!this.AGREEMENT){
            BX.addClass(BX('awz-autform-form-agreement'),'awz-autform-hide');
        }else{
            BX.removeClass(BX('awz-autform-form-agreement'),'awz-autform-hide');
        }

        var lnk_active = BX(this.autFormId+'_link_'+this.modeOptions[this.mode]['active_link']);
        if(lnk_active)
            BX.addClass(lnk_active, 'active');

        if(this.checkLogin && this.mode && BX(this.autFormId+'_phone_label')){
            if(['login','loginnosms'].indexOf(this.mode)>-1){
                BX.adjust(BX(this.autFormId+'_phone_label'), {text: this.loc('AWZ_AUTFORM_TMPL_LABEL_PHONE_LOGIN')});
            }else{
                BX.adjust(BX(this.autFormId+'_phone_label'), {text: this.loc('AWZ_AUTFORM_TMPL_LABEL_PHONE')});
            }
        }

        var len_fix = 0;
        if(typeof this.modes === 'object')
            len_fix = this.modes.length - (this.hiddenReg ? 1 : 0);
        if(this.modes && len_fix < 2){
            if(BX(this.autFormId+'_links'))
                BX(this.autFormId+'_links').remove();
        }

    },
    readOkAction: function(e){
        if(!!e)
            e.preventDefault();

        BX.removeClass(BX(this.autFormId+'_form'),'awz-autform-hide');
        BX(this.autFormId+'_content_agreement').remove();
        this.popupSize();
    },
    clickFormLink: function(e){
        if(!!e)
            e.preventDefault();
        var el = BX(e.target);
        if(!el) return;
        var type = el.getAttribute('data-type');
        this.setMode(type);

        this.deleteErr();
        this.deleteMessage();

        if(!!BX(this.autFormId+'_content_agreement')){
            BX.removeClass(BX(this.autFormId+'_form'),'awz-autform-hide');
            BX(this.autFormId+'_content_agreement').remove();
        }

        this.popupSize();

    },
    agreementAction: function(e){
        if(!!e)
            e.preventDefault();
        var parent = this;

        this.showLoader();
        setTimeout(function(){
            BX.ajax.runComponentAction('awz:autform', 'getAgreement', {
                mode: 'class',
                data: {
                    fmode: parent.mode,
                    signedParameters: parent.signedParameters,
                    method: 'POST'
                }
            }).then(function (response) {
                parent.deleteErr();
                parent.deleteMessage();
                parent.hideLoader();
                if(response && response.hasOwnProperty('data') && response['data'] && response['data'].hasOwnProperty('text')){

                    BX.addClass(BX(parent.autFormId+'_form'),'awz-autform-hide');
                    BX.append(
                        BX.create(
                            {
                                tag: 'div',
                                attrs:{
                                    id: parent.autFormId+'_content_agreement',
                                    className: 'awz-autform-message-agreement'
                                },
                                children: [
                                    BX.create({
                                        tag: 'div',
                                        attrs:{
                                            className: 'awz-autform-message-agreement-content'
                                        },
                                        text: response['data'].text
                                    }),
                                    BX.create(
                                        {
                                            tag: 'div',
                                            attrs:{
                                                className: 'awz-autform-form-buttons'
                                            },
                                            children: [
                                                BX.create({
                                                    tag: 'button',
                                                    props: {
                                                    },
                                                    events: {
                                                        click: BX.proxy(parent.readOkAction, parent)
                                                    },
                                                    text: parent.loc('AWZ_AUTFORM_TMPL_LABEL_BTN_READ')
                                                }),
                                            ]
                                        }
                                    ),
                                ]
                            }
                        ),
                        BX(parent.autFormId+'_ac')
                    );
                }
            }, function (response) {
                parent.showErrors(response);
            });
        },this.ajaxTimer);
    },
    checkRegisterAction: function(e){
        if(!!e)
            e.preventDefault();
        var parent = this;

        this.showLoader();

        setTimeout(function(){
            BX.ajax.runComponentAction('awz:autform', 'checkRegister', {
                mode: 'class',
                data: {
                    AGREEMENT: parent.getAgreementCheckBox(),
                    phone: BX(parent.autFormId+'_phone').value,
                    code: BX(parent.autFormId+'_smscode').value,
                    fmode: parent.mode,
                    signedParameters: parent.signedParameters,
                    method: 'POST'
                }
            }).then(function (response) {
                parent.deleteErr();
                parent.deleteMessage();
                parent.hideLoader();
                if(response && response.hasOwnProperty('data') && response['data'] && response['data'].hasOwnProperty('user')){
                    window.location.reload();
                }
            }, function (response) {
                parent.showErrors(response);
            });
        },this.ajaxTimer);
    },
    checkCodeAction: function(e){
        if(!!e)
            e.preventDefault();
        var parent = this;

        this.showLoader();

        setTimeout(function(){
            BX.ajax.runComponentAction('awz:autform', 'checkCode', {
                mode: 'class',
                data: {
                    AGREEMENT: parent.getAgreementCheckBox(),
                    phone: BX(parent.autFormId+'_phone').value,
                    code: BX(parent.autFormId+'_smscode').value,
                    fmode: parent.mode,
                    signedParameters: parent.signedParameters,
                    method: 'POST'
                }
            }).then(function (response) {
                parent.deleteErr();
                parent.deleteMessage();
                parent.hideLoader();
                if(response && response.hasOwnProperty('data') && response['data'] && response['data'].hasOwnProperty('user')){
                    window.location.reload();
                }
            }, function (response) {
                parent.showErrors(response);
            });
        },this.ajaxTimer);
    },
    getCodeAction: function(e){
        if(!!e)
            e.preventDefault();
        var parent = this;

        this.showLoader();

        setTimeout(function(){
            BX.ajax.runComponentAction('awz:autform', 'getCode', {
                mode: 'class',
                data: {
                    AGREEMENT: parent.getAgreementCheckBox(),
                    phone: BX(parent.autFormId+'_phone').value,
                    fmode: parent.mode,
                    signedParameters: parent.signedParameters,
                    method: 'POST'
                }
            }).then(function (response) {
                parent.deleteErr();
                parent.deleteMessage();
                parent.hideLoader();
                if(response && response.hasOwnProperty('data') && response['data'] && response['data'].hasOwnProperty('phone')){
                    BX(parent.autFormId+'_phone').value = response['data']['phone'];
                }
                if(response && response.hasOwnProperty('data')) {
                    parent.showMessage(response['data']);
                }
                if(response && response.hasOwnProperty('data') && response['data'] && response['data'].hasOwnProperty('step')){
                    if(response['data']['step'] === 'active-code'){
                        parent.setMode('logincode');
                    }
                    if(response['data']['step'] === 'active-code-register'){
                        parent.setMode('registercode');
                    }
                }else{
                    parent.setMode('logincode');
                }
            }, function (response) {
                parent.showErrors(response);
                if(response && response.hasOwnProperty('data') && response['data'] && response['data'].hasOwnProperty('step')){
                    if(response['data']['step'] === 'active-code'){
                        parent.setMode('logincode');
                    }
                    if(response['data']['step'] === 'active-code-register'){
                        parent.setMode('registercode');
                    }
                }
            });
        },this.ajaxTimer);
    },
    checkAuthAction: function(e){
        if(!!e)
            e.preventDefault();
        var parent = this;

        this.showLoader();

        setTimeout(function(){
            BX.ajax.runComponentAction('awz:autform', 'checkAuth', {
                mode: 'class',
                data: {
                    AGREEMENT: parent.getAgreementCheckBox(),
                    phone: BX(parent.autFormId+'_phone').value,
                    password: BX(parent.autFormId+'_password').value,
                    fmode: parent.mode,
                    signedParameters: parent.signedParameters,
                    method: 'POST'
                }
            }).then(function (response) {
                parent.deleteErr();
                parent.deleteMessage();
                parent.hideLoader();
                if(response && response.hasOwnProperty('data') && response['data'] && response['data'].hasOwnProperty('user')){
                    window.location.reload();
                }
            }, function (response) {
                parent.showErrors(response);
            });
        },this.ajaxTimer);

    },
    showLoader: function(){
        BX.append(
            BX.create('div',
                {
                    attrs:{
                        id: this.autFormId+'_preloader',
                        className: 'awz-autform-preloader',
                    },
                    html: this.loader_template()
                }
            ),
            BX(this.autFormId+'_content')
        );
        this.popupSize();
    },
    hideLoader: function(){
        if(BX(this.autFormId+'_preloader')){
            BX(this.autFormId+'_preloader').remove();
        }
        var parent = this;
        this.popupSize();
        setTimeout(function(){
            parent.popupSize();
        },10);

    },

    deleteErr: function(){
        if(!!BX(this.autFormId+'_err')) BX(this.autFormId+'_err').remove();
    },
    deleteMessage: function(){
        if(!!BX(this.autFormId+'_msg')) BX(this.autFormId+'_msg').remove();
    },
    showErrors: function(data){
        if(data && data.hasOwnProperty('status') && data.status === 'error'){
            this.deleteErr();
            this.deleteMessage();
            BX.prepend(BX.create({
                tag: 'div',
                props: {
                    id: this.autFormId+'_err',
                    className: 'awz-autform-errors'
                }
            }), BX(this.autFormId + '_form'));
            if(!data.hasOwnProperty('errors')){
                data['errors'] = [{
                    code: 0,
                    customData: null,
                    message: this.loc('AWZ_AUTFORM_TMPL_ERR_AJAX')
                }];
            }
            var k;
            for(k in data['errors']){
                var err = data['errors'][k];
                if(!this.debug && err.message.indexOf('php')>-1){
                    //enabled debug mode to production
                }else{
                    BX.append(
                        BX.create({
                            tag: 'div',
                            props: {
                                className: 'awz-autform-error-row'
                            },
                            text: err.message
                        }),
                        BX(this.autFormId + '_err')
                    );
                }
            }
        }
        this.hideLoader();
    },
    showMessage: function(data){
        if(data && data.hasOwnProperty('message')){
            this.deleteMessage();
            BX.prepend(BX.create({
                tag: 'div',
                props: {
                    id: this.autFormId+'_msg',
                    className: 'awz-autform-messages'
                }
            }), BX(this.autFormId + '_form'));
            BX.append(
                BX.create({
                    tag: 'div',
                    props: {
                        className: 'awz-autform-message-row'
                    },
                    text: data.message
                }),
                BX(this.autFormId + '_msg')
            );
        }
        this.hideLoader();
    },
    createPopup: function(){
        //TODO unbind events
        BX.append(BX.create('div',{attrs:{id: this.autFormId+'_modal'}, props: {className: 'awz-autform-theme-'+this.theme}}), BX(document.body));
        BX.adjust(BX(this.autFormId+'_modal'), {html: this.template()});
        BX.bind(BX(this.autFormId+'_close'), 'click', BX.proxy(this.close, this));
        this.popupSize();
        var parent = this;
        BX.bind(window, 'resize', function(){
            parent.popupSize();
        });
    },
    popupSize: function(){
        var h = BX.height(window);
        var w = BX.width(window);
        if(w > 860) {
            if(!!BX(this.autFormId+'_close'))
                BX.removeClass(BX(this.autFormId+'_close'),'awz-autform-close-mobile');
            if(!!BX(this.autFormId+'_modal'))
                BX.removeClass(BX(this.autFormId+'_modal'),'awz-autform-modal-mobile');
            w = Math.ceil(w*0.8);
            h = Math.ceil(h*0.8);

            var h2 = BX.height(BX(this.autFormId+'_ac')) + BX.height(BX.findChild(BX(this.autFormId+'_content'), {'className':'awz-autform-modal-header'}, true, false));
            if(h2 && h > h2) h = h2;

            BX.adjust(
                BX(this.autFormId+'_content'), {
                    style: {
                        'margin-top':Math.ceil((BX.height(window)-h)/2)+'px',
                        'width':w+'px',
                        'height': h+'px'
                    }
                }
            );
        }else{
            BX.addClass(BX(this.autFormId+'_close'),'awz-autform-close-mobile');
            BX.addClass(BX(this.autFormId+'_modal'),'awz-autform-modal-mobile');
            w = Math.ceil(w);
            h = Math.ceil(h);
            BX.adjust(
                BX(this.autFormId+'_content'),
                {
                    style: {
                        'margin-top':'0px',
                        'width':w+'px',
                        'height': h+'px'
                    }
                }
            );
        }
        BX.adjust(
            BX(this.autFormId+'_body'),
            {
                style: {
                    'height': (h-BX.height(BX(this.autFormId+'_title')))+'px'
                }
            }
        );
    },
    getAgreementCheckBox: function(){
        var checkBox = BX(this.autFormId+'_agreement');
        if(!checkBox) return 'N';
        return checkBox.checked ? 'Y' : 'N'
    },
    loader_template: function(){
        var loader_mess = this.loc('AWZ_AUTFORM_TMPL_LOADER');
        return '<div class="awz-autform-preload"><div class="awz-autform-load">'+loader_mess+'</div></div>';
    },
    template: function(){

        var ht = '<div class="awz-autform-modal-content-bg"></div>' +
            '<a id="'+this.autFormId+'_close" class="awz-autform-close" href="#"><div>\n' +
            '        <div class="awz-autform-close-leftright"></div>\n' +
            '        <div class="awz-autform-close-rightleft"></div>\n' +
            '        <span class="awz-autform-close-close-btn">'+this.loc('AWZ_AUTFORM_TMPL_CLOSE')+'</span>\n' +
            '    </div></a>' +
            '<div class="awz-autform-modal-content">' +
                '<div id="'+this.autFormId+'_content" class="awz-autform-modal-content-wrap">'+
                    '<div class="awz-autform-modal-header" id="'+this.autFormId+'_title">'+
                    ''+this.loc('AWZ_AUTFORM_TMPL_TITLE_AUTH')+
                    '</div>'+
                    '<div class="awz-autform-modal-body" id="'+this.autFormId+'_body">' +
                        '<div id="'+this.autFormId+'_ac" class="awz-autform-contentWrap"></div>' +
                    '</div>'+
                '</div>' +
            '</div>';

        return ht;
    },
    loginForm: function(){

        var active_links = [];
        if(this.modes && this.modes.indexOf('login')>-1){
            active_links.push(BX.create({
                tag: 'a',
                props: {id: this.autFormId+'_link_login', href: '#'},
                attrs: {'data-type': 'login'},
                events: {
                    click: BX.proxy(this.clickFormLink, this)
                },
                text: this.loc('AWZ_AUTFORM_TMPL_TITLE_AUTH')
            }));
        }
        if(this.modes && this.modes.indexOf('loginsms')>-1){
            active_links.push(BX.create({
                tag: 'a',
                props: {id: this.autFormId+'_link_loginsms', href: '#'},
                attrs: {'data-type': 'loginsms'},
                events: {
                    click: BX.proxy(this.clickFormLink, this)
                },
                text: this.loc('AWZ_AUTFORM_TMPL_TITLE_AUTHSMS')
            }));
        }
        if(this.modes && this.modes.indexOf('register')>-1 && !this.hiddenReg){
            active_links.push(BX.create({
                tag: 'a',
                props: {id: this.autFormId+'_link_register', href: '#'},
                attrs: {'data-type': 'register'},
                events: {
                    click: BX.proxy(this.clickFormLink, this)
                },
                text: this.loc('AWZ_AUTFORM_TMPL_TITLE_REGISTER')
            }));
        }

        var links = BX.create({
            tag: 'div',
            props: {
                className: "awz-autform-form-links",
                id: this.autFormId+'_links'
            },
            children: active_links
        });



        var form = BX.create({
            tag: 'form',
            props: {
                id: this.autFormId + '_form'
            },
            children: [
                BX.create({
                    tag: 'div',
                    props: {
                        className: "awz-autform-form-group"
                    },
                    children: [
                        BX.create({
                            tag: 'label',
                            props: {for: this.autFormId+'_phone', id: this.autFormId+'_phone_label'},
                            text: this.loc('AWZ_AUTFORM_TMPL_LABEL_PHONE')
                        }),
                        BX.create({
                            tag: 'div',
                            props: {className:'awz-autform-inp-wrap'},
                            children: [
                                BX.create({
                                    tag: 'input',
                                    props: {
                                        id: this.autFormId+'_phone',
                                        type:'text',
                                        className:'awz-autform-form-control',
                                        name: 'awz-phone',
                                    },
                                    events: {
                                        keydown: BX.proxy(this.handlerKeydown, this)
                                    },
                                }),
                            ]
                        }),

                    ]
                }),
                BX.create({
                    tag: 'div',
                    props: {
                        className: "awz-autform-form-group",
                        id: "awz-autform-form-group-password"
                    },
                    children: [
                        BX.create({
                            tag: 'label',
                            props: {for: this.autFormId+'_password'},
                            children: [
                                BX.create({
                                    tag: 'span',
                                    text: this.loc('AWZ_AUTFORM_TMPL_LABEL_PASSW'),
                                }),
                                /*BX.create({
                                    tag: 'a',
                                    props: {
                                        href: '#',
                                        id: 'awz-autform-form-group-smslink',
                                    },
                                    events: {
                                        click: BX.proxy(this.getCodeAction, this)
                                    },
                                    text: this.loc('AWZ_AUTFORM_TMPL_LABEL_BTN_CODE2'),
                                })*/
                            ]
                        }),
                        BX.create({
                            tag: 'div',
                            props: {className:'awz-autform-inp-wrap'},
                            children: [
                                BX.create({
                                    tag: 'input',
                                    props: {
                                        id: this.autFormId+'_password',
                                        type:'password',
                                        className:'awz-autform-form-control',
                                        name: 'awz-password',
                                    },
                                    events: {
                                        keydown: BX.proxy(this.handlerKeydown, this)
                                    },
                                }),
                            ]
                        }),

                    ]
                }),
                BX.create({
                    tag: 'div',
                    props: {
                        className: "awz-autform-form-group",
                        id: "awz-autform-form-group-smscode"
                    },
                    children: [
                        BX.create({
                            tag: 'label',
                            props: {for: this.autFormId+'_smscode'},
                            children: [
                                BX.create({
                                    tag: 'span',
                                    text: this.loc('AWZ_AUTFORM_TMPL_LABEL_SMSCODE'),
                                }),
                                BX.create({
                                    tag: 'a',
                                    props: {
                                        href: '#'
                                    },
                                    events: {
                                        click: BX.proxy(this.getCodeAction, this)
                                    },
                                    text: this.loc('AWZ_AUTFORM_TMPL_LABEL_BTN_CODE2'),
                                })
                            ]
                        }),
                        BX.create({
                            tag: 'div',
                            props: {className:'awz-autform-inp-wrap'},
                            children: [
                                BX.create({
                                    tag: 'input',
                                    props: {
                                        id: this.autFormId+'_smscode',
                                        type:'text',
                                        className:'awz-autform-form-control',
                                        name: 'awz-smscode',
                                        autocomplete: 'off'
                                    },
                                    events: {
                                        keydown: BX.proxy(this.handlerKeydown, this)
                                    },
                                }),
                            ]
                        }),

                    ]
                }),

                BX.create({
                    tag: 'div',
                    props: {
                        className: "awz-autform-form-agreement",
                        id: "awz-autform-form-agreement"
                    },
                    children: [
                        BX.create({
                            tag: 'input',
                            props: {
                                type: 'checkbox',
                                checked: 'checked',
                                value: 'Y',
                                name: this.autFormId+'_agreement',
                                id: this.autFormId+'_agreement'
                            },
                        }),
                        BX.create({
                            tag: 'a',
                            props: {
                                href: '#'
                            },
                            events: {
                                click: BX.proxy(this.agreementAction, this)
                            },
                            text: this.loc('AWZ_AUTFORM_TMPL_LABEL_AGREEMENT')
                        }),
                    ]
                }),

                BX.create({
                    tag: 'div',
                    props: {
                        className: "awz-autform-form-buttons",
                        id: "awz-autform-form-buttons-aut"
                    },
                    children: [
                        BX.create({
                            tag: 'button',
                            props: {
                                id: this.autFormId+'_auth',
                                className:'awz-autform-form-control',
                            },
                            events: {
                                click: BX.proxy(this.checkAuthAction, this)
                            },
                            text: this.loc('AWZ_AUTFORM_TMPL_LABEL_BTN_AUTH')
                        }),
                    ]
                }),
                BX.create({
                    tag: 'div',
                    props: {
                        className: "awz-autform-form-buttons",
                        id: "awz-autform-form-buttons-register"
                    },
                    children: [
                        BX.create({
                            tag: 'button',
                            props: {
                                id: this.autFormId+'_auth',
                                className:'awz-autform-form-control',
                            },
                            events: {
                                click: BX.proxy(this.checkRegisterAction, this)
                            },
                            text: this.loc('AWZ_AUTFORM_TMPL_LABEL_BTN_REGISTER')
                        }),
                    ]
                }),
                BX.create({
                    tag: 'div',
                    props: {
                        className: "awz-autform-form-buttons",
                        id: "awz-autform-form-buttons-getcode"
                    },
                    children: [
                        BX.create({
                            tag: 'button',
                            props: {
                                id: this.autFormId+'_send',
                                className:'awz-autform-form-control',
                            },
                            events: {
                                click: BX.proxy(this.getCodeAction, this)
                            },
                            text: this.loc('AWZ_AUTFORM_TMPL_LABEL_BTN_CODE')
                        }),
                    ]
                }),
                BX.create({
                    tag: 'div',
                    props: {
                        className: "awz-autform-form-buttons",
                        id: "awz-autform-form-buttons-checkcode"
                    },
                    children: [
                        BX.create({
                            tag: 'button',
                            props: {
                                id: this.autFormId+'_send',
                                className:'awz-autform-form-control',
                            },
                            events: {
                                click: BX.proxy(this.checkCodeAction, this)
                            },
                            text: this.loc('AWZ_AUTFORM_TMPL_LABEL_BTN_CHECKCODE')
                        }),
                    ]
                })
            ]
        });

        return BX.create({
            tag: 'div',
            props: {
                className: "awz-autform-form-prepare-wrapper",
            },
            children: [
                links,
                form
            ]
        });

    }
};