<?php
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

class Hook extends HookCore
{
    public static function getHookModuleExecList($hook_name = null)
    {
        /* Disable Cash on delivery for smart lockers */
        $return = parent::getHookModuleExecList($hook_name);
        if (Module::isEnabled('lo') && in_array($hook_name, array('displayPayment', 'displayPaymentEU'))) {
            $lo_module = Module::getInstanceByName('lo');
            
            $context = Context::getContext();
            $selected_carrier = $context->cart->id_carrier;
            $carrier = new Carrier($selected_carrier);
            $selected_service = $lo_module->getServiceIdByCarrierReference($carrier->id_reference);
            if (486 == $selected_service) {
                if (is_array($return)) {
                    foreach ($return as $key => $payment_method) {
                        if ($payment_method['module'] == 'cashondelivery' || $payment_method['module'] == 'ps_cashondelivery') {
                            unset($return[$key]);
                        }
                    }
                }
            }
        }
        return $return;
    }
}
