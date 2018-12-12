<?php
/*
 * Slider cell type.
 * Displays set of images using Bootstrap's carousel component
 *
 */



if( ddl_has_feature('slider-cell') === false ){
	return;
}

if (!class_exists('Layouts_cell_slider', false )) {
    class Layouts_cell_slider{

        // define cell name
        private $cell_type = 'slider-cell';
        
        function __construct() {
            add_action( 'init', array(&$this,'register_slider_cell_init' ), 12);
        }
        
        function register_slider_cell_init() {
            if (function_exists('register_dd_layout_cell_type')) {
                register_dd_layout_cell_type($this->cell_type, 
                    array(
                        'name' => __('Slider', 'ddl-layouts'),
                        'cell-image-url' => DDL_ICONS_SVG_REL_PATH . 'layouts-slider-cell.svg',
                        'description' => __('Display the image slider, built using the Bootstrap Carousel component.', 'ddl-layouts'),
                        'category' => __('Fields, text and media', 'ddl-layouts'),
                        'button-text' => __('Assign Slider cell', 'ddl-layouts'),
                        'dialog-title-create' => __('Create new Slider cell', 'ddl-layouts'),
                        'dialog-title-edit' => __('Edit Slider cell', 'ddl-layouts'),
                        'dialog-template-callback' => array(&$this,'slider_cell_dialog_template_callback'),
                        'cell-content-callback' => array(&$this,'slider_cell_content_callback'),
                        'cell-template-callback' => array(__CLASS__,'slider_cell_template_callback'),
                        'cell-class' => '',
                        'has_settings' => true,
                        'preview-image-url' => DDL_ICONS_PNG_REL_PATH . 'slider_expand-image.png',
                        'translatable_fields' => array(
                            'slider[slide_url]' => array('title' => 'Slide URL', 'type' => 'LINE'),
                            'slider[slide_title]' => array('title' => 'Slide title', 'type' => 'LINE'),
                            'slider[slide_text]' => array('title' => 'Slide description', 'type' => 'AREA')
                        ),
                        'register-scripts' => array(
                            array('ddl-slider-cell-script', WPDDL_GUI_RELPATH . 'editor/js/ddl-slider-cell-script.js', array('jquery'), WPDDL_VERSION, true),
                        ),
                    )
                );
            }
        }
        
        
        function slider_cell_dialog_template_callback() {
            ob_start();
            ?>

            <div class="ddl-form pad-bot-0">
                <p>
                    <label for="<?php the_ddl_name_attr('slider_height'); ?>" class="ddl-manual-width-201"><?php _e( 'Slider height', 'ddl-layouts' ) ?>:</label>
                    <span class="ddl-input-wrap"><input type="number" name="<?php the_ddl_name_attr('slider_height'); ?>" value="300" class="ddl-input-half-width"><span class="ddl-measure-unit"><?php _e( 'px', 'ddl-layouts' ) ?></span></span>
                </p>
                <p>
                    <label for="<?php the_ddl_name_attr('interval'); ?>" class="ddl-manual-width-201"><?php _e( 'Interval', 'ddl-layouts' ) ?>:</label>
                    <span class="ddl-input-wrap"><input type="number" name="<?php the_ddl_name_attr('interval'); ?>" value="5000" class="ddl-input-half-width"><span class="ddl-measure-unit"><?php _e( 'ms', 'ddl-layouts' ) ?></span><i class="fa fa-question-circle question-mark-and-the-mysterians js-ddl-question-mark" data-tooltip-text="<?php _e( 'The amount of time to delay between automatically cycling an item, ms.', 'ddl-layouts' ) ?>"></i></span>
                </p>
                <fieldset>
                    <legend><?php _e( 'Options', 'ddl-layouts' ) ?></legend>
                    <div class="fields-group">
                        <label class="checkbox" for="<?php the_ddl_name_attr('autoplay'); ?>">
                            <input type="checkbox" name="<?php the_ddl_name_attr('autoplay'); ?>" id="<?php the_ddl_name_attr('autoplay'); ?>" value="true">
                            <?php _e( 'Autoplay', 'ddl-layouts' ) ?>
                        </label>
                        <label class="checkbox" for="<?php the_ddl_name_attr('pause'); ?>">
                            <input type="checkbox" name="<?php the_ddl_name_attr('pause'); ?>" id="<?php the_ddl_name_attr('pause'); ?>" value="pause">
                            <?php _e( 'Pause on hover', 'ddl-layouts' ) ?>
                        </label>
                        <?php apply_filters('ddl-slider_cell_additional_options', '');?>
                    </div>
                </fieldset>
                <fieldset class="from-top-6">
                    <legend><?php _e( 'Image size', 'ddl-layouts' ) ?></legend>
                    <div class="fields-group">
                        <label class="checkbox checkbox-smaller float-none" for="<?php the_ddl_name_attr('image_size'); ?>">
                            <input type="radio" name="<?php the_ddl_name_attr('image_size'); ?>" id="<?php the_ddl_name_attr('image_size'); ?>" checheked="checked" value="">
                            <?php _e( 'Contain (crop)', 'ddl-layouts' ) ?>
                        </label>
                        <span><i class="fa fa-question-circle question-mark-and-the-mysterians js-ddl-question-mark" data-tooltip-text="<?php _e( 'The background image will be scaled so that each side is as large as possible while not exceeding the length of the corresponding side of the container.', 'ddl-layouts' ) ?>"></i></span>
                        <div class="clear from-bot-4"></div>
                        <label class="checkbox checkbox-smaller float-none" for="<?php the_ddl_name_attr('image_size'); ?>_cover">
                            <input type="radio" name="<?php the_ddl_name_attr('image_size'); ?>" id="<?php the_ddl_name_attr('image_size'); ?>_cover" value="cover">
                            <?php _e( 'Cover (add padding)', 'ddl-layouts' ) ?>
                        </label>
                        <span><i class="fa fa-question-circle question-mark-and-the-mysterians js-ddl-question-mark" data-tooltip-text="<?php _e( 'The background image will be sized so that it is as small as possible while ensuring that both dimensions are greater than or equal to the corresponding size of the container.', 'ddl-layouts' ) ?>"></i></span>
                    </div>
                </fieldset>

                <h3><?php _e('Slides', 'ddl-layouts'); ?></h3>

                <?php ddl_repeat_start( 'slider', __( 'Add another slide', 'ddl-layouts' ), 10 ); ?>

                <p class="js-ddl-media-field">
                    <label for="<?php the_ddl_name_attr('slide_url'); ?>" class="ddl-manual-width-201"><?php _e( 'Image', 'ddl-layouts' ) ?>:</label>
                    <span class="ddl-input-wrap"><input type="text" class="js-ddl-media-url ddl-input-two-thirds-width" name="<?php the_ddl_name_attr('slide_url'); ?>" />
                        <span class="ddl-form-button-wrap ddl-input-two-thirds-width-span">
                                <button class="button js-ddl-add-media"
                                                data-uploader-title="<?php _e( 'Choose an image', 'ddl-layouts' ) ?>"
                                                data-uploader-button-text="Insert image URL"><?php _e( 'Choose an image', 'ddl-layouts' ) ?>
                                </button>
                        </span>
                    </span>
                </p>
                <p>
                    <label for="<?php the_ddl_name_attr('slide_title'); ?>"><?php _e( 'Caption title', 'ddl-layouts' ) ?>:</label>
                    <input type="text" name="<?php the_ddl_name_attr('slide_title'); ?>">
                </p>
                <p>
                    <label for="<?php the_ddl_name_attr('slide_text'); ?>"><?php _e( 'Caption description', 'ddl-layouts' ) ?>:</label>
                    <textarea name="<?php the_ddl_name_attr('slide_text'); ?>" rows="3"></textarea>
                    <span class="desc"><?php _e('You can add HTML to the slide description.', 'ddl-layouts'); ?></span>
                </p>

                <?php ddl_repeat_end( array( 'additional_wrap_class' => ' from-top-0 pad-top-0') ); ?>

            </div>
            <?php
            return ob_get_clean();
	}
        
        
        // Callback function for displaying the cell in the editor.
	public static function slider_cell_template_callback() {
            ob_start();
            ?>
            <div class="cell-content">
                <p class="cell-name"><?php _e('Slider', 'ddl-layouts'); ?></p>
                <div class="cell-preview">
                    <div class="ddl-slider-preview">
                        <img src="<?php echo DDL_ICONS_SVG_REL_PATH . 'slider.svg'; ?>" height="130px">
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
	}

	// Callback function for display the cell in the front end.
	function slider_cell_content_callback() {

            $unique_id = uniqid();
            $pause = '';

            if ( get_ddl_field('pause') ) {
                    $pause = 'data-pause="hover"';
            } else {
                    $pause = 'data-pause="false"';
            }

            ob_start();
            ?>

            <?php if ( get_ddl_field('autoplay') ) :
                $this->get_autoplay_script($unique_id);
            endif; ?>

            <?php $this->get_inline_style($unique_id); ?>

                <?php $count_slides = 1; ?>
                <?php $carousel_container_class = apply_filters( 'ddl-carousel_container_class', 'carousel slide ddl-slider' );?>
                <?php $carousel_tag = apply_filters( 'ddl-carousel_element_tag', 'div' );?>
                <?php $carousels_tags = apply_filters( 'ddl-carousel_elements_tag', 'div' );?>
                <?php $carousel_data_attr = apply_filters( 'ddl-carousel_element_data_attribute', '' );?>
		        <?php $carousel_data_ride = get_ddl_field('autoplay') ? apply_filters( 'ddl-carousel_element_data_ride_attribute', 'data-ride="carousel"' ) : apply_filters( 'ddl-carousel_element_data_ride_attribute', '' );?>
                <?php $carousel_class = apply_filters( 'ddl-carousel_element_class_attribute', 'carousel-inner' );?>
                <?php $carousel_items_classes = apply_filters( 'ddl-carousel_items_classes', '' );?>
                <?php $carousel_active_class = apply_filters( 'ddl-carousel_active_element_class', 'active' );?>
                <?php $container_additional_attributes = apply_filters( 'ddl-carousel_container_additional_attributes', '' );?>


            <div id="slider-<?php echo $unique_id ?>" class="<?php echo $carousel_container_class;?>" <?php echo $pause ?> data-interval="<?php the_ddl_field('interval') ?>" <?php echo $container_additional_attributes;?> <?php echo $carousel_data_ride;?> >

                    <?php $this->get_carousel_indicators($unique_id);?>

                <<?php echo $carousel_tag;?> class="<?php echo $carousel_class?>" <?php echo $carousel_data_attr;?>>

                    <?php
                    apply_filters('ddl-get_additional_carousel_top_controls_if', '');
                    while ( has_ddl_repeater('slider') ) : the_ddl_repeater('slider'); ?>

                    <<?php echo $carousels_tags;?> class="<?php echo $carousel_items_classes;?>item <?php if (get_ddl_repeater_index() == 0) { echo ' '.$carousel_active_class; } ?>"
                        <?php // Cover image to slide
                        if( get_ddl_field('image_size') == 'cover' ): ?>
                            style="background: url(<?php the_ddl_sub_field('slide_url'); ?>) no-repeat; background-size:cover;"
                        <?php endif;?>
                        >
                        <?php if( get_ddl_field('image_size') == '' ): ?>
                            <img src="<?php the_ddl_sub_field('slide_url'); ?>" alt="slide-<?php echo $count_slides;?>" />
                        <?php endif;
                        if( get_ddl_sub_field('slide_title')  || get_ddl_sub_field('slide_text') ) :
                        ?>
                        <div class="<?php echo apply_filters('ddl-carousel_caption_class_attribute', 'carousel-caption');?>">
                            <h4>
                                <?php the_ddl_sub_field('slide_title'); ?>
                            </h4>
                            <p>
                                <?php the_ddl_sub_field('slide_text'); ?>
                            </p>
                        </div>
                        <?php endif;?>
                    </<?php echo $carousels_tags;?>>
                    <?php
                    $count_slides++;
                    endwhile;
                    ?>
                </<?php echo $carousel_tag;?>>

                <?php $this->get_carousel_indicators_bottom($unique_id, $count_slides);?>

                <?php echo apply_filters('ddl-carousel_control_left', '<a class="'.apply_filters("ddl-carousel_control_left_class_attribute", "left carousel-control").'" href="#slider-'.$unique_id .'" data-slide="prev"><span class="'. apply_filters( "ddl-slider-cell-icon-prev", "icon-prev" ) .'"></span></a>', $unique_id);?>
                <?php echo apply_filters('ddl-carousel_control_right', '<a class="'.apply_filters('ddl-carousel_control_right_class_attribute', 'right carousel-control').'" href="#slider-'.$unique_id .'" data-slide="next"><span class="'. apply_filters( "ddl-slider-cell-icon-next", "icon-next" ) .'"></span></a>', $unique_id);?>

            </div>

            <?php
            return apply_filters( 'ddl-minify_html', ob_get_clean() );
	}

        function get_carousel_indicators($unique_id){
                ob_start();?>
                    <ol class="carousel-indicators">
                    <?php while ( has_ddl_repeater('slider') ) : the_ddl_repeater('slider'); ?>
                        <li data-target="#slider-<?php echo $unique_id ?>" data-slide-to="<?php the_ddl_repeater_index(); ?>" <?php if (get_ddl_repeater_index() == 0) { echo ' class="active"'; } ?>></li>
                    <?php endwhile;
                        ddl_rewind_repeater('slider');
                    ?>
                </ol>
                <?php
                echo apply_filters('ddl-get_carousel_indicators', ob_get_clean(), $unique_id);
        }

        function get_carousel_indicators_bottom($unique_id, $count_slides){
                echo apply_filters('ddl-get_carousel_indicators_bottom', '', $unique_id, $count_slides);
        }

        function get_autoplay_script($unique_id){
            ob_start()?>

                <script type="text/javascript">
                //<![CDATA[
                    jQuery(document).ready( function($) {
                        var ddl_slider_id_string = "#slider-<?php echo $unique_id ?>";
                        $(ddl_slider_id_string).carousel({
                            interval : <?php the_ddl_field('interval') ?>
                            <?php if (!get_ddl_field('pause')) {echo ', pause: "false"';} ?>
                        });
                    });
                    //]]>
                </script>
            <?php
            echo apply_filters('ddl-get_autoplay_script', ob_get_clean(), $unique_id);
        }

        function get_inline_style( $unique_id ){
                    ob_start();?>
                        <style>
                            #slider-<?php echo $unique_id ?> .carousel-inner > .item {
                                    height: <?php the_ddl_field('slider_height') ?>px;
                             }
                        </style>
                    <?php
                    echo apply_filters('ddl-get_inline_style', ob_get_clean(), $unique_id );
        }

    }
    
    new Layouts_cell_slider();
}
