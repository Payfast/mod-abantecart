<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
if ( !defined ( 'DIR_CORE' ) )
{
    header ( 'Location: static_pages/' );
}

// delete block
$lm = new ALayoutManager();
$lm->deleteBlock( 'payfast_button' );
$lm->deleteBlock( 'payfast_banner' );

$block_names = array( 'payfast_billmelater_marketing' );
$custom_blocks = $lm->getBlocksList( array( 'subsql_filter' => 'cb.custom_block_id <> 0' ) );

foreach( $custom_blocks as $block )
{
    if( in_array( $block['block_name'], $block_names ) )
    {
        $this->db->query ( "DELETE FROM " . DB_PREFIX . "block_layouts
						   WHERE custom_block_id = '" . ( int ) $block['custom_block_id'] . "'" );
        $this->cache->delete ( 'layout.a.blocks' );
        $this->cache->delete ( 'layout.blocks' );

        $lm->deleteCustomBlock( $block['custom_block_id'] );
    }
}

$rm = new AResourceManager();
$rm->setType( 'image' );

$resources = $rm->getResources( 'extensions', 'payfast' );

if ( is_array($resources) )
{
    foreach( $resources as $resource )
    {
        $rm->deleteResource( $resource['resource_id'] );
    }
}