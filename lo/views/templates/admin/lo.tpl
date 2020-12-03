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

<div class="hide" id="new-service-format">
	<div class="form-wrapper clearfix national_field_form-wrapper">
		<h4>{l s='New service' mod='lo'}</h4>
		<div class="form-group clearfix">
			<label class="control-label col-lg-3">{l s='Service name' mod='lo'}</label>
			<div class="col-lg-9">
				<input type="text" size="50" name="national_field_serviciu[]"" />
			</div>
		</div>
		<div class="form-group clearfix">
			<label class="control-label col-lg-3">{l s='Service ID' mod='lo'}</label>
			<div class="col-lg-9">
				<input type="text" size="50" name="national_field_serviciuid[]" />
			</div>
		</div>
		<div class="form-group clearfix">
			<label class="control-label col-lg-3">{l s='Shipping Company ID' mod='lo'}</label>
			<div class="col-lg-9">
				<input type="text" size="50" name="national_field_shippingcompanyid[]" />
			</div>
		</div>
		<div class="form-group clearfix">
			<label class="control-label col-lg-3">{l s='Carrier' mod='lo'}</label>
			<div class="col-lg-9">
				<select id="national_field_carrier" name="national_field_carrier[]">
					{foreach from=$carriers item=j2}
						<option value="{$j2.id_carrier|escape:'htmlall':'UTF-8'}">{$j2.name|escape:'htmlall':'UTF-8'}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="form-group clearfix">
			<label class="control-label col-lg-3">{l s='Flat price' mod='lo'}</label>
			<div class="col-lg-9">
				<input type="text" size="50" name="national_field_flatprice[]" value="" />
				<p class="help-block">
					{l s='Only for Smart Lockers - will be shown before choosing the locker.' mod='lo'}
				</p>
			</div>
		</div>
		<div class="form-group clearfix">
			<label class="control-label col-lg-3">{l s='Fixed price' mod='lo'}</label>
			<div class="col-lg-9">
				<input type="text" size="50" name="national_field_fixedprice[]" value="" />
			</div>
		</div>
		<div class="form-group clearfix">
			<label class="control-label col-lg-3">{l s='Modify the price' mod='lo'}</label>
			<div class="col-lg-9 modifyprice_area">
				<select class="form-control" name="national_field_modify_sign[]">
					<option value="+">+</option>
					<option value="-">-</option>
				</select>
				<input type="text" size="15" name="national_field_modify_amount[]" value="" />
				<select class="form-control" name="national_field_modify_how[]">
					<option value="amount">{l s='Amount (%s)' mod='lo' sprintf='RON'}</option>
					<option value="percent">{l s='Percent (%)' mod='lo'}</option>
				</select>
			</div>
		</div>
		<div class="form-group clearfix">
			<label class="control-label col-lg-3">{l s='Free for orders bigger than (%s)' mod='lo' sprintf='RON'}</label>
			<div class="col-lg-9">
				<input type="text" name="national_field_freeamount[]" class="form-control" />
			</div>
		</div>
		<div class="form-group clearfix">
			<label class="control-label col-lg-3">{l s='Active' mod='lo'}</label>
			<div class="col-lg-9">
				<select name="national_field_active[]" class="form-control">
					<option value="1">{l s='Yes' mod='lo'}</option>
					<option value="0">{l s='No' mod='lo'}</option>
				</select>
			</div>
		</div>
		<input type="hidden" name="national_field_deleted[]" value="0" />
		<button class="national_field_delete" type="button">{l s='Delete' mod='lo'}</button>
	</div>
</div>

