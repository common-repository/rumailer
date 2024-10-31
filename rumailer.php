<?
/*
Plugin Name: Rumailer
Plugin URI: http://rumailer.ru/page/plugins_wordpress
Description: Rumailer - современный сервис email-рассылок
Version: 0.0.3
Author: Daniil Konoplisky
License: GPL2
Text Domain: send mail servise Rumailer
*/

include_once 'lib/api.php';
include_once 'lib/widgets.php';
include_once 'lib/action.php';

class Rumailer
{

    const PageName = 'rumailer';
    private $API = null;
    public $ListFilds = null;

    /**
     * Constructor.
     * Rumailer constructor.
     */
    public function __construct()
    {
        global $RumailerListFilds, $RumailerAPI;
        $this->API = new API();
        $RumailerAPI = $this->API;
        $this->ListFilds = array('firstname', 'lastname', 'middlename', 'city', 'address', 'phone', 'sex', 'birthday');
        $RumailerListFilds = $this->ListFilds;
        add_action('admin_init', array($this, 'rumailer_register_my_setting'));
        add_action('plugins_loaded', array($this,'rumailer_load_textdomain'));
        add_action('init', array($this, 'my_custom_post_rumailer_list_mail'));
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('user_register', array($this,'rumailer_add_registration_fields'));
        add_action('register_form', array($this,'rumailer_add_registration_fields'), 10, 1 );
    }

    function rumailer_register_my_setting(){
        register_setting('rumailer-my-setting', 'rumailer_setting_name');
        register_setting('rumailer-my-setting', 'rumailer_setting_email');
        register_setting('rumailer-my-setting', 'rumailer_setting_api');
        register_setting('rumailer-my-setting', 'rumailer_setting_list');
    }

    /**
     * Локализация
     */
    function rumailer_load_textdomain() {
        $plugin_dir = basename(dirname(__FILE__));
        load_plugin_textdomain( self::PageName, '/wp-content/plugins/'.$plugin_dir. '/languages/', $plugin_dir. '/languages/' );
    }

    /**
     * Проверка существования пользователя
     * @param string $this_list
     * @param string $name_email
     * @return bool
     */
    private function is_post_user($this_list='',$name_email=''){
        $email_user = trim(sanitize_email($name_email));
        $get_user_item = new WP_Query(array(
            'posts_per_page' => -1,
            'post_type' => 'rumailer',
            'post_status'=> 'any',
            'tax_query' => array(
                'relation' => 'OR',
                array(
                    'taxonomy' => 'rumailer_list',
                    'field' => 'slug',
                    'terms' => $this_list
                )
            ),
            's'=>$email_user,
        ));
        if (!empty($get_user_item->posts)) {
            $is_insert = true;
            foreach ($get_user_item->posts as $user_item) {
                if($email_user == trim($user_item->post_title)){
                    $is_insert = false;
                }
            }
        } else {
            $is_insert = true;
        }
        return $is_insert;
    }

