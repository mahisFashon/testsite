<?php

$DB_Customers = WP_Shopify\Factories\DB\Customers_Factory::build();

$DB_Customers->delete_items_of_type($data);