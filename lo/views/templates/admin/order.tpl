{*
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
*}

<div id="LoOrderPanel" class="card">
	<div class="card-header">
		<h3 class="card-header-title">
			<i class="icon-carrier"></i> {l s='Livrari Online' mod='lo'}
		</h3>
	</div>
	<form id="formLoOrder" method="post" action="" class="form-horizontal clearfix">
		<div class="row">
			<div class="col-lg-6">
				<input type="hidden" id="id_order" name="id_order" value="{$id_order|escape:'htmlall':'UTF-8'}"/>
				<input type="hidden" id="site" name="site" value="{$shop_name|escape:'htmlall':'UTF-8'}"/>
				<input type="hidden" id="action" name="action" value="getAwb"/>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label" for="serviciuid">{l s='Service' mod='lo'}</label>
					<div class="col-lg-9">
						<select class="form-control" name="serviciuid" id="serviciuid">
                            {foreach from=$services item=service}
								<option value="{$service.serviciuid|escape:'htmlall':'UTF-8'}|{$service.shippingcompanyid|escape:'htmlall':'UTF-8'}|{$service.serviciu|escape:'htmlall':'UTF-8'}"
                                        {if $service.carrier == $id_carrier_reference}selected{/if}>
									#{$service.serviciuid|escape:'htmlall':'UTF-8'}
									- {$service.serviciu|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
						</select>
					</div>
				</div>
				<div class="form-group clearfix" id="locker_size_group"
                     {if $selected_service_id != $smartlocker_service_id}style="display: none;"{/if}>
					<label class="col-lg-3 control-label" for="locker_size">{l s='Locker size' mod='lo'}</label>
					<div class="col-lg-9">
						<select class="form-control" name="locker_size" id="locker_size">
							<option value="1">{l s='1: L (440mm / 600mm / 611mm)' mod='lo'}</option>
							<option value="2">{l s='2: M (498mm / 600mm / 382mm)' mod='lo'}</option>
							<option value="3" selected>{l s='3: S (498mm / 600mm / 300mm)' mod='lo'}</option>
							<option value="4">{l s='4: XL (600mm / 600mm / 600mm)' mod='lo'}</option>
						</select>
					</div>
				</div>
				<div class="form-group clearfix" id="locker_select_group"
                     {if $selected_service_id != $smartlocker_service_id}style="display: none;"{/if}>
					<label class="col-lg-3 control-label" for="locker_id">{l s='Locker' mod='lo'}</label>
					<div class="col-lg-9">
						<select class="form-control" name="locker" id="locker">
                            {foreach from=$lockers item=locker}
								<option value="{$locker.dp_id|escape:'htmlall':'UTF-8'}"
                                        {if $locker.dp_id == $id_selected_locker}selected{/if}>{$locker.dp_oras|escape:'htmlall':'UTF-8'}
									- {$locker.dp_denumire|escape:'htmlall':'UTF-8'}
									(#{$locker.dp_id|escape:'htmlall':'UTF-8'})
								</option>
                            {/foreach}
						</select>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="request_data_ridicare">{l s='Pickup date' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="request_data_ridicare" id="request_data_ridicare"
							   value="{$smarty.now|date_format:'Y-m-d'|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label" for="descriere_livrare">{l s='Delivery info' mod='lo'}</label>
					<div class="col-lg-9">
						<textarea class="form-control" id="descriere_livrare"
								  name="descriere_livrare">{$order_message|substr:0:250|escape:'htmlall':'UTF-8'}</textarea>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="referinta_expeditor">{l s='Sender reference' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="referinta_expeditor" id="referinta_expeditor"
							   value="{$shop_name|escape:'htmlall':'UTF-8'} - {l s='Order #%s' sprintf=$id_order|escape:'htmlall':'UTF-8' mod='lo'} ({$order_reference|escape:'htmlall':'UTF-8'})"/>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="valoare_declarata">{l s='Declared value' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="valoare_declarata" id="valoare_declarata"
							   value="{$products_value|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="currency">{l s='Declared value currency' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="currency" id="currency"
							   value="{$currency_iso|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix"
                     {if $selected_service_id == $smartlocker_service_id}style="display: none;"{/if}>
					<label class="col-lg-3 control-label" for="ramburs">{l s='Cash on delivery' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="ramburs" id="ramburs"
							   value="{$ramburs|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix"
                     {if $selected_service_id == $smartlocker_service_id}style="display: none;"{/if}>
					<label class="col-lg-3 control-label"
						   for="currency_ramburs">{l s='Cash on delivery currency' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="currency_ramburs" id="currency_ramburs"
							   value="{$currency_iso|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label" for="cine_plateste">{l s='Who pays' mod='lo'}</label>
					<div class="col-lg-9">
						<select class="form-control" name="cine_plateste" id="cine_plateste">
							<option value="0">{l s='Merchant' mod='lo'}</option>
							<option value="1">{l s='Sender' mod='lo'}</option>
							<option value="2">{l s='Recipient' mod='lo'}</option>
						</select>
					</div>
				</div>
				<button class="btn button btn-secondary toggle_delivery_hours">{l s='Edit delivery hours' mod='lo'}</button>
				<div class="delivery_hours">
					<div class="form-group clearfix">
						<label class="col-lg-3 control-label"
							   for="request_ora_ridicare">{l s='Pickup hour from' mod='lo'}</label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="request_ora_ridicare"
								   id="request_ora_ridicare" value="09:00:00"/>
						</div>
					</div>
					<div class="form-group clearfix">
						<label class="col-lg-3 control-label"
							   for="request_ora_ridicare_end">{l s='Pickup hour to' mod='lo'}</label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="request_ora_ridicare_end"
								   id="request_ora_ridicare_end" value="18:00:00"/>
						</div>
					</div>
					<div class="form-group clearfix">
						<label class="col-lg-3 control-label"
							   for="request_ora_livrare_sambata">{l s='Delivery hour from (Saturday)' mod='lo'}</label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="request_ora_livrare_sambata"
								   id="request_ora_livrare_sambata" value="09:00:00"/>
						</div>
					</div>
					<div class="form-group clearfix">
						<label class="col-lg-3 control-label"
							   for="request_ora_livrare_sambata_end">{l s='Delivery hour to (Saturday)' mod='lo'}</label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="request_ora_livrare_sambata_end"
								   id="request_ora_livrare_sambata_end" value="18:00:00"/>
						</div>
					</div>
					<div class="form-group clearfix">
						<label class="col-lg-3 control-label"
							   for="request_ora_livrare">{l s='Delivery hour from' mod='lo'}</label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="request_ora_livrare" id="request_ora_livrare"
								   value="09:00:00"/>
						</div>
					</div>
					<div class="form-group clearfix">
						<label class="col-lg-3 control-label"
							   for="request_ora_livrare_end">{l s='Delivery hour to' mod='lo'}</label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="request_ora_livrare_end"
								   id="request_ora_livrare_end" value="18:00:00"/>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="pickup_address_email">{l s='Pickup address - Email' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="pickup_address[email]" id="pickup_address_email"
							   value="{$pickup_address.email|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="pickup_address_name">{l s='Pickup address - Store name' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="pickup_address[name]" id="pickup_address_name"
							   value="{$pickup_address.name|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="pickup_address_address">{l s='Pickup address - Address' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="pickup_address[address]"
							   id="pickup_address_address" value="{$pickup_address.address|escape:'htmlall':'UTF-8'}"
							  />
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="pickup_address_city">{l s='Pickup address - City' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="pickup_address[city]" id="pickup_address_city"
							   value="{$pickup_address.city|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="pickup_address_state">{l s='Pickup address - State' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="pickup_address[state]" id="pickup_address_state"
							   value="{$pickup_address.state|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="pickup_address_zip">{l s='Pickup address - Postcode' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="pickup_address[zip]" id="pickup_address_zip"
							   value="{$pickup_address.zip|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="pickup_address_country">{l s='Pickup address - Country' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="pickup_address[country]"
							   id="pickup_address_country" value="{$pickup_address.country|escape:'htmlall':'UTF-8'}"
							  />
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="pickup_address_phone">{l s='Pickup address - Phone' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="text" name="pickup_address[phone]" id="pickup_address_phone"
							   value="{$pickup_address.phone|escape:'htmlall':'UTF-8'}"/>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="col-lg-3 control-label"
						   for="additional_services">{l s='Additional services' mod='lo'}</label>
					<div class="col-lg-9">
						<input class="form-control" type="checkbox" name="asigurare_la_valoarea_declarata"
							   id="asigurare_la_valoarea_declarata"
							   value="1"> {l s='Insurance at the declared value' mod='lo'}<br/>
						<input class="form-control" type="checkbox" name="retur_documente" id="retur_documente"
							   value="1"> {l s='Return documents' mod='lo'}<br/>
						<input class="form-control" type="checkbox" name="retur_documente_bancare"
							   id="retur_documente_bancare" value="1"> {l s='Return bank documents' mod='lo'}<br/>
						<input class="form-control" type="checkbox" name="confirmare_livrare" id="confirmare_livrare"
							   value="1"> {l s='Delivery Confirmation' mod='lo'}<br/>
						<input class="form-control" type="checkbox" name="livrare_sambata" id="livrare_sambata"
							   value="1"> {l s='Delivery on Saturday' mod='lo'}<br/>
						<input class="form-control" type="checkbox" name="notificare_email" id="notificare_email"
							   value="1" checked> {l s='Email notification' mod='lo'}<br/>
						<input class="form-control" type="checkbox" name="notificare_sms" id="notificare_sms" value="1"
							   checked> {l s='SMS notification' mod='lo'}<br/>
						<input class="form-control" type="checkbox" name="request_mpod" id="request_mpod"
							   value="1"> {l s='Merchant Delivery Confirmation' mod='lo'}<br/>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-12 clearfix">
			<div id="detalii-pachete" style="margin-top: 30px;">
				<h3>{l s='Package details' mod='lo'}</h3>
				<div><p style="float:left;height:2em;margin-top:3px;margin-right:10px">{l s='Packages' mod='lo'}</p>
					<input type="text" readonly="readonly" value="1" id="nrcolete" size="2"
						   style="height:2em;border: 1px dashed #ddd; font-size: 14px; font-weight: bold; text-align:center; width:70px"/>
				</div>
				<table id="colete" cellspacing="2" cellpadding="2"
					   style="width:100%; text-align:center; border-collapse: collapse; margin-bottom: 5px;">
					<thead>
					<tr>
						<th style="background: #eee; border-right: 2px solid #fff; padding: 2px 5px;">{l s='Package type' mod='lo'}</th>
						<th style="background: #eee; border-right: 2px solid #fff; padding: 2px 5px;">{l s='Content' mod='lo'}</th>
						<th style="background: #eee; border-right: 2px solid #fff; padding: 2px 5px;">{l s='Weight (kg)' mod='lo'}</th>
						<th style="background: #eee; border-right: 2px solid #fff; padding: 2px 5px;">{l s='Package height (cm)' mod='lo'}</th>
						<th style="background: #eee; border-right: 2px solid #fff; padding: 2px 5px;">{l s='Package width (cm)' mod='lo'}</th>
						<th style="background: #eee; border-right: 2px solid #fff; padding: 2px 5px;">{l s='Package depth (cm)' mod='lo'}</th>
					</tr>
					</thead>
					<tbody>
					<tr>
						<td>
							<select class="form-control" name="tipcolet[]" style="padding: 4px 5px;">
								<option value="1">{l s='Envelope' mod='lo'}</option>
								<option value="2" selected="selected">{l s='Package' mod='lo'}</option>
								<option value="3">{l s='Pallet' mod='lo'}</option>
							</select>
						</td>
						<td>
							<select class="form-control" name="continut[]" style="padding: 4px 5px;">
								<option value="1">{l s='Acts' mod='lo'}</option>
								<option value="2">{l s='Typified' mod='lo'}</option>
								<option value="3">{l s='Fragile' mod='lo'}</option>
								<option value="4" selected="selected">{l s='General' mod='lo'}</option>
							</select>
						</td>
						<td>
							<input class="form-control" style="text-align:right; padding: 4px 5px;" type="text"
								   name="greutate[]" value="{$total_weight|escape:'htmlall':'UTF-8'}"/>
						</td>
						<td>
							<input class="form-control" style="text-align:right; padding: 4px 5px;" type="text"
								   name="lungime[]" value="1"/>
						</td>
						<td>
							<input class="form-control" style="text-align:right; padding: 4px 5px;" type="text"
								   name="latime[]" value="1"/>
						</td>
						<td>
							<input class="form-control" style="text-align:right; padding: 4px 5px;" type="text"
								   name="inaltime[]" value="1"/>
						</td>
					</tr>
					</tbody>
				</table>
				<div>
					<button type="button" class="btn btn-update"
							id="adauga-pachet">{l s='+ Add package' mod='lo'}</button>
				</div>
			</div>
			<input type="hidden" name="f_request_awb" id="f_request_awb"
				   value="{$f_request_awb|escape:'quotes'|escape:'htmlall':'UTF-8'}"/>
			<button type="submit" id="get-awb">{l s='Generate AWB' mod='lo'} &raquo;</button>
		</div>
	</form>
	<div class="LoOrderError alert alert-danger" style="display: none;">

	</div>
</div>
