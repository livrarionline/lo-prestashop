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

class LOIssnModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if (Tools::getValue('pass') != Tools::encrypt('lo_issn')) {
            die("WRONG PASS");
        }
        $lo = new LivrariOnline\LO1;
        $lo->f_login = (int)Configuration::get('LO_LOGINID');
        $lo->setRSAKey(Configuration::get('LO_KEY'));
        
        $this->module->logissn('ISSN ACCESSED: '.Tools::file_get_contents('php://input'));
        
        $lo->issn();
        die();
    }
}
