<?php
/**
 * Created by PhpStorm.
 * User: kofi
 * Date: 6/5/19
 * Time: 11:59 AM
 */

namespace kmcf7_sms_extension;

class KmMenuPage
{
    private $page_title;
    private $menu_title;
    private $capability;
    private $menu_slug;
    private $icon_url;
    private $position;
    private $function;
    private $sub_menu_pages;

    public function __construct($page_title, $menu_title, $capability, $menu_slug, $icon_url = '', $position = null, $function = '')
    {
        $this->page_title = $page_title;
        $this->menu_title = $menu_title;
        $this->capability = $capability;
        $this->menu_slug = $menu_slug;
        $this->icon_url = $icon_url;
        $this->position = $position;
        $this->function = $function == '' ? array($this, 'default_function') : $function;

        $this->sub_menu_pages = array();
    }

    public function run()
    {
        add_action('admin_menu', array($this, 'create_menu_page'));
    }

    public function create_menu_page()
    {
        add_menu_page(
            $this->page_title,
            $this->menu_title,
            $this->capability,
            $this->menu_slug,
            $this->function,
            $this->icon_url,
            $this->position

        );

        foreach ($this->sub_menu_pages as $sub_menu_page) {
            $sub_menu_page->run();
        }
    }

    public function default_function()
    {
        echo "";
    }

    public function get_menu_slug()
    {
        return $this->menu_slug;
    }

    public function add_sub_menu_page($sub_menu_page)
    {
        array_push($this->sub_menu_pages, $sub_menu_page);
    }

}
