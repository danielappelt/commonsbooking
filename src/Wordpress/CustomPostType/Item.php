<?php

namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Plugin;
use CommonsBooking\Repository\UserRepository;

class Item extends CustomPostType
{

    public static $postType = 'cb_item';

    /**
     * Item constructor.
     */
    public function __construct()
    {
        add_filter('the_content', array($this, 'getTemplate'));
        add_action('cmb2_admin_init', array($this, 'registerMetabox'));

        // Listing of locations for item
        add_shortcode('cb_locations', array(\CommonsBooking\View\Location::class, 'listLocations'));

        // Setting role permissions
        add_action('admin_init',array($this, 'addRoleCaps'),999);
        // Listing of items
        add_shortcode('cb_items', array(\CommonsBooking\View\Item::class, 'shortcode'));
    }

    public function getArgs()
    {
        $labels = array(
            'name'                  => __('Items', 'commonsbooking'),
            'singular_name'         => __('Item', 'commonsbooking'),
            'add_new'               => __('Add new', 'commonsbooking'),
            'add_new_item'          => __('Add new item', 'commonsbooking'),
            'edit_item'             => __('Edit item', 'commonsbooking'),
            'new_item'              => __('Add new item', 'commonsbooking'),
            'view_item'             => __('Show item', 'commonsbooking'),
            'view_items'            => __('Show items', 'commonsbooking'),
            'search_items'          => __('Search items', 'commonsbooking'),
            'not_found'             => __('items not found', 'commonsbooking'),
            'not_found_in_trash'    => __('No items found in trash', 'commonsbooking'),
            'parent_item_colon'     => __('Parent items:', 'commonsbooking'),
            'all_items'             => __('All items', 'commonsbooking'),
            'archives'              => __('Item archive', 'commonsbooking'),
            'attributes'            => __('Item attributes', 'commonsbooking'),
            'insert_into_item'      => __('Add to item', 'commonsbooking'),
            'uploaded_to_this_item' => __('Added to item', 'commonsbooking'),
            'featured_image'        => __('Item image', 'commonsbooking'),
            'set_featured_image'    => __('set item image', 'commonsbooking'),
            'remove_featured_image' => __('remove item image', 'commonsbooking'),
            'use_featured_image'    => __('use as item image', 'commonsbooking'),
            'menu_name'             => __('Items', 'commonsbooking'),

        );

        // args for the new post_type
        return array(
            'labels'              => $labels,

            // Sichtbarkeit des Post Types
            'public'              => true,

            // Standart Ansicht im Backend aktivieren (Wie Artikel / Seiten)
            'show_ui'             => true,

            // Soll es im Backend Menu sichtbar sein?
            'show_in_menu'        => false,

            // Position im Menu
            'menu_position'       => 3,

            // Post Type in der oberen Admin-Bar anzeigen?
            'show_in_admin_bar'   => true,

            // in den Navigations Menüs sichtbar machen?
            'show_in_nav_menus'   => true,

            // Hier können Berechtigungen in einem Array gesetzt werden
            // oder die standart Werte post und page in form eines Strings gesetzt werden
            'capability_type'     => array(self::$postType,self::$postType . 's'),

            'map_meta_cap'        => true,

            // Soll es im Frontend abrufbar sein?
            'publicly_queryable'  => true,

            // Soll der Post Type aus der Suchfunktion ausgeschlossen werden?
            'exclude_from_search' => true,

            // Welche Elemente sollen in der Backend-Detailansicht vorhanden sein?
            'supports'            => array('title', 'editor', 'thumbnail', 'custom-fields', 'revisions', 
        'excerpt'),

            // Soll der Post Type Kategorien zugeordnet werden können?
            'has_archive'         => false,

            // Soll man den Post Type exportieren können?
            'can_export'          => false,

            // Slug unseres Post Types für die redirects
            // dieser Wert wird später in der URL stehen
            'rewrite'             => array('slug' => self::getPostType()),
        );
    }

    public function getTemplate($content)
    {
        $cb_content = '';
        if (is_singular(self::getPostType())) {
            ob_start();
            global $post;
            $item = new \CommonsBooking\Model\Item($post);
            set_query_var( 'item', $item );
            cb_get_template_part('item', 'single');
            $cb_content = ob_get_clean();
        } // if archive... 

        return $cb_content . $content;
    }

    /**
     * Creates MetaBoxes for Custom Post Type Location using CMB2
     * more information on usage: https://cmb2.io/
     *
     * @return void
     */
    public function registerMetabox()
    {
        // Initiate the metabox Adress
        $cmb = new_cmb2_box(array(
            'id'           => CB_METABOX_PREFIX . 'item_info',
            'title'        => __('Item Info', 'commonsbooking'),
            'object_types' => array(self::$postType), // Post type
            'context'      => 'normal',
            'priority'     => 'high',
            'show_names'   => true, // Show field names on the left
            // 'cmb_styles' => false, // false to disable the CMB stylesheet
            // 'closed'     => true, // Keep the metabox closed by default
        ));

        $users = UserRepository::getCBItemAdmins();
        $userOptions = [];
        foreach ($users as $user) {
            $userOptions[$user->ID] = $user->get('user_nicename') . " (" .$user->last_name . " " . $user->last_name . ")";
        }

        $cmb->add_field( array(
            'name'       => __('Item Admin(s)', 'commonsbooking'),
            'desc'       => __('Item Admin(s) field description (optional)', 'commonsbooking'),
            'id'      => CB_METABOX_PREFIX . 'item_admins',
            'type'    => 'pw_multiselect',
            'options' => $userOptions,
            'attributes' => array(
                'placeholder' => __('Select item admins.', 'commonsbooking')
            ),
        ) );
    }

    public static function getView()
    {
        return new \CommonsBooking\View\Item();
    }
}
