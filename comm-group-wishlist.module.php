<?php
 function commerce_group_wishlists_menu() {
    $items['customer/wishlists'] = array(
      'page callback' => 'commerce_group_wishlists_list',
      'access arguments' => array('access content'),
      'type' => MENU_NORMAL_ITEM,
    );

    $items['customer/wishlist/%'] = array(
      'page callback' => 'commerce_group_wishlists_wishlist_view',
      'access arguments' => array('access content'),
      'page arguments' => array(2),
      'type' => MENU_NORMAL_ITEM,
    );
    return $items;
  }

  // uses base view to render wishlist to any user that
  // passes the has_group_access() check
  function commerce_group_wishlists_wishlist_view($commerce_order) {
    $has_access = has_group_access($commerce_order);

    if ($has_access == true) {
      $order_data = commerce_order_load($commerce_order);
      $order_user = user_load($order_data->revision_uid);

      $wview = 'User: ' . $order_user->name . "<br>";  
      $wview .= 'Created: ' . date('m/d/Y - g:ia', $order_data->created) . "<br>";
      $wview .= 'Updated: ' . date('m/d/Y - g:ia', $order_data->changed) . "<br>";
      $wview .= views_embed_view('wishlist', 'default', $commerce_order);
      drupal_set_title('Wishlist: ' . $order_data->commerce_wishlist_title);
    } else {
      drupal_set_title('Access Denined');
      $wview = 'You do not have access to this wishlist.';
    }

    return $wview;
  }

  // shows list of all group wishlist orders
  // requires view to contextually filter gid:group id of user
  function commerce_group_wishlists_list() {
    drupal_set_title('Customer Wishlists');
    $wview = views_embed_view('view_wishlists', 'group_wishlists');
    if ($wview) {
      return $wview;
    } else {
      // @TODO figure out why the shit user 1 OR multi-group users can't see view
      return 'Unable to view your group wishlists. Please contact a site administrator.';
    }
  }

  // this guy does our group to user access check
  function has_group_access($order_id) {
    global $user;
    $order = commerce_order_load($order_id);

    // if the current user is the order owner, no need to check further
    if ($order->uid == $user->uid) {
      $access = true;
    } else {

      // get the wishlist owner's groups
      $order_user = user_load($order->uid);
      $order_user_groups = og_get_groups_by_user($order_user);

      // probably  not needed, but in case users belong to multiple groups
      // grab the first group they belong to
      $order_group = array_values($order_user_groups['node']);
      $order_group = $order_group[0];

      // get logged in user's group(s)
      $current_user_groups = og_get_groups_by_user();
      // probably  not needed, but in case users belong to multiple groups
      // grab the first group they belong to
      $user_group = array_values($current_user_groups['node']);
      $user_group = $user_group[0];

      if ($user_group == $order_group) {
        $access = true;
      } else {
        $access = false;
      }
    }
    return $access;
  }
