<?php

namespace LivrariOnline;

use phpseclib\Crypt\AES;
use phpseclib\Crypt\RSA;
use sylouuu\Curl\Method as Curl;

class LO1
{
    //private
    private $f_request = null;
    private $f_secure = null;
    private $aes_key = null;
    private $iv = '285c02831e028bff';
    private $rsa_key = null;

    private $error = array('server' => 'Nu am putut comunica cu serverul', 'notJSON' => 'Raspunsul primit de la server nu este formatat corect (JSON)');
    //public
    public $f_login = null;
    public $version = null;

    //////////////////////////////////////////////////////////////
    // 						METODE PUBLICE						//
    //////////////////////////////////////////////////////////////

    //setez versiunea de kit
    public function __construct()
    {
        $this->version = "LO1.3_R20170704";
    }

    //setez cheia RSA
    public function setRSAKey($rsa_key)
    {
        $this->rsa_key = $rsa_key;
    }

    //helper pentru validarea bifarii unui checkbox si trimiterea de valori boolean catre server
    public function checkboxSelected($value)
    {
        if ($value) {
            return true;
        }
        return false;
    }

    public function encrypt_ISSN($input)
    {
        $aes_key = substr($this->rsa_key, 0, 16) . substr($this->rsa_key, -16);
        $aes = new AES();
        $aes->setIV($this->iv);
        $aes->setKey($aes_key);
        return \base64_encode($aes->encrypt($this->f_request));
    }

    public function decrypt_ISSN($input)
    {
        $aes_key = substr($this->rsa_key, 0, 16) . substr($this->rsa_key, -16);
        $aes = new AES();
        $aes->setIV($this->iv);
        $aes->setKey($aes_key);
        $issn = $aes->decrypt(base64_decode($input));
        return json_decode($issn);
    }

    //////////////////////////////////////////////////////////////
    // 				METODE COMUNICARE CU SERVER					//
    //////////////////////////////////////////////////////////////

    public function CancelLivrare($f_request)
    {
        return $this->LOCommunicate($f_request, 'https://api.livrarionline.ro/Lobackend.asmx/CancelLivrare');
    }

    public function GenerateAwb($f_request)
    {
        return $this->LOCommunicate($f_request, 'https://api.livrarionline.ro/Lobackend.asmx/GenerateAwb');
    }

    public function GetServicii($f_request = array())
    {
        return $this->LOCommunicate($f_request, 'https://api.livrarionline.ro/Lobackend.asmx/GetServicii');
    }

    public function GenerateAwbSmartloker($f_request, $delivery_point_id, $rezervation_id, $order_id)
    {
        $f_request['dulapid'] = (int)$delivery_point_id;
        $f_request['rezervationid'] = (int)$rezervation_id; // obtinut prin call-ul de rezervare prin metoda get_reservationid

        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "lo_delivery_points` where dp_id = " . (int)$delivery_point_id;
        $row = (object)\Db::getInstance()->getRow($sql);

        $f_request['shipTOaddress'] = array(                                                                            //Obligatoriu
                                                                                                                        'address1'   => $row->dp_adresa,
                                                                                                                        'address2'   => '',
                                                                                                                        'city'       => $row->dp_oras,
                                                                                                                        'state'      => $row->dp_judet,
                                                                                                                        'zip'        => $row->dp_cod_postal,
                                                                                                                        'country'    => $row->dp_tara,
                                                                                                                        'phone'      => '',
                                                                                                                        'observatii' => '',
        );

        return $this->LOCommunicate($f_request, 'https://api.livrarionline.ro/Lobackend.asmx/GenerateAwb');
    }

    public function RegisterAwb($f_request)
    {
        return $this->LOCommunicate($f_request, 'https://api.livrarionline.ro/Lobackend.asmx/RegisterAwb');
    }

    public function ReturnareLivrare($f_request)
    {
        return $this->LOCommunicate($f_request, 'https://api.livrarionline.ro/Lobackend.asmx/ReturnareLivrare');
    }

    public function PrintAwb($f_request, $class = '')
    {
        return '<a class="' . $class . '" id="print-awb" href="http://api.livrarionline.ro/Lobackend_print/PrintAwb.aspx?f_login=' . $this->f_login . '&awb=' . $f_request['awb'] . '" target="_blank">Click pentru print AWB</a>';
    }

    public function Tracking($f_request)
    {
        return $this->LOCommunicate($f_request, 'https://api.livrarionline.ro/Lobackend.asmx/Tracking');
    }

    public function EstimeazaPret($f_request)
    {
        return $this->LOCommunicate($f_request, 'https://estimare.livrarionline.ro/EstimarePret.asmx/EstimeazaPret');
    }

    public function EstimeazaPretServicii($f_request)
    {
        return $this->LOCommunicate($f_request, 'https://estimare.livrarionline.ro/EstimarePret.asmx/EstimeazaPretServicii');
    }

    public function EstimeazaPretSmartlocker($f_request, $delivery_point_id, $order_id)
    {
        $f_request['dulapid'] = (int)$delivery_point_id;
        $f_request['orderid'] = strval($order_id);

        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "lo_delivery_points` where dp_id = " . (int)$delivery_point_id;

        $row = (object)\Db::getInstance()->getRow($sql);

        $f_request['shipTOaddress'] = array(                                                                            //Obligatoriu
                                                                                                                        'address1'   => $row->dp_adresa,
                                                                                                                        'address2'   => '',
                                                                                                                        'city'       => $row->dp_oras,
                                                                                                                        'state'      => $row->dp_judet,
                                                                                                                        'zip'        => $row->dp_cod_postal,
                                                                                                                        'country'    => $row->dp_tara,
                                                                                                                        'phone'      => '',
                                                                                                                        'observatii' => '',
        );

        return $this->LOCommunicate($f_request, 'https://estimare.livrarionline.ro/EstimarePret.asmx/EstimeazaPret');
    }

    public function getExpectedIn($f_request)
    {
        return $this->LOCommunicate($f_request, 'https://smartlocker.livrarionline.ro/api/GetLockerExpectedInID', true);
    }

    public function cancelExpectedIn($f_request)
    {
        return $this->LOCommunicate($f_request, 'https://smartlocker.livrarionline.ro/api/CancelLockerExpectedInID', true);
    }

    public function get_sl_cell_reservation_id($f_request)
    {
        return $this->LOCommunicate($f_request, 'https://smartlocker.livrarionline.ro/api/GetLockerCellResevationID', true);
    }

    //////////////////////////////////////////////////////////////
    // 				END METODE COMUNICARE CU SERVER				//
    //////////////////////////////////////////////////////////////

    // CAUTARE PACHETOMATE DUPA LOCALITATE, JUDET SI DENUMIRE

    public function get_all_delivery_points_states()
    {
        $sql = "SELECT
				    distinct dp_judet as judet
				FROM
				    `" . _DB_PREFIX_ . "lo_delivery_points` dp
				WHERE
					dp_active > 0
				order by
				    dp.dp_judet asc
				";

        return \Db::getInstance()->executeS($sql);
    }

    public function get_all_delivery_points_location_by_state($judet)
    {
        $sql = "SELECT
				    distinct dp_oras as oras
				FROM
				    `" . _DB_PREFIX_ . "lo_delivery_points` dp
				WHERE
					dp_active > 0 and dp_judet = '" . pSQL($judet) . "'
				order by
				    dp.dp_oras asc
				";

        return \Db::getInstance()->executeS($sql);
    }

    public function get_all_delivery_points_location_by_judet($judet = '')
    {
        $sql = "SELECT
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
				" . ($judet ? ' and dp_judet = "' . pSQL($judet) . '"' : '') . "
				group by
					dp.dp_id
				order by
				    dp.dp_active desc, dp.dp_id asc
				";

        return \Db::getInstance()->executeS($sql);
    }

