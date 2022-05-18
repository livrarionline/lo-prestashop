/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code.
 *
 * @author    Livrari online <support@livrarionline.ro>
 * @copyright 2018 Livrari online
 * @license   LICENSE.txt
 */

$(document).ready(function() {

    $(document).on('click', '#get-awb', function(e){
        e.preventDefault();
        $(this).prepend('<span class="get-awb-loader"></span>');

        $.ajax({
            type	: "POST",
            url		: lo_adminajax_url,
            data	: $('#formLoOrder').serialize(),
            dataType: 'json',
            error	: function(tXMLHttpRequest, textStatus, errorThrown) {
                alert('XMLHttpRequest.status: ' + tXMLHttpRequest.status
                    + '\n textStatus: ' + textStatus
                    + '\n errorThrown: ' + errorThrown
                );
                // Verifica daca este eroare de DB
                if (textStatus.search(/Warning/i) < 0 && textStatus.search(/Error/i) < 0) {
                    alert('Eroare: ' + tXMLHttpRequest.status);
                }
            },
            success	: function(json){
                if (json.success) {
                    if (typeof json.tpl !== 'undefined') {
                        $('#LoOrderPanel').replaceWith(json.tpl);
                        $('.get-awb-loader').remove();
                    } else {
                        alert('SUCCESS STATUS BUT NO HTML FOR AWBS');
                        $('.get-awb-loader').remove();
                    }
                    $('.LoOrderSuccess').slideDown();
                    $('.LoOrderError, #formLoOrder').slideUp();
                    $('.get-awb-loader').remove();
                } else {
                    error_html = json.error;
                    $('.LoOrderSuccess').slideUp();
                    $('.LoOrderError, #formLoOrder').slideDown();
                    $('.LoOrderError').html(error_html);
                    $('.get-awb-loader').remove();
                }
            }
        });
    });

    $(document).on('click', '.lo_awb_tracking', function(e) {
        e.preventDefault();
        var awb = $(this).parent().data('awb');

        $.ajax({
            type	: "POST",
            url		: lo_adminajax_url,
            data	: 'action=tracking&awb='+awb,
            dataType: 'json',
            error	: function(tXMLHttpRequest, textStatus, errorThrown) {
                alert('XMLHttpRequest.status: ' + tXMLHttpRequest.status
                    + '\n textStatus: ' + textStatus
                    + '\n errorThrown: ' + errorThrown
                );
                // Verifica daca este eroare de DB
                if (textStatus.search(/Warning/i) < 0 && textStatus.search(/Error/i) < 0) {
                    alert('Eroare: ' + tXMLHttpRequest.status);
                }
            },
            success	: function(json){
                $.fancybox({
                    content: json.response,
                    helpers: {
                        overlay: {
                            locked: false
                        }
                    }
                });
            }
        });
    });

    $(document).on('click', '.lo_awb_print', function(e) {
        e.preventDefault();
        var awb = $(this).parent().data('awb');

        $.ajax({
            type	: "POST",
            url		: lo_adminajax_url,
            data	: 'action=print&awb='+awb,
            dataType: 'json',
            error	: function(tXMLHttpRequest, textStatus, errorThrown) {
                alert('XMLHttpRequest.status: ' + tXMLHttpRequest.status
                    + '\n textStatus: ' + textStatus
                    + '\n errorThrown: ' + errorThrown
                );
                // Verifica daca este eroare de DB
                if (textStatus.search(/Warning/i) < 0 && textStatus.search(/Error/i) < 0) {
                    alert('Eroare: ' + tXMLHttpRequest.status);
                }
            },
            success	: function(json){
                $.fancybox({
                    content: json.response,
                    helpers: {
                        overlay: {
                            locked: false
                        }
                    }
                });
            }
        });
    });

    $(document).on('click', '.lo_awb_cancel', function(e) {
        e.preventDefault();
        var $this = $(this);
        var awb = $this.parent().data('awb');

        $.ajax({
            type	: "POST",
            url		: lo_adminajax_url,
            data	: 'action=cancel&awb='+awb,
            dataType: 'json',
            error	: function(tXMLHttpRequest, textStatus, errorThrown) {
                alert('XMLHttpRequest.status: ' + tXMLHttpRequest.status
                    + '\n textStatus: ' + textStatus
                    + '\n errorThrown: ' + errorThrown
                );
                // Verifica daca este eroare de DB
                if (textStatus.search(/Warning/i) < 0 && textStatus.search(/Error/i) < 0) {
                    alert('Eroare: ' + tXMLHttpRequest.status);
                }
            },
            success	: function(json){
                $.fancybox({
                    content: json.response,
                    helpers: {
                        overlay: {
                            locked: false
                        }
                    }
                });
                $this.parent().css('opacity', '0.5');
                $this.parent().find('a').prop('disabled', 1);
            }
        });
    });

    $(document).on('click', '.lo_awb_return', function(e) {
        e.preventDefault();
        var $this = $(this);
        var awb = $this.parent().data('awb');

        $.ajax({
            type	: "POST",
            url		: lo_adminajax_url,
            data	: 'action=return&awb='+awb,
            dataType: 'json',
            error	: function(tXMLHttpRequest, textStatus, errorThrown) {
                alert('XMLHttpRequest.status: ' + tXMLHttpRequest.status
                    + '\n textStatus: ' + textStatus
                    + '\n errorThrown: ' + errorThrown
                );
                // Verifica daca este eroare de DB
                if (textStatus.search(/Warning/i) < 0 && textStatus.search(/Error/i) < 0) {
                    alert('Eroare: ' + tXMLHttpRequest.status);
                }
            },
            success	: function(json){
                $.fancybox({
                    content: json.response,
                    helpers: {
                        overlay: {
                            locked: false
                        }
                    }
                });
                $this.parent().css('opacity', '0.5');
                $this.parent().find('a').prop('disabled', 1);
            }
        });
    });

    $(document).on('click', '.lo_awb_set_trackingnumber', function(e) {
        e.preventDefault();
        var $this = $(this);
        var awb = $this.parent().data('awb');
        var id_carrier = $this.parent().data('id-carrier');
        $('.js-update-shipping-btn').trigger('click');
        $('#update_order_shipping_tracking_number').val(awb);
        $('#update_order_shipping_new_carrier_id').val(id_carrier);
        $('form[name="update_order_shipping"]').submit();
    });

    $(document).on('click', '#adauga-pachet', function(){
        var clonedRow = $('tbody > tr:last', '#colete').clone().find(':input').val(0).end();
        clonedRow.find('select').val(1).end();
        $('tbody > tr:last', '#colete').after(clonedRow);
        var $nrcolete = $('#nrcolete');
        $nrcolete.val(parseInt($nrcolete.val())+1);
    });

    $(document).on('change', '#serviciuid', function() {
        var val = $(this).val();
        var array = val.split('|');
        var service_id = array[0];
        if (service_id == lo_lockers_service_id) {
            $('#locker_size_group').slideDown();
            $('#locker_select_group').slideDown();
            $('#ramburs').closest('.form-group').slideUp();
            $('#currency_ramburs').closest('.form-group').slideUp();
        } else {
            $('#locker_size_group').slideUp();
            $('#locker_select_group').slideUp();
            $('#ramburs').closest('.form-group').slideDown();
            $('#currency_ramburs').closest('.form-group').slideDown();
        }
    });
    $(document).on('click', '.toggle_delivery_hours', function(e) {
        e.preventDefault();
        $('.delivery_hours').slideToggle();
    });
});
