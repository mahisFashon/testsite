<div class="tab-content" data-tab-content="tab-tools" style="<?= !$has_connection ? 'position: relative;margin-top:68px;' : ''; ?>">

   <?php if (!$has_connection) { ?>
   <div class="components-notice is-warning" style="position: absolute;top: -69px;width: 100%;left: 0;">
      <div class="components-notice__content">Warning: You still need to <a href="/wp-admin/admin.php?page=wps-connect">connect your Shopify store</a></div>
   </div>

   <?php } ?>

  <div class="wps-admin-section">

    <h3><?php _e('Sync Product & Collection Detail Pages', 'wpshopify'); ?></h3>
    <p><?php _e(
        'This tool will create native WordPress posts (as a custom post type) for your products and collections. If you\'re not planning to have product detail pages then you won\'t need to use this.',
        'wpshopify'
    ); ?></p>

    <div class="wps-button-group button-group-ajax <?= $has_connection
        ? 'wps-is-active'
        : 'wps-is-not-active' ?>">

      <?php
      if ($has_connection) {
          $props = [
              'id' => 'wps-button-sync',
              'data-wpshopify-tool' => 'Sync Products',
          ];
      } else {
          $props = [
              'disabled' => 'disabled',
              'id' => 'wps-button-sync',
          ];
      }

      submit_button(
          __('Sync Detail Pages', 'wpshopify'),
          'primary large',
          'submitSettings',
          false,
          $props
      );
      ?>

      <div class="spinner"></div>

    </div>

  </div>


  


  <div class="wps-admin-section">

    <h3><?php _e('Remove all synced data', 'wpshopify'); ?></h3>
    <p><?php _e(
        'This will remove all WP Shopify data from WordPress. Nothing will be changed in Shopify. Useful for removing any lingering data without re-installing the plugin. (Note: this can take up to 60 seconds and will delete product and collection posts).',
        'wpshopify'
    ); ?></p>

    <div class="wps-button-group button-group-ajax wps-is-active">

      <?php

         if ($has_connection) {
            $props = [
               'id' => 'wps-button-clear-all-data',
               'data-wpshopify-tool' => 'Remove all synced data',
            ];
         } else {
            $props = [
               'id' => 'wps-button-clear-all-data',
               'data-wpshopify-tool' => 'Remove all synced data',
               'disabled' => 'disabled',
            ];
         }

         submit_button(
            __('Remove all synced data from WordPress', 'wpshopify'),
            'primary large',
            'submitSettings',
            false,
            $props
         );

      ?>

      <div class="spinner"></div>

    </div>

  </div>

  <?php
?>


  <?php
?>

<div class="wps-admin-section">

    <h3><?php _e('Clear Cache', 'wpshopify'); ?></h3>
    <p><?php _e(
        'If you\'re noticing various changes not appearing, try clearing the WP Shopify transient cache here.',
        'wpshopify'
    ); ?></p>

    <div class="wps-button-group button-group-ajax wps-is-active">

      <?php
    
      if ($has_connection) {
          $props = [
              'id' => 'wps-button-clear-cache',
               'data-wpshopify-tool' => __('Clear Cache', 'wpshopify'),
          ];
      } else {
          $props = [
              'disabled' => 'disabled',
              'id' => 'wps-button-clear-cache',
          ];
      }

      submit_button(
          __('Clear WP Shopify Cache', 'wpshopify'),
          'primary large',
          'submitSettings',
          false,
          $props
      );
      ?>

      <div class="spinner"></div>

    </div>

  </div>


</div>
