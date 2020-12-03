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

$(function() {
    $(document).ready(function() {
        $('.btn-smart-locker').fancybox({
            width: '80%',
            autoSize: false,
            afterShow: function() {
                LoGoogleMap();
            },
            afterClose: function() {
                LoCheckSmartLockersCarrier();
            }
        });
        LoCheckSmartLockersCarrier();

        $('#cgv').on('click', function () {
            LoCheckSmartLockersCarrier();
        });
    });
    $(document).on('change', 'select[name="lo_state"]', LoCheckCities);
    $(document).on('change', 'select[name="lo_city"]', LoCheckLockers);
    $(document).on('change', 'select[name="lo_locker"]', LoSaveCartLocker);

    $(document).on('change', 'input.delivery_option_radio, .delivery-option input[type="radio"]', LoCheckSmartLockersCarrier);

    if($(document).find('#lockers_gmap.only_selected_locker').length) {
        LoGoogleMap(lo_selected_locker);
    }


});
function LoCheckSmartLockersCarrier() {
    if ($(document).find('input.delivery_option_radio:checked').length) {
        id_selected_carrier = $(document).find('input.delivery_option_radio:checked').val();
        id_selected_carrier = id_selected_carrier.replace(',', '');
        if (typeof lo_lockers_carrier !== 'undefined' && id_selected_carrier == lo_lockers_carrier) {
            $('#lo_smart_lockers_area').slideDown();
            if (!lo_selected_locker) {
                $('.btn-smart-locker').trigger('click');
                $('[name="processCarrier"]').prop('disabled', true);
                $('.payment_module > a').css('pointerEvents', 'none').css('opacity', 0.5);
            } else {
                $('[name="processCarrier"]').prop('disabled', false);
                $('.payment_module > a').css('pointerEvents', 'auto').css('opacity', 1);
            }
        } else {
            $('#lo_smart_lockers_area').slideUp();
            $('[name="processCarrier"]').prop('disabled', false);
            $('.payment_module > a').css('pointerEvents', 'auto').css('opacity', 1);
        }
    } else if ($(document).find('.delivery-option input[type="radio"]:checked').length) {
        id_selected_carrier = $(document).find('.delivery-option input[type="radio"]:checked').val();
        id_selected_carrier = id_selected_carrier.replace(',', '');
        if (typeof lo_lockers_carrier !== 'undefined' && id_selected_carrier == lo_lockers_carrier) {
            $('#lo_smart_lockers_area').slideDown();
            if (!lo_selected_locker) {
                $('.btn-smart-locker').trigger('click');
                $('[name="confirmDeliveryOption"]').prop('disabled', true);
                $('.payment_module > a').css('pointerEvents', 'none').css('opacity', 0.5);
            } else {
                $('[name="confirmDeliveryOption"]').prop('disabled', false);
                $('.payment_module > a').css('pointerEvents', 'auto').css('opacity', 1);
            }
        } else {
            $('.btn-smart-locker').slideUp();
            $('[name="confirmDeliveryOption"]').prop('disabled', false);
            $('.payment_module > a').css('pointerEvents', 'auto').css('opacity', 1);
        }
    }
}
function LoCheckCities() {
    var state = $('.fancybox-inner select[name="lo_state"]').val();
    if (state) {
        $('.fancybox-inner select[name="lo_city"]').find('option[value!="0"]').remove();

        var options_html = "";
        for(i in lockers_data_array[state]) {
            options_html += '<option value="'+i+'">'+i+'</option>';
        }

        $('.fancybox-inner select[name="lo_city"]').append(options_html);
        if ($('.fancybox-inner select[name="lo_city"] option').length == 2) {
            $('.fancybox-inner select[name="lo_city"]').val($('.fancybox-inner select[name="lo_city"] option:last-child').attr('value'));
        }
        $('.fancybox-inner select[name="lo_city"]').change();
    }
}
function LoCheckLockers() {
    var state = $('.fancybox-inner select[name="lo_state"]').val();
    var city = $('.fancybox-inner select[name="lo_city"]').val();
    if (city) {
        $('.fancybox-inner select[name="lo_locker"]').find('option[value!="0"]').remove();

        var options_html = "";
        for(i in lockers_data_array[state][city]) {
            options_html += '<option '+(lockers_data_array[state][city][i]['dp_active'] == 10 || lockers_data_array[state][city][i]['dp_active'] <= 0?'disabled':'')+' value="'+lockers_data_array[state][city][i]['dp_id']+'">'+lockers_data_array[state][city][i]['dp_denumire']+'</option>';
        }

        $('.fancybox-inner select[name="lo_locker"]').append(options_html);
        if ($('.fancybox-inner select[name="lo_locker"] option').length == 2) {
            $('.fancybox-inner select[name="lo_locker"]').val($('.fancybox-inner select[name="lo_locker"] option:last-child').attr('value'));
        }
        $('.fancybox-inner select[name="lo_locker"]').change();
    }
}
function LoCheckSelectedLocker(selected_locker) {
    if (selected_locker) {
        lo_selected_locker = selected_locker;
    }
    if (typeof lo_selected_locker !== 'undefined' && lo_selected_locker) {
        for(var i in lockers_array) {
            if (lockers_array[i]['dp_id'] == lo_selected_locker) {
                $('.fancybox-inner select[name="lo_state"]').val(lockers_array[i]['dp_judet']).change();
                $('.fancybox-inner select[name="lo_city"]').val(lockers_array[i]['dp_oras']).change();
                $('.fancybox-inner select[name="lo_locker"]').val(lockers_array[i]['dp_id']).change();
                showMarkerDetails(lockers_array[i]['dp_id']);
                return;
            }
        }
    }
}

