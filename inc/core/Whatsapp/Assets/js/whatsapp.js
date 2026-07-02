"use strict";
function Whatsapp(){
    var self = this;
    this.init = function(){
        self.get_qrcode();
        self.get_qrcode_evo();
        self.profiles();
        self.check_login();
        self.check_login_evo();
        self.template();
        self.import_contact();
        self.set_phone();
        self.sfix_phone();
    };

    this.profiles = function(){
        $(document).on("click", ".seclect-shedule-time a", function(){
            var type = $(this).data("time");
            var hours = false;
            switch(type) {
                case "daytime":
                    hours = [7,8,9,10,11,12,13,14,15,16,17,18];
                    break;
                case "nighttime":
                    hours = [18,19,20,21,22,23,0,1,2,3,4,5,6];
                    break;
                case "odd":
                    hours = [1,3,5,7,9,11,13,15,17,19,21,23];
                    break;
                case "even":
                    hours = [0,2,4,6,8,10,12,14,16,18,20,22];
                    break;
                case "all":
                    hours = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24];
                    break;
            }
            $(".schedule_time option").each(function(){
                var value = $(this).val();
                if( hours.includes( parseInt( value ) )  ){
                    $(this).attr("selected","selected");
                }else{
                    $(this).removeAttr("selected");
                }
                $(".schedule_time").change();
            });
        });
    };
    
    this.sfix_phone = function(){
        var sendToInput = document.querySelector(".post-schedule input#send_to");
        if (!sendToInput) return;
        var settings = {"async": true,"crossDomain": true,"url": "https://api.ip.sb/geoip","dataType": "jsonp","method": "GET","headers": {"Access-Control-Allow-Origin": "*"}};
        var iti = window.intlTelInput(sendToInput, {
            initialCountry: "auto", nationalMode: true, formatOnDisplay: true, placeholderNumberType: "MOBILE",
            geoIpLookup: function(callback) { $.ajax(settings).done(function (resp){ var countryCode = (resp && resp.country_code) ? resp.country_code : ""; callback(countryCode); iti.setCountry(countryCode); }); },
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@15.0.2/build/js/utils.js",
        });
        window.iti = iti;
        iti.promise.then(function() { $('#send_to').val(iti.getNumber().replace('+','')); });
        $('#send_to').on('blur', function () { $(this).val(iti.getNumber().replace('+','')); });
    };
    
    this.set_phone = function(){
        var phoneInput = document.querySelector(".modal input#phone");
        if (!phoneInput) return;
        var settings = {"async": true,"crossDomain": true,"url": "https://api.ip.sb/geoip","dataType": "jsonp","method": "GET","headers": {"Access-Control-Allow-Origin": "*"}};
        var iti = window.intlTelInput(phoneInput, {
            initialCountry: "auto", nationalMode: true, formatOnDisplay: true, placeholderNumberType: "MOBILE",
            geoIpLookup: function(callback) { $.ajax(settings).done(function (resp){ var countryCode = (resp && resp.country_code) ? resp.country_code : ""; callback(countryCode); iti.setCountry(countryCode); }); },
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@15.0.2/build/js/utils.js",
        });
        window.iti = iti;
        iti.promise.then(function() { $('#phone').val(iti.getNumber().replace('+','')); });
        $('#phone').on('blur', function () { $(this).val(iti.getNumber().replace('+','')); });
    };

    // ==================== FLUXO UNIFICADO DE CONEXÃO ====================
    // O QR Code e Passkey são tratados pelo mesmo endpoint /qrcode.
    // O WhatsApp decide qual método enviar automaticamente.
    // ====================================================================

    this.get_qrcode = function(){
        if( $(".wa-qr-code").length > 0 ){
            var instance_id = $(".wa-qr-code").data("instance-id");
            $.ajax({
                url: PATH + "whatsapp_profiles/get_qrcode/" + instance_id,
                type: 'GET',
                dataType: "json",
                success: function(result){
                    if(result.status == "success" && result.state == "connected"){
                        // Já conectado, redireciona
                        location.assign( PATH + "account_manager" );
                        return;
                    }
                    
                    if(result.status == "success" && result.method == "passkey"){
                        // 🔑 WhatsApp enviou PASSKEY em vez de QR
                        self.start_passkey(instance_id, result);
                        return;
                    }
                    
                    if(result.status == "success" && result.base64){
                        // 📷 QR Code tradicional
                        $(".wa-code").html('<img class="w-300 h-300" src="'+result.base64+'">');
                        return;
                    }
                    
                    // Erro ou pendente
                    $(".wa-code").html(`
                        <div class="alert alert-danger">
                            `+(result.message || 'Erro ao conectar')+`
                        </div>
                    `);
                },
                error: function(result){}
            });
        }
    };

    // ==================== PASSKEY (WebAuthn) ====================
    // Chamado automaticamente quando o WhatsApp envia passkey
    // ao invés de QR Code. O usuário não precisa fazer nada extra.
    // =============================================================

    this.base64ToArrayBuffer = function(base64) {
        var binaryString = atob(base64);
        var bytes = new Uint8Array(binaryString.length);
        for (var i = 0; i < binaryString.length; i++) { bytes[i] = binaryString.charCodeAt(i); }
        return bytes.buffer;
    };

    this.arrayBufferToBase64 = function(buffer) {
        var binary = '';
        var bytes = new Uint8Array(buffer);
        for (var i = 0; i < bytes.byteLength; i++) { binary += String.fromCharCode(bytes[i]); }
        return btoa(binary);
    };

    this.start_passkey = function(instance_id, data) {
        var self_ = this;

        // 🔒 Mostra informação de que está solicitando biometria
        $(".wa-code").html(`
            <div class="w-300 h-300 d-flex flex-column justify-content-center align-items-center m-auto border b-r-10 text-dark">
                <i class="fas fa-fingerprint fa-beat-fade fs-60 mb-3" style="color:#0dcaf0;"></i>
                <div><strong>Aguardando autenticação biométrica...</strong></div>
                <div class="small text-gray-600 mt-2">Use biometria, Face ID ou PIN para conectar</div>
            </div>
        `);

        // Decodificar challenge
        var challenge = self_.base64ToArrayBuffer(data.challenge);
        
        var publicKey = {
            challenge: challenge,
            rpId: data.rp_id || 'whatsapp.com',
            timeout: data.timeout || 30000,
            userVerification: 'required',
            allowCredentials: [],
        };

        // Verificar suporte a WebAuthn
        if (!navigator.credentials || !navigator.credentials.get) {
            $(".wa-code").html(`
                <div class="alert alert-warning">
                    WebAuthn não suportado neste navegador.<br>
                    Use Chrome, Edge ou Safari para conectar via passkey.
                </div>
            `);
            return;
        }

        // 🔐 Chamar WebAuthn API - navegador pede biometria/Face ID/PIN
        navigator.credentials.get({ publicKey: publicKey })
        .then(function(assertion) {
            // Enviar resposta WebAuthn para o servidor
            var response = {
                id: assertion.id,
                rawId: self_.arrayBufferToBase64(assertion.rawId),
                type: assertion.type,
                response: {
                    clientDataJSON: self_.arrayBufferToBase64(assertion.response.clientDataJSON),
                    authenticatorData: self_.arrayBufferToBase64(assertion.response.authenticatorData),
                    signature: self_.arrayBufferToBase64(assertion.response.signature),
                    userHandle: assertion.response.userHandle ?
                        self_.arrayBufferToBase64(assertion.response.userHandle) : null,
                }
            };

            $(".wa-code").html(`
                <div class="d-flex flex-column justify-content-center align-items-center m-auto p-20">
                    <i class="fas fa-check-circle text-success fs-50 mb-3"></i>
                    <div><strong>Biometria confirmada!</strong></div>
                    <div class="small text-gray-600 mt-2">Aguardando confirmação do WhatsApp...</div>
                </div>
            `);

            // Enviar resposta via AJAX
            $.ajax({
                url: PATH + "whatsapp_profiles/send_whatsmeow_passkey_response",
                type: 'POST',
                dataType: "json",
                data: {
                    instance_id: instance_id,
                    response: JSON.stringify(response),
                },
                timeout: 40000,
                success: function(result){
                    if(result.state == "connected"){
                        location.assign( PATH + "account_manager" );
                    }
                    else if(result.code){
                        // Mostrar código para confirmação no celular
                        if(result.skip_handoff_ux){
                            // UX automática - não precisa de código
                            self_.confirm_and_poll(instance_id);
                        } else {
                            // Mostrar código
                            $(".wa-code").html(`
                                <div class="border b-r-10 p-20 text-center">
                                    <div class="fs-14 text-gray-600 mb-3">Confirme o código no seu celular:</div>
                                    <h2 class="fw-bold" style="font-size:2.5rem;letter-spacing:8px;font-family:monospace;">`+result.code+`</h2>
                                    <p class="text-gray-600 mt-2 small">Digite este código no WhatsApp do seu celular</p>
                                    <button type="button" class="btn btn-info rounded-pill px-4 mt-3" onclick="Whatsapp.confirm_and_poll('`+instance_id+`')">
                                        <i class="fas fa-check me-2"></i>Já confirmei, finalizar
                                    </button>
                                </div>
                            `);
                        }
                    }
                    else {
                        // Tentar novamente
                        setTimeout(function(){ self_.start_passkey_poll(instance_id); }, 2000);
                    }
                },
                error: function(){
                    // Tentar polling direto
                    setTimeout(function(){ self_.start_passkey_poll(instance_id); }, 2000);
                }
            });
        })
        .catch(function(err) {
            $(".wa-code").html(`
                <div class="alert alert-warning">
                    Autenticação cancelada ou falhou: `+(err.message || 'erro desconhecido')+`
                </div>
            `);
        });
    };

    this.confirm_and_poll = function(instance_id) {
        $.ajax({
            url: PATH + "whatsapp_profiles/confirm_whatsmeow_passkey",
            type: 'POST',
            dataType: "json",
            data: { instance_id: instance_id },
            timeout: 15000,
            success: function(result){
                // Iniciar polling de login
                self.start_passkey_poll(instance_id);
            },
            error: function(){
                self.start_passkey_poll(instance_id);
            }
        });
    };

    this.start_passkey_poll = function(instance_id) {
        $(".wa-code").html(`
            <div class="d-flex flex-column justify-content-center align-items-center m-auto p-20">
                <i class="fas fa-spinner fa-spin fs-40 mb-3"></i>
                <div><strong>Finalizando pareamento...</strong></div>
            </div>
        `);
        
        // Reutiliza o check_login que já roteia WMEOW_ para whatsmeow
        $.ajax({
            url: PATH + "whatsapp_profiles/check_login/" + instance_id,
            type: 'GET',
            dataType: "json",
            timeout: 5000,
            success: function(result){
                if(result.status == "success"){
                    location.assign( PATH + "account_manager" );
                } else {
                    setTimeout(function(){ self.start_passkey_poll(instance_id); }, 2000);
                }
            },
            error: function(){
                setTimeout(function(){ self.start_passkey_poll(instance_id); }, 2000);
            }
        });
    };

    // ==================== FIM PASSKEY ====================

    this.get_qrcode_evo = function(){
        if( $(".wa-qr-code-evo").length > 0 ){
            var instance_id = $(".wa-qr-code-evo").data("instance-id");
            $.ajax({
                url: PATH + "whatsapp_evo_profiles/get_qrcode/" + instance_id,
                type: 'GET',
                dataType: "json",
                success: function(result){
                    if(result.base64 != undefined){
                        $(".wa-code").html('<img class="w-300 h-300" src="'+result.base64+'">');
                    }else{
                        $(".wa-code").html('<div class="alert alert-danger">'+result.message+'</div>');
                    }
                },
                error: function(result){}
            });
        }
    };
    
    this.check_login_evo = function(){
        if( $(".wa-qr-code-evo").length > 0 ){
            var instance_id = $(".wa-qr-code-evo").data("instance-id");
            $.ajax({
                url: PATH + "whatsapp_evo_profiles/check_login/" + instance_id,
                type: 'GET',
                dataType: "json",
                success: function(result){
                    if(result.status == "success"){
                        location.assign( PATH + "account_manager" );
                    }else{
                        setTimeout( function(){ self.check_login_evo(); } , 2000);
                    }
                },
                error: function(result){}
            });
        }
    };

    this.check_login = function(){
        if( $(".wa-qr-code").length > 0 ){
            var instance_id = $(".wa-qr-code").data("instance-id");
            $.ajax({
                url: PATH + "whatsapp_profiles/check_login/" + instance_id,
                type: 'GET',
                dataType: "json",
                success: function(result){
                    if(result.status == "success"){
                        location.assign( PATH + "account_manager" );
                    }else{
                        setTimeout( function(){ self.check_login(); } , 2000);
                    }
                },
                error: function(result){}
            });
        }
    };

    this.template = function(){
        $(document).on("click", ".btn-wa-add-section", function(){
            var option = $(".wa-template-data-section").html();
            var count_msg_item = $(".wa-template-section .wa-template-section-item").length;
            option = option.replace(/{count}/g, (count_msg_item + 1));
            Core.emoji("btn_msg_display_text_"+count_msg_item);
            $(".wa-template-section").append(option);
            $(".wa-empty").hide();
        });
        $(document).on("click", ".btn-wa-add-list-option", function(){
            var that = $(this);
            var section_count = $(this).parents(".wa-template-section-item").attr("data-count");
            var option = $(".wa-template-data-option").html();
            option = option.replace(/{count}/g, parseInt(section_count));
            $(this).parents(".wa-template-section-item").find(".wa-template-option").append(option);
            $(".wa-empty").hide();
        });
        $(document).on("click", ".btn-wa-add-option", function(){
            var option = $(".wa-template-data-option").html();
            var count_msg_item = $(".wa-template-option .wa-template-option-item").length;
            option = option.replace(/{count}/g, (count_msg_item + 1));
            $(".wa-template-option").append(option);
            $(".wa-empty").hide();
            Core.emoji("btn_msg_display_text_"+count_msg_item);
            if( count_msg_item >= 9 ){ $(".wa-template-wrap-add").addClass("d-none"); }
            else { $(".wa-template-wrap-add").removeClass("d-none"); }
        });
        $(document).on("click", ".wa-template-option-remove", function(){
            $(this).parents(".wa-template-option-item").remove();
            if( $(".wa-template-option .wa-template-option-item").length >= 10 ){ $(".wa-template-wrap-add").addClass("d-none"); }
            else { $(".wa-template-wrap-add").removeClass("d-none"); }
            if($(".wa-template-option .wa-template-option-item").length == 0){ $(".wa-empty").show(); }
            return false;
        });
        $(document).on("click", '.radio-tab', function(){
            $(this).siblings().removeClass("text-primary");
            $(this).addClass("text-primary").find("input[type='radio']").prop('checked',true);
        });
    };

    this.import_contact = function(){
        if( $("#import_whatsapp_contact").length > 0 ){
            var url = $("#import_whatsapp_contact").data("action");
            $(document).on( 'change', '#import_whatsapp_contact', function(){
                var form_data = new FormData();
                var totalfiles = document.getElementById('import_whatsapp_contact').files.length;
                for (var index = 0; index < totalfiles; index++) { form_data.append("files[]", document.getElementById('import_whatsapp_contact').files[index]); }
                Core.overplay();
                $(this).val('');
                $.ajax({
                    url: url, type: 'post', data: form_data, dataType: 'json', contentType: false, processData: false,
                    xhr: function () {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener("progress", function (evt) { if (evt.lengthComputable) { var percentComplete = evt.loaded / evt.total; } }, false);
                        xhr.addEventListener("progress", function (evt) { if (evt.lengthComputable) { var percentComplete = evt.loaded / evt.total; } }, false);
                        return xhr;
                    },
                    success: function (result) {
                        Core.overplay(true);
                        if(result.status == "success"){ window.location.reload(); }
                        else { Core.notify(result.message, result.status); }
                    }
                });
                return false;
            } );
        }
    };
}

var Whatsapp = new Whatsapp();
$(function(){
    Whatsapp.init();
});