    public function get_all_delivery_points_location_by_localitate($oras = '')
    {
        $sql = "SELECT
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
				" . ($oras ? ' and dp_oras = "' . pSQL($oras) . '"' : '') . "
				group by
					dp.dp_id
				order by
				    dp.dp_active desc, dp.dp_id asc
				";

        return \Db::getInstance()->executeS($sql);
    }

    // AFISARE INFORMATII DESPRE SMARTLOCKER (adresa, orar) dupa selectarea smartlocker-ului din lista de pachetomate disponibile
    public function get_delivery_point_by_id($delivery_point_id)
    {
        $sql = "SELECT
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
				    `" . _DB_PREFIX_ . "lo_dp_program` p ON dp.dp_id = p.dp_id
				WHERE
					dp.dp_id = " . (int)$delivery_point_id . "
				group by
					dp.dp_id
				order by
				    dp.dp_active desc, dp.dp_id asc
				";

        return \Db::getInstance()->getRow($sql);
    }

    // END AFISARE INFORMATII DESPRE SMARTLOCKER (adresa, orar) dupa selectarea smartlocker-ului din lista de pachetomate disponibile

    public function get_all_delivery_points($search = '', $json = true)
    {
        $sql = "SELECT
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
					AND (
						dp_judet like '%" . pSQL($search) . "%'
						OR dp_oras like '%" . pSQL($search) . "%'
						OR dp_denumire like '%" . pSQL($search) . "%'
					)
				group by
					dp.dp_id
				order by
				    dp.dp_active desc, dp.dp_id asc
				";

        $delivery_points = array();

        $rows = \Db::getInstance()->executeS($sql);
        if ($rows) {
            foreach ($rows as $row) {
                $row = (object)$row;
                $delivery_points[] = array(
                    'id'          => $row->dp_id,
                    'denumire'    => $row->dp_denumire,
                    'adresa'      => $row->dp_adresa,
                    'judet'       => $row->dp_judet,
                    'localitate'  => $row->dp_oras,
                    'tara'        => $row->dp_tara,
                    'cod_postal'  => $row->dp_cod_postal,
                    'latitudine'  => $row->dp_gps_lat,
                    'longitudine' => $row->dp_gps_long,
                    'tip'         => ($row->dp_tip == 1 ? 'Pachetomat' : 'Punct de ridicare'),
                    'orar'        => $row->orar,
                    'disabled'    => ((int)$row->dp_active <= 0 ? true : false),
                );
            }
        }
        return \Tools::jsonEncode($delivery_points);
    }
    // END CAUTARE PACHETOMATE DUPA LOCALITATE, JUDET SI DENUMIRE


    // METODA INCREMENTARE EXPECTEDIN
    public function plus_expectedin($delivery_point_id, $orderid)
    {
        $f_request_expected_in = array();
        $f_request_expected_in['f_action'] = 3;
        $f_request_expected_in['f_orderid'] = strval($orderid);
        $f_request_expected_in['f_lockerid'] = $delivery_point_id;
        return $this->getExpectedIn($f_request_expected_in);
    }
    // END METODA INCREMENTARE EXPECTEDIN

    // METODA SCADERE EXPECTEDIN
    public function minus_expectedin($delivery_point_id, $orderid)
    {
        $f_request_expected_in = array();
        $f_request_expected_in['f_action'] = 8;
        $f_request_expected_in['f_orderid'] = strval($orderid);
        $f_request_expected_in['f_lockerid'] = $delivery_point_id;
        $this->cancelExpectedIn($f_request_expected_in);
    }
    // END METODA SCADERE EXPECTEDIN

    // GET RESERVATION ID
    public function get_reservationid($delivery_point_id, $cell_size = 3, $orderid)
    {
        $f_request = array();

        $f_request['f_action'] = 4;
        $f_request['f_lockerid'] = (int)$delivery_point_id;
        $f_request['f_marime_celula'] = (int)$cell_size;
        $f_request['f_orders_id'] = strval($orderid);

        $response = $this->get_sl_cell_reservation_id($f_request);

        if (isset($response->status) && $response->status == 'error') {
            $raspuns['status'] = 'error';
            $raspuns['message'] = $response->message;
        } else {
            if (isset($response->error) && $response->error == 1) {
                if ($response->error_code == '01523') {
                    // eroare rezervare celula
                }
                $raspuns['status'] = 'error';
                $raspuns['error_code'] = $response->error_code;
                $raspuns['message'] = $response->error_message;
            } else {
                $raspuns['status'] = 'success';
                $raspuns['f_lockerid'] = $response->f_lockerid;
                $raspuns['f_reservation_id'] = $response->f_reservation_id;
            }
        }
        return json_encode($raspuns);
    }

    // END GET RESERVATION ID

    public function issn()
    {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (\Tools::getValue('force_lockers')) {
            $user_agent = 'mozilla/5.0 (livrarionline.ro locker update service)';
        }

        switch ($user_agent) {
            case "mozilla/5.0 (livrarionline.ro locker update service aes)":
                $this->run_lockers_update();
                break;
            case "mozilla/5.0 (livrarionline.ro issn service)":
                $this->run_issn();
                break;
            default:
                $this->run_issn();
                break;
        }
    }

    //////////////////////////////////////////////////////////////
    // 					END METODE PUBLICE						//
    //////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////
    // 						METODE PRIVATE						//
    //////////////////////////////////////////////////////////////

    //criptez f_request cu AES
    private function AESEnc()
    {
        $this->aes_key = substr(hash('sha256', uniqid(), 0), 0, 32);
        $aes = new AES();
        $aes->setIV($this->iv);
        $aes->setKey($this->aes_key);
        $this->f_request = bin2hex(base64_encode($aes->encrypt($this->f_request)));
    }

    //criptez cheia AES cu RSA
    private function RSAEnc()
    {
        $rsa = new RSA();
        $rsa->loadKey($this->rsa_key);
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $this->f_secure = base64_encode($rsa->encrypt($this->aes_key));
    }

    //setez f_request, criptez f_request cu AES si cheia AES cu RSA
    private function setFRequest($f_request)
    {
        $this->f_request = json_encode($f_request);
        $this->AESEnc();
        $this->RSAEnc();
    }

    //construiesc JSON ce va fi trimis catre server
    private function createJSON($loapi = false)
    {
        $request = array();
        $request['f_login'] = $this->f_login;
        $request['f_request'] = $this->f_request;
        $request['f_secure'] = $this->f_secure;
        if (!$loapi) {
            return json_encode(array('loapi' => $request));
        } else {
            return json_encode($request);
        }
    }

    //metoda pentru verificarea daca un string este JSON - folosit la primirea raspunsului de la server
    private function isJSON($string)
    {
        if (is_object(json_decode($string))) {
            return true;
        }
        return false;
    }

    //metoda pentru verificarea raspunsului obtinut de la server. O voi apela cand primesc raspunsul de la server
    private function processResponse($response, $loapi = false)
    {
        //daca nu primesc raspuns de la server
        if ($response == false) {
            return (object)array('status' => 'error', 'message' => $this->error['server']);
        } else {
            //verific daca raspunsul este de tip JSON
            if ($this->isJSON($response)) {
                $response = json_decode($response);
                if (!$loapi) {
                    return $response->loapi;
                } else {
                    return $response;
                }
            } else {
                return (object)array('status' => 'error', 'message' => $this->error['notJSON']);
            }
        }
    }

    //metoda comunicare cu server LO
    private function LOCommunicate($f_request, $urltopost, $loapi = false)
    {
        $this->setFRequest($f_request);
        $request = new Curl\Post($urltopost, array(
            'data'       => array(
                'loapijson' => $this->createJSON($loapi),
            ),
            'is_payload' => false,
        ));
        $request->send();

        if ($request->getStatus() === 200) {
            $response = $request->getResponse();
            return $this->processResponse($response, $loapi);
        } else {
            throw new \Exception('Nu am putut comunica cu serverul LivrariOnline [response_code!=200]');
        }
    }

