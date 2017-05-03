<?php
/*------------------------------------------------------------------------------
Copyright (c) 2008 PayFast (Pty) Ltd
You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
------------------------------------------------------------------------------*/
if ( !defined ( 'DIR_CORE' ) )
{
    header ( 'Location: static_pages/' );
}

class ModelExtensionPayFast extends Model
{
    public function getMethod( $address )
    {
        $this->load->language( 'payfast/payfast' );

        if ( $this->config->get( 'payfast_status' ) )
        {
            $sql = "SELECT * FROM " . DB_PREFIX . "zones_to_locations
      		         WHERE location_id = '" . (int)$this->config->get('payfast_location_id') . "'
      		           AND country_id = '" . (int)$address['country_id'] . "'
      		           AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')";
            $query = $this->db->query($sql);

            if ( !$this->config->get( 'payfast_location_id' ) )
            {
                $status = TRUE;
            }
            elseif ( $query->num_rows )
            {
                $status = TRUE;
            }
            else
            {
                $status = FALSE;
            }
        }
        else
        {
            $status = FALSE;
        }

        $method_data = array();

        if ($status)
        {
            $method_data = array(
                'id'         => 'payfast',
                'title'      => $this->language->get( 'text_title' ),
                'sort_order' => $this->config->get( 'payfast_sort_order' )
            );
        }

        return $method_data;
    }
}