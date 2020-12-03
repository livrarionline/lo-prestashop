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

class LOAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if(Tools::getValue('token') != $this->module->generateAjaxToken()) {
            die(json_encode(array('error' => 'wrong_token')));
        }
        if (Tools::getValue('action') == 'saveCartLocker') {
            $id_cart = $this->context->cart->id;
            $id_locker = (int)Tools::getValue('id_locker');
            
            $locker_name = $this->module->getLockerName($id_locker);
            if (!$locker_name && $id_locker) {
                die(json_encode(array('error' => 'inexistent_locker')));
            }
            
            $success = $this->module->saveCartLocker($id_cart, $id_locker);
            if ($success) {
                $this->module->setCarrierSmartLocker();
            }
            
            die(json_encode(array('success' => $success, 'locker_name' => $locker_name)));
        } elseif (Tools::getValue('action') == 'getOrderPageAddressSection') {
            if (Tools::substr(_PS_VERSION_, 0, 3) == '1.7') {
                $id_cart = $this->context->cart->id;
                $id_locker = (int)Tools::getValue('id_locker');
                
                $locker_name = $this->module->getLockerName($id_locker);
                if (!$locker_name && $id_locker) {
                    die(json_encode(array('error' => 'inexistent_locker')));
                }
                $success = $this->module->saveCartLocker($id_cart, $id_locker);
                if ($success) {
                    $this->module->setCarrierSmartLocker();
                }
                die(json_encode(array('success' => $success, 'get_from_window_href' => true)));
            } else {
                $id_cart = $this->context->cart->id;
                $id_locker = (int)Tools::getValue('id_locker');
                
                $locker_name = $this->module->getLockerName($id_locker);
                if (!$locker_name && $id_locker) {
                    die(json_encode(array('error' => 'inexistent_locker')));
                }
                
                $success = $this->module->saveCartLocker($id_cart, $id_locker);
                
                if ($success) {
                    $this->module->setCarrierSmartLocker();
                }
                
                $address = new Address($this->context->cart->id_address_delivery);
                $id_zone = Address::getZoneById($address->id);
                $carriers = $this->context->cart->simulateCarriersOutput(null, true);
                $checked = $this->context->cart->simulateCarrierSelectedOutput(false);
                $delivery_option_list = $this->context->cart->getDeliveryOptionList();
                $delivery_option = $this->context->cart->getDeliveryOption(null, false);
                if (!$this->context->cart->getDeliveryOption(null, true)) {
                    $this->context->cart->setDeliveryOption($this->context->cart->getDeliveryOption());
                }
        
                $this->context->smarty->assign(array(
                    'address_collection' => $this->context->cart->getAddressCollection(),
                    'delivery_option_list' => $delivery_option_list,
                    'carriers' => $carriers,
                    'checked' => $checked,
                    'delivery_option' => $delivery_option
                ));
        
                $advanced_payment_api = (bool)Configuration::get('PS_ADVANCED_PAYMENT_API');
        
                $vars = array(
                    'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
                        'carriers' => $carriers,
                        'checked' => $checked,
                        'delivery_option_list' => $delivery_option_list,
                        'delivery_option' => $delivery_option
                    )),
                    'advanced_payment_api' => $advanced_payment_api
                );
        
                Cart::addExtraCarriers($vars);
        
                $this->context->smarty->assign($vars);
                
                // Wrapping fees
                $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
                $wrapping_fees_tax_inc = $this->context->cart->getGiftWrappingPrice();
        
                // TOS
                $cms = new CMS(Configuration::get('PS_CONDITIONS_CMS_ID'), $this->context->language->id);
                $this->link_conditions = $this->context->link->getCMSLink($cms, $cms->link_rewrite, (bool)Configuration::get('PS_SSL_ENABLED'));
                if (!strpos($this->link_conditions, '?')) {
                    $this->link_conditions .= '?content_only=1';
                } else {
                    $this->link_conditions .= '&content_only=1';
                }
        
                $free_shipping = false;
                foreach ($this->context->cart->getCartRules() as $rule) {
                    if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                        $free_shipping = true;
                        break;
                    }
                }
                $this->context->smarty->assign(array(
                    'free_shipping' => $free_shipping,
                    'checkedTOS' => (int)$this->context->cookie->checkedTOS,
                    'recyclablePackAllowed' => (int)Configuration::get('PS_RECYCLABLE_PACK'),
                    'giftAllowed' => (int)Configuration::get('PS_GIFT_WRAPPING'),
                    'cms_id' => (int)Configuration::get('PS_CONDITIONS_CMS_ID'),
                    'conditions' => (int)Configuration::get('PS_CONDITIONS'),
                    'link_conditions' => $this->link_conditions,
                    'recyclable' => (int)$this->context->cart->recyclable,
                    'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                    'carriers' => $this->context->cart->simulateCarriersOutput(),
                    'checked' => $this->context->cart->simulateCarrierSelectedOutput(),
                    'address_collection' => $this->context->cart->getAddressCollection(),
                    'delivery_option' => $this->context->cart->getDeliveryOption(null, false),
                    'gift_wrapping_price' => (float)$wrapping_fees,
                    'total_wrapping_cost' => Tools::convertPrice($wrapping_fees_tax_inc, $this->context->currency),
                    'override_tos_display' => Hook::exec('overrideTOSDisplay'),
                    'back' => '',
                    'total_wrapping_tax_exc_cost' => Tools::convertPrice($wrapping_fees, $this->context->currency)));
                
                $carriers_tpl = $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl');
                
                die(json_encode(array('success' => $success, 'carriers_tpl' => $carriers_tpl)));
            }
        }
        die(json_encode(array('error' => 'wrong_action')));
    }
}
