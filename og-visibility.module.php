<?php

function commerce_group_visibility_node_presave($node) {
  // only run on commerce_product nodes
  if ($node->type == 'commerce_product') {
    // grab our list of associated entites for this node
    if (isset($node->field_product[LANGUAGE_NONE])) {
      $product_entities = $node->field_product[LANGUAGE_NONE];
      // we will need to ensure that the list for this user/node
      // actually contains an entry for one of the node's referenced
      // entities. Just because a group pricelist exists doesn't
      // mean this node's products are a part of it

      // build useable array of product IDs from this node
      foreach ($product_entities as $product_entity) {
        $products_ids[] = $product_entity['product_id'];
      }

      // query our skus so we can load the pricelist array
      foreach ($products_ids as $product_id) {
        $products[] = commerce_product_load($product_id);
      }

      // get the skus so we can query the commerce pricelist
      foreach ($products as $product) {
        $skus[] = $product->sku;
      }

      // if this doesn't return anything, we don't have a price for this
      // node's products in the pricelist, and need to skip assignment
      // of this node to the group in question
      foreach ($skus as $sku) {
        $prices[] = commerce_pricelist_get_prices($sku);
      }

      // prices still gets set, and its [0] is empty
      // this is a workaround to check this
      if (is_array($prices)) {
        $prices = array_filter($prices);
      }
    }

    // determine price list IDs for this display
    foreach ($prices as $price) {
      $price_groups[] = array_shift($price);
    }

    foreach ($price_groups as $price_group) {
      $price_group_ids[] = $price_group;
    }

    $price_lists = array_unique(call_user_func_array('array_merge', array_map('array_keys', $price_group_ids)));

    // query all pricelists in the site
    //$pricelists = commerce_pricelist_list_load_multiple();
    $pricelists = entity_load('commerce_pricelist_list', FALSE, array(), TRUE);

    foreach ($pricelists as $plids) {
      $pricelist_ids[] = $plids->list_id;
    }

    // sets array of all group ids (nid) needed to jam into group list
    foreach ($price_lists as $pl) {
      if (in_array($pl, $pricelist_ids)) {
        $newgroups[] = array_shift($pricelists[$pl]->data['filter']['organic_group']);
      }
    }

    // we only need to run all of this if there is a pricelist match
    // for this user's group, and for this product
    // pricelist module does this for us so we compare against it
    if (!empty($prices)) {
      // unset the field first to get rid of old settings
      // as many groups may have products removed, and not doing this
      // would force the group assignment to stay on the display
      unset($node->og_group_ref[LANGUAGE_NONE]);
      $node->og_group_ref[LANGUAGE_NONE] = array();

      foreach ($newgroups as $newgroup) {

        $node->og_group_ref[LANGUAGE_NONE][]['target_id'] = $newgroup;

        dpm("Price found and set for group " . $newgroup . " for product " . $node->nid);
      }

      // after we loop all group IDs, return the node object
      return $node;
    } else {
      dpm("No price found for " . $node->nid);
    }
  } else {
    dpm('not a product node');
  }
} 
