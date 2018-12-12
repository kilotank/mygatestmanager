<label for="ddl-single-assignments-lang" class="ddl-single-assignments-lang-label"><?php _e('Filter items by language', 'ddl-layouts');?></label>
<select name="ddl-single-assignments-lang" class="ddl-single-assignments-lang-select js-ddl-single-assignments-lang-select">
    <!-- <option class="js-ddl-single-assignments-lang-option" value="all" data-language-icon="none"><?php _e('All languages', 'ddl-layouts');?></option> -->
    <?php
    $selected = '';
    
    foreach( $languages as $language ):
	$selected = '';
        if( isset( $language['code'] ) && ICL_LANGUAGE_CODE === $language['code'] ){
            $selected = "selected";
        }
        ?>
        <option class="js-ddl-single-assignments-lang-option" value="<?php echo $language['code']; ?>" <?php echo $selected; ?> data-language-icon="<?php echo isset($language['country_flag_url']) ? $language['country_flag_url'] : ''; ?>">

            <?php
            if( isset( $language['translated_name'] ) ): ?>
                <?php echo $language['translated_name']; ?>
            <?php else : ?>
                <?php echo $language['display_name']; ?>
            <?php endif; ?>
        </option>
    <?php endforeach; ?>
</select>