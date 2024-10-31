<?php
/**
 * Created by PhpStorm.
 * User: Даниил
 * Date: 21.09.2016
 * Time: 10:26
 */
class Rumailer_Form_Widget extends WP_Widget {

    /**
     * Constructor.
     * Rumailer_Form_Widget constructor.
     */
    public function __construct() {
        parent::__construct( 'widget_rumailer_rorm', __('Feedback form on Rumailer','rumailer'), array(
            'classname'   => 'widget_rumailer_rorm',
            'description' => __( 'Use the feedback form on Rumailer', 'rumailer' ),
        ) );
    }

    /**
     * Output the HTML for this widget.
     * @access public
     * @since Twenty Fourteen 1.0
     * @param array $args     An array of standard parameters for widgets in this theme.
     * @param array $instance An array of settings for this widget instance.
     */
    public function widget( $args, $instance ) {
        $fields = $instance['fields'];
        $form = $instance['form'];
        echo $args['before_widget'];
        if(!empty($form)) {
            $form = json_decode($form, true);
            ?>
            <!-- RUMAILER FORM SUBSCRIBE START -->
            <form method="post" action="http://rumailer.ru/add_subscriber_from_form">
                <div class="droppedFields ui-droppable ui-sortable"
                     style="margin: 0 auto; background:<?= $form['background'] ?>;width:<?= $form['width'] ?>px;border-width:<?= $form['border_width'] ?>px;border-color:<?= $form['border_color'] ?>;border-radius:<?= $form['border_radius'] ?>px;border-style:solid; position: relative; overflow: hidden"
                     id="rumailer_form">
                    <?
                    if (!empty($fields)) {
                        $form_fields = json_decode($fields, true);
                        foreach ($form_fields as $form_field) {
                            if ($form_field['field_type'] == "main_field") {
                                ?>
                                <div class="fld" field_id="<?= $form_field['field_id'] ?>"
                                     field_type="<?= $form_field['type'] ?>" <? if (!empty($form_field['is_important'])) {echo 'is_important="1"';} ?>
                                     style="<? if ($form_field['type'] == 'text'){ ?>background:<?= $form_field['background'] ?><? } ?>;
                                         padding:10px 5px 10px 5px;
                                         position:relative;
                                         text-align':'center" >
                                    <?switch ($form_field['type']) {
                                        case 'text':
                                            ?>
                                            <span class="field_object"
                                                  style="font-family:<? echo empty($form_field['font-family']) ? 'Arial' : $form_field['font-family']; ?>; font-weight:<?= $form_field['font-weight'] ?>;color:<?= $form_field['color'] ?>; font-size:<?= $form_field['font-size'] ?>px"><?= $form_field['text'] ?></span>
                                            <?
                                            break;
                                        case 'input':
                                            ?>
                                            <input class="field_object"
                                                <? if (!empty($form_field['is_important'])) {?>required="required"<?}?>
                                                   style="
                                                       <? if (!empty($form_field['is_important'])) {?>
                                                           background-image: url('http://rumailer.ru/content/img/imp_form.png');
                                                           background-repeat:no-repeat;
                                                           background-position:98% 12px;
                                                       <?}?>
                                                       width: 85%; padding: 3px; height: 28px; font-family:<? echo empty($form_field['font-family']) ? 'Arial' : $form_field['font-family']; ?>; border-width:<?= $form_field['border-width'] ?>px; border-color:<?= $form_field['border-color'] ?>; background:<?= $form_field['background'] ?>; color:<?= $form_field['color'] ?>; border-radius:<?= $form_field['border-radius'] ?>px; font-size:<?= $form_field['font-size'] ?>px"
                                                   type="text" name="<?= $form_field['name'] ?>"
                                                   placeholder="<?= $form_field['text'] ?>"/>
                                            <?
                                            break;
                                        case 'button':
                                            ?>
                                            <input class="field_object"
                                                   style="width: 85%; padding: 3px; height:40px; cursor: pointer; font-family:<? echo empty($form_field['font-family']) ? 'Arial' : $form_field['font-family']; ?>; border-radius:<?= $form_field['border-radius'] ?>px; border-color:<?= $form_field['border-color'] ?>; border-width:<?= $form_field['border-width'] ?>px; font-weight:<?= $form_field['font-weight'] ?>; background:<?= $form_field['background'] ?>; color:<?= $form_field['color'] ?>; font-size:<?= $form_field['font-size'] ?>px"
                                                   name="<?= $form_field['name'] ?>" type="submit"
                                                   value="<?= $form_field['text'] ?>"/>
                                            <?
                                            break;
                                    }
                                    ?>
                                </div>
                            <?
                            } elseif ($form_field['field_type'] == "user_field") {
                                ?>
                                <div class="fld user_field" field_id="user_f<?= $form_field['field_id'] ?>"
                                     field_type="input" <? if (!empty($form_field['is_important'])) {echo 'is_important="1"';} ?>
                                     style="<? if ($form_field['type'] == 'text'){ ?>background:<?= $form_field['background'] ?><? } ?>;
                                         padding:10px 5px 10px 5px;
                                         position:relative;
                                         text-align':'center"
                                    >
                                    <input class="field_object"
                                           <? if (!empty($form_field['is_important'])) {?>required="required"<?}?>
                                           style="
                                           <? if (!empty($form_field['is_important'])) {?>
                                               background-image: url('http://rumailer.ru/content/img/imp_form.png');
                                               background-repeat:no-repeat;
                                               background-position:98% 12px;
                                           <?}?>
                                               width: 85%; padding: 3px; height: 28px; font-family:<? echo empty($form_field['font-family']) ? 'Arial' : $form_field['font-family']; ?>; border-width:<?= $form_field['border-width'] ?>px; background:<?= $form_field['background'] ?>; color:<?= $form_field['color'] ?>; border-color:<?= $form_field['border-color'] ?>; border-radius:<?= $form_field['border-radius'] ?>px; font-size:<?= $form_field['font-size'] ?>px"
                                           type="text" name="user_f<?= $form_field['field_id'] ?>"
                                           placeholder="<?= $form_field['text'] ?>"/>
                                </div>
                            <?
                            }
                        }
                    }?>
                </div>
                <input type="hidden" name="opt_in_id" value="<?= $form['opt_in_id'] ?>"/>
                <script async type="text/javascript" src="http://rumailer.ru/content/javascript/rm_scripts.js"></script>
            </form>
            <!-- RUMAILER FORM SUBSCRIBE END -->
        <?php
        }
            echo $args['after_widget'];
    }

