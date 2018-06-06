<!--Copyright (c) 2008 PayFast (Pty) Ltd
You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.-->
<form action="<?php echo str_replace('&', '&amp;', $action); ?>" method="post" id="checkout" <?php echo $target_parent; ?>>
<input type="hidden" name="merchant_id" value="<?php echo $merchant_id; ?>"/>
<input type="hidden" name="merchant_key" value="<?php echo $merchant_key; ?>"/>
<input type="hidden" name="return_url" value="<?php echo $return_url; ?>"/>
<input type="hidden" name="cancel_url" value="<?php echo $cancel_url; ?>"/>
<input type="hidden" name="notify_url" value="<?php echo $notify_url; ?>"/>
<input type="hidden" name="name_first" value="<?php echo $name_first; ?>"/>
<input type="hidden" name="name_last" value="<?php echo $name_last; ?>"/>
<input type="hidden" name="email_address" value="<?php echo $email_address; ?>"/>
<input type="hidden" name="m_payment_id" value="<?php echo $m_payment_id; ?>"/>
<input type="hidden" name="amount" value="<?php echo $amount; ?>"/>
<input type="hidden" name="item_name" value="<?php echo $item_name; ?>"/>
<input type="hidden" name="signature" value="<?php echo $signature; ?>"/>
<input type="hidden" name="user_agent" value="<?php echo $user_agent; ?>"/>

<?php if ( $logoimg ): ?>
<input type="hidden" name="image_url" value="<?php echo $logoimg; ?>"/>
<?php endif; ?>

<?php if ( $cartbordercolor ): ?>
<input type="hidden" name="cpp_cart_border_color" value="<?php echo $cartbordercolor; ?>"/>
<?php endif; ?>

<div class="form-group action-buttons text-center">
    <a id="<?php echo $back->name ?>" href="<?php echo $back->href; ?>" class="btn btn-default mr10" title="<?php echo $back->text ?>">
        <i class="fa fa-arrow-left"></i>
        <?php echo $back->text ?>
    </a>
    <button id="<?php echo $button_confirm->name ?>" class="btn btn-orange lock-on-click" title="<?php echo $button_confirm->name ?>" type="submit">
        <i class="fa fa-check"></i>
        <?php echo $button_confirm->name; ?>
    </button>
</div>

</form>
