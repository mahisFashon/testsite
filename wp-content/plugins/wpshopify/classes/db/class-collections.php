<?php

namespace WP_Shopify\DB;

use WP_Shopify\Utils;
use WP_Shopify\CPT;
use WP_Shopify\Transients;

if (!defined('ABSPATH')) {
    exit();
}

class Collections extends \WP_Shopify\DB
{
    private $DB_Collects;
    private $CPT_Model;

    private $DB_Collections_Smart;
    private $DB_Collections_Custom;

    public $lookup_key;
    public $type;

    public function __construct(
        $DB_Collects,
        $CPT_Model,
        $DB_Collections_Smart,
        $DB_Collections_Custom
    ) {
        $this->DB_Collects = $DB_Collects;
        $this->CPT_Model = $CPT_Model;

        $this->DB_Collections_Smart = $DB_Collections_Smart;
        $this->DB_Collections_Custom = $DB_Collections_Custom;

        $this->lookup_key = WP_SHOPIFY_COLLECTIONS_LOOKUP_KEY;
        $this->type = 'collection';
    }

    /*

	Mod before change

	*/
    public function mod_before_change($collection, $post_id = false)
    {
        $collection_copy = $this->copy($collection);

        $collection_copy = $this->maybe_rename_to_lookup_key($collection_copy);
        $collection_copy = Utils::flatten_image_prop($collection_copy);

        if ($post_id) {
            $collection_copy = CPT::set_post_id($collection_copy, $post_id);
        }

        // Important. If handle doesn't match post_name, the product won't show
        $collection_copy->post_name = sanitize_title($collection_copy->handle);

        return $collection_copy;
    }

    /*

	Inserts a single collection

	*/
    public function insert_collection($collection)
    {
        if ($this->is_smart_collection($collection)) {
            return $this->DB_Collections_Smart->insert($collection);
        } else {
            return $this->DB_Collections_Custom->insert($collection);
        }
    }

    /*

	Updates a single collection

	*/
    public function update_collection($collection)
    {
        if ($this->is_smart_collection($collection)) {
            return $this->DB_Collections_Smart->update(
                $this->DB_Collections_Smart->lookup_key,
                $this->DB_Collections_Smart->get_lookup_value($collection),
                $collection
            );
        } else {
            return $this->DB_Collections_Custom->update(
                $this->DB_Collections_Custom->lookup_key,
                $this->DB_Collections_Custom->get_lookup_value($collection),
                $collection
            );
        }
    }

    /*

	Deletes a single collection

	*/
    public function delete_collection($collection)
    {
        if ($this->is_smart_collection($collection)) {
            return $this->DB_Collections_Smart->delete_rows(
                $this->DB_Collections_Smart->lookup_key,
                $this->DB_Collections_Smart->get_lookup_value($collection)
            );
        } else {
            return $this->DB_Collections_Custom->delete_rows(
                $this->DB_Collections_Custom->lookup_key,
                $this->DB_Collections_Custom->get_lookup_value($collection)
            );
        }
    }

    /*

	Delete products from product ID

	*/
    public function delete_collection_from_collection_id($collection_id)
    {
        $results = [];

        $results[
            'collections_smart'
        ] = $this->DB_Collections_Smart->delete_rows(
            WP_SHOPIFY_COLLECTIONS_LOOKUP_KEY,
            $collection_id
        );
        $results[
            'collections_custom'
        ] = $this->DB_Collections_Custom->delete_rows(
            WP_SHOPIFY_COLLECTIONS_LOOKUP_KEY,
            $collection_id
        );

        return $results;
    }

    public function has_collection($maybe_collection)
    {
        if (
            is_object($maybe_collection[0]) &&
            property_exists($maybe_collection[0], 'collection_id')
        ) {
            return true;
        }

        return false;
    }