    /**
     * Перехват новых пользователей
     */
    function rumailer_add_registration_fields(){
        if(!empty($_POST)){
            $data_registers = $_POST;
            $this_list = get_option('rumailer_setting_list');
            if(!empty($this_list)){
                $name_email  = array('email','mail','user_email');
                $email_user = '';
                foreach($name_email as $name_fild){
                    if(!empty($data_registers[$name_fild])){
                        $email_user = trim(sanitize_email($data_registers[$name_fild]));
                    }
                }
                $get_user_item = new WP_Query(array(
                    'posts_per_page' => -1,
                    'post_type' => 'rumailer',
                    'post_status'=> 'any',
                    'tax_query' => array(
                        'relation' => 'OR',
                        array(
                            'taxonomy' => 'rumailer_list',
                            'field' => 'slug',
                            'terms' => $this_list
                        )
                    ),
                    's'=>str_replace(array('@','.'), '', $name_email),
                ));
                if (!empty($get_user_item->posts)) {
                    $is_insert = true;
                    foreach ($get_user_item->posts as $user_item) {
                        if($email_user == trim($user_item->post_title)){
                            $is_insert = false;
                        }
                    }
                } else {
                    $is_insert = true;
                }
                if($is_insert){
                    $post_data = array(
                        'post_title'    => $email_user,
                        'post_name'     => str_replace(array('@','.'), '', $name_email),
                        'post_status'   => 'draft',
                        'post_type'     => 'rumailer'
                    );
                    $post_id = wp_insert_post( $post_data );
                    if(!empty($post_id)){
                        wp_set_object_terms( $post_id, $this_list, 'rumailer_list', true);
                        global $RumailerListFilds, $RumailerAPI;

                        $data_res = array(
                            'list_ids' => $this_list,
                            'email' => $email_user,
                            'fields' => '',
                            'double_optin' => '1',
                        );

                        foreach($RumailerListFilds as $filds){
                            if(!empty($data_registers[$filds])){
                                unset($data_registers[$filds]);
                                $data_res['fields'][$filds] = sanitize_text_field($data_registers[$filds]);
                                add_post_meta($post_id, $filds, sanitize_text_field($data_registers[$filds]));
                            } else {
                                add_post_meta($post_id, $filds, '');
                            }
                        }
                        unset($data_registers['register'],$data_registers['password'],$data_registers['email']);
                        foreach($data_registers as $filds=>$param){
                            $data_res['fields'][sanitize_text_field($filds)] = sanitize_text_field($param);
                            add_post_meta($post_id, sanitize_text_field($filds), sanitize_text_field($param));
                        }
                        $RumailerAPI->add_subscriber($data_res);
                    }
                }
            }
        }
    }