<form action="" method="post" id="services_form">
	<div class="panel">
		<div class="panel-heading"><i class="icon-cogs"></i> {l s='Configure services' mod='lo'}</div>
		<a style="cursor:pointer" id="addNationalField"><img src="../img/admin/add.gif" border="0"> {l s='Add service' mod='lo'}</a><br /><br />
		<div id="national">
		{if $national_fields|@count > 0}
			{foreach from=$national_fields key=key item=national_field}
			<div class="form-wrapper clearfix national_field_form-wrapper">
				<h4>{l s='Service #%s' sprintf=$key+1 mod='lo'}</h4>
				<div class="form-group clearfix">
					<label class="control-label col-lg-3">{l s='Service name' mod='lo'}</label>
					<div class="col-lg-9">
						<input type="text" size="50" name="national_field_serviciu[]" value="{$national_field.serviciu|escape:'htmlall':'UTF-8'}" />
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="control-label col-lg-3">{l s='Service ID' mod='lo'}</label>
					<div class="col-lg-9">
						<input type="text" size="50" name="national_field_serviciuid[]" value="{$national_field.serviciuid|escape:'htmlall':'UTF-8'}" />
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="control-label col-lg-3">{l s='Shipping Company ID' mod='lo'}</label>
					<div class="col-lg-9">
						<input type="text" size="50" name="national_field_shippingcompanyid[]" value="{$national_field.shippingcompanyid|escape:'htmlall':'UTF-8'}" />
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="control-label col-lg-3">{l s='Carrier' mod='lo'}</label>
					<div class="col-lg-9">
						<select id="national_field_carrier" name="national_field_carrier[]">
							{foreach from=$carriers item=j2}
								<option value="{$j2.id_reference|escape:'htmlall':'UTF-8'}" {if $national_field.carrier == $j2.id_reference}selected="selected"{/if}>{$j2.name|escape:'htmlall':'UTF-8'}</option>
							{/foreach}
						</select>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="control-label col-lg-3">{l s='Flat price' mod='lo'}</label>
					<div class="col-lg-9">
						<input type="text" size="50" name="national_field_flatprice[]" value="{if isset($national_field.flatprice)}{$national_field.flatprice|escape:'htmlall':'UTF-8'}{/if}" />
						<p class="help-block">
                            {l s='Only for Smart Lockers - will be shown before choosing the locker.' mod='lo'}
						</p>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="control-label col-lg-3">{l s='Fixed price' mod='lo'}</label>
					<div class="col-lg-9">
						<input type="text" size="50" name="national_field_fixedprice[]" value="{if isset($national_field.fixedprice)}{$national_field.fixedprice|escape:'htmlall':'UTF-8'}{/if}" />
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="control-label col-lg-3">{l s='Modify the price' mod='lo'}</label>
					<div class="col-lg-9 modifyprice_area">
						<select class="form-control" name="national_field_modify_sign[]">
							<option value="+" {if isset($national_field.modify_sign) && $national_field.modify_sign == "+"}selected{/if}>+</option>
							<option value="-" {if isset($national_field.modify_sign) && $national_field.modify_sign == "-"}selected{/if}>-</option>
						</select>
						<input type="text" size="15" name="national_field_modify_amount[]" value="{if isset($national_field.modify_amount)}{$national_field.modify_amount|escape:'htmlall':'UTF-8'}{/if}" />
						<select class="form-control" name="national_field_modify_how[]">
							<option value="amount" {if isset($national_field.modify_how) && $national_field.modify_how == "amount"}selected{/if}>{l s='Amount (%s)' mod='lo' sprintf='RON'}</option>
							<option value="percent" {if isset($national_field.modify_how) && $national_field.modify_how == "percent"}selected{/if}>{l s='Percent (%)' mod='lo'}</option>
						</select>
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="control-label col-lg-3">{l s='Free for orders bigger than (%s)' mod='lo' sprintf='RON'}</label>
					<div class="col-lg-9">
						<input type="text" name="national_field_freeamount[]" class="form-control" value="{if isset($national_field.freeamount)}{$national_field.freeamount|escape:'htmlall':'UTF-8'}{/if}" />
					</div>
				</div>
				<div class="form-group clearfix">
					<label class="control-label col-lg-3">{l s='Active' mod='lo'}</label>
					<div class="col-lg-9">
						<select name="national_field_active[]" class="form-control">
							<option value="1" {if isset($national_field.active) && $national_field.active} selected{/if}>{l s='Yes' mod='lo'}</option>
							<option value="0" {if !isset($national_field.active) || !$national_field.active} selected{/if}>{l s='No' mod='lo'}</option>
						</select>
					</div>
				</div>
				<input type="hidden" name="national_field_deleted[]" value="0" />
				<button class="national_field_delete" type="button">{l s='Delete' mod='lo'}</button>
			</div>
			{/foreach}
		{/if}
		</div>
		<div class="panel-footer">
			<button type="submit" value="1" id="module_form_submit_national_btn" name="submitLONational" class="btn btn-default pull-right">
				<i class="process-icon-save"></i> {l s='Save' mod='lo'}
			</button>
		</div>
	</div>
	
	<div class="panel">
		<div class="panel-heading"><i class="icon-cogs"></i> {l s='Configure order states' mod='lo'}</div>
		<div id="lo_order_states">
			{foreach from=$lo_order_states item=lo_order_state}
				<div class="form-group clearfix">
					<label class="control-label col-lg-3">{$lo_order_state.state_name|escape:'htmlall':'UTF-8'} - {$lo_order_state.id_lo_state|escape:'htmlall':'UTF-8'}</label>
					<div class="col-lg-3">
						<select name="lo_order_states[{$lo_order_state.id_lo_state|escape:'htmlall':'UTF-8'}]" class="form-control">
							<option value="0">{l s='- None -' mod='lo'}</option>
							{foreach from=$ps_order_states item=ps_order_state}
								<option value="{$ps_order_state.id_order_state|escape:'htmlall':'UTF-8'}" {if $ps_order_state.id_order_state == $lo_order_state.id_ps_os}selected{/if}>{$ps_order_state.name|escape:'htmlall':'UTF-8'}</option>
							{/foreach}
						</select>
					</div>
				</div>
			{/foreach}
		</div>
		<div class="panel-footer">
			<button type="submit" value="1" id="module_form_submit_order_states_btn" name="submitLOOrderStates" class="btn btn-default pull-right">
				<i class="process-icon-save"></i> {l s='Save' mod='lo'}
			</button>
		</div>
	</div>
</form>