    /*

	Get all collections query

	*/
    public function get_all_collections_query($limit = false)
    {
        global $wpdb;

        $main_query =
            "SELECT
		smart.collection_id,
		smart.post_id,
		smart.title,
		smart.handle,
		smart.post_name,
		smart.body_html,
		smart.image,
		smart.sort_order,
		smart.published_at,
		smart.updated_at,
		smart.rules
		FROM " .
            $wpdb->prefix .
            WP_SHOPIFY_TABLE_NAME_COLLECTIONS_SMART .
            " smart

		UNION

		SELECT
		custom.collection_id,
		custom.post_id,
		custom.title,
		custom.handle,
		custom.post_name,
		custom.body_html,
		custom.image,
		custom.sort_order,
		custom.published_at,
		custom.updated_at,
		NULL as rules
		FROM " .
            $wpdb->prefix .
            WP_SHOPIFY_TABLE_NAME_COLLECTIONS_CUSTOM .
            ' custom';

        if ($limit) {
            $main_query = $main_query . ' LIMIT ' . $limit;
        }

        return $main_query;
    }

    public function get_collection_ids_query($limit = false)
    {
        global $wpdb;

        $main_query =
            "SELECT
		smart.collection_id
		FROM " .
            $wpdb->prefix .
            WP_SHOPIFY_TABLE_NAME_COLLECTIONS_SMART .
            " smart

		UNION

		SELECT
		custom.collection_id
		FROM " .
            $wpdb->prefix .
            WP_SHOPIFY_TABLE_NAME_COLLECTIONS_CUSTOM .
            ' custom';

        if ($limit) {
            $main_query = $main_query . ' LIMIT ' . $limit;
        }

        return $main_query;
    }

    public function get_collection_ids($limit = false)
    {
        global $wpdb;

        $results = $wpdb->get_results($this->get_collection_ids_query($limit));

        return array_column($results, 'collection_id');
    }

    /*

	Gets all collections

	*/
    public function get_collections($limit = false)
    {
        global $wpdb;

        return $wpdb->get_results($this->get_all_collections_query($limit));
    }

    public function get_post_id_by_collection_id($collection_id)
    {
        return \get_posts([
            'post_type' => 'wps_collections',
            'posts_per_page' => 1,
            'meta_key' => 'collection_id',
            'meta_value' => $collection_id,
            'fields' => 'ids', // we don't need it's content, etc.
        ]);
    }

    /*

	Get Collection

	*/
    public function get_collection_by_post_id($postID = null)
    {
        global $wpdb;
        global $post;

        if ($postID === null && is_object($post)) {
            $postID = $post->ID;
        }

        $query =
            "SELECT
		smart.collection_id,
		smart.post_id,
		smart.title,
		smart.handle,
		smart.post_name,
		smart.body_html,
		smart.image,
		smart.sort_order,
		smart.published_at,
		smart.updated_at,
		smart.rules
		FROM " .
            $wpdb->prefix .
            WP_SHOPIFY_TABLE_NAME_COLLECTIONS_SMART .
            " smart WHERE smart.post_id = $postID

		UNION

		SELECT
		custom.collection_id,
		custom.post_id,
		custom.title,
		custom.handle,
		custom.post_name,
		custom.body_html,
		custom.image,
		custom.sort_order,
		custom.published_at,
		custom.updated_at,
		NULL as rules
		FROM " .
            $wpdb->prefix .
            WP_SHOPIFY_TABLE_NAME_COLLECTIONS_CUSTOM .
            " custom WHERE custom.post_id = $postID;";

        return $wpdb->get_results($query);
    }