function LoSaveCartLocker() {
    var id_locker = $('.fancybox-inner select[name="lo_locker"]').val();
    if (!id_locker || id_locker == "0") {
        return;
    }
    lo_selected_locker = id_locker;

    if (typeof updateAddressSelection !== 'undefined' || typeof loadCarriers !== 'undefined') {
        $.ajax({
            url: lo_ajax_url,
            cache: false,
            dataType: 'json',
            data: {
                action: 'saveCartLocker',
                id_locker: id_locker,
                token: lo_ajax_token,
            },
        })
            .done(function(json) {
                if (typeof json.error !== 'undefined') {
                    alert(json.error);
                } else {
                    showMarkerDetails(id_locker);
                    if (typeof json.locker_name !== 'undefined' && json.locker_name) {
                        $('.smart_lockers_selected').show();
                        $('.smart_lockers_selected > span').html(json.locker_name);
                        $('[name="processCarrier"]').prop('disabled', false);
                        $('.payment_module > a').css('pointerEvents', 'auto').css('opacity', 1);
                    } else {
                        $('.smart_lockers_selected').hide();
                    }
                    if (typeof updateAddressSelection !== 'undefined') {
                        updateAddressSelection();
                    }
                    if (typeof loadCarriers !== 'undefined') {
                        loadCarriers();
                    }
                }
            });
    } else {
        $.ajax({
            url: lo_ajax_url,
            cache: false,
            dataType: 'json',
            data: {
                action: 'getOrderPageAddressSection',
                id_locker: id_locker,
                token: lo_ajax_token,
            },
        })
            .done(function(json) {
                if (typeof json.error !== 'undefined') {
                    alert(json.error);
                } else {
                    showMarkerDetails(id_locker);
                    $('[name="processCarrier"]').prop('disabled', false);
                    $('.payment_module > a').css('pointerEvents', 'auto').css('opacity', 1);
                    if (typeof json.carriers_tpl !== 'undefined') {
                        $('#carrier_area').replaceWith(json.carriers_tpl);
                        if (!!$.prototype.uniform)
                            $("select.form-control,input[type='radio'],input[type='checkbox']").not(".not_uniform").uniform();
                    } else {
                        if (json.get_from_window_href) {
                            $.ajax({
                                url: window.location.href,
                                cache: false,
                            })
                                .done(function(html) {
                                    $(document).find('#checkout-delivery-step').replaceWith($(html).find('#checkout-delivery-step'));
                                });
                        }
                    }
                }
            });
    }
}

function LoGoogleMap(only_selected = false) {
    var div = '#lockers_gmap';
    map = new GMaps({
        div: div,
        lat: 46.203567,
        lng: 25.003274,
        zoom: 7,
    });
    plotMarkers(lockers_array, only_selected);
    LoCheckSelectedLocker();
}