    // SMARTLOCKER UPDATE
    private function run_lockers_update()
    {
        $posted_json = file_get_contents('php://input');
        $lockers_data = $this->decrypt_ISSN($posted_json);

        if (empty($lockers_data)) {
            throw new \Exception('No data sent for Smartlocker update');
        }

        if (\Tools::getValue('force_lockers')) {
            $posted_json = '{"merchid":1692,"dulap":[{"dulapid":1,"denumire":"Panduri 71","adresa":"Sos. Panduri, nr.71","judet":"Bucuresti","oras":"Sector 5","tara":"Romania","latitudine":44.427443,"longitudine":26.065654,"versionid":253093,"active":1,"tip_dulap":1,"dp_temperatura":12,"dp_indicatii":"la parter între restaurant și Carrefour Express","termosensibil":0}, {"dulapid":2,"denumire":"Auchan Galați","adresa":"Bd. Galați, nr. 3A","judet":"Galati","oras":"Galati","tara":"Romania","latitudine":45.4048606,"longitudine":28.0203558,"versionid":253092,"active":1,"tip_dulap":1,"dp_temperatura":21,"dp_indicatii":"în complexul Auchan Galați, vis-a-vis de casele de marcat Auchan, dulapul portocaliu","termosensibil":0}, {"dulapid":3,"denumire":"Auchan Crangasi","adresa":"Bd. Constructorilor, nr. 16A","judet":"Bucuresti","oras":"Sector 6","tara":"Romania","latitudine":44.457623,"longitudine":26.040507,"versionid":252576,"active":1,"tip_dulap":1,"dp_temperatura":21,"dp_indicatii":"în complexul Auchan Crangași, intrarea din partea stangă, dulapul portocaliu","termosensibil":1}, {"dulapid":4,"denumire":"West Gate H1","adresa":"West Gate H1, Militari","judet":"Bucuresti","oras":"Sector 6","tara":"Romania","latitudine":44.4328694,"longitudine":25.9877376,"versionid":252690,"active":1,"tip_dulap":1,"dp_temperatura":25,"dp_indicatii":"în complexul West Gate din Militari, în recepția clădirii H1, dulapul portocaliu","termosensibil":0}, {"dulapid":5,"denumire":"Pipera Plaza","adresa":"Sos. București Nord","judet":"Ilfov","oras":"Voluntari","tara":"Romania","latitudine":44.4902259,"longitudine":26.1281866,"versionid":252369,"active":1,"tip_dulap":1,"dp_temperatura":19,"dp_indicatii":"intrarea Lidl de langa cladirea de birouri, dulapul portocaliu","termosensibil":0}, {"dulapid":6,"denumire":"Dristor Farmacia Tei","adresa":"Bd. Camil Ressu, nr. 7","judet":"Bucuresti","oras":"Sector 3","tara":"Romania","latitudine":44.4203533,"longitudine":26.1415708,"versionid":252949,"active":10,"tip_dulap":1,"dp_temperatura":27,"dp_indicatii":"în sediul FarmaciaTei din Dristor, dulapul portocaliu","termosensibil":0}, {"dulapid":7,"denumire":"West Gate H2","adresa":"West Gate H2, Militari","judet":"Bucuresti","oras":"Sector 6","tara":"Romania","latitudine":44.432774,"longitudine":25.986818,"versionid":251969,"active":1,"tip_dulap":1,"dp_temperatura":24,"dp_indicatii":"în complexul West Gate din Militari, în recepția clădirii H2, dulapul portocaliu","termosensibil":0}, {"dulapid":8,"denumire":"VIVO! Constanta","adresa":"Bulevardul Aurel Vlaicu nr. 220","judet":"Constanta","oras":"Constanta","tara":"Romania","latitudine":44.1992705,"longitudine":28.6105764,"versionid":253101,"active":1,"tip_dulap":1,"dp_temperatura":19,"dp_indicatii":"in Mall Vivo! Constanta, dulapul portocaliu este amplasat jos la scarile rulante din zona caselor Auchan","termosensibil":0}, {"dulapid":9,"denumire":"West Gate H4","adresa":"West Gate H4, Militari","judet":"Bucuresti","oras":"Sector 6","tara":"Romania","latitudine":44.432778,"longitudine":25.985992,"versionid":252876,"active":1,"tip_dulap":1,"dp_temperatura":25,"dp_indicatii":"în complexul West Gate din Militari, în recepția clădirii H4, dulapul portocaliu","termosensibil":0}, {"dulapid":10,"denumire":"West Gate H5","adresa":"West Gate H5, Militari","judet":"Bucuresti","oras":"Sector 6","tara":"Romania","latitudine":44.433303,"longitudine":25.985911,"versionid":251105,"active":1,"tip_dulap":1,"dp_temperatura":25,"dp_indicatii":"în complexul West Gate din Militari, în recepția clădirii H5, dulapul portocaliu","termosensibil":0}, {"dulapid":11,"denumire":"Vivo Cluj-Napoca","adresa":"Strada Avram Iancu 492-500","judet":"Cluj","oras":"Floresti","tara":"Romania","latitudine":46.7508645,"longitudine":23.5325164,"versionid":250441,"active":1,"tip_dulap":1,"dp_temperatura":null,"dp_indicatii":"dulapul portocaliu","termosensibil":0}, {"dulapid":15,"denumire":"Otopeni","adresa":"Str. 23 August Nr. 11, Bl. U5","judet":"Ilfov","oras":"Otopeni","tara":"Romania","latitudine":44.5511091,"longitudine":26.0731469,"versionid":253100,"active":1,"tip_dulap":1,"dp_temperatura":19,"dp_indicatii":"in Centrul Comercial de langa primarie, dulapul portocaliu","termosensibil":0}, {"dulapid":16,"denumire":"Auchan Vitan","adresa":"Calea Vitan, nr. 236","judet":"Bucuresti","oras":"Sector 3","tara":"Romania","latitudine":44.408276,"longitudine":26.139533,"versionid":252960,"active":1,"tip_dulap":1,"dp_temperatura":21,"dp_indicatii":"în complexul Auchan Vitan la intrarea din parcarea subterană lângă automatele de cafea și carți, dulapul portocaliu","termosensibil":1}, {"dulapid":17,"denumire":"Cora Constanta","adresa":"Strada Cumpenei nr. 2","judet":"Constanta","oras":"Constanta","tara":"Romania","latitudine":44.1692599,"longitudine":28.6103611,"versionid":252831,"active":1,"tip_dulap":1,"dp_temperatura":23,"dp_indicatii":"în complexul Cora Constanta la intrarea principala pe partea stanga, dulapul portocaliu","termosensibil":0}, {"dulapid":19,"denumire":"Auchan Brasov Vest (Eliana)","adresa":"Șos. Cristianului, nr. 5","judet":"Brasov","oras":"Brasov","tara":"Romania","latitudine":45.6574316,"longitudine":25.562219,"versionid":252850,"active":1,"tip_dulap":1,"dp_temperatura":23,"dp_indicatii":"în complexul Auchan Eliana vis-a-vis curățătorie haine, dulapul portocaliu","termosensibil":0}, {"dulapid":20,"denumire":"Auchan Dr. Taberei","adresa":"Str. Brasov, nr. 25","judet":"Bucuresti","oras":"Sector 6","tara":"Romania","latitudine":44.417000,"longitudine":26.03590,"versionid":253045,"active":1,"tip_dulap":1,"dp_temperatura":23,"dp_indicatii":"intrarea dinspre intersecție, vizavi de parc, la etajul 1 în partea dreaptă, dulapul portocaliu","termosensibil":0}, {"dulapid":21,"denumire":"Auchan Ploiești","adresa":"Centura de Vest","judet":"Prahova","oras":"Ploiesti","tara":"Romania","latitudine":44.945701,"longitudine":25.964018,"versionid":253000,"active":1,"tip_dulap":1,"dp_temperatura":18,"dp_indicatii":"în complexul Auchan Ploiești la intrare, dulapul portocaliu","termosensibil":0}, {"dulapid":26,"denumire":"Auchan Theodor Pallady","adresa":"Bd. Theodor Pallady, nr. 51","judet":"Bucuresti","oras":"Sector 3","tara":"Romania","latitudine":44.4084585,"longitudine":26.2031543,"versionid":252902,"active":1,"tip_dulap":1,"dp_temperatura":20,"dp_indicatii":"în complexul Auchan Th. Pallady la intrarea dinspre Metro, Zona servicii, dulapul portocaliu","termosensibil":0}, {"dulapid":27,"denumire":"Auchan Berceni","adresa":"Drumul Dealul Bisericii, nr. 67-109","judet":"Bucuresti","oras":"Sector 4","tara":"Romania","latitudine":44.3611067,"longitudine":26.1227625,"versionid":252821,"active":1,"tip_dulap":1,"dp_temperatura":22,"dp_indicatii":"în complexul Auchan Berceni de la intrarea principală mergeți pe galerie înspre dreapta, dulapul portocaliu","termosensibil":0}, {"dulapid":28,"denumire":"Plaza Romania","adresa":"Bulevardul Timișoara 26","judet":"Bucuresti","oras":"Sector 6","tara":"Romania","latitudine":44.4287734,"longitudine":26.0355156,"versionid":252874,"active":1,"tip_dulap":1,"dp_temperatura":26,"dp_indicatii":"in Plaza Romania la parter in incinta Farmacia Tei, dulapul portocaliu","termosensibil":0}, {"dulapid":29,"denumire":"Coresi Shopping Resort","adresa":"Strada Zaharia Stancu, nr. 1","judet":"Brasov","oras":"Brasov","tara":"Romania","latitudine":45.6728455,"longitudine":25.6159868,"versionid":253098,"active":10,"tip_dulap":1,"dp_temperatura":26,"dp_indicatii":"în complexul Coresi la intrarea Energiei, dulapul portocaliu","termosensibil":0}, {"dulapid":30,"denumire":"Sun Plaza","adresa":"Calea Vacaresti 391, Bucuresti ","judet":"Bucuresti","oras":"Sector 4","tara":"Romania","latitudine":44.395609,"longitudine":26.1232695,"versionid":253073,"active":1,"tip_dulap":1,"dp_temperatura":-62,"dp_indicatii":"dulapul portocaliu de pe culoarul ce leaga SunPlaza cu Leroy Merlin","termosensibil":0}, {"dulapid":35,"denumire":"Auchan Sibiu","adresa":"Strada Sibiului, nr. 5, Șelimbăr","judet":"Sibiu","oras":"Sibiu","tara":"Romania","latitudine":45.776372,"longitudine":24.169471,"versionid":253095,"active":1,"tip_dulap":1,"dp_temperatura":20,"dp_indicatii":"în complexul Auchan City, dulapul portocaliu","termosensibil":1}, {"dulapid":38,"denumire":"Auchan Constanta Sud","adresa":"Șos. Mangaliei, nr. 195A","judet":"Constanta","oras":"Constanta","tara":"Romania","latitudine":44.1341165,"longitudine":28.6228314,"versionid":251963,"active":1,"tip_dulap":1,"dp_temperatura":-62,"dp_indicatii":"în complexul Auchan Constanta Sud, dulapul portocaliu","termosensibil":1}, {"dulapid":40,"denumire":"TeComm Bucharest","adresa":"Calea Victoriei, Nr. 63-81","judet":"Bucuresti","oras":"Bucuresti","tara":"Romania","latitudine":44.441622,"longitudine":26.0915913,"versionid":234764,"active":-1,"tip_dulap":1,"dp_temperatura":24,"dp_indicatii":"Radisson Blu Hotel*****","termosensibil":0}, {"dulapid":47,"denumire":"Cora Cluj-Napoca","adresa":"Bd. 1 Decembrie 1918, nr. 142","judet":"Cluj","oras":"Cluj-Napoca","tara":"Romania","latitudine":46.7589818,"longitudine":23.5396698,"versionid":252944,"active":1,"tip_dulap":1,"dp_temperatura":24,"dp_indicatii":"în complexul Cora Cluj la intrare, dulapul portocaliu","termosensibil":1}, {"dulapid":49,"denumire":"Auchan Cluj-Napoca Iris","adresa":"Bulevardul Muncii 1-15","judet":"Cluj","oras":"Cluj-Napoca","tara":"Romania","latitudine":46.7998088,"longitudine":23.6117776,"versionid":250870,"active":1,"tip_dulap":1,"dp_temperatura":20,"dp_indicatii":"dulapul portocaliu","termosensibil":0}, {"dulapid":51,"denumire":"Trivale Shopping Center","adresa":"Strada Victoriei 12","judet":"Arges","oras":"Pitesti","tara":"Romania","latitudine":44.8571798,"longitudine":24.8734894,"versionid":253028,"active":1,"tip_dulap":1,"dp_temperatura":15,"dp_indicatii":"în complexul Trivale Shopping Center la intrarea/iesirea de la supermarket, dulapul portocaliu","termosensibil":0}, {"dulapid":53,"denumire":"Mall Liberty Center","adresa":"Str. Progresului, nr. 151 - 171","judet":"Bucuresti","oras":"Sector 5","tara":"Romania","latitudine":44.415073,"longitudine":26.080056,"versionid":253054,"active":1,"tip_dulap":1,"dp_temperatura":0,"dp_indicatii":"în mall la parter în holul de acces parcare auto, dulapul portocaliu","termosensibil":0}, {"dulapid":55,"denumire":"Auchan City - Targu Mures","adresa":"Bd. 1 Decembrie 1918, nr. 291","judet":"Mures","oras":"Targu Mures","tara":"Romania","latitudine":46.5311738,"longitudine":24.5986891,"versionid":252818,"active":1,"tip_dulap":1,"dp_temperatura":21,"dp_indicatii":"în complexul Auchan, dulapul portocaliu","termosensibil":0}, {"dulapid":58,"denumire":"Cora Alexandriei","adresa":"Sos. Alexandriei, nr. 152","judet":"Bucuresti","oras":"Sector 5","tara":"Romania","latitudine":44.399279,"longitudine":26.043659,"versionid":252919,"active":1,"tip_dulap":1,"dp_temperatura":23,"dp_indicatii":"în Cora Alexandriei de la intrarea principală în stanga înspre acces parcare auto, dulapul portocaliu","termosensibil":0}, {"dulapid":61,"denumire":"Auchan Gavana - Pitesti","adresa":"Strada Constantin Dobrogeanu Gherea 1","judet":"Arges","oras":"Pitesti","tara":"Romania","latitudine":44.886678,"longitudine":24.8301744,"versionid":251608,"active":1,"tip_dulap":1,"dp_temperatura":20,"dp_indicatii":"indicatii","termosensibil":0}, {"dulapid":64,"denumire":"Auchan Timisoara Nord","adresa":"Calea Aradului, nr. 56A","judet":"Timis","oras":"Timisoara","tara":"Romania","latitudine":45.785444,"longitudine":21.2216828,"versionid":253052,"active":1,"tip_dulap":1,"dp_temperatura":22,"dp_indicatii":"în complexul Auchan Nord la intrarea din partea stangă, dulapul portocaliu","termosensibil":0}, {"dulapid":67,"denumire":"Auchan Baia Mare","adresa":"Bulevardul București 144","judet":"Maramures","oras":"Baia Mare","tara":"Romania","latitudine":47.6445636,"longitudine":23.5255621,"versionid":253079,"active":1,"tip_dulap":1,"dp_temperatura":22,"dp_indicatii":"In complexul comercial Auchan Baia Mare","termosensibil":0}, {"dulapid":71,"denumire":"Auchan Bradu - Pitesti","adresa":"Comuna Bradu, Sat Geamana, DN 65B","judet":"Arges","oras":"Bradu","tara":"Romania","latitudine":44.8225661,"longitudine":24.910406,"versionid":252569,"active":1,"tip_dulap":1,"dp_temperatura":23,"dp_indicatii":"în complexul Auchan Bradu  vis-a-vis de parcarea carucioarelor din galerie, dulapul portocaliu","termosensibil":0}, {"dulapid":73,"denumire":"Cora Lujerului","adresa":"Bd. Iuliu Maniu, Nr 19","judet":"Bucuresti","oras":"Sector 6","tara":"Romania","latitudine":44.433132,"longitudine":26.0366958,"versionid":253026,"active":1,"tip_dulap":1,"dp_temperatura":14,"dp_indicatii":"intrarea în Cora Lujerului din partea stangă, paralelă cu bulevardul Iuliu Maniu, dulapul portocaliu","termosensibil":1}, {"dulapid":74,"denumire":"Auchan Timisoara Sud","adresa":"Calea Sagului, nr. 223, Chisoda","judet":"Timis","oras":"Timisoara","tara":"Romania","latitudine":45.704624,"longitudine":21.190709,"versionid":253090,"active":1,"tip_dulap":1,"dp_temperatura":23,"dp_indicatii":"în complexul Auchan Sud de la intrarea principală mergeți pe galerie înspre dreapta in zona de bancomate, dulapul portocaliu","termosensibil":0}, {"dulapid":77,"denumire":"Auchan Titan","adresa":"Bd. 1 Decembrie, nr. 33A","judet":"Bucuresti","oras":"Sector 3","tara":"Romania","latitudine":44.4199119,"longitudine":26.1797517,"versionid":253088,"active":1,"tip_dulap":1,"dp_temperatura":22,"dp_indicatii":"în complexul Auchan Titan, langă intrarea în hipermarket din partea stangă, după automatele de lapte, dulapul portocaliu","termosensibil":0}, {"dulapid":84,"denumire":"AUSHOPPING Oradea","adresa":"Strada Ogorului, nr. 171","judet":"Bihor","oras":"Oradea","tara":"Romania","latitudine":47.0467,"longitudine":21.9092542,"versionid":252878,"active":1,"tip_dulap":1,"dp_temperatura":23,"dp_indicatii":"In complexul comercial AUSHOPPING Oradea in holul intrarii de langa Jumbo","termosensibil":1}, {"dulapid":85,"denumire":"Auchan Satu Mare","adresa":"Strada Careiului","judet":"Satu Mare","oras":"Satu Mare","tara":"Romania","latitudine":47.7849088,"longitudine":22.8436419,"versionid":252517,"active":1,"tip_dulap":1,"dp_temperatura":21,"dp_indicatii":"în complexul Auchan Satu Mare, langa Altex si locul de joaca pt copii, dulapul portocaliu","termosensibil":0}, {"dulapid":91,"denumire":"Carpati Shopping Center - Sinaia","adresa":"Sinaia","judet":"Prahova","oras":"Sinaia","tara":"Romania","latitudine":45.349487,"longitudine":25.549108,"versionid":245346,"active":1,"tip_dulap":1,"dp_temperatura":8,"dp_indicatii":"indicatii","termosensibil":0}, {"dulapid":94,"denumire":"Auchan Craiova","adresa":"Calea Severinului 5A","judet":"Dolj","oras":"Craiova","tara":"Romania","latitudine":44.3274797,"longitudine":23.7756474,"versionid":252887,"active":1,"tip_dulap":1,"dp_temperatura":23,"dp_indicatii":"in complexul Auchan Craiova, intrarea din partea dreapta vizavi de casele de marcat si punctul Digi, sub scara rulanta,  dulapul portocaliu","termosensibil":0}, {"dulapid":95,"denumire":"Hello Shopping Park - Auchan Bacau","adresa":"Calea Republicii 181","judet":"Bacau","oras":"Bacau","tara":"Romania","latitudine":46.5105658,"longitudine":26.9289031,"versionid":253072,"active":1,"tip_dulap":1,"dp_temperatura":22,"dp_indicatii":"indicatii","termosensibil":0}],"zile2dulap":[{"start_program":"09:00:00","end_program":"21:00:00","dulapid":20,"active":1,"versionid":109,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":20,"active":1,"versionid":110,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":3,"active":1,"versionid":112,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":3,"active":1,"versionid":113,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":3,"active":1,"versionid":114,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":3,"active":1,"versionid":115,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":3,"active":1,"versionid":116,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":3,"active":1,"versionid":117,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":3,"active":1,"versionid":118,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":77,"active":1,"versionid":119,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":77,"active":1,"versionid":120,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":77,"active":1,"versionid":121,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":77,"active":1,"versionid":122,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":77,"active":1,"versionid":123,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":77,"active":1,"versionid":124,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"00:00:01","end_program":"23:59:59","dulapid":1,"active":2,"versionid":83,"day_number":1,"day_name":"Luni"}, {"start_program":"00:00:01","end_program":"23:59:59","dulapid":1,"active":2,"versionid":84,"day_number":5,"day_name":"Vineri"}, {"start_program":"00:00:01","end_program":"23:59:59","dulapid":1,"active":2,"versionid":85,"day_number":2,"day_name":"Marți"}, {"start_program":"00:00:01","end_program":"23:59:59","dulapid":1,"active":2,"versionid":86,"day_number":3,"day_name":"Miercuri"}, {"start_program":"00:00:01","end_program":"23:59:59","dulapid":1,"active":2,"versionid":87,"day_number":4,"day_name":"Joi"}, {"start_program":"00:00:01","end_program":"23:59:59","dulapid":1,"active":2,"versionid":88,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"00:00:01","end_program":"23:59:59","dulapid":1,"active":2,"versionid":89,"day_number":0,"day_name":"Duminică"}, {"start_program":"10:00:00","end_program":"23:00:00","dulapid":53,"active":1,"versionid":90,"day_number":1,"day_name":"Luni"}, {"start_program":"10:00:00","end_program":"23:00:00","dulapid":53,"active":1,"versionid":91,"day_number":2,"day_name":"Marți"}, {"start_program":"10:00:00","end_program":"23:00:00","dulapid":53,"active":1,"versionid":92,"day_number":3,"day_name":"Miercuri"}, {"start_program":"10:00:00","end_program":"23:00:00","dulapid":53,"active":1,"versionid":93,"day_number":4,"day_name":"Joi"}, {"start_program":"10:00:00","end_program":"23:00:00","dulapid":53,"active":1,"versionid":94,"day_number":5,"day_name":"Vineri"}, {"start_program":"10:00:00","end_program":"23:00:00","dulapid":53,"active":1,"versionid":95,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"10:00:00","end_program":"23:00:00","dulapid":53,"active":1,"versionid":96,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":58,"active":1,"versionid":97,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":58,"active":1,"versionid":98,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":58,"active":1,"versionid":99,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":58,"active":1,"versionid":100,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":58,"active":1,"versionid":101,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":58,"active":1,"versionid":102,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":58,"active":1,"versionid":103,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":20,"active":1,"versionid":104,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":20,"active":1,"versionid":105,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":20,"active":1,"versionid":106,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":20,"active":1,"versionid":107,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":20,"active":1,"versionid":108,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":77,"active":1,"versionid":125,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":27,"active":1,"versionid":126,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":27,"active":1,"versionid":127,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":27,"active":1,"versionid":128,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":27,"active":1,"versionid":129,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":27,"active":1,"versionid":130,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":27,"active":1,"versionid":131,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":27,"active":1,"versionid":132,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":26,"active":1,"versionid":133,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":26,"active":1,"versionid":134,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":26,"active":1,"versionid":135,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":26,"active":1,"versionid":136,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":26,"active":1,"versionid":137,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":26,"active":1,"versionid":138,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":26,"active":1,"versionid":139,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":16,"active":1,"versionid":140,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":16,"active":1,"versionid":141,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":16,"active":1,"versionid":142,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":16,"active":1,"versionid":143,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":16,"active":1,"versionid":144,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":16,"active":1,"versionid":145,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":16,"active":1,"versionid":146,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":29,"active":1,"versionid":147,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":29,"active":1,"versionid":148,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":29,"active":1,"versionid":149,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":29,"active":1,"versionid":150,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":29,"active":1,"versionid":151,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":29,"active":1,"versionid":152,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":29,"active":1,"versionid":153,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":19,"active":1,"versionid":154,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":19,"active":1,"versionid":155,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":19,"active":1,"versionid":156,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":19,"active":1,"versionid":157,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":19,"active":1,"versionid":158,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":19,"active":1,"versionid":159,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":19,"active":1,"versionid":160,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":73,"active":1,"versionid":161,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":73,"active":1,"versionid":162,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":73,"active":1,"versionid":163,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":73,"active":1,"versionid":164,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":73,"active":1,"versionid":165,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":73,"active":1,"versionid":166,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":73,"active":1,"versionid":167,"day_number":5,"day_name":"Vineri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":21,"active":1,"versionid":168,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":21,"active":1,"versionid":169,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":21,"active":1,"versionid":170,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":21,"active":1,"versionid":171,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":21,"active":1,"versionid":172,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":21,"active":1,"versionid":173,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":21,"active":1,"versionid":174,"day_number":5,"day_name":"Vineri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":47,"active":1,"versionid":175,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":47,"active":1,"versionid":176,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":47,"active":1,"versionid":177,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":47,"active":1,"versionid":178,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":47,"active":1,"versionid":179,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":47,"active":1,"versionid":180,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":47,"active":1,"versionid":181,"day_number":5,"day_name":"Vineri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":2,"active":1,"versionid":182,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":2,"active":1,"versionid":183,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":2,"active":1,"versionid":184,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":2,"active":1,"versionid":185,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":2,"active":1,"versionid":186,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":2,"active":1,"versionid":187,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":2,"active":1,"versionid":188,"day_number":5,"day_name":"Vineri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":38,"active":1,"versionid":189,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":38,"active":1,"versionid":190,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":38,"active":1,"versionid":191,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":38,"active":1,"versionid":192,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":38,"active":1,"versionid":193,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":38,"active":1,"versionid":194,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":38,"active":1,"versionid":195,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":35,"active":1,"versionid":217,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":35,"active":1,"versionid":218,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":35,"active":1,"versionid":219,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":35,"active":1,"versionid":220,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":35,"active":1,"versionid":221,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":35,"active":1,"versionid":222,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":35,"active":1,"versionid":223,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":55,"active":1,"versionid":224,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":55,"active":1,"versionid":225,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":55,"active":1,"versionid":226,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":55,"active":1,"versionid":227,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":55,"active":1,"versionid":228,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":55,"active":1,"versionid":229,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":55,"active":1,"versionid":230,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":74,"active":1,"versionid":231,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":74,"active":1,"versionid":232,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":74,"active":1,"versionid":233,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":74,"active":1,"versionid":234,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":74,"active":1,"versionid":235,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":74,"active":1,"versionid":236,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":74,"active":1,"versionid":237,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":64,"active":1,"versionid":238,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":64,"active":1,"versionid":239,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":64,"active":1,"versionid":240,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":64,"active":1,"versionid":241,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":64,"active":1,"versionid":242,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":64,"active":1,"versionid":243,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":64,"active":1,"versionid":244,"day_number":0,"day_name":"Duminică"}, {"start_program":"07:30:00","end_program":"22:30:00","dulapid":5,"active":1,"versionid":267,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"07:30:00","end_program":"22:30:00","dulapid":5,"active":1,"versionid":274,"day_number":0,"day_name":"Duminică"}, {"start_program":"07:30:00","end_program":"22:30:00","dulapid":5,"active":1,"versionid":275,"day_number":1,"day_name":"Luni"}, {"start_program":"07:30:00","end_program":"22:30:00","dulapid":5,"active":1,"versionid":276,"day_number":2,"day_name":"Marți"}, {"start_program":"07:30:00","end_program":"22:30:00","dulapid":5,"active":1,"versionid":277,"day_number":3,"day_name":"Miercuri"}, {"start_program":"07:30:00","end_program":"22:30:00","dulapid":5,"active":1,"versionid":278,"day_number":4,"day_name":"Joi"}, {"start_program":"07:30:00","end_program":"22:30:00","dulapid":5,"active":1,"versionid":279,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":84,"active":1,"versionid":287,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":84,"active":1,"versionid":288,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":84,"active":1,"versionid":289,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":84,"active":1,"versionid":290,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"20:45:00","dulapid":6,"active":1,"versionid":451,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"20:45:00","dulapid":6,"active":1,"versionid":452,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"18:45:00","dulapid":6,"active":1,"versionid":453,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"16:45:00","dulapid":6,"active":1,"versionid":454,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":84,"active":1,"versionid":291,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":84,"active":1,"versionid":292,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":84,"active":1,"versionid":293,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":67,"active":1,"versionid":294,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":67,"active":1,"versionid":295,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":67,"active":1,"versionid":296,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":67,"active":1,"versionid":297,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":67,"active":1,"versionid":298,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":67,"active":1,"versionid":299,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":67,"active":1,"versionid":300,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":8,"active":1,"versionid":301,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":8,"active":1,"versionid":302,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":8,"active":1,"versionid":303,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":8,"active":1,"versionid":304,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":8,"active":1,"versionid":305,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":8,"active":1,"versionid":306,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":8,"active":1,"versionid":307,"day_number":5,"day_name":"Vineri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":17,"active":1,"versionid":308,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":17,"active":1,"versionid":309,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":17,"active":1,"versionid":310,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":17,"active":1,"versionid":311,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":17,"active":1,"versionid":312,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":17,"active":1,"versionid":313,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":17,"active":1,"versionid":314,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":15,"active":1,"versionid":315,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":15,"active":1,"versionid":316,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":15,"active":1,"versionid":317,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":15,"active":1,"versionid":318,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":15,"active":1,"versionid":319,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":15,"active":1,"versionid":320,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":15,"active":1,"versionid":321,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":91,"active":1,"versionid":322,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":91,"active":1,"versionid":323,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":91,"active":1,"versionid":324,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":91,"active":1,"versionid":325,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":91,"active":1,"versionid":326,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":91,"active":1,"versionid":327,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":91,"active":1,"versionid":328,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":40,"active":1,"versionid":329,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":40,"active":1,"versionid":330,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":40,"active":1,"versionid":331,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":40,"active":1,"versionid":332,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":40,"active":1,"versionid":333,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":40,"active":1,"versionid":334,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":40,"active":1,"versionid":335,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":541,"active":1,"versionid":336,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":541,"active":1,"versionid":337,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":541,"active":1,"versionid":338,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":541,"active":1,"versionid":339,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":541,"active":1,"versionid":340,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":541,"active":1,"versionid":341,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":541,"active":1,"versionid":342,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":4,"active":1,"versionid":343,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":4,"active":1,"versionid":344,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":4,"active":1,"versionid":345,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":4,"active":1,"versionid":346,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":4,"active":1,"versionid":347,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":4,"active":1,"versionid":348,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":4,"active":1,"versionid":349,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":7,"active":1,"versionid":350,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":7,"active":1,"versionid":351,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":7,"active":1,"versionid":352,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":7,"active":1,"versionid":353,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":7,"active":1,"versionid":354,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":7,"active":1,"versionid":355,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":7,"active":1,"versionid":356,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":9,"active":1,"versionid":357,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":9,"active":1,"versionid":358,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":9,"active":1,"versionid":359,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":9,"active":1,"versionid":360,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":9,"active":1,"versionid":361,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":9,"active":1,"versionid":362,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":9,"active":1,"versionid":363,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":10,"active":1,"versionid":364,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":10,"active":1,"versionid":365,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":10,"active":1,"versionid":366,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":10,"active":1,"versionid":367,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":10,"active":1,"versionid":368,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":10,"active":1,"versionid":369,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":10,"active":1,"versionid":370,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":95,"active":1,"versionid":378,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":95,"active":1,"versionid":379,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":95,"active":1,"versionid":380,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":95,"active":1,"versionid":381,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":95,"active":1,"versionid":382,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":95,"active":1,"versionid":383,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":95,"active":1,"versionid":384,"day_number":5,"day_name":"Vineri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":51,"active":1,"versionid":385,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":51,"active":1,"versionid":386,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":51,"active":1,"versionid":387,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":51,"active":1,"versionid":388,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":51,"active":1,"versionid":389,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":51,"active":1,"versionid":390,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":51,"active":1,"versionid":391,"day_number":5,"day_name":"Vineri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":61,"active":1,"versionid":392,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":61,"active":1,"versionid":393,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":61,"active":1,"versionid":394,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":61,"active":1,"versionid":395,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":61,"active":1,"versionid":396,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":61,"active":1,"versionid":397,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":61,"active":1,"versionid":398,"day_number":5,"day_name":"Vineri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":71,"active":1,"versionid":399,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":71,"active":1,"versionid":400,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":71,"active":1,"versionid":401,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":71,"active":1,"versionid":402,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":71,"active":1,"versionid":403,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":71,"active":1,"versionid":404,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":71,"active":1,"versionid":405,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":30,"active":1,"versionid":406,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":30,"active":1,"versionid":407,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":30,"active":1,"versionid":408,"day_number":3,"day_name":"Miercuri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":30,"active":1,"versionid":409,"day_number":4,"day_name":"Joi"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":30,"active":1,"versionid":410,"day_number":5,"day_name":"Vineri"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":30,"active":1,"versionid":411,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"08:00:00","end_program":"22:00:00","dulapid":30,"active":1,"versionid":412,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":49,"active":1,"versionid":413,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":49,"active":1,"versionid":414,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":49,"active":1,"versionid":415,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":49,"active":1,"versionid":416,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":49,"active":1,"versionid":417,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":49,"active":1,"versionid":418,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":49,"active":1,"versionid":419,"day_number":5,"day_name":"Vineri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":85,"active":1,"versionid":420,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":85,"active":1,"versionid":421,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":85,"active":1,"versionid":422,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":85,"active":1,"versionid":423,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":85,"active":1,"versionid":424,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":85,"active":1,"versionid":425,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":85,"active":1,"versionid":426,"day_number":5,"day_name":"Vineri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":94,"active":1,"versionid":427,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":94,"active":1,"versionid":428,"day_number":0,"day_name":"Duminică"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":94,"active":1,"versionid":429,"day_number":1,"day_name":"Luni"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":94,"active":1,"versionid":430,"day_number":2,"day_name":"Marți"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":94,"active":1,"versionid":431,"day_number":3,"day_name":"Miercuri"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":94,"active":1,"versionid":432,"day_number":4,"day_name":"Joi"}, {"start_program":"09:00:00","end_program":"21:00:00","dulapid":94,"active":1,"versionid":433,"day_number":5,"day_name":"Vineri"}, {"start_program":"10:00:00","end_program":"21:30:00","dulapid":28,"active":1,"versionid":441,"day_number":1,"day_name":"Luni"}, {"start_program":"10:00:00","end_program":"21:30:00","dulapid":28,"active":1,"versionid":442,"day_number":2,"day_name":"Marți"}, {"start_program":"10:00:00","end_program":"21:30:00","dulapid":28,"active":1,"versionid":443,"day_number":3,"day_name":"Miercuri"}, {"start_program":"10:00:00","end_program":"21:30:00","dulapid":28,"active":1,"versionid":444,"day_number":4,"day_name":"Joi"}, {"start_program":"10:00:00","end_program":"21:30:00","dulapid":28,"active":1,"versionid":445,"day_number":5,"day_name":"Vineri"}, {"start_program":"10:00:00","end_program":"21:30:00","dulapid":28,"active":1,"versionid":446,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"10:00:00","end_program":"21:30:00","dulapid":28,"active":1,"versionid":447,"day_number":0,"day_name":"Duminică"}, {"start_program":"08:00:00","end_program":"20:45:00","dulapid":6,"active":1,"versionid":448,"day_number":1,"day_name":"Luni"}, {"start_program":"08:00:00","end_program":"20:45:00","dulapid":6,"active":1,"versionid":449,"day_number":2,"day_name":"Marți"}, {"start_program":"08:00:00","end_program":"20:45:00","dulapid":6,"active":1,"versionid":450,"day_number":3,"day_name":"Miercuri"}, {"start_program":"10:00:00","end_program":"22:00:00","dulapid":11,"active":1,"versionid":462,"day_number":1,"day_name":"Luni"}, {"start_program":"10:00:00","end_program":"22:00:00","dulapid":11,"active":1,"versionid":463,"day_number":2,"day_name":"Marți"}, {"start_program":"10:00:00","end_program":"22:00:00","dulapid":11,"active":1,"versionid":464,"day_number":3,"day_name":"Miercuri"}, {"start_program":"10:00:00","end_program":"22:00:00","dulapid":11,"active":1,"versionid":465,"day_number":4,"day_name":"Joi"}, {"start_program":"10:00:00","end_program":"22:00:00","dulapid":11,"active":1,"versionid":466,"day_number":5,"day_name":"Vineri"}, {"start_program":"10:00:00","end_program":"22:00:00","dulapid":11,"active":1,"versionid":467,"day_number":6,"day_name":"Sâmbătă"}, {"start_program":"10:00:00","end_program":"22:00:00","dulapid":11,"active":1,"versionid":468,"day_number":0,"day_name":"Duminică"}],"exceptii_zile":[{"dulapid":58,"ziua":"2016-07-31","versionid":38,"start_program":"08:00:00","end_program":"22:00:00","active":1}]}';
            $lockers_data = \Tools::jsonDecode($posted_json, true);
        } else {
            $lockers_data = \Tools::jsonDecode(\Tools::jsonEncode($lockers_data), true);
        }

        $error = false;

        $login_id = $lockers_data['merchid'];
        $lo_delivery_points = $lockers_data['dulap'];
        $lo_dp_program = $lockers_data['zile2dulap'];
        $lo_dp_exceptii = $lockers_data['exceptii_zile'];

        foreach ($lo_delivery_points as $delivery_point) {
            $check_sql = "SELECT count(dp_id) AS `exists` FROM `" . _DB_PREFIX_ . "lo_delivery_points` WHERE dp_id = " . (int)$delivery_point['dulapid'];
            $check = \Db::getInstance()->getValue($check_sql);

            if (!$check) {
                \Db::getInstance()->insert(
                    "lo_delivery_points",
                    array(
                        'dp_id'          => (int)$delivery_point['dulapid'],
                        'dp_denumire'    => $delivery_point['denumire'],
                        'dp_adresa'      => $delivery_point['adresa'],
                        'dp_judet'       => $delivery_point['judet'],
                        'dp_oras'        => $delivery_point['oras'],
                        'dp_tara'        => $delivery_point['tara'],
                        'dp_gps_lat'     => $delivery_point['latitudine'],
                        'dp_gps_long'    => $delivery_point['longitudine'],
                        'dp_tip'         => $delivery_point['tip_dulap'],
                        'dp_active'      => $delivery_point['active'],
                        'version_id'     => $delivery_point['versionid'],
                        'dp_temperatura' => $delivery_point['dp_temperatura'],
                        'dp_indicatii'   => $delivery_point['dp_indicatii'],
                        'stamp_created'  => date('Y-m-d H:i:s'),
                    )
                );
            } else {
                \Db::getInstance()->update(
                    "lo_delivery_points",
                    array(
                        'dp_id'          => (int)$delivery_point['dulapid'],
                        'dp_denumire'    => $delivery_point['denumire'],
                        'dp_adresa'      => $delivery_point['adresa'],
                        'dp_judet'       => $delivery_point['judet'],
                        'dp_oras'        => $delivery_point['oras'],
                        'dp_tara'        => $delivery_point['tara'],
                        'dp_gps_lat'     => $delivery_point['latitudine'],
                        'dp_gps_long'    => $delivery_point['longitudine'],
                        'dp_tip'         => $delivery_point['tip_dulap'],
                        'dp_active'      => $delivery_point['active'],
                        'version_id'     => $delivery_point['versionid'],
                        'dp_temperatura' => $delivery_point['dp_temperatura'],
                        'dp_indicatii'   => $delivery_point['dp_indicatii'],
                    ),
                    'dp_id = "' . (int)$delivery_point['dulapid'] . '"'
                );
            }
        }

        foreach ($lo_dp_program as $program) {
            $check_sql = "SELECT count(leg_id) AS `exists` FROM `" . _DB_PREFIX_ . "lo_dp_program` WHERE dp_id = " . (int)$program['dulapid'] . " AND day_number = " . (int)$program['day_number'];
            $check = \Db::getInstance()->getValue($check_sql);
            if (!$check) {
                \Db::getInstance()->insert(
                    "lo_dp_program",
                    array(
                        'dp_start_program' => $program['start_program'],
                        'dp_end_program'   => $program['end_program'],
                        'dp_id'            => (int)$program['dulapid'],
                        'day_active'       => (int)$program['active'],
                        'version_id'       => (int)$program['versionid'],
                        'day_number'       => (int)$program['day_number'],
                        'day'              => $program['day_name'],
                        'stamp_created'    => date('Y-m-d H:i:s'),
                    )
                );
            } else {
                \Db::getInstance()->update(
                    "lo_dp_program",
                    array(
                        'dp_start_program' => $program['start_program'],
                        'dp_end_program'   => $program['end_program'],
                        'dp_id'            => (int)$program['dulapid'],
                        'day_active'       => (int)$program['active'],
                        'version_id'       => (int)$program['versionid'],
                        'day_number'       => (int)$program['day_number'],
                        'day'              => $program['day_name'],
                    ),
                    'dp_id = "' . (int)$program['dulapid'] . '" AND day_number = "' . (int)$program['day_number'] . '"'
                );
            }
        }

        foreach ($lo_dp_exceptii as $exceptie) {
            $check_sql = "SELECT count(leg_id) AS `exists` FROM `" . _DB_PREFIX_ . "lo_dp_day_exceptions` WHERE dp_id = " . (int)$exceptie['dulapid'] . " AND date(exception_day) = date('" . pSQL($exceptie['ziua']) . "')";
            $check = \Db::getInstance()->getValue($check_sql);

            if (!$check) {
                \Db::getInstance()->insert(
                    "lo_dp_day_exceptions",
                    array(
                        'dp_start_program' => $exceptie['start_program'],
                        'dp_end_program'   => $exceptie['end_program'],
                        'dp_id'            => (int)$exceptie['dulapid'],
                        'active'           => (int)$exceptie['active'],
                        'version_id'       => (int)$exceptie['versionid'],
                        'exception_day'    => (int)$exceptie['ziua'],
                        'stamp_created'    => date('Y-m-d H:i:s'),
                    )
                );
            } else {
                \Db::getInstance()->update(
                    "lo_dp_day_exceptions",
                    array(
                        'dp_start_program' => $exceptie['start_program'],
                        'dp_end_program'   => $exceptie['end_program'],
                        'dp_id'            => (int)$exceptie['dulapid'],
                        'active'           => (int)$exceptie['active'],
                        'version_id'       => (int)$exceptie['versionid'],
                    ),
                    'dp_id = "' . (int)$exceptie['dulapid'] . '" AND date(exception_day) = "' . date($exceptie['ziua']) . '"'
                );
            }
        }

        $sql = "SELECT
					COALESCE(MAX(dp.version_id), 0) AS max_dulap_id,
					COALESCE(MAX(dpp.version_id), 0) AS max_zile2dp,
				    COALESCE(MAX(dpe.version_id), 0) AS max_exceptii_zile
				FROM
					`" . _DB_PREFIX_ . "lo_delivery_points` dp
					LEFT join `" . _DB_PREFIX_ . "lo_dp_program` dpp ON dpp.dp_id = dp.dp_id
					LEFT join `" . _DB_PREFIX_ . "lo_dp_day_exceptions` dpe ON dpe.dp_id = dp.dp_id";

        $row = (object)\Db::getInstance()->getRow($sql);

        $response['merch_id'] = (int)$login_id;
        $response['max_dulap_id'] = (int)$row->max_dulap_id;
        $response['max_zile2dp'] = (int)$row->max_zile2dp;
        $response['max_exceptii_zile'] = (int)$row->max_exceptii_zile;

        echo \Tools::jsonEncode($response);
    }
    // END SMARTLOCKER UPDATE

