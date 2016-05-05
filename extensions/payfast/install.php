<?php

if ( !defined ( 'DIR_CORE' ) )
{
    header ( 'Location: static_pages/' );
}

$registry = Registry::getInstance();
//Current extension text id from extension maanger
$extension_txt_id = $name;
$language_list = $this->model_localisation_language->getLanguages();

$lm = new ALayoutManager();
// block with button
$block_data = array(
    'block_txt_id' => 'payfast_button',
    'controller' => 'blocks/payfast_button',
    'templates' => array(
        array(
            'parent_block_txt_id' => 'header_bottom',
            'template' => 'blocks/payfast_button.tpl',
        ),
        array(
            'parent_block_txt_id' => 'header',
            'template' => 'blocks/payfast_button.tpl',
        ),
        array(
            'parent_block_txt_id' => 'column_left',
            'template' => 'blocks/payfast_button.tpl',
        ),
        array(
            'parent_block_txt_id' => 'column_right',
            'template' => 'blocks/payfast_button.tpl',
        ),
        array(
            'parent_block_txt_id' => 'content_top',
            'template' => 'blocks/default_pp_express_button.tpl',
        ),
        array(
            'parent_block_txt_id' => 'content_bottom',
            'template' => 'blocks/payfast_button.tpl',
        ),
        array(
            'parent_block_txt_id' => 'footer_top',
            'template' => 'blocks/payfast_button.tpl',
        ),
        array(
            'parent_block_txt_id' => 'footer',
            'template' => 'blocks/payfast_button.tpl',
        ),
    ),
);
$lm->saveBlock( $block_data );

// payfast banner block
$block_data = array(
    'block_txt_id' => 'payfast_bml_button',
    'controller' => 'blocks/payfast_button',
    'templates' => array(
        array(
            'parent_block_txt_id' => 'column_left',
            'template' => 'blocks/payfast_bml_button_lr.tpl',
        ),
        array(
            'parent_block_txt_id' => 'column_right',
            'template' => 'blocks/payfast_bml_button_lr.tpl',
        ),
        array(
            'parent_block_txt_id' => 'footer_top',
            'template' => 'blocks/payfast_bml_button_fb.tpl',
        ),
        array(
            'parent_block_txt_id' => 'footer',
            'template' => 'blocks/payfast_bml_button_fb.tpl',
        ),
        array(
            'parent_block_txt_id' => 'header_bottom',
            'template' => 'blocks/payfast_bml_button_fb.tpl',
        ),
    ),
);
$lm->saveBlock( $block_data );


$rm = new AResourceManager();
$rm->setType( 'image' );

$result = copy( DIR_EXT.'payfast/image/payfast.png', DIR_RESOURCE.'image/payfast.png');

$resource = array(
    'language_id' => $this->config->get( 'storefront_language_id' ),
    'name' => array(),
    'title' => array(),
    'description' => array(),
    'resource_path' => 'payfast.png',
    'resource_code' => ''
);

foreach( $language_list as $lang )
{
    $resource['name'][$lang['language_id']] = 'payfast.png';
    $resource['title'][$lang['language_id']] = 'payfast_storefront_icon';
    $resource['description'][$lang['language_id']] = 'PayFast Default Storefront Icon';
}
$resource_id = $rm->addResource( $resource );

if ( $resource_id )
{
    // get hexpath of resource (RL moved given file from rl-image-directory in own dir tree)
    $resource_info = $rm->getResource( $resource_id, $this->config->get( 'admin_language_id' ) );
    // write it path in settings (array from parent method "install" of extension manager)
    $settings['payfast_payment_storefront_icon'] =  'image/'.$resource_info['resource_path'];

}

$settings['payfast_custom_logo'] = $this->config->get( 'config_logo' );