    /**
     * Обновляем
     * @param array $new_instance
     * @param array $instance
     * @return array
     */
    function update( $new_instance, $instance ) {
        $instance['format'] = $new_instance['format'];
        if(!empty($new_instance['format'])){
            global $RumailerAPI;
            $get_forms_list = $RumailerAPI->get_forms_list();
            if(!empty($get_forms_list->result)){
                foreach($get_forms_list->result as $forms){
                    if($forms->id == $instance['format']){
                        $instance['fields'] = json_encode($forms->fields);
                        $instance['form'] = json_encode($forms->setting);
                    }
                }
            }
        }
        return $instance;
    }

    /**
     * Display the form for this widget on the Widgets page of the Admin area.
     * @param array $instance
     * @return string|void
     */
    function form( $instance ) {
        global $RumailerAPI;
        $get_forms_list = $RumailerAPI->get_forms_list();
        $format = empty( $instance['format'] ) ? '' : esc_attr( $instance['format'] );
        if(!empty($get_forms_list->result)){
            ?>
            <p><b><? _e('Attention!','rumailer');?></b> <? _e('After changing the shapes on Rumailer not forget to click the save button here.','rumailer');?> </p>
            <p style="text-align: center"><a class="button action" target="_blank" href="http://rumailer.ru/opt_ins"><? _e('Edit form on Rumailer','rumailer');?></a></p><?
            $get_forms_list = $get_forms_list->result;?>
            <input id="<?php echo esc_attr( $this->get_field_id( 'fields' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'fields' ) ); ?>" type="hidden" value="json">
            <input id="<?php echo esc_attr( $this->get_field_id( 'form' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'form' ) ); ?>" type="hidden" value="json">
            <p><label for="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>">Форма:</label>
            <select id="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>" class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'format' ) ); ?>">
                <?php foreach ( $get_forms_list as $slug ) : ?>
                    <option value="<?php echo $slug->id; ?>"<?php selected( $format, $slug->id ); ?>><?php echo $slug->title; ?> (<?php echo $slug->status ; ?>)</option>
                <?php endforeach; ?>
            </select>
        <?
        } else {?>
          <p style="text-align: center"><? _e('You have yet not one form!','rumailer');?> <br><br>
              <a class="button action" target="_blank" href="http://rumailer.ru/opt_ins"><? _e('Create a feedback form','rumailer');?></a>
          </p>
        <?}
        ?>

        <?php
    }
}

add_action( 'widgets_init', 'register_rumailer_form_widget' );
function register_rumailer_form_widget() {
    register_widget( 'Rumailer_Form_Widget' );
}