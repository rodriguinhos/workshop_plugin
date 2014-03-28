<?php
/*
Plugin Name: Plugin do Workshop
Plugin URI: http://gaindsa.ws/
Version: 0.1-Alpha
Author: Eu
Description: O nosso plugin
*/

add_action('admin_menu', array('IASD_Workshop', 'AdminMenu'));

add_action('init', array('IASD_Workshop', 'Init'));

add_action('save_post', array('IASD_Workshop', 'SavePost'));


class IASD_Workshop {
    public static function AdminMenu() {
        self::SaveSettings();
        self::AdminMenuPageRegister();
        self::AdminSubmenuPageRegister();
        self::AdminMenuSettingsSectionRegister();
        self::AdminMenuSettingsRegister();
        self::AddMetaBox();
    }

    const ADMIN_MENU = 'workshop_menu';
    const PERMISSION_DEFAULT = 'edit_pages';
    public static function AdminMenuPageRegister() {
        add_menu_page('Titulo da Página do Menu',
            'Nome do Menu',
            self::PERMISSION_DEFAULT,
            self::ADMIN_MENU,
            array(__CLASS__, 'AdminMenuPageRender'));
    }
    public static function AdminMenuPageRender() { }

    const ADMIN_SUBMENU = 'workshop_submenu';
    const POST_SETTINGS = 'workshop_update_settings';
    public static function AdminSubmenuPageRegister() {
        add_submenu_page(
            self::ADMIN_MENU,
            'Titulo da Página do Submenu',
            'Nome do Submenu',
            self::PERMISSION_DEFAULT,
            self::ADMIN_SUBMENU,
            array(__CLASS__, 'SettingsPageRender'));
    }
    // Função padrão
    public static function SettingsPageRender() {
?>
        <div class="wrap">
            <form method="POST" action="">
                <?php do_settings_sections($_GET['page']); ?>
                <div>
                    <div id="publishing-action">
                        <input type="submit"
                               name="<?php echo self::POST_SETTINGS; ?>"
                               id="<?php echo self::POST_SETTINGS; ?>"
                               value="<?php _e('Save'); ?>"
                               class="button-primary" tabindex="4">
                    </div>
                </div>
            </form>
        </div>
    <?php
    }

    const SETTINGS_SECTION = 'workshop_settings';
    public static function AdminMenuSettingsSectionRegister() {
        add_settings_section(self::SETTINGS_SECTION, 'Seção de Configurações',
            array(__CLASS__, 'SettingsSectionInfo'), self::ADMIN_SUBMENU);
    }
    public static function SettingsSectionInfo() {
        echo '<p>Esta função dá algumas informações sobre as configurações. Use <i>HTML</i><b>PURO</b>.</p>';
    }

    const SETTING_WORKSHOP = 'workshop_setting';
    public static function AdminMenuSettingsRegister() {
        register_setting(self::SETTINGS_SECTION, self::SETTING_WORKSHOP);
        add_settings_field(self::SETTING_WORKSHOP,
            'Configuração', array(__CLASS__, 'SettingsFieldRender'),
            self::ADMIN_SUBMENU, self::SETTINGS_SECTION,
            array(self::SETTING_WORKSHOP, 'text'));
    }
    public static function SettingsFieldRender($params) {
        list($setting_name, $setting_type) = $params;
        switch ($setting_type) {
            default:
                echo '<input name="'.$setting_name.'"
                        id="'.$setting_name.'" type="input" value="'. get_option($setting_name) .
                    '" class="widefat" />';
                break;
        }
    }
    public static function SaveSettings() {
        if(isset($_POST[self::POST_SETTINGS])) {
//Settings existentes
            $settings_registered_by_page = apply_filters( 'whitelist_options', array());
            if(isset($settings_registered_by_page[self::SETTINGS_SECTION])) {
                $settings = $settings_registered_by_page[self::SETTINGS_SECTION];
                foreach($settings as $setting_name) {
                    if(isset($_POST[$setting_name])) {
                        delete_option($setting_name);
                        add_option($setting_name, $_POST[$setting_name], false, 'no');
                    }
                }
            }
        }
    }

