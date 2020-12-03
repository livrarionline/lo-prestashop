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
<div id="lo_smart_lockers_area" {if $id_selected_carrier != $id_lockers_carrier}style="display: none;"{/if}>
  <h3>{l s='Smart lockers' mod='lo'}</h3>
  <p class="smart_lockers_desc">{l s='You don\'t have to meet with courier again, you can pick up your products from a smart locker.' mod='lo'}</p>
  <a class="btn-smart-locker" href="#lockers_container">{l s='Choose a smart locker' mod='lo'}</a>
  <p class="smart_lockers_selected" {if !isset($selected_locker_name) || !$selected_locker_name}style="display: none;"{/if}>{l s='Selected locker:' mod='lo'} <span>{if isset($selected_locker_name) && $selected_locker_name}{$selected_locker_name|escape:'htmlall':'UTF-8'}{/if}</span></p>
  <div id="lockers_container" style="display: none;">
    <div class="lockers_form">
      <div class="form-group">
        <select name="lo_state" class="form-control" style="width: 200px">
          <option value="0">{l s='Choose a state' mod='lo'}</option>
          {foreach from=$lockers_data_array item=item key=state}
            <option value="{$state|escape:'htmlall':'UTF-8'}">{$state|escape:'htmlall':'UTF-8'}</option>
          {/foreach}
        </select>
      </div>
      <div class="form-group">
        <select name="lo_city" class="form-control" style="width: 200px">
          <option value="0">{l s='Choose a city' mod='lo'}</option>
        </select>
      </div>
      <div class="form-group">
        <select name="lo_locker" class="form-control" style="width: 200px">
          <option value="0">{l s='Chooose a locker' mod='lo'}</option>
        </select>
      </div>
    </div>
    <div id="lockers_gmap">
      
    </div>
  </div>
</div>
<div class="clearfix"></div>