    /*

	Default Collections Query

	*/
    public function get_default_collections_query()
    {
        global $wpdb;

        return [
            'where' => '',
            'groupby' => '',
            'join' =>
                ' INNER JOIN (

			SELECT
			smart.collection_id,
			smart.post_id,
			smart.title,
			smart.handle,
			smart.post_name,
			smart.body_html,
			smart.image,
			smart.sort_order,
			smart.published_at,
			smart.updated_at
			FROM ' .
                $wpdb->prefix .
                WP_SHOPIFY_TABLE_NAME_COLLECTIONS_SMART .
                ' smart

			UNION ALL

			SELECT
			custom.collection_id,
			custom.post_id,
			custom.title,
			custom.handle,
			custom.post_name,
			custom.body_html,
			custom.image,
			custom.sort_order,
			custom.published_at,
			custom.updated_at
			FROM ' .
                $wpdb->prefix .
                WP_SHOPIFY_TABLE_NAME_COLLECTIONS_CUSTOM .
                ' custom

		) as collections ON ' .
                $wpdb->prefix .
                WP_SHOPIFY_TABLE_NAME_WP_POSTS .
                '.ID = collections.post_id',
            'orderby' => $wpdb->posts . '.menu_order',
            'distinct' => '',
            'fields' => 'collections.*',
            'limits' => '',
        ];
    }

    /*

	Used to check the type of collection
	- Predicate Function (returns boolean)

	*/
    public function is_smart_collection($collection)
    {
        return Utils::has($collection, 'rules') ? true : false;
    }

    public function get_post_id_from_collection($collection)
    {
        if ($this->is_smart_collection($collection)) {
            $collection_found = $this->DB_Collections_Smart->get_row_by(
                WP_SHOPIFY_COLLECTIONS_LOOKUP_KEY,
                $collection->id
            );
        } else {
            $collection_found = $this->DB_Collections_Custom->get_row_by(
                WP_SHOPIFY_COLLECTIONS_LOOKUP_KEY,
                $collection->id
            );
        }

        if (empty($collection_found)) {
            return false;
        }

        return $collection_found->post_id;
    }

    public function collection_exists_by_id($collection_id)
    {
        if (empty($collection_id)) {
            return false;
        }

        $smart_collection_found = $this->DB_Collections_Smart->get(
            $collection_id
        );
        $custom_collection_found = $this->DB_Collections_Custom->get(
            $collection_id
        );

        if (empty($smart_collection_found) && empty($custom_collection_found)) {
            return false;
        } else {
            return true;
        }
    }

    /*

	Gets collections from post name

	*/
    public function get_collection_from_post_name($post_name = false)
    {
        global $wpdb;

        if ($post_name === false) {
            return;
        }

        $query =
            "SELECT
		smart.collection_id,
		smart.rules
		FROM " .
            $wpdb->prefix .
            WP_SHOPIFY_TABLE_NAME_COLLECTIONS_SMART .
            " smart WHERE smart.post_name = %s

		UNION

		SELECT
		custom.collection_id,
		NULL as rules
		FROM " .
            $wpdb->prefix .
            WP_SHOPIFY_TABLE_NAME_COLLECTIONS_CUSTOM .
            ' custom WHERE custom.post_name = %s;';

        return $wpdb->get_row($wpdb->prepare($query, $post_name, $post_name));
    }

    /*

	Gets collections from post name

	*/
    public function get_collections_from_ids($collection_ids = [])
    {
        global $wpdb;

        if (empty($collection_ids)) {
            return $collection_ids;
        }

        $collection_ids = maybe_unserialize($collection_ids);
        $collection_ids = Utils::convert_array_to_in_string($collection_ids);

        $query =
            "SELECT
		smart.collection_id
		FROM " .
            $wpdb->prefix .
            WP_SHOPIFY_TABLE_NAME_COLLECTIONS_SMART .
            ' smart WHERE smart.collection_id IN ' .
            $collection_ids .
            "

		UNION

		SELECT
		custom.collection_id
		FROM " .
            $wpdb->prefix .
            WP_SHOPIFY_TABLE_NAME_COLLECTIONS_CUSTOM .
            ' custom WHERE custom.collection_id IN ' .
            $collection_ids .
            ';';

        return $wpdb->get_results($query, ARRAY_A);
    }

    public function get_collections_from_posts($posts)
    {
        $collections = [];

        if (is_object($posts)) {
            $posts = [$posts];
        }

        foreach ($posts as $post) {
            $collections[$post->ID]['post_id'] = $post->ID;

            $collection = $this->get_collection_from_post_name(
                $post->post_name
            );

            if (!empty($collection)) {
                $collections[$post->ID]['collection_id'] =
                    $collection->collection_id;
            } else {
                $collections[$post->ID]['collection_id'] = 0;
            }

            if (!empty($collection->rules)) {
                $collections[$post->ID]['rules'] = $collection->rules;
            }
        }

        return $collections;
    }

    public function combine_collection_ids($smart_ids, $custom_ids)
    {
        return array_unique(array_merge($smart_ids, $custom_ids), SORT_REGULAR);
    }

    /*

   Responsible for returning an array of product ids from an array of title strings

   */
    public function get_collection_ids_from_titles($titles)
    {
        $smart = $this->DB_Collections_Smart->select_in_col(
            'collection_id',
            'title',
            $titles
        );
        $custom = $this->DB_Collections_Custom->select_in_col(
            'collection_id',
            'title',
            $titles
        );

        return $this->combine_collection_ids($smart, $custom);
    }
}
