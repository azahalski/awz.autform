(function() {
    'use strict';

    if (!!window.AwzAutFormComponentV2){
        return;
    }
    window.AwzAutFormComponentV2_Instances = {};
    window.AwzAutFormComponentV2 = function(options) {
        if(typeof options !== 'object') {
            throw new Error('options is not object');
        }
        if(!options.hasOwnProperty('siteId')) {
            throw new Error('options.siteId is required');
        }
        if(!options.hasOwnProperty('templateId')) {
            throw new Error('options.templateId is required');
        }
        if(!options.hasOwnProperty('autFormId')) {
            throw new Error('options.autFormId is required');
        }
        if(!options.hasOwnProperty('loadFormNodeId')) {
            throw new Error('options.loadFormNodeId is required');
        }
        if(!options.hasOwnProperty('templateName')) {
            throw new Error('options.templateName is required');
        }
        if(!options.hasOwnProperty('templateFolder')) {
            throw new Error('options.templateFolder is required');
        }
        if(!options.hasOwnProperty('signedParameters')) {
            throw new Error('options.signedParameters is required');
        }
        if(!options.hasOwnProperty('componentName')) {
            throw new Error('options.componentName is required');
        }

        this.autFormId = options.autFormId;
        this.loadFormNodeId = options.loadFormNodeId;

        this.siteId = options.siteId;
        this.templateId = options.templateId;
        this.templateName = options.templateName;
        this.templateFolder = options.templateFolder;
        this.componentName = options.componentName;
        this.signedParameters = options.signedParameters;
        this.ajaxTimer = (!!options.ajaxTimer ? options.ajaxTimer : false) || 100;
        this.debug = !!options.debug ? true : false;
        this.lang = (!!options.lang ? options.lang : false) || {};

        window.AwzAutFormComponentV2_Instances[this.autFormId] = this;

        var parent = this;

        $(document).on('click', '#awz-autform2__repeat_code_button', function(e){
            if(!!e){
                e.preventDefault();
            }
            $('#'+parent.loadFormNodeId).find('input[name="step"]').val("");
            var form_data = new FormData(
                $(this).closest('form')[0],
                $(this).closest('form').find('button[type="submit"]')[0]
            );
            parent.send(form_data);
        });
        $(document).on('click', '.awz-autform2__link', function(e){
            if(!!e){
                e.preventDefault();
            }
            $('#'+parent.loadFormNodeId).find('input[name="step"]').val("");
            parent.load($(this).attr('data-mode'));
        });
        $(document).on('click', '.awz-autform2__ui-ctl-icon-password-show', function(){
            var inp = $(this).parent().find('input');
            inp.attr('type', inp.attr('type')==='password' ? 'text' : 'password');
        });
        $(document).on('click', '#'+parent.loadFormNodeId+' button[type="submit"]', function(e){
            if(!!e){
                e.preventDefault();
            }
            var form_data = new FormData(
                $(this).closest('form')[0],
                $(this)[0]
            );
            parent.send(form_data);
        });

        this.load();

    };
    window.AwzAutFormComponentV2.prototype = {
        getInstance: function (formId)
        {
            if(!formId){
                formId = 'default';
            }
            if(!window.AwzAutFormComponentV2_Instances.hasOwnProperty(formId)){
                window.AwzAutFormComponentV2_Instances[formId] = this;
            }
            return window.AwzAutFormComponentV2_Instances[formId];
        },

        load: function(mode, clear){
            this.showLoader();
            if(mode) {
                $('#' + this.loadFormNodeId).find('form input[name="mode"]').val(mode);
            }
            var parent = this;
            if(clear){
                var formData = {
                    'signedParameters':parent.signedParameters,
                    'method':'POST',
                    'autFormId':parent.autFormId,
                    'mode':mode,
                    'SITE_ID':parent.siteId,
                    'SITE_TEMPLATE':parent.templateId
                };
            }else{
                var formData = new FormData(
                    $('#'+this.loadFormNodeId).find('form')[0],
                    $('#'+this.loadFormNodeId).find('button')[0]
                );
                formData.append('signedParameters', parent.signedParameters);
                formData.append('method', 'POST');
                formData.append('autFormId', parent.autFormId);
                formData.append('SITE_ID', parent.siteId);
                formData.append('SITE_TEMPLATE', parent.templateId);
            }

            setTimeout(function(){
                BX.ajax.runComponentAction('awz:autform2', 'getForm', {
                    mode: 'class',
                    data: formData
                }).then(function (response) {
                    parent.hideLoader();
                    BX.onCustomEvent(this, 'awz.autform2.beforeLoad', [response]);
                    if(response && response.hasOwnProperty('data') &&
                        response.hasOwnProperty('status') && response['status'] === 'success'
                    ){
                        $('#' + parent.loadFormNodeId).html(response['data']);
                    }
                    parent.resize();
                }, function (response) {
                    parent.hideLoader();
                    parent.showErrors(response);
                });
            },this.ajaxTimer);
        },

        send: function(formData) {
            this.showLoader();
            var parent = this;
            formData.append('signedParameters', parent.signedParameters);
            formData.append('method', 'POST');
            formData.append('autFormId', parent.autFormId);
            formData.append('SITE_ID', parent.siteId);
            formData.append('SITE_TEMPLATE', parent.templateId);

            setTimeout(function(){
                BX.ajax.runComponentAction('awz:autform2', 'sendForm', {
                    mode: 'class',
                    data: formData
                }).then(function (response) {
                    parent.hideLoader();
                    parent.hideErrors();
                    BX.onCustomEvent(this, 'awz.autform2.beforeSendLoad', [response]);
                    if(response && response.hasOwnProperty('data')
                        && response.hasOwnProperty('status') && response['status'] === 'success'
                    ){
                        $('#' + parent.loadFormNodeId).html(response['data']);
                    }
                    parent.resize();
                }, function (response) {
                    parent.hideLoader();
                    parent.showErrors(response);
                });
            },this.ajaxTimer);

        },

        resize: function(){
            BX.onCustomEvent(this, 'awz.autform2.resize');
        },
        hideErrors: function(){
            $('#' + this.loadFormNodeId).find('.awz-autform2__err p').remove();
            $('#' + this.loadFormNodeId).find('.ui-ctl-warning').removeClass('ui-ctl-warning');
            this.resize();
        },
        showErrors: function(response){
            this.hideErrors();
            BX.onCustomEvent(this, 'awz.autform2.errLoad', [response]);

            var codes = [];
            if(response && response.hasOwnProperty('status')
                && response['status'] === 'error'
            ){
                var k;
                for(k in response['errors']){
                    var err = response['errors'][k];
                    if(err.code) codes.push(err.code);
                    $('#' + this.loadFormNodeId).find('.awz-autform2__err').append('<p>'+err.message+'</p>');
                    if(err.code == 'login') {
                        $('#' + this.loadFormNodeId).find('input[name="login"]').parent().addClass('ui-ctl-warning');
                    }else if(err.code == 'email') {
                        $('#' + this.loadFormNodeId).find('input[name="email"]').parent().addClass('ui-ctl-warning');
                    }else if(err.code == 'phone') {
                        $('#' + this.loadFormNodeId).find('input[name="phone"]').parent().addClass('ui-ctl-warning');
                    }else if(err.code == 'password') {
                        $('#' + this.loadFormNodeId).find('input[name="password"]').parent().addClass('ui-ctl-warning');
                    }else if(err.code == 'code') {
                        $('#' + this.loadFormNodeId).find('input[name="code"]').parent().addClass('ui-ctl-warning');
                    }else if(err.code == 'name') {
                        $('#' + this.loadFormNodeId).find('input[name="name"]').parent().addClass('ui-ctl-warning');
                    }
                }
            }
            //console.log(codes.join());
            if(codes.join() == '107' && response && response.hasOwnProperty('data')){
                $('#' + this.loadFormNodeId).html(response['data']);
            }

            this.resize();
        },
        loaderTemplate: function(){
            return '<div class="awz-autform2__preload"><div class="awz-autform2__load"></div></div>';
        },
        showLoader: function(){
            $('#' + this.loadFormNodeId+' .awz-autform2__form-border').append(this.loaderTemplate());
            return this;
        },
        hideLoader: function(){
            $('#' + this.loadFormNodeId+' .awz-autform2__form-border').find('.awz-autform2__preload').remove();
            return this;
        },
    };

})();

