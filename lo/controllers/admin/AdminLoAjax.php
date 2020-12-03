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

class AdminLoAjaxController extends AdminController
{
	public function initContent()
	{
		$lo_module = Module::getInstanceByName('lo');
		if (Tools::getValue('action') == 'getAwb') {
			$rsakey = Configuration::get('LO_KEY');
			$post = Tools::getAllValues();
			$id_order = (int)Tools::getValue('id_order');

			$lo = new LivrariOnline\LO1;

			$f_request_awb = array();

			//preiau serviciul selectat. Vine de forma 1|2, unde 1 este serviciuid si 2 este shipping_company_id, Denumire = denumirea serviciului prin care se trimite coletul
			$serviciu = $post['serviciuid'];
			$serviciu = explode('|', $serviciu);

			$f_request_awb['serviciuid'] = (int)$serviciu[0];
			$f_request_awb['f_shipping_company_id'] = (int)$serviciu[1];
			$f_request_awb['request_data_ridicare'] = $post['request_data_ridicare'];
			$f_request_awb['request_ora_ridicare'] = $post['request_ora_ridicare'];
			$f_request_awb['request_ora_ridicare_end'] = $post['request_ora_ridicare_end'];
			$f_request_awb['request_ora_livrare_sambata'] = $post['request_ora_livrare_sambata'];
			$f_request_awb['request_ora_livrare_sambata_end'] = $post['request_ora_livrare_sambata_end'];
			$f_request_awb['request_ora_livrare'] = $post['request_ora_livrare'];
			$f_request_awb['request_ora_livrare_end'] = $post['request_ora_livrare_end'];
			$f_request_awb['descriere_livrare'] = $post['descriere_livrare'];
			$f_request_awb['referinta_expeditor'] = $post['referinta_expeditor'];
			$f_request_awb['valoare_declarata'] = (float)$post['valoare_declarata'];
			$f_request_awb['ramburs'] = (float)$post['ramburs'];
			$f_request_awb['asigurare_la_valoarea_declarata'] = $lo->checkboxSelected(isset($post['asigurare_la_valoarea_declarata']) ? $post['asigurare_la_valoarea_declarata'] : '');
			$f_request_awb['retur_documente'] = $lo->checkboxSelected(isset($post['retur_documente']) ? $post['retur_documente'] : '');
			$f_request_awb['retur_documente_bancare'] = $lo->checkboxSelected(isset($post['retur_documente_bancare']) ? $post['retur_documente_bancare'] : '');
			$f_request_awb['confirmare_livrare'] = $lo->checkboxSelected(isset($post['confirmare_livrare']) ? $post['confirmare_livrare'] : '');
			$f_request_awb['livrare_sambata'] = $lo->checkboxSelected(isset($post['livrare_sambata']) ? $post['livrare_sambata'] : '');
			$f_request_awb['currency'] = $post['currency'];
			$f_request_awb['currency_ramburs'] = $post['currency_ramburs'];
			$f_request_awb['notificare_email'] = $lo->checkboxSelected(isset($post['notificare_email']) ? $post['notificare_email'] : '');
			$f_request_awb['notificare_sms'] = $lo->checkboxSelected(isset($post['notificare_sms']) ? $post['notificare_sms'] : '');
			$f_request_awb['cine_plateste'] = (int)$post['cine_plateste'];
			$f_request_awb['serviciuid'] = (int)$post['serviciuid'];
			$f_request_awb['request_mpod'] = $lo->checkboxSelected(isset($post['request_mpod']) ? $post['request_mpod'] : '');

			$f_request_awb['shipFROMaddress'] = array(
				'email' => $post['pickup_address']['email'],
				'first_name' => $post['pickup_address']['name'],
				'last_name' => '',
				'mobile' => '',
				'main_address' => $post['pickup_address']['address'],
				'city' => $post['pickup_address']['city'],
				'state' => $post['pickup_address']['state'],
				'zip' => $post['pickup_address']['zip'],
				'country' => $post['pickup_address']['country'],
				'phone' => $post['pickup_address']['phone'],
				'instructiuni' => '',
			);


			$f_request_post = json_decode(base64_decode($post['f_request_awb']), true);

			$colete = array();

			for ($i = 0; $i < count($post['tipcolet']); $i++) {
				$colete[] = array(
					'greutate' => (float)$post['greutate'][$i],
					'lungime' => (float)$post['lungime'] [$i],
					'latime' => (float)$post['latime']  [$i],
					'inaltime' => (float)$post['inaltime'][$i],
					'continut' => (int)$post['continut'][$i],
					'tipcolet' => (int)$post['tipcolet'][$i],
				);
			}

			foreach ($f_request_post as $p => $value) {
				$f_request_awb[$p] = $value;
			}

			$f_request_awb['colete'] = $colete;


			//f_login si RSA key vor fi setate in config
			$lo->f_login = (int)Configuration::get('LO_LOGINID');
			$lo->setRSAKey($rsakey);
			if ((int)$serviciu[0] != $lo_module::$lockers_service_id) {
				$response_awb = $lo->GenerateAwb($f_request_awb);
			} else {
				$reservation = $lo->get_reservationid((int)Tools::getValue('locker'), 3, $id_order);
				$reservation = json_decode($reservation);
				if ($reservation->status == 'success') {
					$reservation_id = $reservation->f_reservation_id;
					unset($f_request_awb['shipTOaddress']);
					$f_request_awb['ramburs'] = 0;
					$response_awb = $lo->GenerateAwbSmartloker($f_request_awb, (int)Tools::getValue('locker'),
						$reservation_id, $id_order);  // 42 - dulapid, '10002222' - orderid
				} else {
					if (isset($reservation->message)) {
						$error = $reservation->message;
					} else {
						$error = $this->l('We could not generate a reservation ID');
					}
					die(json_encode(array(
						'success' => false,
						'error' => $error,
					)));
				}
			}
			//generare AWB
			if ($response_awb) {
				if (isset($response_awb->f_awb_collection)) {
					$data = array(
						'id_order' => $id_order,
						'date_add' => date('Y-m-d H:i:s'),
						'serviciu' => $serviciu[2],
					);
					$lo_module->registerAwbs($data, $response_awb->f_awb_collection);
					if ((int)$serviciu[0] == $lo_module::$lockers_service_id) {
						$lo->minus_expectedin((int)Tools::getValue('locker'), $id_order);
					}
					die(json_encode(array(
						'success' => true,
						'tpl' => $lo_module->printAwbsTpl($id_order),
					)));
				} elseif (isset($response_awb->status) && $response_awb->status == 'error') {
					if (isset($response_awb->message)) {
						die(json_encode(array(
							'success' => false,
							'error' => $response_awb->message,
						)));
					} else {
						die(json_encode(array(
							'success' => false,
							'error' => $this->l('Unknown error'),
						)));
					}
				} else {
					die(json_encode(array(
						'success' => false,
						'error' => json_encode($response_awb),
					)));
				}
			} else {
				die(json_encode(array(
					'success' => false,
					'error' => $this->l('No response from server. Please try again later.'),
				)));
			}
		} elseif (Tools::getValue('action') == 'tracking') {
			$lo = new LivrariOnline\LO1;
			$awb = Tools::getValue('awb');
			$rsakey = Configuration::get('LO_KEY');
			$lo->f_login = (int)Configuration::get('LO_LOGINID');
			$lo->setRSAKey($rsakey);

			$f_request_tracking = array();
			$f_request_tracking['awb'] = trim($awb);
			$response_tracking = $lo->Tracking($f_request_tracking);
			if (isset($response_tracking->status) && $response_tracking->status == 'error') {
				$response = '<h3>'.sprintf($this->l('Tracking AWB %s'), $awb).'</h3>';
				$response .= '<div>'.$response_tracking->message.'</div>';
				die(json_encode(array(
					'success' => false,
					'response' => $response,
				)));
			} else {
				$current = $response_tracking->f_stare_curenta;
				$history = $response_tracking->f_istoric;
				$response = '<h3>'.sprintf($this->l('Tracking AWB %s'), $awb).'</h3>';
				$current->stamp = date('Y-m-d H:i:s', strtotime($current->stamp));
				$response .= '<div><span>'.Tools::displayDate($current->stamp, null,
						true).'</span> - <span>'.$current->stare.'</span></div>';
				foreach ($history as $hi) {
					$hi->stamp = date('Y-m-d H:i:s', strtotime($hi->stamp));
					$response .= '<div><span>'.Tools::displayDate($hi->stamp, null,
							true).'</span> - <span>'.$hi->stare.'</span></div>';
				}
				die(json_encode(array(
					'success' => true,
					'response' => $response,
				)));
			}
		} elseif (Tools::getValue('action') == 'cancel') {
			$lo = new LivrariOnline\LO1;

			$awb = Tools::getValue('awb');
			$rsakey = Configuration::get('LO_KEY');
			$lo->f_login = (int)Configuration::get('LO_LOGINID');
			$lo->setRSAKey($rsakey);

			$f_request_cancel = array();
			$f_request_cancel['awb'] = trim($awb);
			$response_cancel = $lo->CancelLivrare($f_request_cancel);
			if (isset($response_cancel->status) && $response_cancel->status == 'error') {
				$response = '<h3>'.sprintf($this->l('Cancel AWB %s'), $awb).'</h3>';
				$response .= '<div>'.$response_cancel->message.'</div>';
				$lo_module->deleteAwb($awb);
				die(json_encode(array(
					'success' => false,
					'response' => $response,
				)));
			} else {
				$response = '<h3>'.sprintf($this->l('Cancel AWB %s'), $awb).'</h3>';
				if (isset($response_cancel->message) && $response_cancel->message) {
					$message = $response_cancel->message;
				} else {
					$message = $this->l('AWB was canceled.');
				}
				$response .= '<div>'.$message.'</div>';
				$lo_module->deleteAwb($awb);
				die(json_encode(array(
					'success' => true,
					'response' => $response,
				)));
			}
		} elseif (Tools::getValue('action') == 'return') {
			$lo = new LivrariOnline\LO1;

			$awb = Tools::getValue('awb');
			$id_order = $lo_module->getOrderIdByAwb($awb);
			$rsakey = Configuration::get('LO_KEY');
			$lo->f_login = (int)Configuration::get('LO_LOGINID');
			$lo->setRSAKey($rsakey);

			$f_request_cancel = array();
			$f_request_cancel['awb'] = trim($awb);
			$response_cancel = $lo->ReturnareLivrare($f_request_cancel);
			if (isset($response_cancel->status) && $response_cancel->status == 'error') {
				$response = '<h3>'.sprintf($this->l('Return AWB %s'), $awb).'</h3>';
				$response .= '<div>'.$response_cancel->message.'</div>';
				die(json_encode(array(
					'success' => false,
					'response' => $response,
				)));
			} else {
				$response = '<h3>'.sprintf($this->l('Return AWB %s'), $awb).'</h3>';
				$data = array(
					'id_order' => $id_order,
					'date_add' => date('Y-m-d H:i:s'),
					'serviciu' => 'RETUR',
				);
				$lo_module->registerAwbs($data, array($response_cancel->f_awb_retur));
				if (isset($response_cancel->message) && $response_cancel->message) {
					$message = $response_cancel->message;
				} else {
					$message = sprintf($this->l('Request for AWB return was made. Return awb: %s'),
						$response_cancel->f_awb_retur);
				}
				$response .= '<div>'.$message.'</div>';
				die(json_encode(array(
					'success' => true,
					'response' => $response,
				)));
			}
		}
	}
}