    public static function TypeLabels() {
        $labels = array(
            'name' => 'Livros',
            'singular_name' => 'Livros',
            'add_new' => 'Criar Novo',
            'add_new_item' => 'Criar Novo Livro',
            'edit_item' => 'Editar Livro',
            'new_item' => 'Novo Livro',
            'view_item' => 'Ver Livro',
            'search_items' => 'Buscar Livro'
        );
        return $labels;
    }

    public static function TypeArguments() {
        $args = array(
            'labels' => self::TypeLabels(),
            'public' => true,
            'rewrite' => array('slug' => 'livro'),
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title','thumbnail','excerpt','comments'),
            'has_archive' => 'biblioteca'
        );
        return $args;
    }

    public static function RegisterType() {
        register_post_type('livros', self::TypeArguments() );
    }

    public static function RegisterTaxonomies() {
        register_taxonomy('Genero',
            array('livros'),
            array(
                'hierarchical' => true,
                'label' => __( 'Generos' ),
                'show_ui' => true
            )
        );
    }


    public static function Init() {
        self::RegisterType();
        self::RegisterTaxonomies();
    }

    public static function AddMetaBox() {
        add_meta_box('livros-autor',
            'Autor do Livro',
            array(__CLASS__, 'AddMetaBoxLivrosAutor'),
            'livros',
            'normal',
            'high'
        );
    }

    public static function AddMetaBoxLivrosAutor( $post ) {
        $post_livro_autor = get_post_meta($post->ID, 'post_livro_autor', true);
        echo '<textarea class="attachmentlinks"
                    id="post_livro_autor"
                    name="post_livro_autor">'.$post_livro_autor.'</textarea><br />';
        echo '<label for="post_livro_autor">';
        _e('Nome do autor');
        echo '</label> ';
    }

    public static function SavePost($post_id) {
        $post = get_post($post_id);
        if($post->post_type == 'livros' ) {
            if(!wp_is_post_revision( $post ) &&
                !wp_is_post_autosave( $post ) && count($_POST)) {
                if(isset($_POST['post_livro_autor'])) {
                    $post_livro_autor = $_POST['post_livro_autor'];
                    $post_livro_autor = trim($post_livro_autor);
                    if($post_livro_autor)
                        update_post_meta($post_id, 'post_livro_autor', $post_livro_autor);
                }
            }
        }
    }
}


add_action('widgets_init', array('IASD_FlickrWidget', 'Init'));
class IASD_FlickrWidget extends WP_Widget
{
    static function Init() {
        register_widget(__CLASS__);
    }

    function IASD_FlickrWidget()
    {
        $widget_ops = array('classname' => __CLASS__,
            'description' => __('Plug-in social do Flickr', 'iasd'));
        $this->WP_Widget(__CLASS__, __('IASD: Flickr Box', 'iasd'), $widget_ops);
    }

    function form($instance)
    {
// if(!isset($instance['flickr_user']))
// $instance['flickr_user'] = '';
        echo '<p>' . __('Nome do usuário', 'iasd') . '<br />';
        echo '<input type="text" class="widefat"
id="'.$this->get_field_id('flickr_user').'"
name="'.$this->get_field_name('flickr_user').'"
value="'.$instance['flickr_user'].'" />';
        echo '</p>';
    }

    function widget($args, $instance)
    {
        if(isset($instance['flickr_user'])
            && $flickr_user = $instance['flickr_user']) {
            ?>
            <div class="iasd-widget iasd-widget-social_media flickr col-md-4">
                <h1><?php _e('Flickr', 'iasd'); ?></h1>
                <div class="row">
                    <iframe class="col-md-12" src="http://www.flickr.com/photos/<?php echo
                    $flickr_user; ?>/show/"></iframe>
                </div>
            </div>
        <?php
        }
    }
}

