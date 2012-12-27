$(document).ready(function(){
    
    $('.add-transaction').each(function(){
        var $el = $(this);
        $(this).qtip({
            overwrite: true,
            content: {
                text: function(api) {
                    $('#create-mini-transaction-form').show();
                    return $('#create-mini-transaction-form')
                }
            },
            events: {
                visible: function(event, api) {
                    onVisibleMiniTransaction($el);
                }, 
                hidden: function(event, api) {
                    onHiddenMiniTransaction();
                }
            },
            show: {
                event: 'click'
            },
            hide: {
                event: 'unfocus'
            }
        });
    });
    
    $("body").click(function(event){
        if($(event.target).hasClass("delete-transaction")){
            var $parent = $(event.target).parents(".transaction-container");
            var name = $parent.hasClass("js-transaction-income") ? Contabilidad.tr("ingreso") : Contabilidad.tr("egreso");
            var output = Mustache.render($("#delete-popup-tpl").html(), 
                        {message: Contabilidad.tr("¿Realmente quieres eliminar este %s?", name)});
            $.fancybox({
                'content' : output,
                'onComplete' : function(){
                    $("#delete-popup input[type='button']").click(function(){
                        if($(this).attr("id") == "yes"){
                            var id = $parent.attr("data-id");
                            $parent.remove();
                            Contabilidad.getEndPoint({async : true, success: function(resp){
                            }}).deleteTransaction(id);
                        }
                        $.fancybox.close();
                    });
                }
            });
            return false;
        }
    });
});

/*************************************
 **********TRANSACTION METHODS***********
 *************************************/
function onVisibleMiniTransaction($el){
    
    var transactionName = getNewTransactionName($el);
    
    $("#create-mini-transaction-form input[name='name']").val(transactionName).select();
    $("#create-mini-transaction-form input[name='value']").focus();
    
    //rules
    $("#create-mini-transaction-form input[type='text']").each(function(){
        setInputRule($(this));
    });
    $('#create-mini-transaction-form form').submit(function(){
        Contabilidad.Validate.clean($('#create-mini-transaction-form'));
        $('#create-mini-transaction-form .response')
        .html("").removeClass("*").addClass("response");
        if(Contabilidad.Validate.isValid($(this))){
            var data = {};
            data.name = $("#create-mini-transaction-form input[name='name']").val();
            data.value = $("#create-mini-transaction-form input[name='value']")
            .val().replace(/\./g, '').replace(/,/g, '.');
            data.id_account = parseInt(Contabilidad.getURLParameter("id"));
            data.id_transaction_type = $el.attr("id") == "add-income" ? 1 : 2;
            Contabilidad.getEndPoint({async : true, success: function(resp){
                var output = Mustache.render($("#transaction-row-tpl").html(), resp.transaction);
                $("#transactions-container").prepend(output);
                $("body").data("transaction-names").push(resp.transaction.name.toLowerCase());
                $el.qtip('hide');
            }}).createTransaction(data);
        } else {
            findAndDisplayErrors($("#create-mini-transaction-form").parent());
        }
        return false;
    });

    $("#create-mini-transaction-form input[name='value']").keypress(function(evt){
        var charCode = (evt.which) ? evt.which : evt.keyCode
        if ((charCode > 31 && (charCode < 48 || charCode > 57)) &&
        (charCode != 44 && charCode != 46)){
            return false;
        }
        return true;
    }).keyup(function(evt){
        var charCode = (evt.which) ? evt.which : evt.keyCode
        if ((charCode > 36 && charCode < 40) || charCode==16){
            return true;
        } else {
            var value = this.value.replace(/\./g, '');
            value = value.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1.");
            $(this).val(value);
        }
    });
    
    //more options->open big transaction form
    $(".more-options").click(function(){
        $el.qtip('hide');
        var nAddFrag = document.createDocumentFragment();
        if(!$(this).data("el")){
            var el = document.getElementById("create-transaction-form");
            $(this).data("el", el);
        }
        nAddFrag.appendChild($(this).data("el"));
        var $div = $("<div>").append(nAddFrag);
        $.fancybox({
            content : $div,
            'onStart' : onCreateTransactionStart($div),
            'onCleanup' : function(){
                this.form = document.getElementById("create-transaction-form");
            },
            'onClosed' : function(){
                onClose(this.form);
                $(this.form).find(".hasDatepicker").removeClass("hasDatepicker");
            },
            'onComplete' : function(){
                $div = this.form ? this.form : $div;
                onCreateTransactionComplete($div, $el);
            }
        });
        return false;
    });
}

function onHiddenMiniTransaction(){
    $("#create-mini-transaction-form input[name='value']")
    .unbind("keypress").unbind("keyup");
    $('#create-mini-transaction-form form').unbind("submit");
    $('#create-mini-transaction-form .response')
    .html("").removeClass("*").addClass("response");
    $("#create-mini-transaction-form input[type='text']").each(function(){
        $(this).val("");
    });
}

function getNewTransactionName($el){
    //transaction name
    var defaultNumber = 1;
    var defaultName = $el.attr("id") == "add-income" ? Contabilidad.tr("Ingreso ") :  Contabilidad.tr("Egreso ");
    var transactionName = defaultName + defaultNumber;
    if(!$("body").data("transaction-names")){
        var transactionNames = [];
        $(".js-transaction-name").each(function(){
            transactionNames.push($(this).html().toLowerCase());
        });
        $("body").data("transaction-names", transactionNames);
    }
    while($.inArray(transactionName.toLowerCase(), $("body").data("transaction-names")) >= 0){
        defaultNumber++;
        transactionName = defaultName + defaultNumber;
    }
    return transactionName;
}

/*************************************
 ****CREATE BIG TRANSACTION METHODS***
 *************************************/

function onCreateTransactionStart($div){
    $div.find("#create-transaction-form").show();
}

function onCreateTransactionComplete($div, $el){
    var transactionName = getNewTransactionName($el);
    
    $div.find("#create-transaction-form input[name='name']").val(transactionName).select();
    $div.find("#create-transaction-form input[name='value']").focus();
    
    $div.find(".less-options").click(function(){
        $.fancybox.close();
        $el.qtip('show');
        return false;
    })
}

/*************************************
 **********ALL FORMS METHODS***********
 *************************************/

function onClose(form){
    Contabilidad.Validate.clean($(form));
    $(form).hide();
    $(form).find("input[type='text'], input[type='password']").val('');
    $(form).find(".response").html("")
    .removeClass("*")
    .addClass("response")
    .html("");
    var nAddFrag = document.createDocumentFragment();
    nAddFrag.appendChild(form);
    $("body").append(nAddFrag);
}

/*************************************
 **********VALIDATE METHODS***********
 *************************************/

function findAndDisplayErrors($form)
{
    var errors;
    $form.find(".input-error").each(function(){
        errors =  $(this).data("errors");
        if(errors.length){
            return;
        }
        return;
    });
    $form.find(".response").html(errors[0].message).show();
}

//set rules to an input
function setInputRule($input){
    if($input.attr("type") == "submit") return;
    //validation rulesc
    var rules = {required : $input.hasClass("required")};
    if($input.hasClass("money")){rules.money = true;}
    if($input.hasClass("is_email")){rules.email = true;}
    if($input.attr("type") == "password"){rules.password = true;}
    if($input.attr("is_equal_to")){rules.equalsTo = $input.parent().find("input[name='" + $input.attr("is_equal_to") + "']");}
    Contabilidad.Validate.setRules($input, rules);
}