    /**
     * Регистрируем новый тип записи для Rumailer
     */
    function my_custom_post_rumailer_list_mail()
    {
        $labels = array(
            'name' => __('Rumailer',self::PageName),
            'singular_name' => __('Add subscriber',self::PageName),//Добавить подписчика
            'add_new' => __('Add subscriber',self::PageName),//Добавить подписчика
            'add_new_item' => __('Add',self::PageName),
            'edit_item' => __('Edit subscriber',self::PageName),
            'new_item' => __('New subscriber',self::PageName),
            'all_items' => __('All subscriber',self::PageName),
            'view_item' => __('Show subscriber',self::PageName),
            'search_items' => __('Search subscriber',self::PageName),
            'not_found' => __('Not a subscriber found',self::PageName),
            'not_found_in_trash' => __('Not a subscriber found',self::PageName),
            'parent_item_colon' => '',
            'menu_name' => __('RumailerList',self::PageName)
        );
        $args = array(
            'labels' => $labels,
            'description' => 'Your subscribers to the service Rumailer',
            'public' => true,
            'publicly_queryable' => false,
            'show_in_menu' => true,
            'menu_position' => 30,
            'supports' => array('title', 'page-attributes', 'custom-fields'),
            'has_archive' => true,
            'menu_icon' => 'dashicons-email-alt'
        );
        register_post_type(self::PageName, $args);

        register_taxonomy(
            'rumailer_list',
            self::PageName,
            array(
                'hierarchical' => true,
                'label' => __('Subscription list',self::PageName),
                'singular_name' => 'rumailer_list',
                'rewrite' => array('slug' => 'rumailer_list'),
                'query_var' => true
            )
        );

        function add_rumailer_columns($columns)
        {
            $results = array();
            $i = 0;
            foreach ($columns as $k => $itam) {
                $i++;
                if ($i == 3) {
                    $results['name'] = __('FIO','rumailer');
                }
                if ($i == 3) {
                    $results['rumailer_list'] = __('Sheet','rumailer');
                }
                if ($itam == 'title') {
                    $results['title'] = __('Email','rumailer');
                }
                $results[$k] = $itam;
            }

            return $results;
        }

        add_filter('manage_' . self::PageName . '_posts_columns', 'add_rumailer_columns');
        function custom_rumailer_column($column){
            $get_post_id = get_post()->ID;
            switch ($column) {
                case 'name':
                    $fild = get_post_custom($get_post_id);
                    echo '' . $fild['firstname'][0] . ' ' . $fild['lastname'][0];
                    break;
                case 'rumailer_list':
                    $rumailer_list = get_the_terms($get_post_id, 'rumailer_list');
                    $result_list = array();
                    if ($rumailer_list) {
                        foreach ($rumailer_list as $list) {
                            $result_list[] = "<a href='".esc_url( add_query_arg( 'rumailer_list', $list->slug ) )."'>".$list->name."</a>";
                        }
                    }
                    echo implode(' | ',$result_list);
                    break;
            }
        }
        function rumailer_list_add_taxonomy_filters() {
            global $typenow;

            // Массив всех taxonomyies , которые вы хотите отобразить. Используйте имя таксономию или противослизневых
            $taxonomies = array('rumailer_list');

            // Должны установить это к типу пост требуется фильтр (ы) на экране ,
            if( $typenow == 'rumailer' ){

                foreach ($taxonomies as $tax_slug) {
                    $tax_obj = get_taxonomy($tax_slug);
                    $tax_name = $tax_obj->labels->name;
                    $terms = get_terms($tax_slug);
                    if(count($terms) > 0) {
                        echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
                        echo "<option value=''>".__('All petitions','rumailer')."</option>";
                        foreach ($terms as $term) {
                            echo '<option value='. $term->slug, $_GET[$tax_slug] == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>';
                        }
                        echo "</select>";
                    }
                }
            }
        }
        add_action( 'restrict_manage_posts', 'rumailer_list_add_taxonomy_filters' );

        add_action('manage_' . self::PageName . '_posts_custom_column', 'custom_rumailer_column');
        add_filter('manage_edit-' . self::PageName . '_sortable_columns', 'my_rumailer_sortable_columns');
        function my_rumailer_sortable_columns($columns)
        {
            $columns['name'] = 'name';
            $columns['rumailer_list'] = 'rumailer_list';
            return $columns;
        }

        add_action(self::PageName . "_list_add_form_fields", 'add_new_custom_fields');
        add_action(self::PageName . "_list_edit_form_fields", 'edit_custom_taxonomy_meta');
        add_action("create_rumailer_list", 'save_custom_taxonomy_meta', 10, 3);
        add_action("edited_rumailer_list", 'save_custom_taxonomy_meta', 10, 3);
        function save_custom_taxonomy_meta($term_id)
        {
//            global $RumailerAPI;
//            $data = get_term($term_id,'rumailer_list');
//            $RumailerAPI->add_lists(array('name'=>$data->name));
            return $term_id;
        }

        function edit_custom_taxonomy_meta()
        {
            ?>
            <style>
                .submit,
                .form-field.term-parent-wrap {
                    display: none;
                }
            </style>
            <?
        }

        function add_new_custom_fields()
        {
            global $RumailerAPI;
            $rumailer_list = $RumailerAPI->get_lists();
            $wp_list = get_terms(array(
                'taxonomy' => 'rumailer_list',
                'hide_empty' => false,
                'orderby' => 'id',
                'order' => 'ASC'
            ));
            if (!empty($rumailer_list->result)) {
                foreach ($rumailer_list->result as $list) {
                    $is_add_list = true;
                    $ID_list_name = '';
                    $ID_list_BD = null;
                    $ID_list = $list->id;
                    foreach ($wp_list as $list_bd) {
                        $ID_list_BD = null;
                        $ID_list_name = null;
                        if ($list_bd->slug == $ID_list) {
                            $is_add_list = false;
                            $ID_list_name = $list_bd->name;
                            $ID_list_BD = $list_bd->id;
                        }
                    }
                    if ($is_add_list) {
                        wp_insert_term($list->title, 'rumailer_list',
                            array(
                                'description' => '' . $list->title,
                                'slug' => '' . $list->id
                            )
                        );
                    } else {
                        if ($list->title != $ID_list_name) {
                            wp_update_term($ID_list_BD, 'rumailer_list', array(
                                'name' => $list->title,
                            ));
                        }
                    }
                }
            }
            ?>
            <style>
                #col-left {
                    display: none;
                }

                #col-right {
                    width: 100%;
                    min-width: 100%;
                }
                .row-actions{
                    display: none !important;
                }
            </style>
            <script>
                jQuery(document).ready(function(){
                    jQuery('.row-title').attr('href','#');
                });
            </script>
            <?
        }
        add_action('before_delete_post', 'rumailer_action_function_del_user');
        function rumailer_action_function_del_user($post_id){
            global $post_type;
            if ($post_type == 'rumailer'){
                $user_rumailer = new WP_Query(array(
                    'posts_per_page' => -1,
                    'post_type' => 'rumailer',
                    'post_status'=> 'trash',
                    'p'=> $post_id,
                ));
                if ($user_rumailer->posts) {
                    global $RumailerAPI;
                    $user_rumailer = $user_rumailer->posts[0];
                    $res = (object)array();
                    $rumailer_list = get_the_terms($post_id, 'rumailer_list');
                    $res->error = 'none_list';
                    if ($rumailer_list) {
                        foreach ($rumailer_list as $list) {
                            $data_res = array(
                                'email' => $user_rumailer->post_title,
                                'list_ids' => $list->slug
                            );
                            $res = $RumailerAPI->del_subscriber($data_res);
                        }
                    }
                    if (!empty($res->error) and trim($res->error) != 'none_email') {
                        $user_rumailer->post_status = 'trash';
                        wp_update_post( $user_rumailer );
                    }
                }
            }
        }
        add_action('save_post_' . self::PageName, 'rumailer_action_function_set_user', 10, 3);
        function rumailer_action_function_set_user($post_ID, $post, $update){
            global $RumailerListFilds, $RumailerAPI;
            if ($post->post_status == 'publish') {
                $rumailer_list = get_the_terms($post_ID, 'rumailer_list');
                $rumailer_list_res = array();
                if ($rumailer_list) {
                    foreach ($rumailer_list as $list) {
                        $rumailer_list_res[] = $list->slug;
                    }
                }
                $data_res = array(
                    'list_ids' => implode(',', $rumailer_list_res),
                    'email' => $post->post_title,
                    'fields' => '',
                    'double_optin' => '1',
                );
                $filds = get_post_custom($post_ID);
                if(empty($filds)){
                    $meta = $_POST['meta'];
                    if(!empty($meta)){
                        foreach ($meta as $item) {
                            if (!empty($item['value'])) {
                                $data_res['fields'][$item['key']] = $item['value'];
                            }
                        }
                    }
                } else {
                    if (!empty($filds)) {
                        unset($filds['_edit_lock'], $filds['_edit_last']);
                        foreach ($filds as $nameFilds => $item) {
                            if (!empty($item[0])) {
                                $data_res['fields'][$nameFilds] = $item[0];
                            }
                        }
                    }
                }
                $res = $RumailerAPI->add_subscriber($data_res);
                if(!empty($res->error)){
                    $post->post_status = 'pending';
                    wp_update_post( $post );
                }
            } elseif($post->post_status == 'draft'){
                $post->post_status = 'publish';
                wp_update_post( $post );
            } else {
                foreach ($RumailerListFilds as $filds) {
                    $item = get_post_meta($post_ID, $filds);
                    if (empty($item)) {
                        add_post_meta($post_ID, $filds, '');
                    }
                }
            }
        }
    }

    /**
     * Add administration menus
     */
    public function add_admin_pages()
    {
        add_submenu_page(
            'edit.php?post_type=' . self::PageName,
            "setting",
            __('Setting',self::PageName),
            'manage_options',
            'setting-page',
            array($this, 'setting_callback')
        );
        $api_user = get_option('rumailer_setting_api');
        if (!empty($api_user)) {
            add_submenu_page(
                'edit.php?post_type=' . self::PageName,
                "Info",
                __('Information',self::PageName),
                'manage_options',
                'get_info',
                array($this, 'get_info')
            );
            $quota = $this->API->get_balance();
            if (empty($quota->error)) {
                add_submenu_page(
                    'edit.php?post_type=' . self::PageName,
                    "Export",
                    __('Export',self::PageName),
                    'manage_options',
                    'export',
                    array($this, 'export')
                );
            }
        }
    }

    /**
     * Экспорт зарегистрированных пользователей
     */
    public function export(){?>
        <div class="wrap" id="clones-forms">
            <h2><? _e('Export', self::PageName); ?></h2>
            <? if (!empty($_POST) and wp_verify_nonce($_POST['rumailer_export_user'], 'export')) {
                if(!empty($_POST['rumailer_setting_list']) and !empty($_POST['role'])){
                    global $RumailerAPI;
                    $data = $_POST;
                    $users = get_users( array(
                        'blog_id'      => $GLOBALS['blog_id'],
                        'role'         => sanitize_text_field($data['role']),
                        'role__in'     => array(),
                        'role__not_in' => array(),
                        'meta_key'     => '',
                        'meta_value'   => '',
                        'meta_compare' => '',
                        'meta_query'   => array(),
                        'include'      => array(),
                        'exclude'      => array(),
                        'orderby'      => 'login',
                        'order'        => 'ASC',
                        'offset'       => '',
                        'search'       => '',
                        'search_columns' => array(),
                        'number'       => '',
                        'paged'        => 1,
                        'count_total'  => false,
                        'fields'       => 'all',
                        'who'          => '',
                        'has_published_posts' => null,
                        'date_query'   => array() // смотрите WP_Date_Query
                    ) );
                    $wp_list = get_terms(array(
                        'taxonomy' => 'rumailer_list',
                        'hide_empty' => false,
                        'orderby' => 'id',
                        'slug'   => sanitize_text_field($data['rumailer_setting_list']),
                        'order' => 'ASC'
                    ));

                    if(!empty($wp_list)){
                        $wp_list = $wp_list[0];
                    }
                    foreach($users as $k=>$user){
                        $is_post = $this->is_post_user($data['rumailer_setting_list'],$user->data->user_email);
                        if($is_post){
                            $userdata = get_user_meta( $user->data->ID );
                            unset(
                                $userdata['description'],
                                $userdata['rich_editing'],
                                $userdata['comment_shortcuts'],
                                $userdata['admin_color'],
                                $userdata['use_ssl'],
                                $userdata['show_admin_bar_front'],
                                $userdata['qp0vvweva2_capabilities'],
                                $userdata['qp0vvweva2_user_level'],
                                $userdata['session_tokens'],
                                $userdata['last_update'],
                                $userdata['dismissed_wp_pointers'],
                                $userdata['qp0vvweva2_user-settings'],
                                $userdata['qp0vvweva2_user-settings-time'],
                                $userdata['meta-box-order_product'],
                                $userdata['closedpostboxes_product'],
                                $userdata['metaboxhidden_product'],
                                $userdata['closedpostboxes_post'],
                                $userdata['metaboxhidden_post'],
                                $userdata['nav_menu_recently_edited'],
                                $userdata['managenav-menuscolumnshidden'],
                                $userdata['metaboxhidden_nav-menus'],
                                $userdata['itsec-settings-view'],
                                $userdata['qp0vvweva2_yoast_notifications'],
                                $userdata['closedpostboxes_dashboard'],
                                $userdata['qp0vvweva2_dashboard_quick_press_last_post_id'],
                                $userdata['wpseo_ignore_tour'],
                                $userdata['metaboxhidden_dashboard'],
                                $userdata['itsec_user_activity_last_seen'],
                                $userdata['meta-box-order_dashboard'],
                                $userdata['_yoast_wpseo_profile_updated'],
                                $userdata['_woocommerce_persistent_cart'],
                                $userdata['manageedit-shop_ordercolumnshidden']);
                            $post_data = array(
                                'post_title'    => $user->data->user_email,
                                'post_name'     => $user->data->user_nicename,
                                'post_status'   => 'pending',
                                'post_type'     => 'rumailer'
                            );
                            $data_res = array(
                                'list_ids' => sanitize_text_field($data['rumailer_setting_list']),
                                'email' => $user->data->user_email,
                                'fields' => '',
                                'double_optin' => '1',
                            );
                            $post_id = wp_insert_post( $post_data );
                            if(!empty($wp_list)){
                                wp_set_object_terms( $post_id, $wp_list->slug, 'rumailer_list', true);
                            }
                            foreach($userdata as $filds=>$param){
                                if($filds == 'last_name'){
                                    $filds = 'lastname';
                                }
                                if($filds == 'first_name'){
                                    $filds = 'firstname';
                                }
                                if($filds == 'billing_phone'){
                                    $filds = 'phone';
                                }
                                if($filds == 'billing_address_1'){
                                    $filds = 'address';
                                }
                                if($filds == 'billing_city'){
                                    $filds = 'city';
                                }
                                if(!empty($param[0])){
                                    $data_res['fields'][sanitize_text_field($filds)] = sanitize_text_field($param[0]);
                                    add_post_meta($post_id, sanitize_text_field($filds), sanitize_text_field($param[0]));
                                }
                            }
                            sleep(1);
                            $RumailerAPI->add_subscriber($data_res);
                        } else {
                            unset($users[$k]);
                            continue;
                        }
                    }
                    if(empty($wp_list->name)){
                        $name_list = 'ERROR LIST NAME';
                    } else {
                        $name_list = $wp_list->name;
                    }
                    $users_count = count($users);
                    if($users_count==0){
                        $name_list = 'ERROR COUNT USER';
                    } ?>
                    <p><? echo sprintf( __('All %d exported to the subscription list "%s"', self::PageName), count($users), $name_list ); ?></p>
                    <?
                } else {
                    echo __('Error parametr', self::PageName);
                }
            } else {?>
                <form id="reg_profile" action="" method="post" enctype="multipart/form-data">
                    <? echo wp_nonce_field('export', 'rumailer_export_user', true, false); ?>
                    <table class="form-table">
                        <tbody>
                        <tr class="user-role-wrap"><th><label for="role"><?php _e('Role') ?></label></th>
                            <td><select name="role" id="role">
                                    <?php
                                    if(empty($_POST['role'])){
                                        $this_role = '';
                                    } else {
                                        $this_role =  $_POST['role'];
                                    }
                                    $user_roles = array_intersect( $this_role, array_keys( get_editable_roles() ) );
                                    $user_role  = reset( $user_roles );
                                    wp_dropdown_roles($user_role);
                                    if ( $user_role )
                                        echo '<option value="">' . __('&mdash; No role for this site &mdash;') . '</option>';
                                    else
                                        echo '<option value="" selected="selected">' . __('&mdash; No role for this site &mdash;') . '</option>';
                                    ?>
                                </select></td></tr>
                        <?
                        $api_user = get_option('rumailer_setting_api');
                        if (!empty($api_user)) {
                            global $RumailerAPI;
                            $get_lists = $RumailerAPI->get_lists();
                            $this_list = get_option('rumailer_setting_list');
                            ?>
                            <tr class="form-field form-required term-name-wrap">
                                <th scope="row"><label for="rumailer_setting_list"><? _e('List', self::PageName); ?></label>
                                </th>
                                <td>
                                    <select name="rumailer_setting_list" id="rumailer_setting_list">
                                        <option value="">-</option>
                                        <? foreach ($get_lists->result as $list) { ?>
                                            <option value="<?= $list->id ?>"
                                                    <? if ($this_list == $list->id){ ?>selected="selected"<? } ?>><?= $list->title ?></option>
                                        <? } ?>
                                    </select>

                                    <p class="description"><? _e('The subscription list, will be added to users', self::PageName); ?></p>
                                </td>
                            </tr>
                        <? } ?>
                        </tbody>
                    </table>
                    <input type="hidden" name="action" value="export"/>

                    <p class="submit">
                        <input type="submit" class="button-primary" value="<? _e('Export', self::PageName); ?>"/>
                    </p>
                </form>
            <?} ?>
        </div>
        <?
    }

    /**
     * Информация с сервиса
     */
    public function get_info()
    {
        $quota = $this->API->get_quota();
        $balanc = $this->API->get_balance();
        ?>
        <style>
            .m_table_wr3 {
                background: #faf5df;
                border: 1px solid #e5daab;
                border-radius: 3px;
                width: 331px;
                margin-bottom: 18px;
                color: #655b31;
            }

            .zag20 {
                font: bold 20px/22px PT Sans;
                color: #000;
                padding-bottom: 14px;
                margin: 0;
            }

            .m_pad_in_tbl {
                padding: 20px 10px 0px 18px;
                position: relative;
            }

            .yellow_line {
                background: #e5daab;
                height: 1px;
                width: 100%;
            }

            .list_filter_wr {
                padding: 15px;
                line-height: 20px;
            }

            .summ_acc {
                position: relative;
                color: #655b31;
                font: bold 32px/35px PT Sans;
            }

            .summ_acc span {
                font: bold 13px/15px PT Sans;
            }

            .add_money_btn {
                position: absolute;
                top: 17px;
                right: 10px;
                width: 75px;
                padding: 0px 10px 0px 38px;
                background: url('http://rumailer.ru/content/img/cash.png') no-repeat top left;
                font: normal 13px/15px PT Sans;
                outline: none;
            }

            .list_filter_wr {
                padding: 15px;
                line-height: 20px;
            }

            /**/
            .m_table_wr2 {
                background: #e4ecf0;
                border: 1px solid #c8d7df;
                border-radius: 3px;
                width: 331px;
                margin-bottom: 18px;
            }

            .auto_width {
                width: 100%;
                position: relative;
                overflow-x: hidden;
                overflow: auto;
            }

            .blue_line {
                background: #c8d7df;
                height: 1px;
                width: 100%;
            }

            .list_filter_wr {
                padding: 15px;
                line-height: 20px;
            }

            .string_pseudo_tr {
                border-bottom: 1px solid #b0c2a8;
                border-top: 1px solid #fff;
                color: #586752;
                height: 33px;
                background: #ecf2e9;
                font: bold 15px/33px PT Sans;
                position: relative;
            }

            .sending_status {
                background: url(http://rumailer.ru/content/img/progress.png) repeat-x top left;
                position: absolute;
                width: 100%;
                height: 100%;
                left: 0px;
                top: 0px;
            }

            .under_sending_status {
                z-index: 1;
                position: relative;
            }
        </style>


        <div class="wrap" id="clones-forms">
            <h2><?= __('Information',self::PageName) ?></h2>
            <? if (!empty($quota->error)) {?>
                <div><? _e($quota->error,self::PageName)?></div>
            <? } else {?>
        <div>
                <div class="clerfix">
                    <div class="m_table_wr3 clerfix" style="float: left">
                        <div class="m_pad_in_tbl">
                            <h2 class="zag20"><? _e('Account balance',self::PageName);?></h2>
                        </div>
                        <div class="yellow_line"></div>
                        <div class="list_filter_wr summ_acc">
                            <?= number_format($balanc->result->balance, 2, '.', ' ') ?> <span><? _e('rub',self::PageName);?>.</span>
                            <a target="_blank" href="http://rumailer.ru/user/users/add_money/page"
                               class="add_money_btn"><? _e('Fill',self::PageName);?> <br><? _e('balance',self::PageName);?></a>
                        </div>
                        <div class="yellow_line"></div>
                        <div class="list_filter_wr">
                            <img src="http://rumailer.ru/content/img/opl.png" alt="">
                        </div>
                        <div class="clear"></div>
                    </div>
                    <div style="float: left; margin-left: 25px">
                        <p><a class="button-primary" target="_blank" href="http://rumailer.ru/create_list"><? _e('Create a sign-up sheet',self::PageName);?></a></p>
                        <p><a class="button-primary" href="/wp-admin/edit-tags.php?taxonomy=rumailer_list&post_type=rumailer"><? _e('Get a subscription sheets Rumailer',self::PageName);?></a></p>
                        <p><a class="button-primary" target="_blank" href="http://rumailer.ru/opt_ins"><? _e('Create a feedback form',self::PageName);?></a></p>
                        <p><a class="button-primary" target="_blank" href="http://rumailer.ru/letter_series"><? _e('Set up a series of letters',self::PageName);?></a></p>
                    </div>
                </div>
                <div class="m_table_wr2 auto_width">
                    <div class="m_pad_in_tbl">
                        <h2 class="zag20"><? _e('Activity',self::PageName);?></h2>
                    </div>
                    <div class="blue_line"></div>
                    <div class="list_filter_wr">
                        <div class="string_pseudo_tr">
                            <div class="sending_status"
                                 style="width:<?= ($quota->result->count_send_letters / $quota->result->max_letters_in_month) * 100 ?>%;"></div>
                            <div style="font-size:12px;text-align:center;font-weight:normal"
                                 class="under_sending_status"><? _e('Emails Sent this month:',self::PageName);?><?= $quota->result->count_send_letters ?>
                                /<?= $quota->result->max_letters_in_month ?>
                            </div>
                        </div>
                    </div>
                    <div class="list_filter_wr" style="padding-top:0px;padding-bottom:0px;">
                        <div class="string_pseudo_tr">
                            <div class="sending_status"
                                 style="width:<?= ($quota->result->count_subscribers / $quota->result->max_tarif_subscribers) * 100 ?>%;"></div>
                            <div style="font-size:12px;text-align:center;font-weight:normal"
                                 class="under_sending_status"><? _e('Subscribers:',self::PageName);?>
                                 <?= $quota->result->count_subscribers ?>
                                /<?= $quota->result->max_tarif_subscribers ?>
                            </div>
                        </div>
                    </div>
                    <div class="list_filter_wr">
                        <a style="margin: 0px auto; width: 300px; outline: none;" target="_blank"
                           class="green_btn height31" href="http://rumailer.ru/my_tarifs">
                            <? _e('Change rate:',self::PageName);?>
                        </a>
                    </div>
                </div>
            </div>
           <? } ?>
        </div>

        <?
    }

    /**
     * Настройка Rumailer
     */
    public function setting_callback()
    {
        ?>
        <div class="wrap" id="clones-forms">
            <h2><? _e('Settings',self::PageName);?></h2>
            <?

            $api_user = get_option('rumailer_setting_api');
            if (empty($api_user)) {
                ?>
                <p><? _e('For API you need',self::PageName);?> <a href="http://rumailer.ru/my_tickets" target="_blank"><? _e('make a request to support',self::PageName);?></a></p>
            <?
            } ?>
            <form method="post" action="options.php">
                <?php settings_fields('rumailer-my-setting'); ?>
                <table class="form-table">
                    <tbody>
                    <tr class="form-field form-required term-name-wrap">
                        <th scope="row"><label for="rumailer_setting_name"><? _e('Name',self::PageName);?></label></th>
                        <td><input name="rumailer_setting_name" id="rumailer_setting_name" type="text"
                                   value="<?php echo get_option('rumailer_setting_name'); ?>" aria-required="true"/>
                            <p class="description"><? _e('Username on Rumailer',self::PageName);?></p>
                        </td>
                    </tr>
                    <tr class="form-field form-required term-name-wrap">
                        <th scope="row"><label for="rumailer_setting_email"><? _e('E-mail',self::PageName);?></label></th>
                        <td><input name="rumailer_setting_email" id="rumailer_setting_email" type="text"
                                   value="<?php echo get_option('rumailer_setting_email'); ?>" aria-required="true"/>
                            <p class="description"><? _e('The main user post on Rumailer',self::PageName);?></p>
                        </td>
                    </tr>
                    <tr class="form-field form-required term-name-wrap">
                        <th scope="row"><label for="rumailer_setting_api"><? _e('API',self::PageName);?></label></th>
                        <td><input name="rumailer_setting_api" id="rumailer_setting_api" type="text"
                                   value="<?php echo get_option('rumailer_setting_api'); ?>" aria-required="true"/>
                            <p class="description"><? _e('API issued on Rumailer',self::PageName);?></p>
                        </td>
                    </tr>
                    <?
                    $api_user = get_option('rumailer_setting_api');
                    if (!empty($api_user)) {
                        global $RumailerAPI;
                        $get_lists = $RumailerAPI->get_lists();
                        $this_list = get_option('rumailer_setting_list');
                        ?>
                        <tr class="form-field form-required term-name-wrap">
                            <th scope="row"><label for="rumailer_setting_list"><? _e('List',self::PageName);?></label></th>
                            <td>
                                <select name="rumailer_setting_list" id="rumailer_setting_list" >
                                    <option value="">-</option>
                                    <?foreach ($get_lists->result as $list){?>
                                        <option value="<?=$list->id?>" <?if($this_list==$list->id){?>selected="selected"<?}?>><?=$list->title?></option>
                                    <?}?>
                                </select>
                                <p class="description"><? _e('The subscription list, which will be added to users upon registration',self::PageName);?></p>
                            </td>
                        </tr>
                    <? } ?>
                    </tbody>
                </table>
                <input type="hidden" name="action" value="update"/>
                <input type="hidden" name="page_options"
                       value="rumailer_setting_name,rumailer_setting_email,rumailer_setting_api"/>

                <p class="submit">
                    <input type="submit" class="button-primary" value="<? _e('Save',self::PageName);?>"/>
                </p>
            </form>
        </div>
    <?
    }

}

new Rumailer;
?>