    // ISSN UPDATE ORDER STATUS
    private function run_issn()
    {
        $this->module = Module::getInstanceByName('lo');
        if (!\Tools::getValue('F_CRYPT_MESSAGE_ISSN')) {
            die('F_CRYPT_MESSAGE_ISSN nu a fost trimis');
        }
        $F_CRYPT_MESSAGE_ISSN = \Tools::getValue('F_CRYPT_MESSAGE_ISSN');
        $error = false;
        $issn = $this->decrypt_ISSN($F_CRYPT_MESSAGE_ISSN); //obiect decodat din JSON in clasa LO
        if (!isset($issn) || empty($issn)) {
            $this->module->logissn('Hacking attempt');
            die('Hacking attempt!');
        }
        //issn este un obiect, cu atributele: f_order_number, f_statusid, f_stamp, f_awb_collection (array de AWB-uri)
        $this->module->logissn('Decrypted ISSN: ' . \Tools::jsonEncode($issn));
        //f_order_number - referinta
        if (isset($issn->f_order_number)) {
            $vF_Ref = $issn->f_order_number;
            $this->module->logissn("vF_REF: " . $vF_Ref);
        } else {
            $error = true;
            $this->module->logissn('Parametrul f_order_number lipseste.');
            die('Parametrul f_order_number lipseste.');
        }
        //f_statusid
        if (isset($issn->f_statusid)) {
            $vF_statusid = $issn->f_statusid;
            $this->module->logissn("vF_statusid: " . $vF_statusid);
        } else {
            $error = true;
            $this->module->logissn('Parametrul f_statusid lipseste.');
            die('Parametrul f_statusid lipseste.');
        }
        // f_stamp
        if (isset($issn->f_stamp)) {
            $vF_stamp = $issn->f_stamp;
            $this->module->logissn('vF_stamp: ' . $vF_stamp);
        } else {
            $error = true;
            $this->module->logissn('Parametrul f_stamp lipseste.');
            die('Parametrul f_stamp lipseste.');
        }
        // f_awb_collection
        if (isset($issn->f_awb_collection)) {
            $vF_AWB = $issn->f_awb_collection; //array de awb-uri
            $vF_AWB = $vF_AWB[0];
            $this->module->logissn('vF_AWB: ' . $vF_AWB);
        } else {
            $error = true;
            $this->module->logissn('Parametrul f_awb lipseste.');
            die('Parametrul f_awb lipseste.');
        }

        if (!$error) {
            $this->module->changeOrderStatusByAwb($vF_AWB, $vF_statusid);
            $stare1 = '<f_response_code>0</f_response_code>';
            $raspuns_xml = '<?xml version="1.0" encoding="UTF-8" ?>';
            $raspuns_xml .= '<issn>';
            $raspuns_xml .= '<x_order_number>' . $issn->f_order_number . '</x_order_number>';
            $raspuns_xml .= '<merchServerStamp>' . date("Y-m-dTH:m:s") . '</merchServerStamp>';
            $raspuns_xml .= '<f_response_code>1</f_response_code>';
            $raspuns_xml .= '</issn>';
            echo $raspuns_xml;
        }
    }


    //////////////////////////////////////////////////////////////
    // 						END METODE PRIVATE					//
    //////////////////////////////////////////////////////////////
}
