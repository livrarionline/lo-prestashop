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
    $(document).on('click', '.national_field_delete', function(e) {
        e.preventDefault();
        var $this = $(this);
        $this.closest('.form-wrapper').slideUp().find('input[name="national_field_deleted[]"]').val(1);
    });
    $(document).on('click', '#addNationalField', function(){
		$('#national').append($('#new-service-format').html());
	});
});
