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

<div id="LoAwbsPanel" class="card">
    <div class="card-header">
        <h3 class="card-header-title">
            <i class="icon-carrier"></i> {l s='Livrari Online' mod='lo'}
        </h3>
    </div>
    <div class="col-12" id="lo-awbs">
        <p><br/>{l s='AWBs created at %s using %s:' mod='lo' sprintf=[$awbs_date, $service]}</p>
        <ul id='awbs'>
            {foreach $awbs as $awb}
                <li data-id-carrier="{$id_carrier|escape:'htmlall':'UTF-8'}" data-awb="{$awb.awb|escape:'htmlall':'UTF-8'}">{$awb.awb|escape:'htmlall':'UTF-8'} -
                    <a class="lo_awb_set_trackingnumber btn button btn-primary" href="#">{l s='Set as Tracking number' mod='lo'}</a>
                    <a class="lo_awb_tracking btn button btn-info" href="#">{l s='Tracking' mod='lo'}</a>
                    <a class="btn button btn-success" href="http://api.livrarionline.ro/Lobackend_print/PrintAwb.aspx?f_login={$f_login|escape:'htmlall':'UTF-8'}&awb={$awb.awb|escape:'htmlall':'UTF-8'}" target="_blank">{l s='Print' mod='lo'}</a>
                    {if $awb.f_statusid < 300}
                        <a class="lo_awb_cancel btn button btn-danger" href="#">{l s='Cancel AWB/Return delivery' mod='lo'}</a>
                    {else}
                        <a class="lo_awb_return btn button btn-danger" href="#">{l s='Return order/products' mod='lo'}</a>
                    {/if}
                </li>
            {/foreach}
        </ul>
    </div>
</div>
