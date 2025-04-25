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
        if (Tools::getValue('token') != $this->module->generateAjaxToken()) {
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
        }

        die(json_encode(array('error' => 'wrong_action')));
    }
}
