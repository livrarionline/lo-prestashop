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

if (!defined('_PS_VERSION_')) {
	exit;
}

class LO extends Module
{
	public $id_carrier;
	public $ship_cost = array();
	public $apel = array();
	public $apel2 = array();
	public static $estimari = array();
	public static $lockers_service_id = 486;

	public function __construct()
	{
		$this->name = 'lo';
		$this->tab = 'shipping_logistics';

		$this->version = '2.1.6';
		$this->author = 'Livrari Online';
		$this->need_instance = 1;
		$this->bootstrap = true;
		$this->module_key = 'aae710b9ce26c5dfbd8188d5f67ec70e';

		parent::__construct();

		$this->displayName = $this->l('Livrari Online');
		$this->description = $this->l('Ship your goods using "Livrari Online" system');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		self::registerAutoload('phpseclib');
		self::registerAutoload('sylouuu');
		self::registerAutoload('LivrariOnline');
	}

	public function install()
	{
		if (!parent::install() ||
			!$this->installDB() ||
			!$this->installAdminTabs() ||
			!$this->registerHook('displayBackofficeHeader') ||
			!$this->registerHook('displayBeforeCarrier') ||
			!$this->registerHook('displayHeader') ||
			!$this->registerHook('displayAdminOrder') ||
			!$this->registerHook('displayOrderConfirmation') ||
			!$this->registerHook('actionOrderStatusUpdate') ||
			!$this->registerHook('header') ||
			!$this->registerHook('backOfficeHeader') ||
			!$this->registerHook('displayBackOfficeHeader'))
		{
			return false;
		}

		Configuration::updateValue('LO_STORE_COUNTRY', 'Romania');

		$config = array(
			'name'                 => 'Carrier LivrariOnline',
			'id_tax_rules_group'   => 0,
			'url'                  => 'https://static.livrarionline.ro/?awb=@',
			'active'               => true,
			'deleted'              => 0,
			'shipping_handling'    => true,
			'range_behavior'       => 0,
			'is_module'            => true,
			'delay'                => array(Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => $this->l('Fast carrier: 24h')),
			'id_zone'              => 1,
			'shipping_external'    => true,
			'external_module_name' => 'lo',
			'need_range'           => true,
		);

		//add carrier in back office
		if (!$this->createLOCarrier($config)) {
			return false;
		}

		return true;
	}

