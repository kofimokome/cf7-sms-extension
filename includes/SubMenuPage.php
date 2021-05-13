<?php
/**
 * Created by PhpStorm.
 * User: kofi
 * Date: 6/5/19
 * Time: 12:41 PM
 */

namespace kmcf7_sms_extension;


class SubMenuPage
{
    private $page_title;
    private $menu_title;
    private $capability;
    private $menu_slug;
    private $parent_slug;
    private $function;
    private $tabs;

    public function __construct($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = null, $use_tabs = false)
    {
        $this->page_title = $page_title;
        $this->menu_title = $menu_title;
        $this->capability = $capability;
        $this->menu_slug = $menu_slug;
        $this->parent_slug = $parent_slug;
        $this->function = $function;
        if ($use_tabs) {
            $this->function = array(&$this, 'show_tabs');
        }
        $this->tabs = array();
    }

    public function show_tabs()
    {
        $current_tab = isset($_GET['tab']) ? $_GET['tab'] : null;
        ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php echo $this->page_title ?></h1>
            <?php if (sizeof($this->tabs) > 0): ?>
                <nav class="nav-tab-wrapper">
                    <?php foreach ($this->tabs as $id => $tab): ?>
                        <a href="?page=<?php echo $this->menu_slug ?>&tab=<?php echo $id ?>"
                           class="nav-tab <?php if ($id === $current_tab): ?>nav-tab-active<?php endif; ?>"><?php echo $tab['title'] ?></a>
                    <?php endforeach; ?>

                </nav>
                <?php
                $to_display = $current_tab == null ? array_shift($this->tabs) : $this->tabs[$current_tab];
                ?>
                <?php echo is_callable($to_display['contents']) ? $to_display['contents']($to_display['args']) : $to_display; ?>
            <?php else: ?>
                <div class="notice notice-error">
                    <p><strong>Please add a tab first, or set <code>use_tab</code> to false</strong></p>
                </div>
            <?php endif; ?>


        </div>
        <?php
        //echo $this->default_content;
    }

    /**
     * Adds a new tab to the page
     * @param string $id ID of the tab
     * @param string $title Title of the tab
     * @param callable|string $contents Content to display in the tab
     * @param array $args Arguments to pass to callback function
     */
    public function add_tab($id, $title, $contents, array $args = [])
    {
        $id = trim($id);
        $this->tabs[$id] = array('title' => $title, 'contents' => $contents, 'args' => $args);
        // array_push($this->tabs, array($id, $title));
    }

    public function run()
    {
        $this->create_sub_menu_page();
    }


    public function create_sub_menu_page()
    {
        add_submenu_page(
            $this->parent_slug,
            $this->page_title,
            $this->menu_title,
            $this->capability,
            $this->menu_slug,
            $this->function
        );
    }
}
