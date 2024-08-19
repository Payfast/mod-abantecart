<?php
/*
 * Copyright (c) 2024 Payfast (Pty) Ltd
 */

if (!defined('DIR_CORE')) {
    header('Location: static_pages/');
}

class ModelExtensionPayfast extends Model
{
    public function getMethod($address)
    {
        $this->load->language('payfast/payfast');

        if ($this->config->get('payfast_status')) {
            $sql   = "SELECT * FROM " . DB_PREFIX . "zones_to_locations
      		         WHERE location_id = '" . (int)$this->config->get('payfast_location_id') . "'
      		           AND country_id = '" . (int)$address['country_id'] . "'
      		           AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')";
            $query = $this->db->query($sql);

            if (!$this->config->get('payfast_location_id')) {
                $status = true;
            } elseif ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'id'         => 'payfast',
                'title'      => $this->language->get('text_title'),
                'sort_order' => $this->config->get('payfast_sort_order')
            );
        }

        return $method_data;
    }
}