	public function installDB()
	{
		// Creation of the tables in a database
		$result = true;
		$queries = array();

		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lo` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_order` INT UNSIGNED NOT NULL,
            `awb` varchar(500) NOT NULL,
            `f_statusid` INT UNSIGNED NOT NULL,
            `date_add` DATETIME NOT NULL,
            `serviciu` varchar(500) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lo_delivery_points` (
            `dp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `dp_denumire` varchar(255) NOT NULL,
            `dp_adresa` varchar(255) NOT NULL,
            `dp_judet` varchar(50) NOT NULL,
            `dp_oras` varchar(50) NOT NULL,
            `dp_tara` varchar(255) NOT NULL,
            `dp_cod_postal` varchar(255) NOT NULL,
            `dp_gps_lat` double NOT NULL,
            `dp_gps_long` double NOT NULL,
            `dp_tip` int(11) NOT NULL,
            `dp_active` tinyint(1) NOT NULL DEFAULT "0",
            `version_id` int(11) NOT NULL,
            `stamp_created` datetime NULL,
            `dp_temperatura` decimal(10,2) DEFAULT NULL,
            `dp_indicatii` text,
            PRIMARY KEY (`dp_id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lo_dp_day_exceptions` (
            `leg_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `dp_id` int(10) unsigned NOT NULL,
            `exception_day` date NOT NULL,
            `dp_start_program` time NOT NULL DEFAULT "00:00:00",
            `dp_end_program` time NOT NULL DEFAULT "00:00:00",
            `active` tinyint(1) NOT NULL,
            `version_id` int(10) NOT NULL,
            `stamp_created` datetime NULL,
            PRIMARY KEY (`leg_id`),
            KEY `delivery_point` (`dp_id`,`exception_day`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lo_dp_program` (
            `leg_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `dp_start_program` time NOT NULL DEFAULT "00:00:00",
            `dp_end_program` time NOT NULL DEFAULT "00:00:00",
            `dp_id` int(10) unsigned NOT NULL,
            `day_active` tinyint(1) NOT NULL,
            `version_id` int(10) NOT NULL,
            `day_number` int(11) NOT NULL,
            `day` varchar(50) NOT NULL,
            `day_sort_order` int(1) NOT NULL,
            `stamp_created` datetime NULL,
            PRIMARY KEY (`leg_id`),
            KEY `delivery_point` (`dp_id`,`day`(1))
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lo_dp_cart` (
            `id_cart` INT NOT NULL AUTO_INCREMENT,
            `id_locker` INT NOT NULL,
            PRIMARY KEY (`id_cart`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

		$queries[] = 'CREATE TRIGGER ' . _DB_PREFIX_ . 'lo_dp_program_BEFORE_INSERT BEFORE INSERT ON ' . _DB_PREFIX_ . 'lo_dp_program FOR EACH ROW
            SET new.`day_sort_order` =
            CASE
                WHEN (new.`day_number` = 1) THEN 1
                WHEN (new.`day_number` = 2) THEN 2
                WHEN (new.`day_number` = 3) THEN 3
                WHEN (new.`day_number` = 4) THEN 4
                WHEN (new.`day_number` = 5) THEN 5
                WHEN (new.`day_number` = 6) THEN 6
                WHEN (new.`day_number` = 0) THEN 7
        END';

		$queries[] = 'CREATE TRIGGER ' . _DB_PREFIX_ . 'lo_dp_program_BEFORE_UPDATE BEFORE UPDATE ON ' . _DB_PREFIX_ . 'lo_dp_program FOR EACH ROW
            SET new.`day_sort_order` =
            CASE
                WHEN (new.`day_number` = 1) THEN 1
                WHEN (new.`day_number` = 2) THEN 2
                WHEN (new.`day_number` = 3) THEN 3
                WHEN (new.`day_number` = 4) THEN 4
                WHEN (new.`day_number` = 5) THEN 5
                WHEN (new.`day_number` = 6) THEN 6
                WHEN (new.`day_number` = 0) THEN 7
        END';

		foreach ($queries as $query) {
			$result &= Db::getInstance()->Execute($query);
		}

		if (!$result) {
			return false;
		}

		return true;
	}

	public function installAdminTabs()
	{
		$tab = new Tab;

		$tab->class_name = "AdminLoAjax";
		$tab->id_parent = -1;
		$tab->module = $this->name;
		$tab->name[(int)(Configuration::get('PS_LANG_DEFAULT'))] = $this->displayName;
		if (!$tab->add()) {
			return false;
		}
		return true;
	}

	private static function registerAutoload($classname)
	{
		spl_autoload_extensions('.php'); // Only Autoload PHP Files
		spl_autoload_register(function ($classname) {
			if (strpos($classname, 'LivrariOnline') !== false || strpos($classname, 'phpseclib') !== false || strpos($classname, 'sylouuu') !== false) {
				if (strpos($classname, '\\') !== false) {
					// Namespaced Classes
					$classfile = str_replace('\\', '/', $classname);
					if ($classname[0] !== '/') {
						$classfile = dirname(__FILE__) . '/libraries/Namespaced/' . $classfile . '.php';
					}
					require($classfile);
				}
			}
		});
	}

	public function uninstall()
	{
		Configuration::deleteByName('LO_LOGINID');
		Configuration::deleteByName('LO_KEY');
		Configuration::deleteByName('LO_SERVICIUID');
		Configuration::deleteByName('LO_STATUS');
		Configuration::deleteByName('LO_NATIONAL_FIELDS');

		$result = Db::getInstance()->Execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lo`');

		for ($i = 1; $i <= 5; $i++) {
			Configuration::deleteByName('LO_CARRIER_ID' . '_' . $i);
		}

		return parent::uninstall();
	}

	public function hookDisplayBackofficeHeader()
	{
		$this->context->controller->addJquery();
		if (Tools::getValue('module_name') == $this->name || Tools::getValue('configure') == $this->name) {
			$this->context->controller->addCSS($this->_path . 'views/css/admin.css');
			$this->context->controller->addJs($this->_path . 'views/js/admin.js');
		} elseif (Tools::getValue('controller') == 'AdminOrders') {
			Media::addJsDef(array(
				'lo_adminajax_url'      => $this->context->link->getAdminLink('AdminLoAjax'),
				'lo_lockers_service_id' => self::$lockers_service_id,
			));
			$this->context->controller->addCSS($this->_path . 'views/css/order.css');
			$this->context->controller->addJs($this->_path . 'views/js/order.js');
		}
	}

	public function hookDisplayHeader()
	{
		if (empty($this->context->controller->php_self) || !in_array($this->context->controller->php_self, array('order', 'order-opc', 'order-confirmation'))) {
			if (isset($this->context->controller->name) && $this->context->controller->name !== 'supercheckout') {
				return false;
			}
		}

		$this->context->controller->addJquery();
		$this->context->controller->addjQueryPlugin(array('fancybox'));
		$this->context->controller->addCSS($this->_path . 'views/css/front.css');
		$this->context->controller->addJs($this->_path . 'views/js/front.js');
		$this->context->controller->addJs($this->_path . 'views/js/postapanduri-public.js');

		$id_lockers_carrier = self::getLockersCarrierId();

		Media::addJsDef(array(
			'lockers_data_array' => self::getLockersData(true, true),
			'lockers_array'      => self::getLockersData(false, true),
			'lo_icon'            => _PS_BASE_URL_SSL_ . __PS_BASE_URI__ . 'modules/' . $this->name . '/views/img/location-pin.png',
			'lo_ajax_url'        => $this->context->link->getModuleLink($this->name, 'ajax'),
			'lo_ajax_token'      => $this->generateAjaxToken(),
			'lo_selected_locker' => (int)self::getSelectedLocker($this->context->cart->id),
			'lo_lockers_carrier' => $id_lockers_carrier,
		));
		if ($this->context->controller->php_self == 'order-confirmation') {
			$id_cart = (int)Tools::getValue('id_cart');
			Media::addJsDef(array(
				'lo_selected_locker' => (int)self::getSelectedLocker($id_cart),
			));
		}

		if ($maps_api_key = Configuration::get('LO_MAPS_API_KEY')) {
			return '<script src="https://maps.googleapis.com/maps/api/js?key=' . trim($maps_api_key) . '"></script>';
		}
	}

	public static function getLockersCarrierId()
	{
		$national_fields = Tools::jsonDecode(Configuration::get('LO_NATIONAL_FIELDS'), true);
		$lockers_carrier = 0;
		foreach ($national_fields as $carrier) {
			if ($carrier['serviciuid'] == self::$lockers_service_id) {
				$id_carrier_reference = $carrier['carrier'];
				$lockers_carrier_obj = Carrier::getCarrierByReference($id_carrier_reference);
				$lockers_carrier = $lockers_carrier_obj->id;
			}
		}

		return $lockers_carrier;
	}

	public function getContent()
	{
		$this->_html = '';

		if (Tools::isSubmit('submitLoModule')) {
			$this->_html .= $this->postProcess();
		} elseif (Tools::isSubmit('submitLOOrderStates') || Tools::isSubmit('submitLONational')) {
			$errors = array();
			$fields_array = array(
				'serviciu',
				'serviciuid',
				'shippingcompanyid',
				'carrier',
				'flatprice',
				'fixedprice',
				'modify_sign',
				'modify_amount',
				'modify_how',
				'freeamount',
				'active',
				'deleted',
			);
			$values_array = array();
			foreach ($fields_array as $field) {
				if (Tools::getIsset('national_field_' . $field)) {
					foreach (Tools::getValue('national_field_' . $field) as $field_index => $field_val) {
						if (Tools::getValue('national_field_deleted')[$field_index]) {
							continue;
						}
						if ($field == 'serviciu' && !$field_val) {
							$errors[] = $this->l('Service name is mandatory.');
							$values_array[$field][] = "";
						} elseif ($field == 'serviciuid' && !Validate::isInt($field_val)) {
							$errors[] = $this->l('Service ID has to be an integer.');
							$values_array[$field][] = "";
						} elseif ($field == 'shippingcompanyid' && !Validate::isInt($field_val)) {
							$errors[] = $this->l('Shipping Company ID has to be an integer.');
							$values_array[$field][] = "";
						} elseif ($field == 'flatprice' && $field_val && !Validate::isFloat($field_val)) {
							$errors[] = $this->l('Flat price has to be a decimal number.');
							$values_array[$field][] = "";
						} elseif ($field == 'fixedprice' && $field_val && !Validate::isFloat($field_val)) {
							$errors[] = $this->l('Fixed price has to be a decimal number.');
							$values_array[$field][] = "";
						} elseif ($field == 'modify_amount' && $field_val && !Validate::isFloat($field_val)) {
							$errors[] = $this->l('Modify amount has to be a decimal number.');
							$values_array[$field][] = "";
						} elseif ($field == 'freeamount' && $field_val && !Validate::isFloat($field_val)) {
							$errors[] = $this->l('Free amount has to be a decimal number.');
							$values_array[$field][] = "";
						} else {
							$values_array[$field][] = $field_val;
						}
					}
				}
			}
			$final_array = array();
			foreach ($values_array as $key1 => $values) {
				foreach ($values as $key2 => $value) {
					$final_array[$key2][$key1] = $value;
				}
			}

			foreach ($final_array as $key => $carrier_array) {
				if ($carrier_array['deleted']) {
					unset($final_array[$key]);
					continue;
				}
				$id_carrier_reference = $carrier_array['carrier'];
				$carrier = Carrier::getCarrierByReference($id_carrier_reference);
				if ($carrier) {
					if ($carrier_array['serviciu']) {
						$carrier->name = $carrier_array['serviciu'];
					}
					$carrier->active = $carrier_array['active'];
					$carrier->save();
				}
			}
			Configuration::updateValue('LO_NATIONAL_FIELDS', Tools::jsonEncode($final_array));

			$lo_order_states = Tools::getValue('lo_order_states');

			foreach ($lo_order_states as $id_lo_order_state => $ps_order_state) {
				if ($ps_order_state) {
					Configuration::updateValue('LO_OS_' . (int)$id_lo_order_state, (int)$ps_order_state);
				} else {
					Configuration::deleteByName('LO_OS_' . (int)$id_lo_order_state);
				}
			}
			if (!sizeof($errors)) {
				$this->_html .= $this->displayConfirmation($this->l('Settings saved.'));
			} else {
				$errors = array_unique($errors);
				$this->_html .= $this->displayError(implode('<br>', $errors));
			}
		}

		$country_ro = Country::getByIso('RO');
		if (!$country_ro) {
			$this->_html .= $this->displayWarning($this->l('No country found by ISO: RO'));
		} else {
			$states = State::getStatesByIdCountry($country_ro);
			if (!$states || !sizeof($states)) {
				$this->_html .= $this->displayWarning($this->l('No states found for Romania'));
			}
		}
		$currency_ron = Currency::getIdByIsoCode('RON');
		if (!$currency_ron) {
			$this->_html .= $this->displayWarning($this->l('RON currency not found'));
		}

		if (!(int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'lo_delivery_points`')) {
			$this->_html .= $this->displayWarning(sprintf($this->l('We could not find any Smart Locker in your database. Please open the following link: %s , then contact us at %s.'), '<a target="_blank" href="' . $this->context->link->getModuleLink($this->name, 'issn', array('pass' => Tools::encrypt('lo_issn') . '', 'force_lockers' => 1)) . '">' . $this->context->link->getModuleLink($this->name, 'issn', array('pass' => Tools::encrypt('lo_issn') . '', 'force_lockers' => 1)) . '</a>', '<a href="mailto:support@livrarionline.ro">support@livrarionline.ro</a>'));
		}


		$this->_html .= $this->displayForm();

		return $this->_html;
	}

	public function postProcess()
	{
		$errors = array();
		$success = true;
		foreach ($this->getConfigInputs() as $input) {
			if (Tools::getValue($input['name']) || !isset($input['required']) || !$input['required']) {
				$ok = true;
				if (isset($input['validate'])) {
					if ($input['validate'] == 'isInt' && !Validate::isInt(Tools::getValue($input['name']))) {
						$ok = false;
						$errors[] = sprintf($this->l('%s has to be an integer.'), $input['label']);
					} elseif ($input['validate'] == 'isEmail' && !Validate::isEmail(Tools::getValue($input['name']))) {
						$ok = false;
						$errors[] = sprintf($this->l('%s is not a valid email address.'), Tools::getValue($input['name']));
					} elseif ($input['validate'] == 'isPhoneNumber' && !Validate::isPhoneNumber(Tools::getValue($input['name']))) {
						$ok = false;
						$errors[] = sprintf($this->l('%s is not a valid phone number.'), Tools::getValue($input['name']));
					}
				}
				if ($ok) {
					Configuration::updateValue($input['name'], Tools::getValue($input['name']));
				} else {
					$success = false;
				}
			} elseif (isset($input['required']) && $input['required']) {
				$errors[] = sprintf($this->l('%s is mandatory.'), $input['label']);
				$success = false;
			}
		}

		if ($success) {
			return $this->displayConfirmation($this->l('Configuration saved.'));
		} else {
			return $this->displayError($this->l('Please check the errors below.') . "<br /><br />" . implode("<br />", $errors));
		}
	}

	public function displayForm()
	{
		$carriers = new Carrier();
		$carriers = $carriers->getCarriers($this->context->language->id, false, false, false, null, CARRIERS_MODULE);

		$national_fields = Tools::jsonDecode(Configuration::get('LO_NATIONAL_FIELDS'), true);

		$this->context->smarty->assign(array(
			'carriers'        => $carriers,
			'national_fields' => $national_fields,
			'currency_iso'    => $this->context->currency->iso_code,
			'lo_order_states' => self::getIssnOrderStates(),
			'ps_order_states' => self::getPsOrderStates(),
		));

		return $this->renderForm() . $this->display(__FILE__, 'views/templates/admin/lo.tpl');
	}

	public function renderForm()
	{
		$helper = new HelperForm();

		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitLoModule';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			. '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $this->getConfiguration(), /* Add values for your inputs */
			'languages'    => $this->context->controller->getLanguages(),
			'id_language'  => $this->context->language->id,
		);

		return $helper->generateForm(array($this->getConfigForm()));
	}

	public function getConfiguration()
	{
		$array = array();
		foreach ($this->getConfigInputs() as $field) {
			$array[$field['name']] = Configuration::get($field['name']);
		}
		$array['LO_ISSN_URL'] = $this->context->link->getModuleLink($this->name, 'issn', array('pass' => Tools::encrypt('lo_issn')));
		return $array;
	}

	public function getConfigInputs()
	{
		$id_lang = (int)$this->context->language->id;
		$orderStates = array_merge(array(array('id_order_state' => '0', 'name' => '- All -')), OrderState::getOrderStates($id_lang));
		$country_ro = Country::getByIso('RO');
		if (!$country_ro) {
			$ro_states = array();
		} else {
			$ro_states = State::getStatesByIdCountry($country_ro);
		}
		return array(
			array(
				'type'     => 'text',
				'label'    => $this->l('Login ID'),
				'name'     => 'LO_LOGINID',
				'required' => true,
				'validate' => 'isInt',
			),
			array(
				'type'     => 'textarea',
				'label'    => $this->l('Security key'),
				'desc'     => $this->l('Also known as rsakey'),
				'name'     => 'LO_KEY',
				'required' => true,
			),
			array(
				'type'    => 'select',
				'label'   => $this->l('Order state'),
				'desc'    => $this->l('On which order state shall we show the module in Admin Order page?'),
				'name'    => 'LO_STATUS',
				'options' => array(
					'query' => $orderStates,
					'id'    => 'id_order_state',
					'name'  => 'name',
				),
			),
			array(
				'type'     => 'text',
				'label'    => $this->l('Google maps API key'),
				'desc'     => sprintf($this->l('Get one here: %s'), '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">https://developers.google.com/maps/documentation/javascript/get-api-key</a>'),
				'name'     => 'LO_MAPS_API_KEY',
				'required' => true,
			),
			array(
				'type'     => 'text',
				'label'    => $this->l('Store email'),
				'name'     => 'LO_STORE_EMAIL',
				'required' => true,
				'validate' => 'isEmail',
			),
			array(
				'type'     => 'text',
				'label'    => $this->l('Store name'),
				'name'     => 'LO_STORE_NAME',
				'required' => true,
			),
			array(
				'type'     => 'text',
				'label'    => $this->l('Store main address'),
				'name'     => 'LO_STORE_ADDRESS',
				'required' => true,
			),
			array(
				'type'     => 'text',
				'label'    => $this->l('Store city'),
				'name'     => 'LO_STORE_CITY',
				'required' => true,
			),
			array(
				'type'    => 'select',
				'label'   => $this->l('Store state'),
				'name'    => 'LO_STORE_STATE',
				'options' => array(
					'query' => $ro_states,
					'id'    => 'id_state',
					'name'  => 'name',
				),
			),
			array(
				'type'     => 'text',
				'label'    => $this->l('Store ZIP code'),
				'name'     => 'LO_STORE_ZIP',
				'required' => true,
			),
			array(
				'type'     => 'text',
				'label'    => $this->l('Store country'),
				'name'     => 'LO_STORE_COUNTRY',
				'required' => true,
				'readonly' => true,
			),
			array(
				'type'     => 'text',
				'label'    => $this->l('Store phone'),
				'name'     => 'LO_STORE_PHONE',
				'required' => true,
				'validate' => 'isPhoneNumber',
			),
			array(
				'type'     => 'text',
				'label'    => $this->l('ISSN URL'),
				'name'     => 'LO_ISSN_URL',
				'readonly' => true,
			),
		);
	}

	public function getConfigForm()
	{
		return array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Parameters'),
					'icon'  => 'icon-cogs',
				),
				'input'  => $this->getConfigInputs(),
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right',
				),
			),
		);
	}

	public function createLOCarrier($config)
	{
		for ($carrier_cont = 1; $carrier_cont <= 5; $carrier_cont++) {
			$carrier = new Carrier();
			$carrier->name = $config['name'] . ' - ' . 'General ' . $carrier_cont;
			$carrier->id_tax_rules_group = $config['id_tax_rules_group'];
			$carrier->id_zone = $config['id_zone'];
			$carrier->url = $config['url'];
			if ($carrier_cont == 1) {
				$carrier->active = $config['active'];
			} else {
				$carrier->active = 0;
			}

			$carrier->deleted = $config['deleted'];
			$carrier->delay = $config['delay'];
			$carrier->shipping_handling = $config['shipping_handling'];
			$carrier->range_behavior = $config['range_behavior'];
			$carrier->is_module = $config['is_module'];
			$carrier->shipping_external = $config['shipping_external'];
			$carrier->external_module_name = $config['external_module_name'];
			$carrier->need_range = $config['need_range'];

			$languages = Language::getLanguages(true);
			foreach ($languages as $language) {
				$carrier->delay[(int)$language['id_lang']] = $this->l('Fast carrier: 24h');
			}

			if ($carrier->add()) {
				Configuration::updateValue('LO_CARRIER_ID' . '_' . $carrier_cont, (int)($carrier->id));
				$groups = Group::getgroups(true);
				foreach ($groups as $group) {
					Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'carrier_group VALUE (\'' . (int)($carrier->id) . '\',\'' . (int)($group['id_group']) . '\')');
				}
				$rangePrice = new RangePrice();
				$rangePrice->id_carrier = $carrier->id;
				$rangePrice->delimiter1 = '0';
				$rangePrice->delimiter2 = '10000';
				$rangePrice->add();

				$rangeWeight = new RangeWeight();
				$rangeWeight->id_carrier = $carrier->id;
				$rangeWeight->delimiter1 = '0';
				$rangeWeight->delimiter2 = '10000';
				$rangeWeight->add();

				$zones = Zone::getZones(true);
				foreach ($zones as $zone) {
					Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'carrier_zone  (id_carrier, id_zone) VALUE (\'' . (int)($carrier->id) . '\',\'' . (int)($zone['id_zone']) . '\')');
					Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'delivery (id_carrier, id_range_price, id_range_weight, id_zone, price) VALUE (\'' . (int)($carrier->id) . '\',\'' . (int)($rangePrice->id) . '\',NULL,\'' . (int)($zone['id_zone']) . '\',\'0\')');
					Db::getInstance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'delivery (id_carrier, id_range_price, id_range_weight, id_zone, price) VALUE (\'' . (int)($carrier->id) . '\',NULL,\'' . (int)($rangeWeight->id) . '\',\'' . (int)($zone['id_zone']) . '\',\'0\')');
				}
				//copy logo
				if (!copy(dirname(__FILE__) . '/views/img/logo.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int)$carrier->id . '.jpg')) {
					return false;
				}
			}
		}
		return true;
	}

	public static function getAwbsForOrder($id_order)
	{
		return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'lo` WHERE `id_order` = "' . (int)$id_order . '"');
	}

	public function printAwbsTpl($id_order)
	{
		$awbs = self::getAwbsForOrder($id_order);
		$id_carrier = (new Order((int)$id_order))->id_carrier;
		if (!$awbs) {
			return false;
		}
		$awbs_date = $awbs[0]['date_add'];
		$service = $awbs[0]['serviciu'];
		$this->context->smarty->assign(array(
			'awbs_date' => $awbs_date,
			'service'   => $service,
			'awbs'      => $awbs,
			'f_login'   => Configuration::get('LO_LOGINID'),
			'id_carrier'   => $id_carrier,
		));
		return $this->display(__FILE__, 'views/templates/admin/order_awbs.tpl');
	}

	public function checkZone($id_carrier)
	{
		return (bool)Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'carrier_zone WHERE id_carrier = ' . (int)($id_carrier));
	}

	public function checkDelivery($id_carrier)
	{
		return (bool)Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'delivery WHERE id_carrier = ' . (int)($id_carrier));
	}

	public static function getLockersData($by_city = true, $order_by_oras = false)
	{
		$sql = "
            SELECT
                dp.*,
                COALESCE(group_concat(
                    CASE
                        WHEN p.day_active = 0 and day_sort_order > 5 THEN CONCAT('<div>', p.day, ': <b>Inchis</b>')
                        WHEN p.day_active = 1 and day_sort_order > 5 THEN CONCAT('<div>', p.`day`, ': <b>', DATE_FORMAT(p.dp_start_program,'%H:%i'), '</b> - <b>', DATE_FORMAT(p.dp_end_program,'%H:%i'),'</b>')
                        WHEN p.day_active = 2 and day_sort_order > 5 THEN CONCAT('<div>', p.day, ': <b>Non-Stop</b>')
                        WHEN p.day_active = 0 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.day, ': <b>Inchis</b>')
                        WHEN p.day_active = 1 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.`day`, ': <b>', DATE_FORMAT(p.dp_start_program,'%H:%i'), '</b> - <b>', DATE_FORMAT(p.dp_end_program,'%H:%i'),'</b>')
                        WHEN p.day_active = 2 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.day, ': <b>Non-Stop</b>')
                    END
                    order by p.day_sort_order
                    separator '</div>'
                ),' - ') as orar
            FROM
                `" . _DB_PREFIX_ . "lo_delivery_points` dp
                    LEFT JOIN
                `" . _DB_PREFIX_ . "lo_dp_program` p ON dp.dp_id = p.dp_id and day_sort_order > 4
            WHERE
                dp_active > 0
            group by
                dp.dp_id
            ";
		if (!$order_by_oras) {
			$sql .= "order by
                dp.dp_id asc";
		} else {
			$sql .= "order by
                dp.dp_judet asc, dp.dp_oras asc, dp.dp_denumire asc";
		}
		$lockers_data = Db::getInstance()->executeS($sql);
		if ($by_city) {
			$lockers_data_array = array();
			$locations = array();
			foreach ($lockers_data as $locker) {
				$lockers_data_array[$locker['dp_judet']][$locker['dp_oras']][] = $locker;
			}
			return $lockers_data_array;
		} else {
			return $lockers_data;
		}
	}

	public function hookDisplayBeforeCarrier($params)
	{
		if (!(int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'lo_delivery_points`')) {
			return false;
		}
		$servicii_nationale = Tools::jsonDecode(Configuration::get('LO_NATIONAL_FIELDS'), true);

		foreach ($servicii_nationale as $serviciu) {
			if ($serviciu['serviciuid'] == self::$lockers_service_id) {
				if ($serviciu['active']) {
					$this->context->smarty->assign(array(
						'lockers_data_array'   => self::getLockersData(true, true),
						'selected_locker_name' => self::getLockerName(self::getSelectedLocker($this->context->cart->id)),
						'id_lockers_carrier'   => self::getLockersCarrierId(),
						'id_selected_carrier'  => $this->context->cart->id_carrier,
					));
					return $this->display(__FILE__, 'views/templates/front/lockers.tpl');
				}
			}
		}
	}

	public function hookDisplayOrderConfirmation($params)
	{
		if (isset($params['objOrder'])) {
			$order = $params['objOrder'];
		} else {
			$order = $params['order'];
		}
		$id_carrier = $order->id_carrier;
		$carrier = new Carrier($id_carrier);
		if (Validate::isLoadedObject($carrier)) {
			$id_service = self::getServiceIdByCarrierReference($carrier->id_reference);
			if ($id_service == self::$lockers_service_id && $id_locker = self::getSelectedLocker((int)$order->id_cart)) {
				$lockers = self::getLockersData(false);
				foreach ($lockers as $locker) {
					if ($locker['dp_id'] == $id_locker) {
						$this->context->smarty->assign(array(
							'selected_locker' => $locker,
						));
						return $this->display(__FILE__, 'views/templates/front/order-confirmation.tpl');
					}
				}
			}
		}
		return false;
	}

	public function getOrderShippingCost($params, $shipping_cost)
	{
		if (!empty(Tools::getValue('id_order'))) {
			$order = new Order((int)Tools::getValue('id_order'));
			return $order->total_shipping;
		}
		$context = Context::getContext();
        if ( !( in_array($context->controller->php_self, array('cart', 'order')) || in_array(($context->controller->module->name?:''), array('ps_shoppingcart','thecheckout')))) {
            return false;
        }

		$servicii_nationale = Tools::jsonDecode(Configuration::get('LO_NATIONAL_FIELDS'), true);
		$address = new Address($params->id_address_delivery);

		$cart = new Cart($params->id);
		$carrier_reference = (int)Db::getInstance()->getValue('SELECT `id_reference` FROM `' . _DB_PREFIX_ . 'carrier` WHERE `id_carrier` = "' . $this->id_carrier . '"');

		$shipping_cost = $this->EstimeazaPret($params);

		if (!empty($servicii_nationale) && !empty($shipping_cost)) {
			$ramburs_ron = (float)$cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
			$cart_id_currency = $cart->id_currency;
			$ramburs_ron = self::convertToRON($ramburs_ron, $cart_id_currency);
			foreach ($servicii_nationale as $cf) {
				foreach ($shipping_cost as $s => $p) {
					if ($s == $cf['serviciuid'] && $cf['carrier'] == $carrier_reference) {
						if (!empty($cf['freeamount']) && $ramburs_ron >= $cf['freeamount']) {
							$this->ship_cost[$this->id_carrier][$cart->nbProducts()] = 0;
							return 0;
						}
						if (!empty($cf['fixedprice']) && $cf['fixedprice'] > 0) {
							$this->ship_cost[$this->id_carrier][$cart->nbProducts()] = (float)$cf['fixedprice'];
							return (float)$cf['fixedprice'];
						}
						$this->ship_cost[$this->id_carrier][$cart->nbProducts()] = (float)$p;
						return (float)$p;
					}
				}
			}
		}

		return false;
	}

	public function hookActionOrderStatusUpdate($params)
	{
		$id_order = (int)$params['id_order'];
		if ($params['newOrderStatus']->paid || $params['newOrderStatus']->id == (int)Configuration::get('PS_OS_CANCELED')) {
			$order = new Order($id_order);
			if (Validate::isLoadedObject($order)) {
				$id_carrier = $order->id_carrier;
				$carrier = new Carrier($id_carrier);
				if ($carrier->external_module_name == $this->name) {
					$id_service = self::getServiceIdByCarrierReference($carrier->id_reference);
					if ($id_service == self::$lockers_service_id && $id_locker = self::getSelectedLocker((int)$order->id_cart)) {
						$lo = new LivrariOnline\LO1;
						$lo->f_login = (int)Configuration::get('LO_LOGINID');

						$lo->setRSAKey(Configuration::get('LO_KEY'));

						if ($params['newOrderStatus']->id == (int)Configuration::get('PS_OS_CANCELED')) {
							$lo->minus_expectedin($id_locker, $id_order);
						} else {
							$plus = $lo->plus_expectedin($id_locker, $id_order);
						}
					}
				}
			}
		}
	}

	public function hookDisplayAdminOrder($params)
	{
		if (!empty($params['id_order'])) {
			if (self::getAwbsForOrder((int)$params['id_order'])) {
				return $this->printAwbsTpl((int)$params['id_order']);
			}
			$order = new Order((int)$params['id_order']);
			if (Validate::isLoadedObject($order)) {
				$id_carrier = (int)$order->id_carrier;
				if ($id_carrier) {
					$carrier = new Carrier($id_carrier);
					if ($carrier->external_module_name == $this->name && (!(int)Configuration::get('LO_STATUS') || (int)Configuration::get('LO_STATUS') == $order->getCurrentState())) {
						$services = Tools::jsonDecode(Configuration::get('LO_NATIONAL_FIELDS'), true);
						$order_messages = Message::getMessagesByOrderId($order->id, false);
						if ($order_messages && isset($order_messages[0])) {
							$order_message = $order_messages[0]['message'];
						} else {
							$order_message = "";
						}
						$currency = new Currency($order->id_currency);
						$currency_iso = $currency->iso_code;
						$total_weight = number_format((float)$order->getTotalWeight() ?: 1, 2, '.', '');

						$id_address = (int)$order->id_address_delivery;
						$address = new Address($id_address);
						$id_customer = (int)$order->id_customer;
						$customer = new Customer($id_customer);
						$deliveryState = new State($address->id_state);

						$f_request_awb['destinatar'] = array(
							'first_name'   => $address->firstname,
							'last_name'    => $address->lastname,
							'email'        => $customer->email,
							'phone'        => $address->phone ?: '',
							'mobile'       => $address->phone_mobile ?: '',
							'lang'         => Language::getIsoById($order->id_lang),
							'company_name' => $address->company ?: '',
							'j'            => $address->dni ?: '',
							'bank_account' => '',
							'bank_name'    => '',
							'cui'          => $address->vat_number ?: '',
						);

						$f_request_awb['shipTOaddress'] = array(
							'address1'   => $address->address1,
							'address2'   => $address->address2,
							'city'       => $address->city,
							'state'      => $deliveryState->name,
							'zip'        => $address->postcode,
							'country'    => $address->country,
							'phone'      => ($address->phone_mobile ? $address->phone_mobile : $address->phone),
							'observatii' => '',
						);

						$this->context->smarty->assign(array(
							'id_order'               => $order->id,
							'shop_name '             => Configuration::get('PS_SHOP_NAME'),
							'services'               => $services,
							'id_carrier_reference'   => $carrier->id_reference,
							'order_message'          => $order_message,
							'order_reference'        => $order->reference,
							'products_value'         => round((float)$order->getTotalProductsWithTaxes(), 2),
							'currency_iso'           => $currency_iso,
							'pickup_address'         => self::getPickupAddress(),
							'total_weight'           => $total_weight,
							'f_request_awb'          => base64_encode(Tools::jsonEncode($f_request_awb)),
							'selected_service_id'    => self::getServiceIdByCarrierReference($carrier->id_reference),
							'smartlocker_service_id' => self::$lockers_service_id,
							'lockers'                => self::getLockersData(false, true),
							'id_selected_locker'     => self::getSelectedLocker($order->id_cart),
							'ramburs'                => $order->module == 'plationline' ? 0 : round((float)$order->getTotalPaid(), 2),
						));
						return $this->display(__FILE__, 'views/templates/admin/order.tpl');
					}
				}
			}
		}
		return false;
	}

	public static function getServiceIdByCarrierReference($id_carrier_reference)
	{
		$carriers = Tools::jsonDecode(Configuration::get('LO_NATIONAL_FIELDS'));
		foreach ($carriers as $carrier) {
			if ($id_carrier_reference == $carrier->carrier) {
				return $carrier->serviciuid;
			}
		}
		return 0;
	}

	public static function multidimensional_search($parents, $searched)
	{
		if (empty($searched) || empty($parents)) {
			return false;
		}

		$keys = array();

		foreach ($parents as $key => $value) {
			$exists = true;
			foreach ($searched as $skey => $svalue) {
				$exists = ($exists && isset($parents[$key][$skey]) && $parents[$key][$skey] == $svalue);
			}
			if ($exists) {
				$keys[] = $key;
			}
		}

		return $keys;
	}

	public function EstimeazaPret($params)
	{
		$address = new Address($params->id_address_delivery);
		$cart = new Cart($params->id);
		$currency = new Currency($cart->id_currency);

		if (!Validate::isLoadedObject($address)) {
			// If address is not loaded, we take data from shipping estimator module (if installed)
			global $cookie;
			$address->postcode = $cookie->postcode;
			$address->city = $cookie->city;
			$address->id_state = $cookie->id_state;
			$address->company = $cookie->company;
			$address->lastname = $cookie->lastname;
			$address->firstname = $cookie->firstname;
			$address->address1 = $cookie->address1;
			$address->address2 = $cookie->address2;
			$address->phone_mobile = $cookie->phone_mobile;
		}

		$products = $params->getProducts();

		$ramburs_ron = (float)$cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
		$cart_id_currency = $cart->id_currency;
		$ramburs_ron = self::convertToRON($ramburs_ron, $cart_id_currency);

		$f_request_awb = array();
		$f_request_awb['f_shipping_company_id'] = 107;
		$f_request_awb['request_data_ridicare'] = date('Y-m-d');
		$f_request_awb['request_ora_ridicare'] = date('H:i:s');
		$f_request_awb['request_ora_ridicare_end'] = date('H:i:s');
		$f_request_awb['request_ora_livrare_sambata'] = date('H:i:s');
		$f_request_awb['request_ora_livrare_end_sambata'] = date('H:i:s');
		$f_request_awb['request_ora_livrare'] = date('H:i:s');
		$f_request_awb['request_ora_livrare_end'] = date('H:i:s');
		$f_request_awb['descriere_livrare'] = 'estimare pret ' . Configuration::get('PS_SHOP_NAME');
		$f_request_awb['referinta_expeditor'] = '';
		$f_request_awb['valoare_declarata'] = (float)$ramburs_ron;
		$f_request_awb['ramburs'] = (float)$ramburs_ron;
		$f_request_awb['asigurare_la_valoarea_declarata'] = false;
		$f_request_awb['retur_documente'] = false;
		$f_request_awb['retur_documente_bancare'] = false;
		$f_request_awb['confirmare_livrare'] = false;
		$f_request_awb['livrare_sambata'] = false;
		$f_request_awb['currency'] = 'RON';
		$f_request_awb['currency_ramburs'] = 'RON';
		$f_request_awb['notificare_email'] = false;
		$f_request_awb['notificare_sms'] = false;
		$f_request_awb['cine_plateste'] = 0;
		$f_request_awb['serviciuid'] = 0;
		$f_request_awb['request_mpod'] = false;
		if ($id_locker = self::getSelectedLocker($cart->id)) {
			$f_request_awb['dulapid'] = $id_locker;
		} else {
			$f_request_awb['dulapid'] = 0;
		}

		$national_fields = Tools::jsonDecode(Configuration::get('LO_NATIONAL_FIELDS'), true);

		if (isset($params->serviciuid) && (int)$params->serviciuid == self::$lockers_service_id && !$id_locker) {
			foreach ($national_fields as $carrier) {
				if ($carrier['serviciuid'] == $params->serviciuid && isset($carrier['flatprice']) && (float)$carrier['flatprice']) {
					return self::convertFromRON((float)$carrier['flatprice']);
				}
			}
			return false;
		}

		$colete = array();
		$greutate = 0;
		foreach ($products as $product) {
			$greutate += (int)$product['quantity'] * ($product['weight'] ? (float)$product['weight'] : 1);
		}

		$colete[] = array(
			'greutate' => $greutate,
			'lungime'  => 1,
			'latime'   => 1,
			'inaltime' => 1,
			'continut' => 1,
			'tipcolet' => 2,
		);

		$f_request_awb['colete'] = $colete;

		$customer = new Customer($address->id_customer);
		$state = new State($address->id_state);

		$f_request_awb['destinatar'] = array(
			'first_name'   => $address->firstname,                                         //Obligatoriu
			'last_name'    => $address->lastname,                                              //Obligatoriu
			'email'        => $customer->email?:'',                                //Obligatoriu
			'phone'        => $address->phone ?: '',                                                 //phone sau mobile Obligatoriu
			'mobile'       => $address->phone_mobile ?: '',
			'lang'         => 'ro',                                                //Obligatoriu ro/en
			'company_name' => $address->company ?: '',                                   //optional
			'j'            => '',                                      //optional
			'bank_account' => '',                              //optional
			'bank_name'    => '',                                              //optional
			'cui'          => ''                                           //optional
		);

		$f_request_awb['shipTOaddress'] = array(
			'address1'   => $address->address1,
			'address2'   => $address->address2,
			'city'       => $address->city,
			'state'      => $state->name,
			'zip'        => $address->postcode,
			'country'    => Country::getIsoById($address->id_country),
			'phone'      => $address->phone_mobile,
			'observatii' => '',
		);

		$f_request_awb['shipFROMaddress'] = array(
			'email'        => Configuration::get('LO_STORE_EMAIL'),
			'first_name'   => Configuration::get('LO_STORE_NAME'),
			'last_name'    => '',
			'mobile'       => '',
			'main_address' => Configuration::get('LO_STORE_ADDRESS'),
			'city'         => Configuration::get('LO_STORE_CITY'),
			'state'        => State::getNameById((int)Configuration::get('LO_STORE_STATE')),
			'zip'          => Configuration::get('LO_STORE_ZIP'),
			'country'      => Configuration::get('LO_STORE_COUNTRY'),
			'phone'        => Configuration::get('LO_STORE_PHONE'),
			'instructiuni' => '',
		);

		$lo = new LivrariOnline\LO1();
		$lo->f_login = (int)Configuration::get('LO_LOGINID');
		$lo->setRSAKey(Configuration::get('LO_KEY'));

		$raspuns = $lo->EstimeazaPretServicii($f_request_awb);

		if ($raspuns->status == 'error') {
			return false;
		}

		$preturi = json_encode($raspuns);
		$preturi = json_decode($preturi, true);

		if (empty($preturi)) {
			return false;
		}
		$local = false;
		//die(var_dump((float)$rambursare));
		if ($raspuns && is_array($raspuns)) {
			$matches = self::multidimensional_search($preturi, array('f_tip' => 'l'));

			if (!empty($matches)) {
				foreach ($matches as $key => $value) {
					$m = self::multidimensional_search($national_fields, array('serviciuid' => $preturi[$value]['f_serviciuid']));
					if (!empty($m)) {
						foreach ($m as $v) {
							$carrier = $national_fields[$v];
							if (isset($ramburs_ron) && (float)$carrier['freeamount'] && (float)$ramburs_ron >= (float)$carrier['freeamount']) {
								$raspuns[$value]->f_pret = 0;
							} elseif ($carrier['fixedprice']) {
								$raspuns[$value]->f_pret = (float)$carrier['fixedprice'];
							} else {
								if ((float)$carrier['modify_amount']) {
									if ($carrier['modify_how'] == 'amount') {
										$diff = (float)$carrier['modify_amount'];
									} else {
										$diff = (float)$raspuns[$value]->f_pret * ((float)$carrier['modify_amount'] / 100);
									}

									if ($carrier['modify_sign'] == '+') {
										$raspuns[$value]->f_pret += $diff;
									} else {
										$raspuns[$value]->f_pret -= $diff;
									}
								}
							}
						}
					}
					$raspuns[$value]->f_pret = self::convertFromRON($raspuns[$value]->f_pret, $cart->id_currency);
					self::$estimari[$address->id][$raspuns[$value]->f_serviciuid] = $raspuns[$value]->f_pret;
					$local = true;
					break;
				}
			}
		}

		if ($raspuns && is_array($raspuns)) {
			$matches = self::multidimensional_search($preturi, array('f_tip' => 'n'));

			if ($local == false && !empty($matches)) {
				foreach ($matches as $key => $value) {
					$m = self::multidimensional_search($national_fields, array('serviciuid' => $preturi[$value]['f_serviciuid']));
					if (!empty($m)) {
						foreach ($m as $v) {
							$carrier = $national_fields[$v];
							if (isset($ramburs_ron) && (float)$carrier['freeamount'] && (float)$ramburs_ron >= (float)$carrier['freeamount']) {
								$raspuns[$value]->f_pret = 0;
							} elseif ($carrier['fixedprice']) {
								$raspuns[$value]->f_pret = (float)$carrier['fixedprice'];
							} else {
								if ((float)$carrier['modify_amount']) {
									if ($carrier['modify_how'] == 'amount') {
										$diff = (float)$carrier['modify_amount'];
									} else {
										$diff = (float)$raspuns[$value]->f_pret * ((float)$carrier['modify_amount'] / 100);
									}

									if ($carrier['modify_sign'] == '+') {
										$raspuns[$value]->f_pret += $diff;
									} else {
										$raspuns[$value]->f_pret -= $diff;
									}
								}
							}
						}
					}
					$raspuns[$value]->f_pret = self::convertFromRON($raspuns[$value]->f_pret, $cart->id_currency);
					self::$estimari[$address->id][$raspuns[$value]->f_serviciuid] = $raspuns[$value]->f_pret;
					break;
				}
			}
		}

		if ($id_locker) {

			$f_request_awb['ramburs'] = 0;
			$f_request_awb['serviciuid'] = 486;
			$raspuns_p = $lo->EstimeazaPretSmartlocker($f_request_awb, $id_locker, $cart->id);
		}

		if ($raspuns && is_array($raspuns)) {
			$matches = self::multidimensional_search($preturi, array('f_tip' => 'p'));
			if (!empty($matches)) {
				foreach ($matches as $key => $value) {
					$m = self::multidimensional_search($national_fields, array('serviciuid' => $preturi[$value]['f_serviciuid']));
					if (!empty($m)) {
						foreach ($m as $v) {
							$carrier = $national_fields[$v];
							if (isset($raspuns_p) && $raspuns[$value]->f_serviciuid == 486) {
								$raspuns[$value]->f_pret = $raspuns_p->f_pret;
							}
							if (isset($ramburs_ron) && (float)$carrier['freeamount'] && (float)$ramburs_ron >= (float)$carrier['freeamount']) {
								$raspuns[$value]->f_pret = 0;
							} elseif ($carrier['fixedprice']) {
								$raspuns[$value]->f_pret = (float)$carrier['fixedprice'];
							} else {
								if ((float)$carrier['modify_amount']) {
									if ($carrier['modify_how'] == 'amount') {
										$diff = (float)$carrier['modify_amount'];
									} else {
										$diff = (float)$raspuns[$value]->f_pret * ((float)$carrier['modify_amount'] / 100);
									}

									if ($carrier['modify_sign'] == '+') {
										$raspuns[$value]->f_pret += $diff;
									} else {
										$raspuns[$value]->f_pret -= $diff;
									}
								}
							}
						}
					}
					$raspuns[$value]->f_pret = self::convertFromRON($raspuns[$value]->f_pret, $cart->id_currency);
					self::$estimari[$address->id][$raspuns[$value]->f_serviciuid] = $raspuns[$value]->f_pret;
					break;
				}
			}
		}

		if (isset(self::$estimari[$address->id])) {
			return self::$estimari[$address->id];
		} else {
			return false;
		}
	}

	public static function getSelectedLocker($id_cart)
	{
		return (int)Db::getInstance()->getValue('SELECT `id_locker` FROM `' . _DB_PREFIX_ . 'lo_dp_cart` WHERE `id_cart` = "' . (int)$id_cart . '"');
	}

	public static function saveCartLocker($id_cart, $id_locker)
	{
		if (!$id_locker) {
			return Db::getInstance()->delete('lo_dp_cart', '`id_cart` = "' . (int)$id_cart . '"');
		} else {
			return Db::getInstance()->insert('lo_dp_cart', array(
				'id_cart'   => (int)$id_cart,
				'id_locker' => (int)$id_locker,
			), false, true, Db::REPLACE);
		}
	}

	public static function getLockerName($id_locker)
	{
		if (!$id_locker) {
			return false;
		} else {
			return Db::getInstance()->getValue('SELECT `dp_denumire` FROM `' . _DB_PREFIX_ . 'lo_delivery_points` WHERE `dp_id` = "' . (int)$id_locker . '"');
		}
	}

	public static function getDir()
	{
		return __DIR__ . '/';
	}

	public static function logissn($log)
	{
		//file_put_contents(__DIR__ . '/issn.log', '[' . date('Y-m-d H:i:s') . '] ' . $log . PHP_EOL, FILE_APPEND);
	}

	public static function convertToRON($sum, $id_currency_now = 0)
	{
		if (!$id_currency_now) {
			$context = Context::getContext();
			$id_currency_now = (int)$context->currency->id;
		}
		if (!$id_currency_now) {
			return $sum;
		}
		$id_currency_ron = Currency::getIdByIsoCode('RON');
		$currency_now = new Currency($id_currency_now);
		if ($id_currency_ron) {
			$currency_ron = new Currency($id_currency_ron);
			return number_format(round((float)Tools::convertPriceFull($sum, $currency_now, $currency_ron), 2), 2, '.', '');
		}
	}

	public static function convertFromRON($sum, $id_currency_now = 0)
	{
		if (!$id_currency_now) {
			$context = Context::getContext();
			$id_currency_now = (int)$context->currency->id;
		}
		if (!$id_currency_now) {
			return $sum;
		}
		$id_currency_ron = Currency::getIdByIsoCode('RON');
		$currency_now = new Currency($id_currency_now);
		if ($id_currency_ron) {
			$currency_ron = new Currency($id_currency_ron);
			return number_format(round((float)Tools::convertPriceFull($sum, $currency_ron, $currency_now), 2), 2, '.', '');
		}
	}

	public static function getPickupAddress()
	{
		$datas = array(
			'LO_STORE_EMAIL',
			'LO_STORE_NAME',
			'LO_STORE_COUNTRY',
			'LO_STORE_STATE',
			'LO_STORE_CITY',
			'LO_STORE_ADDRESS',
			'LO_STORE_ZIP',
			'LO_STORE_PHONE',
		);
		$datas = Configuration::getMultiple($datas);
		$pickup_address = array(
			'email'   => $datas['LO_STORE_EMAIL'],
			'name'    => $datas['LO_STORE_NAME'],
			'address' => $datas['LO_STORE_ADDRESS'],
			'city'    => $datas['LO_STORE_CITY'],
			'state'   => State::getNameById($datas['LO_STORE_STATE']),
			'zip'     => $datas['LO_STORE_ZIP'],
			'country' => $datas['LO_STORE_COUNTRY'],
			'phone'   => $datas['LO_STORE_PHONE'],
		);
		return $pickup_address;
	}

	public static function registerAwbs($data, $awbs)
	{
		foreach ($awbs as $awb) {
			$data['awb'] = $awb;
			Db::getInstance()->insert('lo', $data);
		}
		return true;
	}

	public static function deleteAwb($awb)
	{
		return Db::getInstance()->delete('lo', '`awb` = "' . pSQL($awb) . '"');
	}

	public static function getIssnOrderStates()
	{
		$data = '{"35":"Modificata","40":"Livrare acceptata","50":"Alocata curier","55":"Vizualizata curier","90":"Preluata partial de curier de la comerciant","100":"Preluata de curier de la comerciant","110":"Preluata de curier din Smart Locker","120":"Predata partial in hub","130":"Predata in hub","150":"Preluata de curier din hub","250":"Destinatarul nu a fost gasit","290":"Predata in Smart Locker","300":"Livrata la destinatar","350":"Livrare amanata","400":"Redirectionare destinatar","450":"Destinatarul nu a fost gasit","500":"Destinatie gresita","550":"In curs de anulare","600":"Anulata","625":"Retur in hub","650":"Retur","655":"Retur finalizat","660":"Retur documente","830":"Ramburs in plic cu bani","850":"Ramburs achitat","860":"Ramburs achitat confirmat","900":"Facturata","1000":"Finalizata"}';
		$data_array = Tools::jsonDecode($data, true);
		$states = array();
		foreach ($data_array as $id_lo_state => $state_name) {
			$states[] = array(
				'id_lo_state' => (int)$id_lo_state,
				'state_name'  => $state_name,
				'id_ps_os'    => (int)Configuration::get('LO_OS_' . (int)$id_lo_state),
			);
		}
		return $states;
	}

	public static function getPsOrderStates()
	{
		$context = Context::getContext();
		$id_lang = $context->language->id;
		return OrderState::getOrderStates($id_lang);
	}

	public static function changeOrderStatusByAwb($awb, $id_lo_status)
	{
		self::updateLastStatusIdByAwb($awb, $id_lo_status);
		if ($id_ps_status = Configuration::get('LO_OS_' . (int)$id_lo_status)) {
			$id_order = self::getOrderIdByAwb($awb);
			if ($id_order) {
				$order = new Order($id_order);
				if (Validate::isLoadedObject($order)) {
					$context = Context::getContext();
					$context->language = new Language($order->id_lang);
					$history = new OrderHistory();
					$history->id_order = $order->id;
					$history->id_employee = 0;

					$use_existings_payment = !$order->hasInvoice();
					$history->changeIdOrderState($id_ps_status, $order, $use_existings_payment);

					$carrier = new Carrier($order->id_carrier, $order->id_lang);
					$templateVars = array();
					if ($id_ps_status == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number) {
						$templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
					}

					if ($history->addWithemail(true, $templateVars)) {
						if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
							foreach ($order->getProducts() as $product) {
								if (StockAvailable::dependsOnStock($product['product_id'])) {
									StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
								}
							}
						}
						self::logissn('Order #' . $id_order . ' - status changed to #' . $id_ps_status);
					} else {
						self::logissn('Could not send history email');
					}
				} else {
					self::logissn('ORDER #' . $id_order . ' could not be loaded');
				}
			} else {
				self::logissn('NO ID ORDER FOUND FOR AWB ' . $awb);
			}
		} else {
			self::logissn('LO STATE #' . $id_lo_status . ' not configured');
		}
	}

	public static function updateLastStatusIdByAwb($awb, $f_statusid)
	{
		return (bool)Db::getInstance()->update('lo', array('f_statusid' => (int)$f_statusid), '`awb` = "' . pSQL($awb) . '"');
	}

	public static function getOrderIdByAwb($awb)
	{
		return (int)Db::getInstance()->getValue('SELECT `id_order` FROM `' . _DB_PREFIX_ . 'lo` WHERE `awb` = "' . pSQL($awb) . '"');
	}

	public static function setCarrierSmartLocker()
	{
		$servicii_nationale = Tools::jsonDecode(Configuration::get('LO_NATIONAL_FIELDS'), true);
		foreach ($servicii_nationale as $serviciu) {
			if ($serviciu['serviciuid'] == self::$lockers_service_id) {
				$id_reference = (int)$serviciu['carrier'];
				$id_carrier = (int)Db::getInstance()->getValue('SELECT `id_carrier` FROM `' . _DB_PREFIX_ . 'carrier`
			WHERE id_reference = ' . (int)$id_reference . ' AND deleted = 0 ORDER BY id_carrier DESC');
				if ($id_carrier) {
					$context = Context::getContext();
					$delivery_option = $context->cart->getDeliveryOption();
					$delivery_option[(int)$context->cart->id_address_delivery] = $id_carrier . ",";
					$context->cart->setDeliveryOption($delivery_option);
					$context->cart->save();
				}
			}
		}
	}

	public function generateAjaxToken()
	{
		return Tools::encrypt('livrarionline_ajax_token');
	}
}
